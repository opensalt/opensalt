/**
 * External Endpoint Cache Service
 * Provides persistent caching of non-CASE entities (exemplars, external URIs) using IndexedDB
 */

import { logger } from '../utils/logger.js';

const DB_NAME = 'opensalt-external-endpoint-cache';
const DB_VERSION = 1;
const STORE_NAME = 'endpoints';
const CACHE_MAX_AGE_MS = 86400000; // 1 day

export class ExternalEndpointCacheService {
    constructor() {
        this.db = null;
    }

    async openDatabase() {
        if (this.db) return this.db;

        return new Promise((resolve, reject) => {
            try {
                const request = indexedDB.open(DB_NAME, DB_VERSION);

                request.onerror = (event) => {
                    logger.error('Error opening ExternalEndpoint DB:', event);
                    resolve(null);
                };

                request.onsuccess = (event) => {
                    this.db = event.target.result;
                    resolve(this.db);
                };

                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains(STORE_NAME)) {
                        db.createObjectStore(STORE_NAME, { keyPath: 'uri' });
                    }
                };
            } catch (err) {
                logger.error('IndexedDB not supported or accessible:', err);
                resolve(null);
            }
        });
    }

    async get(uri) {
        const db = await this.openDatabase();
        if (!db) return null;

        return new Promise((resolve) => {
            try {
                const transaction = db.transaction([STORE_NAME], 'readonly');
                const store = transaction.objectStore(STORE_NAME);
                const request = store.get(uri);

                request.onerror = () => resolve(null);
                request.onsuccess = () => resolve(request.result || null);
            } catch (err) {
                resolve(null);
            }
        });
    }

    async set(uri, data) {
        const db = await this.openDatabase();
        if (!db) return false;

        return new Promise((resolve) => {
            try {
                const transaction = db.transaction([STORE_NAME], 'readwrite');
                const store = transaction.objectStore(STORE_NAME);
                const entry = {
                    uri,
                    ...data,
                    cachedAt: new Date().toISOString()
                };
                const request = store.put(entry);

                request.onerror = () => resolve(false);
                request.onsuccess = () => resolve(true);
            } catch (err) {
                resolve(false);
            }
        });
    }

    async getAll() {
        const db = await this.openDatabase();
        if (!db) return [];

        return new Promise((resolve) => {
            try {
                const transaction = db.transaction([STORE_NAME], 'readonly');
                const store = transaction.objectStore(STORE_NAME);
                const request = store.getAll();

                request.onerror = () => resolve([]);
                request.onsuccess = () => resolve(request.result || []);
            } catch (err) {
                resolve([]);
            }
        });
    }

    async clear() {
        const db = await this.openDatabase();
        if (!db) return false;

        return new Promise((resolve) => {
            try {
                const transaction = db.transaction([STORE_NAME], 'readwrite');
                const store = transaction.objectStore(STORE_NAME);
                const request = store.clear();

                request.onerror = () => resolve(false);
                request.onsuccess = () => resolve(true);
            } catch (err) {
                resolve(false);
            }
        });
    }
}

export const externalEndpointCacheService = new ExternalEndpointCacheService();
