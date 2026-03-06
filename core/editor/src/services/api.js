/**
 * Centralized API Service
 * Provides a unified interface for all API calls with consistent error handling
 */

/**
 * Custom API Error class for better error handling
 */
class ApiError extends Error {
  constructor(message, status, response) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.response = response;
  }
}

/**
 * API Service class
 * Handles all HTTP requests with authentication, error handling, and response parsing
 */
class ApiService {
  constructor() {
    this.baseUrl = '';
    this.defaultHeaders = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    };
  }

  /**
   * Get authentication token from localStorage
   * @returns {string|null} - The authentication token or null
   */
  getAuthToken() {
    return localStorage.getItem('saltApiToken') || null;
  }

  /**
   * Create an appropriate error object based on response status
   * @param {Response} response - The fetch response object
   * @returns {ApiError} - Formatted error object
   */
  createApiError(response) {
    const errorMessages = {
      400: 'Invalid request. Please check your input.',
      401: 'Authentication failed. Please check your token.',
      403: 'Access denied. You do not have permission.',
      404: 'Resource not found.',
      409: 'Conflict. The resource already exists.',
      422: 'Validation error. Please check your input.',
      429: 'Too many requests. Please try again later.',
      500: 'Server error. Please try again later.',
      502: 'Service unavailable. Please try again later.',
      503: 'Service temporarily unavailable. Please try again later.'
    };

    const message = errorMessages[response.status] ||
      `Request failed: ${response.statusText} (${response.status})`;

    return new ApiError(message, response.status, response);
  }

  /**
   * Make an HTTP request
   * @param {string} endpoint - The API endpoint
   * @param {Object} options - Fetch options (method, headers, body, etc.)
   * @returns {Promise<Object>} - Parsed JSON response
   * @throws {ApiError} - If   request fails
   */
  async request(endpoint, options = {}) {
    const token = this.getAuthToken();
    const headers = {
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers
    };

    const url = `${this.baseUrl}${endpoint}`;
    const requestOptions = {
      ...options,
      headers
    };

    try {
      const response = await fetch(url, requestOptions);

      if (!response.ok) {
        throw this.createApiError(response);
      }

      // Handle empty responses
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        return null;
      }

      return await response.json();
    } catch (error) {
      // Re-throw ApiError as-is, wrap other errors
      if (error instanceof ApiError) {
        throw error;
      }

      // Handle network errors
      if (error.name === 'TypeError' && error.message.includes('fetch')) {
        throw new ApiError(
          'Network error. Please check your connection.',
          0,
          null
        );
      }

      // Handle other unexpected errors
      console.error('Unexpected API error:', error);
      throw new ApiError(
        'An unexpected error occurred. Please try again.',
        0,
        null
      );
    }
  }

  /**
   * Make a GET request
   * @param {string} endpoint - The API endpoint
   * @param {Object} options - Additional options
   * @returns {Promise<Object>} - Parsed JSON response
   */
  async get(endpoint, options = {}) {
    return this.request(endpoint, {
      ...options,
      method: 'GET'
    });
  }

  /**
   * Make a POST request
   * @param {string} endpoint - The API endpoint
   * @param {Object} data - The request body data
   * @param {Object} options - Additional options
   * @returns {Promise<Object>} - Parsed JSON response
   */
  async post(endpoint, data, options = {}) {
    return this.request(endpoint, {
      ...options,
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  /**
   * Make a PUT request
   * @param {string} endpoint - The API endpoint
   * @param {Object} data - The request body data
   * @param {Object} options - Additional options
   * @returns {Promise<Object>} - Parsed JSON response
   */
  async put(endpoint, data, options = {}) {
    return this.request(endpoint, {
      ...options,
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  /**
   * Make a PATCH request
   * @param {string} endpoint - The API endpoint
   * @param {Object} data - The request body data
   * @param {Object} options - Additional options
   * @returns {Promise<Object>} - Parsed JSON response
   */
  async patch(endpoint, data, options = {}) {
    return this.request(endpoint, {
      ...options,
      method: 'PATCH',
      body: JSON.stringify(data)
    });
  }

  /**
   * Make a DELETE request
   * @param {string} endpoint - The API endpoint
   * @param {Object} options - Additional options
   * @returns {Promise<Object>} - Parsed JSON response
   */
  async delete(endpoint, options = {}) {
    return this.request(endpoint, {
      ...options,
      method: 'DELETE'
    });
  }

  /**
   * Upload a file
   * @param {string} endpoint - The API endpoint
   * @param {FormData} formData - The form data with files
   * @param {Object} options - Additional options
   * @returns {Promise<Object>} - Parsed JSON response
   */
  async upload(endpoint, formData, options = {}) {
    const token = this.getAuthToken();

    // Don't set Content-Type for FormData - let browser set it with boundary
    const headers = {
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers
    };

    // Remove Content-Type if it exists in headers
    delete headers['Content-Type'];

    return this.request(endpoint, {
      ...options,
      method: 'POST',
      headers,
      body: formData
    });
  }

  /**
   * Fetch related documents for a given document identifier
   * @param {string} identifier - The document identifier
   * @returns {Promise<Array>} - Array of related DocumentDto objects
   * @throws {ApiError} - If   request fails
   */
  async getRelatedDocuments(identifier) {
    const endpoint = `/ims/case/v1p1/CFDocuments/${identifier}/related`;
    console.log('[api.getRelatedDocuments] Calling endpoint:', endpoint);
    const result = await this.get(endpoint);
    console.log('[api.getRelatedDocuments] Response:', result);
    return result;
  }
}

// Create singleton instance
export const api = new ApiService();

// Export ApiError class for use in components/stores
export { ApiError };
