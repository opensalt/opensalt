/**
 * Framework Cache Service
 * Provides persistent caching of frameworks using IndexedDB
 * Cache persists across page refreshes and browser sessions
 */

import { logger } from '../utils/logger.js';

const DB_NAME = 'opensalt-framework-cache';
const DB_VERSION = 1;
const STORE_NAME = 'frameworks';
const CACHE_MAX_AGE_MS = 600000; // 10 minutes in milliseconds

/**
 * Framework Cache Service class
 * Manages IndexedDB operations for framework caching
 */
class FrameworkCacheService {
  constructor() {
    this.db = null;
  }

  /**
   * Initialize and open the IndexedDB connection
   * Creates the object store if it doesn't exist
   * @returns {Promise<IDBDatabase|null>} - The IndexedDB database instance or null if unavailable
   */
  async openDatabase() {
    if (this.db) {
      return this.db;
    }

    // Check if IndexedDB is available
    if (!window.indexedDB) {
      logger.warn('IndexedDB is not available in this browser');
      return null;
    }

    return new Promise((resolve, reject) => {
      const request = window.indexedDB.open(DB_NAME, DB_VERSION);

      request.onerror = (event) => {
        logger.warn('Failed to open IndexedDB:', event.target.error);
        resolve(null);
      };

      request.onsuccess = (event) => {
        this.db = event.target.result;
        logger.debug('IndexedDB opened successfully');
        resolve(this.db);
      };

      request.onupgradeneeded = (event) => {
        const database = event.target.result;

        // Create the frameworks object store with id as keyPath
        if (!database.objectStoreNames.contains(STORE_NAME)) {
          const store = database.createObjectStore(STORE_NAME, { keyPath: 'id' });
          logger.debug('Created frameworks object store in IndexedDB');
        }
      };
    });
  }

  /**
   * Retrieve a cached framework by document UUID
   * @param {string} documentId - The document UUID
   * @returns {Promise<Object|null>} - The cached entry or null if not found/error
   */
  async getFramework(documentId) {
    if (!documentId) {
      logger.warn('getFramework called without documentId');
      return null;
    }

    const database = await this.openDatabase();
    if (!database) {
      return null;
    }

    return new Promise((resolve) => {
      try {
        const transaction = database.transaction([STORE_NAME], 'readonly');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.get(documentId);

        request.onerror = (event) => {
          logger.warn('Error retrieving framework from cache:', event.target.error);
          resolve(null);
        };

        request.onsuccess = (event) => {
          const result = event.target.result;
          if (result) {
            logger.debug('Framework retrieved from cache:', documentId);
          }
          resolve(result || null);
        };
      } catch (error) {
        logger.warn('Exception retrieving framework from cache:', error);
        resolve(null);
      }
    });
  }

  /**
   * Store a framework in the cache with timestamp
   * @param {string} documentId - The document UUID
   * @param {Object} cfPackage - The CFPackage object to cache
   * @returns {Promise<boolean>} - True if stored successfully, false otherwise
   */
  async setFramework(documentId, cfPackage) {
    if (!documentId || !cfPackage) {
      logger.warn('setFramework called with invalid arguments');
      return false;
    }

    const database = await this.openDatabase();
    if (!database) {
      return false;
    }

    // Extract lastChangeDateTime from the CFDocument
    const lastChangeDateTime = cfPackage.CFDocument?.lastChangeDateTime || null;

    const cacheEntry = {
      id: documentId,
      data: cfPackage,
      cachedAt: Date.now(),
      lastChangeDateTime: lastChangeDateTime
    };

    return new Promise((resolve) => {
      try {
        const transaction = database.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.put(cacheEntry);

        request.onerror = (event) => {
          logger.warn('Error storing framework in cache:', event.target.error);
          resolve(false);
        };

        request.onsuccess = () => {
          logger.debug('Framework stored in cache:', documentId);
          resolve(true);
        };
      } catch (error) {
        logger.warn('Exception storing framework in cache:', error);
        resolve(false);
      }
    });
  }

  /**
   * Check if a cached framework is still valid
   * A cache is invalid if:
   * 1. The cached entry is older than 10 minutes (600,000 ms), OR
   * 2. The serverLastChangeDateTime is newer than the cached lastChangeDateTime
   * @param {string} documentId - The document UUID
   * @param {string} serverLastChangeDateTime - The server's lastChangeDateTime for comparison
   * @returns {Promise<boolean>} - True if cache is valid and fresh, false otherwise
   */
  async isCacheValid(documentId, serverLastChangeDateTime) {
    if (!documentId) {
      return false;
    }

    const cachedEntry = await this.getFramework(documentId);

    if (!cachedEntry) {
      logger.debug('Cache miss for document:', documentId);
      return false;
    }

    // Check if cache is older than max age (10 minutes)
    const now = Date.now();
    const cacheAge = now - cachedEntry.cachedAt;

    if (cacheAge > CACHE_MAX_AGE_MS) {
      logger.debug('Cache expired for document:', documentId, '(age:', cacheAge, 'ms)');
      return false;
    }

    // Check if server has a newer version
    if (serverLastChangeDateTime && cachedEntry.lastChangeDateTime) {
      const serverDate = new Date(serverLastChangeDateTime);
      const cachedDate = new Date(cachedEntry.lastChangeDateTime);

      if (serverDate > cachedDate) {
        logger.debug('Server has newer version for document:', documentId);
        return false;
      }
    }

    logger.debug('Cache valid for document:', documentId);
    return true;
  }

  /**
   * Clear all cached frameworks
   * @returns {Promise<boolean>} - True if cleared successfully, false otherwise
   */
  async clearCache() {
    const database = await this.openDatabase();
    if (!database) {
      return false;
    }

    return new Promise((resolve) => {
      try {
        const transaction = database.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.clear();

        request.onerror = (event) => {
          logger.warn('Error clearing framework cache:', event.target.error);
          resolve(false);
        };

        request.onsuccess = () => {
          logger.info('Framework cache cleared successfully');
          resolve(true);
        };
      } catch (error) {
        logger.warn('Exception clearing framework cache:', error);
        resolve(false);
      }
    });
  }

  /**
   * Delete a specific framework from the cache
   * @param {string} documentId - The document UUID to delete
   * @returns {Promise<boolean>} - True if deleted successfully, false otherwise
   */
  async deleteFramework(documentId) {
    if (!documentId) {
      logger.warn('deleteFramework called without documentId');
      return false;
    }

    const database = await this.openDatabase();
    if (!database) {
      return false;
    }

    return new Promise((resolve) => {
      try {
        const transaction = database.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.delete(documentId);

        request.onerror = (event) => {
          logger.warn('Error deleting framework from cache:', event.target.error);
          resolve(false);
        };

        request.onsuccess = () => {
          logger.debug('Framework deleted from cache:', documentId);
          resolve(true);
        };
      } catch (error) {
        logger.warn('Exception deleting framework from cache:', error);
        resolve(false);
      }
    });
  }

  /**
   * Get the cached framework data if cache is valid, otherwise return null
   * This is a convenience method that combines isCacheValid and getFramework
   * @param {string} documentId - The document UUID
   * @param {string} serverLastChangeDateTime - The server's lastChangeDateTime for validation
   * @returns {Promise<Object|null>} - The cached CFPackage if valid, null otherwise
   */
  async getValidFramework(documentId, serverLastChangeDateTime) {
    const isValid = await this.isCacheValid(documentId, serverLastChangeDateTime);

    if (!isValid) {
      return null;
    }

    const entry = await this.getFramework(documentId);
    return entry ? entry.data : null;
  }
}

// Create singleton instance
export const frameworkCacheService = new FrameworkCacheService();

// Export the class for testing purposes
export { FrameworkCacheService };
