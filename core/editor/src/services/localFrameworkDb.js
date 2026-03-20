import { logger } from '../utils/logger.js';
import { editorConfig } from '../config/editorConfig.js';
import { createPgliteClient } from '../db/pgliteClient.js';

class LocalFrameworkDbService {
  constructor() {
    this.client = null;
    this.initialized = false;
    this.initPromise = null;
    this.enabled = editorConfig.features.useLocalFrameworkDb === true;
    this.memory = {
      frameworks: new Map(),
      relatedFrameworks: new Map(),
    };
  }

  async initialize() {
    if (this.initialized) return this.client;
    if (this.initPromise) return this.initPromise;

    this.initPromise = (async () => {
      if (!this.enabled) {
        this.initialized = true;
        return null;
      }

      try {
        const client = createPgliteClient();
        const mode = await client.init();
        this.client = client;
        logger.info(`Local framework DB initialized (${mode || 'unknown mode'})`);
      } catch (error) {
        logger.warn('Local framework DB initialization failed. Falling back to in-memory adapter.', error);
        this.client = null;
      } finally {
        this.initialized = true;
      }

      return this.client;
    })();

    return this.initPromise;
  }

  async callWithFallback(methodName, fallback, ...args) {
    await this.initialize();

    if (this.client && typeof this.client[methodName] === 'function') {
      try {
        return await this.client[methodName](...args);
      } catch (error) {
        logger.warn(`Local framework DB call failed (${methodName}); falling back to in-memory adapter.`, error);
        this.client = null;
      }
    }

    return fallback(...args);
  }

  getMemoryFramework(documentId) {
    return this.memory.frameworks.get(documentId) || null;
  }

  setMemoryFramework(documentId, cfPackage, etag = null, lastModified = null) {
    this.memory.frameworks.set(documentId, {
      id: documentId,
      data: cfPackage,
      cachedAt: Date.now(),
      lastChangeDateTime: cfPackage?.CFDocument?.lastChangeDateTime || null,
      etag,
      lastModified
    });
    return true;
  }

  getFramework(documentId) {
    return this.callWithFallback(
      'getFramework',
      (id) => this.getMemoryFramework(id),
      documentId
    );
  }

  setFramework(documentId, cfPackage, etag = null, lastModified = null) {
    return this.callWithFallback(
      'setFramework',
      (id, pkg, et, lm) => this.setMemoryFramework(id, pkg, et, lm),
      documentId,
      cfPackage,
      etag,
      lastModified
    );
  }

  upsertPackage(documentId, cfPackage, etag = null, lastModified = null) {
    return this.callWithFallback(
      'upsertPackage',
      (id, pkg, et, lm) => this.setMemoryFramework(id, pkg, et, lm),
      documentId,
      cfPackage,
      etag,
      lastModified
    );
  }

  getPackage(documentId) {
    return this.callWithFallback(
      'getPackage',
      async (id) => this.getMemoryFramework(id)?.data || null,
      documentId
    );
  }

  getItemAssociations(itemId, displayedFrameworkId = null, groupId = null) {
    return this.callWithFallback(
      'getItemAssociations',
      async () => null,
      itemId,
      displayedFrameworkId,
      groupId
    );
  }

  searchItems(documentId, query, limit = 1000) {
    return this.callWithFallback(
      'searchItems',
      async () => [],
      documentId,
      query,
      limit
    );
  }

  isCacheValid(documentId, serverLastChangeDateTime) {
    return this.callWithFallback(
      'isCacheValid',
      (id, serverTs) => {
        const entry = this.getMemoryFramework(id);
        if (!entry) return false;
        if (serverTs && entry.lastChangeDateTime) {
          const serverDate = new Date(serverTs);
          const cachedDate = new Date(entry.lastChangeDateTime);
          if (serverDate > cachedDate) return false;
        }
        return true;
      },
      documentId,
      serverLastChangeDateTime
    );
  }

  clearCache() {
    return this.callWithFallback(
      'clearCache',
      () => {
        this.memory.frameworks.clear();
        this.memory.relatedFrameworks.clear();
        return true;
      }
    );
  }

  deleteFramework(documentId) {
    return this.callWithFallback(
      'deleteFramework',
      (id) => {
        this.memory.frameworks.delete(id);
        return true;
      },
      documentId
    );
  }

  getRelatedFrameworks(documentId) {
    return this.callWithFallback(
      'getRelatedFrameworks',
      (id) => this.memory.relatedFrameworks.get(id) || null,
      documentId
    );
  }

  setRelatedFrameworks(documentId, relatedDocs) {
    return this.callWithFallback(
      'setRelatedFrameworks',
      (id, docs) => {
        this.memory.relatedFrameworks.set(id, docs || []);
        return true;
      },
      documentId,
      relatedDocs
    );
  }
}

export const localFrameworkDb = new LocalFrameworkDbService();
