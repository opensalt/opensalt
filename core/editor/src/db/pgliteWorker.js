// Worker environment is configured via global lint config
import { PGlite } from '@electric-sql/pglite';
import { live } from '@electric-sql/pglite/live';
import { worker } from '@electric-sql/pglite/worker';
import { ensureWebLocks } from './webLocksShim.js';

ensureWebLocks();

const PGLITE_INDEXEDDB_DATA_DIR = 'idb://opensalt-framework-db-v2';
const PGLITE_MEMORY_DATA_DIR = 'memory://opensalt-framework-db-v2';

const migrations = import.meta.glob('./migrations/*.sql', { query: '?raw', import: 'default', eager: true });

function getIndexedDbName(dataDir) {
  if (typeof dataDir !== 'string' || !dataDir.startsWith('idb://')) return null;
  return dataDir.slice(6) || null;
}

async function resetIndexedDbDataDir(dataDir) {
  const databaseName = getIndexedDbName(dataDir);
  if (!databaseName) return false;
  if (typeof indexedDB === 'undefined') return false;

  return new Promise((resolve) => {
    const request = indexedDB.deleteDatabase(databaseName);
    request.onsuccess = () => resolve(true);
    request.onerror = () => resolve(false);
    request.onblocked = () => resolve(false);
  });
}

worker({
  async init(options = {}) {
    const pgliteOptions = {
      relaxedDurability: true,
      extensions: {
        live
      },
      // debug: 1,
    };

    const requestedDataDir = options.dataDir || PGLITE_INDEXEDDB_DATA_DIR;
    let db;

    try {
      db = await PGlite.create(requestedDataDir, pgliteOptions);
    } catch (error) {
      console.warn('[PGliteWorker] Persistent init failed:', error);

      const resetApplied = await resetIndexedDbDataDir(requestedDataDir);
      if (resetApplied) {
        console.warn('[PGliteWorker] Reset persistent data, retrying...');
        try {
          db = await PGlite.create(requestedDataDir, pgliteOptions);
        } catch (retryError) {
          console.warn('[PGliteWorker] Retry failed:', retryError);
        }
      }

      if (!db) {
        console.warn('[PGliteWorker] Falling back to in-memory mode.');
        db = await PGlite.create(PGLITE_MEMORY_DATA_DIR, pgliteOptions);
      }
    }

    try {
      await db.exec(`
        CREATE TABLE IF NOT EXISTS schema_migrations (
          version TEXT PRIMARY KEY,
          applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
      `);

      const { rows } = await db.query('SELECT version FROM schema_migrations');
      const appliedMigrations = new Set(rows.map(row => row.version));

      const migrationFiles = Object.keys(migrations).sort();
      for (const file of migrationFiles) {
        const version = file.split('/').pop();
        if (!appliedMigrations.has(version)) {
          console.log(`[PGliteWorker] Applying migration: ${version}`);
          await db.exec(migrations[file]);
          await db.query('INSERT INTO schema_migrations (version) VALUES ($1)', [version]);
        }
      }
    } catch (error) {
      console.error('[PGliteWorker] Migration failed:', error);
    }

    return db;
  }
});
