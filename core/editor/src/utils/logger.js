/**
 * Logger Utility
 * Provides consistent logging with levels for development and production
 */

const levels = { DEBUG: 0, INFO: 1, WARN: 2, ERROR: 3 };

// Set log level based on environment
const currentLevel = import.meta.env.PROD ? levels.ERROR : levels.DEBUG;

/**
 * Logger object with methods for different log levels
 */
export const logger = {
  /**
   * Log debug messages (only in development)
   * @param {...any} args - Arguments to log
   */
  debug: (...args) => {
    if (currentLevel <= levels.DEBUG) {
      console.log('[DEBUG]', ...args);
    }
  },

  /**
   * Log info messages
   * @param {...any} args - Arguments to log
   */
  info: (...args) => {
    if (currentLevel <= levels.INFO) {
      console.info('[INFO]', ...args);
    }
  },

  /**
   * Log warning messages
   * @param {...any} args - Arguments to log
   */
  warn: (...args) => {
    if (currentLevel <= levels.WARN) {
      console.warn('[WARN]', ...args);
    }
  },

  /**
   * Log error messages (always logged)
   * @param {...any} args - Arguments to log
   */
  error: (...args) => {
    console.error('[ERROR]', ...args);
  }
};

export default logger;
