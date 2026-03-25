import { logger } from '../utils/logger.js';
import { localFrameworkDb } from './localFrameworkDb.js';

/**
 * Placeholder for DB-backed search reads.
 * Phase 1 keeps existing recursive search/filter logic while the
 * persistence path migrates to localFrameworkDb.
 */
class LocalSearchService {
  async searchItems({ documentId, query, limit = 1000 }) {
    if (!documentId || !query) {
      return [];
    }

    try {
      return await localFrameworkDb.searchItems(documentId, query, limit);
    } catch (error) {
      logger.warn('localSearchService.searchItems failed:', error);
      return [];
    }
  }
}

export const localSearchService = new LocalSearchService();
