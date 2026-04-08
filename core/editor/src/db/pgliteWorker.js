// Worker environment is configured via global lint config
import { PGlite } from '@electric-sql/pglite';
import { live } from '@electric-sql/pglite/live';
import { worker } from '@electric-sql/pglite/worker';
import { ensureWebLocks } from './webLocksShim.js';
import { logger } from '../utils/logger.js';
import {
  PGLITE_INDEXEDDB_DATA_DIR,
  PGLITE_MEMORY_DATA_DIR,
  cleanupStaleDatabases,
  resetIndexedDbDataDir,
} from './pgliteDataDir.js';

ensureWebLocks();

const migrations = import.meta.glob('./migrations/*.sql', { query: '?raw', import: 'default', eager: true });

worker({
  async init(options = {}) {
    const pgliteOptions = {
      relaxedDurability: true,
      extensions: {
        live
      },
      // debug: 1,
    };

    await cleanupStaleDatabases();

    const requestedDataDir = options.dataDir || PGLITE_INDEXEDDB_DATA_DIR;
    let db;

    try {
      db = await PGlite.create(requestedDataDir, pgliteOptions);
    } catch (error) {
      logger.warn('[PGliteWorker] Persistent init failed:', error);

      const resetApplied = await resetIndexedDbDataDir(requestedDataDir);
      if (resetApplied) {
        logger.warn('[PGliteWorker] Reset persistent data, retrying...');
        try {
          db = await PGlite.create(requestedDataDir, pgliteOptions);
        } catch (retryError) {
          logger.warn('[PGliteWorker] Retry failed:', retryError);
        }
      }

      if (!db) {
        logger.warn('[PGliteWorker] Falling back to in-memory mode.');
        db = await PGlite.create(PGLITE_MEMORY_DATA_DIR, pgliteOptions);
      }
    }

    async function runMigrations(db) {
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
          logger.debug(`[PGliteWorker] Applying migration: ${version}`);
          await db.exec(migrations[file]);
          await db.query('INSERT INTO schema_migrations (version) VALUES ($1)', [version]);
        }
      }
    }

    try {
      await runMigrations(db);
    } catch (error) {
      const msg = error?.message || String(error);
      const isStructuralError = /constraint|duplicate|already exists|syntax|column|relation/i.test(msg);

      if (!isStructuralError) {
        logger.error('[PGliteWorker] Transient migration error (not resetting):', error);
      } else {
        logger.error('[PGliteWorker] Structural migration error, resetting database:', error);

        try {
          await db.close();
        } catch (_) { /* ignore */ }

        const resetApplied = await resetIndexedDbDataDir(requestedDataDir);
        if (resetApplied) {
          logger.warn('[PGliteWorker] Database reset, recreating from scratch...');
          try {
            db = await PGlite.create(requestedDataDir, pgliteOptions);
            await runMigrations(db);
          } catch (retryError) {
            logger.error('[PGliteWorker] Migration failed after reset:', retryError);
          }
        }

        if (!db) {
          logger.warn('[PGliteWorker] Falling back to in-memory mode after migration failure.');
          db = await PGlite.create(PGLITE_MEMORY_DATA_DIR, pgliteOptions);
          try {
            await runMigrations(db);
          } catch (_) { /* in-memory migration failures are non-fatal */ }
        }
      }
    }

    return db;
  }
});
