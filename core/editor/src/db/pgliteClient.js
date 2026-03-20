import { logger } from '../utils/logger.js';

class PgliteClient {
  constructor() {
    this.worker = null;
    this.pending = new Map();
    this.requestId = 0;
    this.initialized = false;
    this.mode = null;
    this.supported = typeof window !== 'undefined' && typeof Worker !== 'undefined';
  }

  ensureWorker() {
    if (!this.supported) {
      throw new Error('Web workers are not available in this runtime');
    }

    if (this.worker) return this.worker;

    this.worker = new Worker(new URL('./pgliteWorker.js', import.meta.url), { type: 'module' });
    this.worker.onmessage = (event) => {
      const message = event.data;
      if (message?.type === 'warning') {
        logger.warn('[PgliteWorker]', message.payload?.message, message.payload?.error || '');
        return;
      }

      const entry = this.pending.get(message.id);
      if (!entry) return;
      this.pending.delete(message.id);

      if (message.ok) {
        entry.resolve(message.result);
      } else {
        entry.reject(new Error(message.error || 'Unknown worker error'));
      }
    };

    this.worker.onerror = (error) => {
      logger.error('Pglite worker error:', error);
      for (const entry of this.pending.values()) {
        entry.reject(error);
      }
      this.pending.clear();
    };

    return this.worker;
  }

  async call(method, params = {}) {
    const worker = this.ensureWorker();
    const id = ++this.requestId;

    return new Promise((resolve, reject) => {
      this.pending.set(id, { resolve, reject });
      worker.postMessage({ id, method, params });
    });
  }

  async init(options = {}) {
    if (this.initialized) return this.mode;
    const response = await this.call('initDatabase', { options });
    this.mode = response?.mode || null;
    this.initialized = true;
    return this.mode;
  }

  getFramework(documentId) {
    return this.call('getFramework', { documentId });
  }

  setFramework(documentId, cfPackage, etag = null, lastModified = null) {
    return this.call('setFramework', { documentId, cfPackage, etag, lastModified });
  }

  upsertPackage(documentId, cfPackage, etag = null, lastModified = null) {
    return this.call('upsertPackage', { documentId, cfPackage, etag, lastModified });
  }

  getPackage(documentId) {
    return this.call('getPackage', { documentId });
  }

  getItemAssociations(itemId, displayedFrameworkId = null, groupId = null) {
    return this.call('getItemAssociations', { itemId, displayedFrameworkId, groupId });
  }

  searchItems(documentId, query, limit = 1000) {
    return this.call('searchItems', { documentId, query, limit });
  }

  isCacheValid(documentId, serverLastChangeDateTime) {
    return this.call('isCacheValid', { documentId, serverLastChangeDateTime });
  }

  deleteFramework(documentId) {
    return this.call('deleteFramework', { documentId });
  }

  clearCache() {
    return this.call('clearCache');
  }

  getRelatedFrameworks(documentId) {
    return this.call('getRelatedFrameworks', { documentId });
  }

  setRelatedFrameworks(documentId, relatedDocs) {
    return this.call('setRelatedFrameworks', { documentId, relatedDocs });
  }
}

export function createPgliteClient() {
  return new PgliteClient();
}
