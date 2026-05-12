/**
 * useAdditionalFields Composable
 *
 * Fetches and caches additional field definitions from the API.
 * Provides helpers for reading field values from additionalFields data objects.
 */
import { ref } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';

/**
 * Module-level cache for field definitions by appliesTo type.
 * Prevents re-fetching when the composable is instantiated multiple times.
 * @type {Map<string, Array<Object>>}
 */
const fieldDefinitionsCache = new Map();

/**
 * Module-level tracking for in-flight requests to avoid duplicate fetches.
 * @type {Map<string, Promise<Array<Object>>>}
 */
const pendingRequests = new Map();

export function useAdditionalFields() {
  const fieldDefinitions = ref([]);
  const loading = ref(false);
  const error = ref(null);

  /**
   * Fetch field definitions for a given entity type.
   * Results are cached so subsequent calls with the same type do not re-fetch.
   *
   * @param {string} appliesTo - One of 'item', 'doc', or 'association'
   * @returns {Promise<Array<Object>>} The field definitions array
   */
  async function fetchFields(appliesTo) {
    if (!appliesTo) {
      logger.warn('[useAdditionalFields] fetchFields called without appliesTo');
      return [];
    }

    // Return cached results immediately
    if (fieldDefinitionsCache.has(appliesTo)) {
      const cached = fieldDefinitionsCache.get(appliesTo);
      fieldDefinitions.value = cached;
      return cached;
    }

    // If a request is already in-flight for this type, await it
    if (pendingRequests.has(appliesTo)) {
      loading.value = true;
      try {
        const result = await pendingRequests.get(appliesTo);
        fieldDefinitions.value = result;
        return result;
      } finally {
        loading.value = false;
      }
    }

    loading.value = true;
    error.value = null;

    const request = (async () => {
      try {
        const data = await api.get(`/framework/editor/additional-fields/${appliesTo}`);
        const definitions = Array.isArray(data) ? data : [];
        fieldDefinitionsCache.set(appliesTo, definitions);
        return definitions;
      } catch (err) {
        logger.error(`[useAdditionalFields] Failed to fetch fields for "${appliesTo}":`, err);
        error.value = err;
        return [];
      } finally {
        pendingRequests.delete(appliesTo);
        loading.value = false;
      }
    })();

    pendingRequests.set(appliesTo, request);

    const result = await request;
    fieldDefinitions.value = result;
    return result;
  }

  /**
   * Get the current value for a field from the additionalFields data object.
   *
   * @param {Array<Object>} definitions - The field definitions array
   * @param {string} fieldName - The field name to look up
   * @param {Object} additionalFieldsData - Key-value pairs of field name → value
   * @returns {*} The field value, or undefined if not found
   */
  function getFieldValue(definitions, fieldName, additionalFieldsData) {
    if (!additionalFieldsData || typeof additionalFieldsData !== 'object') {
      return undefined;
    }

    if (!Array.isArray(definitions)) {
      return additionalFieldsData[fieldName];
    }

    const definition = definitions.find(def => def.name === fieldName);
    if (!definition) {
      return undefined;
    }

    return additionalFieldsData[fieldName];
  }

  /**
   * Check if field definitions are loaded for a given entity type.
   *
   * @param {string} appliesTo - One of 'item', 'doc', or 'association'
   * @returns {boolean} True if field definitions are cached for the given type
   */
  function hasFields(appliesTo) {
    if (!appliesTo) {
      return false;
    }

    const cached = fieldDefinitionsCache.get(appliesTo);
    return Array.isArray(cached) && cached.length > 0;
  }

  return {
    fieldDefinitions,
    loading,
    error,
    fetchFields,
    getFieldValue,
    hasFields
  };
}
