/* eslint-env worker */

import { PGlite as ImportedPGlite } from '@electric-sql/pglite';

const CACHE_MAX_AGE_MS = 86400000;
const PGLITE_INDEXEDDB_DATA_DIR = 'idb://opensalt-framework-db';
const FALLBACK_STATE = {
  frameworks: new Map(),
  relatedFrameworks: new Map(),
  documents: new Map(),
  itemsByDocument: new Map(),
  associationsByDocument: new Map(),
  associationEdgesByItem: new Map(),
};

let adapter = null;

const SQL_MIGRATION = `
CREATE TABLE IF NOT EXISTS frameworks (
  id TEXT PRIMARY KEY,
  data JSON NOT NULL,
  cached_at BIGINT NOT NULL,
  last_change_datetime TEXT,
  etag TEXT,
  last_modified TEXT
);
CREATE TABLE IF NOT EXISTS related_frameworks (
  id TEXT PRIMARY KEY,
  data JSON NOT NULL,
  cached_at BIGINT NOT NULL
);
CREATE TABLE IF NOT EXISTS documents (
  document_id TEXT PRIMARY KEY,
  uri TEXT,
  title TEXT,
  last_change_datetime TEXT,
  json_data JSON
);
CREATE TABLE IF NOT EXISTS items (
  item_id TEXT PRIMARY KEY,
  document_id TEXT NOT NULL,
  uri TEXT,
  full_statement TEXT,
  abbreviated_statement TEXT,
  human_coding_scheme TEXT,
  item_type TEXT,
  last_change_datetime TEXT,
  json_data JSON
);
CREATE INDEX IF NOT EXISTS idx_items_document ON items(document_id);
CREATE TABLE IF NOT EXISTS associations (
  association_id TEXT PRIMARY KEY,
  document_id TEXT NOT NULL,
  association_type TEXT NOT NULL,
  origin_item_id TEXT,
  destination_item_id TEXT,
  group_id TEXT,
  sequence_number INTEGER,
  json_data JSON
);
CREATE INDEX IF NOT EXISTS idx_associations_document ON associations(document_id);
CREATE INDEX IF NOT EXISTS idx_associations_origin ON associations(origin_item_id);
CREATE INDEX IF NOT EXISTS idx_associations_destination ON associations(destination_item_id);
CREATE TABLE IF NOT EXISTS item_association_edges (
  item_id TEXT NOT NULL,
  association_id TEXT NOT NULL,
  direction TEXT NOT NULL,
  association_type TEXT NOT NULL,
  other_item_id TEXT,
  group_id TEXT,
  source_document_id TEXT NOT NULL,
  PRIMARY KEY (item_id, association_id, direction)
);
CREATE INDEX IF NOT EXISTS idx_item_assoc_item ON item_association_edges(item_id);
CREATE INDEX IF NOT EXISTS idx_item_assoc_item_type ON item_association_edges(item_id, association_type);
CREATE TABLE IF NOT EXISTS item_search (
  item_id TEXT PRIMARY KEY,
  document_id TEXT NOT NULL,
  search_text TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_item_search_doc ON item_search(document_id);
`;

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

async function createPgliteAdapter(options = {}) {
  const PGlite = ImportedPGlite;
  if (!PGlite) {
    throw new Error('PGlite module resolved without PGlite export');
  }

  const pgliteDataUrl = new URL('../../node_modules/@electric-sql/pglite/dist/pglite.data', import.meta.url);
  const pgliteWasmUrl = new URL('../../node_modules/@electric-sql/pglite/dist/pglite.wasm', import.meta.url);
  const fsBundleResponse = await fetch(pgliteDataUrl);
  if (!fsBundleResponse.ok) {
    throw new Error(`Failed to load PGlite bundle from ${pgliteDataUrl}`);
  }
  const wasmResponse = await fetch(pgliteWasmUrl);
  if (!wasmResponse.ok) {
    throw new Error(`Failed to load PGlite wasm from ${pgliteWasmUrl}`);
  }
  const wasmModule = await WebAssembly.compile(await wasmResponse.arrayBuffer());

  const db = await new PGlite({
    // Keep PGlite on the browser's IndexedDB-backed filesystem.
    dataDir: options.dataDir || PGLITE_INDEXEDDB_DATA_DIR,
    relaxedDurability: true,
    fsBundle: await fsBundleResponse.blob(),
    wasmModule,
  });

  await db.exec(SQL_MIGRATION);

  async function upsertPackage(documentId, cfPackage, etag = null, lastModified = null) {
    const now = Date.now();
    const cfDocument = cfPackage?.CFDocument || {};
    const cfItems = Array.isArray(cfPackage?.CFItems) ? cfPackage.CFItems : [];
    const cfAssociations = Array.isArray(cfPackage?.CFAssociations) ? cfPackage.CFAssociations : [];

    await db.exec('BEGIN');
    try {
      await db.query(
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

      await db.query(
        `INSERT INTO documents (document_id, uri, title, last_change_datetime, json_data)
         VALUES ($1, $2, $3, $4, $5::json)
         ON CONFLICT (document_id) DO UPDATE SET
           uri = excluded.uri,
           title = excluded.title,
           last_change_datetime = excluded.last_change_datetime,
           json_data = excluded.json_data`,
        [documentId, cfDocument.uri || null, cfDocument.title || null, cfDocument.lastChangeDateTime || null, JSON.stringify(cfDocument || {})]
      );

      await db.query(`DELETE FROM items WHERE document_id = $1`, [documentId]);
      await db.query(`DELETE FROM associations WHERE document_id = $1`, [documentId]);
      await db.query(`DELETE FROM item_association_edges WHERE source_document_id = $1`, [documentId]);
      await db.query(`DELETE FROM item_search WHERE document_id = $1`, [documentId]);

      for (const item of cfItems) {
        await db.query(
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

        await db.query(
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

        await db.query(
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
          await db.query(
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
          await db.query(
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

      await db.exec('COMMIT');
      return true;
    } catch (error) {
      await db.exec('ROLLBACK');
      throw error;
    }
  }

  return {
    mode: 'pglite',
    async getFramework(documentId) {
      const row = getRow(await db.query(
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
    },
    async setFramework(documentId, cfPackage, etag = null, lastModified = null) {
      return upsertPackage(documentId, cfPackage, etag, lastModified);
    },
    async upsertPackage(documentId, cfPackage, etag = null, lastModified = null) {
      return upsertPackage(documentId, cfPackage, etag, lastModified);
    },
    async getPackage(documentId) {
      const doc = getRow(await db.query(`SELECT json_data FROM documents WHERE document_id = $1`, [documentId]));
      if (!doc) return null;
      const itemRows = getRows(await db.query(
        `SELECT json_data FROM items WHERE document_id = $1`,
        [documentId]
      ));
      const assocRows = getRows(await db.query(
        `SELECT json_data FROM associations WHERE document_id = $1`,
        [documentId]
      ));
      return {
        CFDocument: parseJsonValue(doc.json_data),
        CFItems: itemRows.map((row) => parseJsonValue(row.json_data)),
        CFAssociations: assocRows.map((row) => parseJsonValue(row.json_data)),
      };
    },
    async getItemAssociations(itemId, displayedFrameworkId = null, groupId = null) {
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

      const rows = getRows(await db.query(sql, params));
      return rows.map((row) => ({
        association: parseJsonValue(row.json_data),
        frameworkId: row.source_document_id,
      }));
    },
    async searchItems(documentId, query, limit = 1000) {
      if (!query) return [];
      const q = `%${String(query).toLowerCase()}%`;
      const rows = getRows(await db.query(
        `SELECT item_id
         FROM item_search
         WHERE document_id = $1 AND search_text LIKE $2
         LIMIT $3`,
        [documentId, q, limit]
      ));
      return rows.map((row) => row.item_id);
    },
    async deleteFramework(documentId) {
      await db.query(`DELETE FROM frameworks WHERE id = $1`, [documentId]);
      await db.query(`DELETE FROM documents WHERE document_id = $1`, [documentId]);
      await db.query(`DELETE FROM items WHERE document_id = $1`, [documentId]);
      await db.query(`DELETE FROM associations WHERE document_id = $1`, [documentId]);
      await db.query(`DELETE FROM item_association_edges WHERE source_document_id = $1`, [documentId]);
      await db.query(`DELETE FROM item_search WHERE document_id = $1`, [documentId]);
      return true;
    },
    async clearCache() {
      await db.query(`DELETE FROM frameworks`);
      await db.query(`DELETE FROM related_frameworks`);
      await db.query(`DELETE FROM documents`);
      await db.query(`DELETE FROM items`);
      await db.query(`DELETE FROM associations`);
      await db.query(`DELETE FROM item_association_edges`);
      await db.query(`DELETE FROM item_search`);
      return true;
    },
    async getRelatedFrameworks(documentId) {
      const row = getRow(await db.query(
        `SELECT data FROM related_frameworks WHERE id = $1`,
        [documentId]
      ));
      if (!row) return null;
      return parseJsonValue(row.data);
    },
    async setRelatedFrameworks(documentId, relatedDocs) {
      await db.query(
        `INSERT INTO related_frameworks (id, data, cached_at)
         VALUES ($1, $2::json, $3)
         ON CONFLICT (id) DO UPDATE SET
           data = excluded.data,
           cached_at = excluded.cached_at`,
        [documentId, JSON.stringify(relatedDocs || []), Date.now()]
      );
      return true;
    },
  };
}

function createMemoryAdapter() {
  return {
    mode: 'memory',
    async getFramework(documentId) {
      return FALLBACK_STATE.frameworks.get(documentId) || null;
    },
    async setFramework(documentId, cfPackage, etag = null, lastModified = null) {
      FALLBACK_STATE.frameworks.set(documentId, {
        id: documentId,
        data: cfPackage,
        cachedAt: Date.now(),
        lastChangeDateTime: cfPackage?.CFDocument?.lastChangeDateTime || null,
        etag,
        lastModified
      });
      FALLBACK_STATE.documents.set(documentId, cfPackage?.CFDocument || null);
      FALLBACK_STATE.itemsByDocument.set(documentId, Array.isArray(cfPackage?.CFItems) ? cfPackage.CFItems : []);
      FALLBACK_STATE.associationsByDocument.set(documentId, Array.isArray(cfPackage?.CFAssociations) ? cfPackage.CFAssociations : []);

      const edgeMap = new Map();
      for (const association of (cfPackage?.CFAssociations || [])) {
        const { originId, destinationId } = associationEndpoints(association);
        const groupId = toGroupId(association);
        const type = association.associationType || 'unknown';
        if (originId) {
          if (!edgeMap.has(originId)) edgeMap.set(originId, []);
          edgeMap.get(originId).push({ association, frameworkId: documentId, groupId, type });
        }
        if (destinationId) {
          if (!edgeMap.has(destinationId)) edgeMap.set(destinationId, []);
          edgeMap.get(destinationId).push({ association, frameworkId: documentId, groupId, type });
        }
      }
      for (const [itemId, entries] of edgeMap.entries()) {
        const current = FALLBACK_STATE.associationEdgesByItem.get(itemId) || [];
        const retained = current.filter((entry) => entry.frameworkId !== documentId);
        FALLBACK_STATE.associationEdgesByItem.set(itemId, retained.concat(entries));
      }

      return true;
    },
    async upsertPackage(documentId, cfPackage, etag = null, lastModified = null) {
      return this.setFramework(documentId, cfPackage, etag, lastModified);
    },
    async getPackage(documentId) {
      const doc = FALLBACK_STATE.documents.get(documentId);
      if (!doc) return null;
      return {
        CFDocument: doc,
        CFItems: FALLBACK_STATE.itemsByDocument.get(documentId) || [],
        CFAssociations: FALLBACK_STATE.associationsByDocument.get(documentId) || [],
      };
    },
    async getItemAssociations(itemId, displayedFrameworkId = null, groupId = null) {
      const entries = FALLBACK_STATE.associationEdgesByItem.get(itemId) || [];
      return entries
        .filter((entry) => {
          const type = entry.association?.associationType || entry.type;
          if (displayedFrameworkId && type === 'isChildOf' && entry.frameworkId === displayedFrameworkId) return false;
          if (groupId && groupId !== 'all' && entry.groupId !== groupId) return false;
          return true;
        })
        .map((entry) => ({
          association: entry.association,
          frameworkId: entry.frameworkId,
        }));
    },
    async searchItems(documentId, query, limit = 1000) {
      const q = String(query || '').toLowerCase();
      if (!q) return [];
      const items = FALLBACK_STATE.itemsByDocument.get(documentId) || [];
      const matched = [];
      for (const item of items) {
        if (toSearchText(item).includes(q)) {
          matched.push(item.identifier);
          if (matched.length >= limit) break;
        }
      }
      return matched;
    },
    async deleteFramework(documentId) {
      FALLBACK_STATE.frameworks.delete(documentId);
      FALLBACK_STATE.documents.delete(documentId);
      FALLBACK_STATE.itemsByDocument.delete(documentId);
      FALLBACK_STATE.associationsByDocument.delete(documentId);
      for (const [itemId, entries] of FALLBACK_STATE.associationEdgesByItem.entries()) {
        const retained = entries.filter((entry) => entry.frameworkId !== documentId);
        if (retained.length === 0) {
          FALLBACK_STATE.associationEdgesByItem.delete(itemId);
        } else {
          FALLBACK_STATE.associationEdgesByItem.set(itemId, retained);
        }
      }
      return true;
    },
    async clearCache() {
      FALLBACK_STATE.frameworks.clear();
      FALLBACK_STATE.relatedFrameworks.clear();
      FALLBACK_STATE.documents.clear();
      FALLBACK_STATE.itemsByDocument.clear();
      FALLBACK_STATE.associationsByDocument.clear();
      FALLBACK_STATE.associationEdgesByItem.clear();
      return true;
    },
    async getRelatedFrameworks(documentId) {
      return FALLBACK_STATE.relatedFrameworks.get(documentId) || null;
    },
    async setRelatedFrameworks(documentId, relatedDocs) {
      FALLBACK_STATE.relatedFrameworks.set(documentId, relatedDocs || []);
      return true;
    },
  };
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

async function ensureAdapter(options = {}) {
  if (adapter) return adapter;
  try {
    adapter = await createPgliteAdapter(options);
  } catch (error) {
    adapter = createMemoryAdapter();
    postMessage({
      type: 'warning',
      payload: {
        message: 'Failed to initialize PGlite, using in-memory adapter',
        error: error?.message || String(error),
      }
    });
  }
  return adapter;
}

async function handleCommand(message) {
  const { id, method, params } = message;
  const db = await ensureAdapter(params?.options || {});

  try {
    let result = null;
    switch (method) {
      case 'initDatabase':
        result = { mode: db.mode };
        break;
      case 'getFramework':
        result = await db.getFramework(params.documentId);
        break;
      case 'setFramework':
        result = await db.setFramework(params.documentId, params.cfPackage, params.etag ?? null, params.lastModified ?? null);
        break;
      case 'upsertPackage':
        result = await db.upsertPackage(params.documentId, params.cfPackage, params.etag ?? null, params.lastModified ?? null);
        break;
      case 'getPackage':
        result = await db.getPackage(params.documentId);
        break;
      case 'getItemAssociations':
        result = await db.getItemAssociations(params.itemId, params.displayedFrameworkId || null, params.groupId || null);
        break;
      case 'searchItems':
        result = await db.searchItems(params.documentId, params.query, params.limit ?? 1000);
        break;
      case 'isCacheValid': {
        const entry = await db.getFramework(params.documentId);
        result = isCacheValidEntry(entry, params.serverLastChangeDateTime || '');
        break;
      }
      case 'deleteFramework':
        result = await db.deleteFramework(params.documentId);
        break;
      case 'clearCache':
        result = await db.clearCache();
        break;
      case 'getRelatedFrameworks':
        result = await db.getRelatedFrameworks(params.documentId);
        break;
      case 'setRelatedFrameworks':
        result = await db.setRelatedFrameworks(params.documentId, params.relatedDocs);
        break;
      default:
        throw new Error(`Unknown worker method: ${method}`);
    }

    postMessage({ id, ok: true, result });
  } catch (error) {
    postMessage({
      id,
      ok: false,
      error: error?.message || String(error)
    });
  }
}

onmessage = (event) => {
  void handleCommand(event.data);
};
