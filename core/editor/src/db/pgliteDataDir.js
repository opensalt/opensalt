const DELETE_RETRY_DELAY_MS = 250;
const DELETE_RETRY_ATTEMPTS = 4;
const LEGACY_INDEXEDDB_NAME_PREFIX = 'opensalt-framework-db';
const CURRENT_INDEXEDDB_NAME = 'opensalt-framework-db-v3';

export const PGLITE_INDEXEDDB_DATA_DIR = `idb://${CURRENT_INDEXEDDB_NAME}`;
export const PGLITE_MEMORY_DATA_DIR = `memory://${CURRENT_INDEXEDDB_NAME}`;
export const DEFAULT_DATA_DIR = PGLITE_INDEXEDDB_DATA_DIR;

function getIndexedDbName(dataDir) {
  if (typeof dataDir !== 'string' || !dataDir.startsWith('idb://')) return null;
  return dataDir.slice(6) || null;
}

function delay(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export async function deleteIndexedDb(databaseName, retries = DELETE_RETRY_ATTEMPTS) {
  if (typeof indexedDB === 'undefined' || !databaseName) return false;

  for (let attempt = 0; attempt <= retries; attempt += 1) {
    const deleted = await new Promise((resolve) => {
      const request = indexedDB.deleteDatabase(databaseName);
      request.onsuccess = () => resolve(true);
      request.onerror = () => resolve(false);
      request.onblocked = () => resolve(false);
    });

    if (deleted) return true;
    if (attempt < retries) {
      await delay(DELETE_RETRY_DELAY_MS);
    }
  }

  return false;
}

async function getLegacyIndexedDbNames() {
  if (typeof indexedDB === 'undefined') return [];

  if (typeof indexedDB.databases === 'function') {
    try {
      const databases = await indexedDB.databases();
      return databases
        .map(db => db?.name)
        .filter(name => typeof name === 'string')
        .filter(name => name.startsWith(LEGACY_INDEXEDDB_NAME_PREFIX))
        .filter(name => name !== CURRENT_INDEXEDDB_NAME);
    } catch (_) {
      // Fall back to the fixed list below.
    }
  }

  return [
    'opensalt-framework-db',
    'opensalt-framework-db-v2',
  ];
}

export async function resetIndexedDbDataDir(dataDir, retries = DELETE_RETRY_ATTEMPTS) {
  const databaseName = getIndexedDbName(dataDir);
  if (!databaseName) return false;
  return deleteIndexedDb(databaseName, retries);
}

export async function cleanupStaleDatabases(retries = DELETE_RETRY_ATTEMPTS) {
  const names = await getLegacyIndexedDbNames();
  for (const name of names) {
    await deleteIndexedDb(name, retries);
  }
}
