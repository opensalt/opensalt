import { describe, it, expect } from 'vitest';
import {
  getReservedKeys,
  getEditableExtensions
} from '@/config/extensionsConfig.js';

describe('extensionsConfig', () => {
  describe('getReservedKeys', () => {
    it('always includes salt:type for items, plus the kind-specific keys', () => {
      expect(getReservedKeys('item', 'organization')).toEqual([
        'salt:type',
        'ceterms:agentType',
        'ceterms:image',
        'sdo:legalName',
        'ceterms:ctid',
        'ceterms:subjectWebpage',
        'ceterms:jurisdiction'
      ]);
    });

    it('returns only salt:type for general items', () => {
      expect(getReservedKeys('item', 'general')).toEqual(['salt:type']);
    });

    it('returns the document reserved keys', () => {
      expect(getReservedKeys('document')).toEqual(['salt:relatedFrameworks']);
    });

    it('returns the association reserved keys', () => {
      expect(getReservedKeys('association')).toEqual([
        'crosswalk:status',
        'crosswalk:confidence',
        'crosswalk:subtype',
        'sourceDocumentIdentifier',
        'sourceDocumentURI'
      ]);
    });

    it('returns only salt:type for an unknown item kind', () => {
      expect(getReservedKeys('item', 'mystery')).toEqual(['salt:type']);
    });
  });

  describe('getEditableExtensions', () => {
    it('splits organization extensions into editable and reserved', () => {
      const raw = {
        'salt:type': 'organization',
        'ceterms:agentType': 'orgType:Business',
        'ceterms:subjectWebpage': 'https://x.test',
        'acme:customField': 'hello',
        'acme:count': 3
      };
      const { editable, reserved } = getEditableExtensions(raw, 'item', 'organization');
      expect(reserved).toEqual({
        'salt:type': 'organization',
        'ceterms:agentType': 'orgType:Business',
        'ceterms:subjectWebpage': 'https://x.test'
      });
      expect(editable).toEqual({ 'acme:customField': 'hello', 'acme:count': 3 });
    });

    it('general item exposes everything except salt:type', () => {
      const raw = { 'salt:type': 'general', 'acme:a': '1' };
      const { editable, reserved } = getEditableExtensions(raw, 'item', 'general');
      expect(editable).toEqual({ 'acme:a': '1' });
      expect(reserved).toEqual({ 'salt:type': 'general' });
    });

    it('handles missing/empty extensions without throwing', () => {
      expect(getEditableExtensions(undefined, 'document')).toEqual({ editable: {}, reserved: {} });
      expect(getEditableExtensions(null, 'item', 'job')).toEqual({ editable: {}, reserved: {} });
      expect(getEditableExtensions({}, 'association')).toEqual({ editable: {}, reserved: {} });
    });

    it('does not mutate the input object', () => {
      const raw = { 'salt:type': 'job', 'acme:a': '1' };
      const snapshot = { ...raw };
      getEditableExtensions(raw, 'item', 'job');
      expect(raw).toEqual(snapshot);
    });
  });
});