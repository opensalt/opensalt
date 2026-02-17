import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { api, ApiError } from '@/services/api.js';

describe('ApiService', () => {
  let mockFetch;

  beforeEach(() => {
    mockFetch = vi.fn();
    global.fetch = mockFetch;
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('getAuthToken', () => {
    it('returns token from localStorage when present', () => {
      const token = 'test-token-123';
      localStorage.getItem.mockReturnValue(token);
      expect(api.getAuthToken()).toBe(token);
    });

    it('returns null when token not present', () => {
      localStorage.getItem.mockReturnValue(null);
      expect(api.getAuthToken()).toBe(null);
    });
  });

  describe('GET requests', () => {
    it('makes successful GET request without auth', async () => {
      const mockResponse = { data: [{ id: 1, title: 'Test' }] };
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ 'content-type': 'application/json' }),
        json: () => Promise.resolve(mockResponse)
      });

      const result = await api.get('/api/v1/documents');
      expect(result).toEqual(mockResponse);
    });

    it('makes GET request with auth token when available', async () => {
      const token = 'bearer-token';
      localStorage.getItem.mockReturnValue(token);

      const mockResponse = { data: [] };
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ 'content-type': 'application/json' }),
        json: () => Promise.resolve(mockResponse)
      });

      await api.get('/api/v1/documents');
      expect(mockFetch).toHaveBeenCalledWith('/api/v1/documents', expect.objectContaining({
        headers: expect.objectContaining({
          Authorization: `Bearer ${token}`
        })
      }));
    });

    it('handles 401 unauthorized error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        statusText: 'Unauthorized'
      });

      await expect(api.get('/api/v1/documents')).rejects.toMatchObject({
        status: 401,
        name: 'ApiError'
      });
    });

    it('handles 404 not found error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 404,
        statusText: 'Not Found'
      });

      await expect(api.get('/api/v1/documents/non-existent')).rejects.toMatchObject({
        status: 404
      });
    });

    it('handles network errors', async () => {
      mockFetch.mockRejectedValueOnce(new TypeError('fetch failed'));

      await expect(api.get('/api/v1/documents')).rejects.toMatchObject({
        status: 0,
        message: 'Network error. Please check your connection.'
      });
    });

    it('returns null for non-JSON responses', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ 'content-type': 'text/html' })
      });

      const result = await api.get('/api/v1/documents');
      expect(result).toBeNull();
    });
  });

  describe('POST requests', () => {
    it('creates new document successfully', async () => {
      const newDoc = { title: 'New Document', creator: 'Test User' };
      const mockResponse = { id: 'abc123', ...newDoc };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 201,
        headers: new Headers({ 'content-type': 'application/json' }),
        json: () => Promise.resolve(mockResponse)
      });

      const result = await api.post('/api/v1/documents', newDoc);
      expect(result).toEqual(mockResponse);
      expect(mockFetch).toHaveBeenCalledWith('/api/v1/documents', expect.objectContaining({
        method: 'POST',
        body: JSON.stringify(newDoc)
      }));
    });

    it('handles validation error (400)', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 400,
        statusText: 'Bad Request'
      });

      await expect(api.post('/api/v1/documents', {})).rejects.toMatchObject({
        status: 400
      });
    });

    it('handles unauthorized (401)', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        statusText: 'Unauthorized'
      });

      await expect(api.post('/api/v1/documents', {})).rejects.toMatchObject({
        status: 401
      });
    });
  });

  describe('PUT requests', () => {
    it('updates document successfully', async () => {
      const updateData = { title: 'Updated Title' };
      const mockResponse = { id: 'abc123', title: 'Updated Title' };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ 'content-type': 'application/json' }),
        json: () => Promise.resolve(mockResponse)
      });

      const result = await api.put('/api/v1/documents/abc123', updateData);
      expect(result).toEqual(mockResponse);
      expect(mockFetch).toHaveBeenCalledWith('/api/v1/documents/abc123', expect.objectContaining({
        method: 'PUT',
        body: JSON.stringify(updateData)
      }));
    });

    it('handles not found on update', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 404,
        statusText: 'Not Found'
      });

      await expect(api.put('/api/v1/documents/non-existent', {})).rejects.toMatchObject({
        status: 404
      });
    });
  });

  describe('PATCH requests', () => {
    it('patches resource successfully', async () => {
      const patchData = [{ op: 'replace', path: '/title', value: 'New Title' }];
      const mockResponse = { id: 'abc123', title: 'New Title' };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ 'content-type': 'application/json' }),
        json: () => Promise.resolve(mockResponse)
      });

      const result = await api.patch('/api/v1/documents/abc123', patchData);
      expect(result).toEqual(mockResponse);
    });
  });

  describe('DELETE requests', () => {
    it('deletes resource successfully', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 204,
        headers: new Headers()
      });

      await api.delete('/api/v1/documents/abc123');
      expect(mockFetch).toHaveBeenCalledWith('/api/v1/documents/abc123', expect.objectContaining({
        method: 'DELETE'
      }));
    });

    it('handles not found on delete', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 404,
        statusText: 'Not Found'
      });

      await expect(api.delete('/api/v1/documents/non-existent')).rejects.toMatchObject({
        status: 404
      });
    });
  });

  describe('file upload', () => {
    it('uploads file with FormData', async () => {
      const formData = new FormData();
      formData.append('file', new Blob(['test content']), 'test.json');

      const mockResponse = { success: true };
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ 'content-type': 'application/json' }),
        json: () => Promise.resolve(mockResponse)
      });

      const result = await api.upload('/api/v1/import', formData);
      expect(result).toEqual(mockResponse);
    });

    it('includes auth token in upload request', async () => {
      const token = 'upload-token';
      localStorage.getItem.mockReturnValue(token);

      const formData = new FormData();
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ 'content-type': 'application/json' }),
        json: () => Promise.resolve({})
      });

      await api.upload('/api/v1/import', formData);
      expect(mockFetch).toHaveBeenCalledWith('/api/v1/import', expect.objectContaining({
        headers: expect.objectContaining({
          Authorization: `Bearer ${token}`
        })
      }));
    });
  });

  describe('error handling', () => {
    it('creates appropriate error for 403 Forbidden', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 403,
        statusText: 'Forbidden'
      });

      await expect(api.get('/api/v1/documents')).rejects.toMatchObject({
        status: 403,
        message: 'Access denied. You do not have permission.'
      });
    });

    it('creates appropriate error for 422 Validation Error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 422,
        statusText: 'Unprocessable Entity'
      });

      await expect(api.post('/api/v1/documents', {})).rejects.toMatchObject({
        status: 422,
        message: 'Validation error. Please check your input.'
      });
    });

    it('creates appropriate error for 429 Rate Limited', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 429,
        statusText: 'Too Many Requests'
      });

      await expect(api.get('/api/v1/documents')).rejects.toMatchObject({
        status: 429,
        message: 'Too many requests. Please try again later.'
      });
    });

    it('creates appropriate error for 500 Server Error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        statusText: 'Internal Server Error'
      });

      await expect(api.get('/api/v1/documents')).rejects.toMatchObject({
        status: 500,
        message: 'Server error. Please try again later.'
      });
    });

    it('handles unknown error codes', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 418,
        statusText: "I'm a teapot"
      });

      await expect(api.get('/api/v1/documents')).rejects.toMatchObject({
        status: 418,
        message: expect.stringContaining("I'm a teapot")
      });
    });
  });
});
