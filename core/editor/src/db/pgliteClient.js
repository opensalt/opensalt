import { logger } from '../utils/logger.js';
import { PGliteWorker } from '@electric-sql/pglite/worker';
import { ensureWebLocks } from './webLocksShim.js';

const DEFAULT_DATA_DIR = 'idb://opensalt-framework-db';
const CACHE_MAX_AGE_MS = 86400000;

ensureWebLocks();

function getRows(result) {
  if (!result) return [];
  if (Array.isArray(result.rows)) return result.rows;
  if (Array.isArray(result)) return result;
  return [];
}

function getRow(result) {
  return getRows(result)[0] || null;
}

function parseJsonValue(value) {
  if (value == null) return null;
  return typeof value === 'string' ? JSON.parse(value) : value;
}

function toGroupId(association) {
  return (
    association?.CFAssociationGroupingURI?.identifier ||
    association?.CFAssociationGroupingURI?.uri ||
    'default'
  );
}

function associationEndpoints(association) {
  const originId = association?.originNodeURI?.identifier || association?.originNodeIdentifier || null;
  const destinationId = association?.destinationNodeURI?.identifier || association?.destinationNodeIdentifier || null;
  return { originId, destinationId };
}

function toSearchText(item) {
  return [
    item?.humanCodingScheme || '',
    item?.abbreviatedStatement || '',
    item?.fullStatement || '',
    item?.title || '',
    item?.identifier || '',
  ].join(' ').toLowerCase();
}

function isCacheValidEntry(entry, serverLastChangeDateTime) {
  if (!entry) return false;
  if (serverLastChangeDateTime && entry.lastChangeDateTime) {
    const serverDate = new Date(serverLastChangeDateTime);
    const cachedDate = new Date(entry.lastChangeDateTime);
    if (serverDate > cachedDate) return false;
  }
  return (Date.now() - entry.cachedAt) <= CACHE_MAX_AGE_MS;
}

class PgliteClient {
  constructor() {
    this.worker = null;
    this.pgliteWorker = null;
    this.pgliteWorkerPromise = null;
    this.initialized = false;
    this.mode = null;
    this.supported = typeof window !== 'undefined' && typeof Worker !== 'undefined';
  }

  ensureWorker() {
    if (!this.supported) {
      throw new Error('Web workers are not available in this runtime');
    }
    if (!this.worker) {
      this.worker = new Worker(new URL('./pgliteWorker.js', import.meta.url), { type: 'module' });
      this.worker.onerror = (error) => {
        logger.error('[PgliteWorker] Error in worker thread:', error);
      };
    }
    return this.worker;
  }

  async init(options = {}) {
    if (this.initialized) return this.mode;
    await this.getPGliteWorker({
      dataDir: DEFAULT_DATA_DIR,
      ...options,
    });
    this.mode = 'pglite-worker';
    this.initialized = true;
    return this.mode;
  }

  async getPGliteWorker(options = {}) {
    if (this.pgliteWorker) {
      return this.pgliteWorker;
    }
    if (this.pgliteWorkerPromise) {
      return this.pgliteWorkerPromise;
    }

    const workerOptions = {
      dataDir: DEFAULT_DATA_DIR,
      ...options,
    };

    this.pgliteWorkerPromise = PGliteWorker.create(this.ensureWorker(), workerOptions)
      .then(async (pgWorker) => {
        await pgWorker.waitReady;
        this.pgliteWorker = pgWorker;
        return pgWorker;
      })
      .catch((error) => {
        logger.error('[PgliteWorker] Failed to create worker:', error);
        this.pgliteWorker = null;
        this.pgliteWorkerPromise = null;
        throw error;
      });

    return this.pgliteWorkerPromise;
  }

  execSql(query, options = {}) {
    return this.getPGliteWorker().then((pgWorker) => pgWorker.exec(query, options));
  }

  querySql(query, params = [], options = {}) {
    return this.getPGliteWorker().then((pgWorker) => pgWorker.query(query, params, options));
  }

  createReplInterface(options = {}) {
    const replOptions = {
      dataDir: DEFAULT_DATA_DIR,
      ...options,
    };
    const waitReady = this.getPGliteWorker(replOptions).then(() => undefined);

    return {
      waitReady,
      exec: async (query, queryOptions = {}) => {
        const pgWorker = await this.getPGliteWorker(replOptions);
        return pgWorker.exec(query, queryOptions);
      },
      query: async (query, params = [], queryOptions = {}) => {
        const pgWorker = await this.getPGliteWorker(replOptions);
        return pgWorker.query(query, params, queryOptions);
      },
      close: async () => {},
    };
  }

  getFramework(documentId) {
    return this.getPGliteWorker().then(async (pgWorker) => {
      const row = getRow(await pgWorker.query(
        `SELECT id, data, cached_at, last_change_datetime, etag, last_modified
         FROM frameworks
         WHERE id = $1`,
        [documentId]
      ));

      if (!row) return null;

      return {
        id: row.id,
        data: parseJsonValue(row.data),
        cachedAt: Number(row.cached_at),
        lastChangeDateTime: row.last_change_datetime,
        etag: row.etag,
        lastModified: row.last_modified,
      };
    });
  }

  setFramework(documentId, cfPackage, etag = null, lastModified = null) {
    return this.upsertPackage(documentId, cfPackage, etag, lastModified);
  }

  upsertPackage(documentId, cfPackage, etag = null, lastModified = null) {
    return this.getPGliteWorker().then(async (pgWorker) => {
      const now = Date.now();
      const cfDocument = cfPackage?.CFDocument || {};
      const cfItems = Array.isArray(cfPackage?.CFItems) ? cfPackage.CFItems : [];
      const cfAssociations = Array.isArray(cfPackage?.CFAssociations) ? cfPackage.CFAssociations : [];

      await pgWorker.transaction(async (tx) => {
        await tx.query(
          `INSERT INTO frameworks (id, data, cached_at, last_change_datetime, etag, last_modified)
           VALUES ($1, $2::json, $3, $4, $5, $6)
           ON CONFLICT (id) DO UPDATE SET
             data = excluded.data,
             cached_at = excluded.cached_at,
             last_change_datetime = excluded.last_change_datetime,
             etag = excluded.etag,
             last_modified = excluded.last_modified`,
          [documentId, JSON.stringify(cfPackage), now, cfDocument.lastChangeDateTime || null, etag, lastModified]
        );

        await tx.query(
          `INSERT INTO documents (document_id, uri, title, last_change_datetime, json_data)
           VALUES ($1, $2, $3, $4, $5::json)
           ON CONFLICT (document_id) DO UPDATE SET
             uri = excluded.uri,
             title = excluded.title,
             last_change_datetime = excluded.last_change_datetime,
             json_data = excluded.json_data`,
          [documentId, cfDocument.uri || null, cfDocument.title || null, cfDocument.lastChangeDateTime || null, JSON.stringify(cfDocument || {})]
        );

        await tx.query(`DELETE FROM items WHERE document_id = $1`, [documentId]);
        await tx.query(`DELETE FROM associations WHERE document_id = $1`, [documentId]);
        await tx.query(`DELETE FROM item_association_edges WHERE source_document_id = $1`, [documentId]);
        await tx.query(`DELETE FROM item_search WHERE document_id = $1`, [documentId]);

        for (const item of cfItems) {
          await tx.query(
            `INSERT INTO items (item_id, document_id, uri, full_statement, abbreviated_statement, human_coding_scheme, item_type, last_change_datetime, json_data)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9::json)
             ON CONFLICT (item_id) DO UPDATE SET
               document_id = excluded.document_id,
               uri = excluded.uri,
               full_statement = excluded.full_statement,
               abbreviated_statement = excluded.abbreviated_statement,
               human_coding_scheme = excluded.human_coding_scheme,
               item_type = excluded.item_type,
               last_change_datetime = excluded.last_change_datetime,
               json_data = excluded.json_data`,
            [
              item.identifier,
              documentId,
              item.uri || null,
              item.fullStatement || null,
              item.abbreviatedStatement || null,
              item.humanCodingScheme || null,
              item.CFItemType || null,
              item.lastChangeDateTime || null,
              JSON.stringify(item || {})
            ]
          );

          await tx.query(
            `INSERT INTO item_search (item_id, document_id, search_text)
             VALUES ($1, $2, $3)
             ON CONFLICT (item_id) DO UPDATE SET
               document_id = excluded.document_id,
               search_text = excluded.search_text`,
            [item.identifier, documentId, toSearchText(item)]
          );
        }

        for (const association of cfAssociations) {
          const { originId, destinationId } = associationEndpoints(association);
          const groupId = toGroupId(association);

          await tx.query(
            `INSERT INTO associations (association_id, document_id, association_type, origin_item_id, destination_item_id, group_id, sequence_number, json_data)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8::json)
             ON CONFLICT (association_id) DO UPDATE SET
               document_id = excluded.document_id,
               association_type = excluded.association_type,
               origin_item_id = excluded.origin_item_id,
               destination_item_id = excluded.destination_item_id,
               group_id = excluded.group_id,
               sequence_number = excluded.sequence_number,
               json_data = excluded.json_data`,
            [
              association.identifier,
              documentId,
              association.associationType || 'unknown',
              originId,
              destinationId,
              groupId,
              association.sequenceNumber || null,
              JSON.stringify(association || {})
            ]
          );

          if (originId) {
            await tx.query(
              `INSERT INTO item_association_edges (item_id, association_id, direction, association_type, other_item_id, group_id, source_document_id)
               VALUES ($1, $2, 'origin', $3, $4, $5, $6)
               ON CONFLICT (item_id, association_id, direction) DO UPDATE SET
                 association_type = excluded.association_type,
                 other_item_id = excluded.other_item_id,
                 group_id = excluded.group_id,
                 source_document_id = excluded.source_document_id`,
              [originId, association.identifier, association.associationType || 'unknown', destinationId, groupId, documentId]
            );
          }

          if (destinationId) {
            await tx.query(
              `INSERT INTO item_association_edges (item_id, association_id, direction, association_type, other_item_id, group_id, source_document_id)
               VALUES ($1, $2, 'destination', $3, $4, $5, $6)
               ON CONFLICT (item_id, association_id, direction) DO UPDATE SET
                 association_type = excluded.association_type,
                 other_item_id = excluded.other_item_id,
                 group_id = excluded.group_id,
                 source_document_id = excluded.source_document_id`,
              [destinationId, association.identifier, association.associationType || 'unknown', originId, groupId, documentId]
            );
          }
        }
      });

      return true;
    });
  }

  getPackage(documentId) {
    return this.getPGliteWorker().then(async (pgWorker) => {
      const doc = getRow(await pgWorker.query(`SELECT json_data FROM documents WHERE document_id = $1`, [documentId]));
      if (!doc) return null;

      const itemRows = getRows(await pgWorker.query(
        `SELECT json_data FROM items WHERE document_id = $1`,
        [documentId]
      ));
      const assocRows = getRows(await pgWorker.query(
        `SELECT json_data FROM associations WHERE document_id = $1`,
        [documentId]
      ));

      return {
        CFDocument: parseJsonValue(doc.json_data),
        CFItems: itemRows.map((row) => parseJsonValue(row.json_data)),
        CFAssociations: assocRows.map((row) => parseJsonValue(row.json_data)),
      };
    });
  }

  getItemAssociations(itemId, displayedFrameworkId = null, groupId = null) {
    return this.getPGliteWorker().then(async (pgWorker) => {
      const params = [itemId];
      let sql = `
        SELECT a.json_data, e.source_document_id
        FROM item_association_edges e
        JOIN associations a ON a.association_id = e.association_id
        WHERE e.item_id = $1
      `;

      if (groupId && groupId !== 'all') {
        params.push(groupId);
        sql += ` AND COALESCE(e.group_id, 'default') = $${params.length}`;
      }

      if (displayedFrameworkId) {
        params.push(displayedFrameworkId);
        sql += ` AND NOT (e.association_type = 'isChildOf' AND e.source_document_id = $${params.length})`;
      }

      const rows = getRows(await pgWorker.query(sql, params));
      return rows.map((row) => ({
        association: parseJsonValue(row.json_data),
        frameworkId: row.source_document_id,
      }));
    });
  }

  searchItems(documentId, query, limit = 1000) {
    return this.getPGliteWorker().then(async (pgWorker) => {
      if (!query) return [];
      const q = `%${String(query).toLowerCase()}%`;
      const rows = getRows(await pgWorker.query(
        `SELECT item_id
         FROM item_search
         WHERE document_id = $1 AND search_text LIKE $2
         LIMIT $3`,
        [documentId, q, limit]
      ));
      return rows.map((row) => row.item_id);
    });
  }

  isCacheValid(documentId, serverLastChangeDateTime) {
    return this.getFramework(documentId).then((entry) => isCacheValidEntry(entry, serverLastChangeDateTime || ''));
  }

  deleteFramework(documentId) {
    return this.getPGliteWorker().then(async (pgWorker) => {
      await pgWorker.query(`DELETE FROM frameworks WHERE id = $1`, [documentId]);
      await pgWorker.query(`DELETE FROM documents WHERE document_id = $1`, [documentId]);
      await pgWorker.query(`DELETE FROM items WHERE document_id = $1`, [documentId]);
      await pgWorker.query(`DELETE FROM associations WHERE document_id = $1`, [documentId]);
      await pgWorker.query(`DELETE FROM item_association_edges WHERE source_document_id = $1`, [documentId]);
      await pgWorker.query(`DELETE FROM item_search WHERE document_id = $1`, [documentId]);
      return true;
    });
  }

  clearCache() {
    return this.getPGliteWorker().then(async (pgWorker) => {
      await pgWorker.query(`DELETE FROM frameworks`);
      await pgWorker.query(`DELETE FROM related_frameworks`);
      await pgWorker.query(`DELETE FROM documents`);
      await pgWorker.query(`DELETE FROM items`);
      await pgWorker.query(`DELETE FROM associations`);
      await pgWorker.query(`DELETE FROM item_association_edges`);
      await pgWorker.query(`DELETE FROM item_search`);
      return true;
    });
  }

  getRelatedFrameworks(documentId) {
    return this.getPGliteWorker().then(async (pgWorker) => {
      const row = getRow(await pgWorker.query(
        `SELECT data FROM related_frameworks WHERE id = $1`,
        [documentId]
      ));
      if (!row) return null;
      return parseJsonValue(row.data);
    });
  }

  setRelatedFrameworks(documentId, relatedDocs) {
    return this.getPGliteWorker().then(async (pgWorker) => {
      await pgWorker.query(
        `INSERT INTO related_frameworks (id, data, cached_at)
         VALUES ($1, $2::json, $3)
         ON CONFLICT (id) DO UPDATE SET
           data = excluded.data,
           cached_at = excluded.cached_at`,
        [documentId, JSON.stringify(relatedDocs || []), Date.now()]
      );
      return true;
    });
  }
}

let sharedClient = null;

export function createPgliteClient() {
  if (!sharedClient) {
    sharedClient = new PgliteClient();
  }

  return sharedClient;
}
