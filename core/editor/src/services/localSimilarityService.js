import { logger } from '../utils/logger.js';

/**
 * Placeholder service for pgvector-backed similarity search.
 * Wired in phase 5 after the local DB read model is stable.
 */
class LocalSimilarityService {
  async findSimilarItems() {
    logger.debug('localSimilarityService.findSimilarItems is not yet implemented');
    return [];
  }
}

export const localSimilarityService = new LocalSimilarityService();

