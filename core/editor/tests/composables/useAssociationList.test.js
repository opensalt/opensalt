import { describe, it, expect } from 'vitest';
import { getDocumentAssociationDirection } from '../../src/composables/useAssociationList';

const DOC_ID = '561ac8db-f922-54a8-90a8-77dd2cb53bb4';
const OTHER_DOC_ID = 'd5a41835-97b4-558e-9f14-1cbb4a8d8600';

describe('getDocumentAssociationDirection', () => {
    it('returns "normal" when the document is the origin node', () => {
        const assoc = {
            associationType: 'isRelatedTo',
            originNodeURI: { identifier: DOC_ID, documentIdentifier: DOC_ID, targetType: 'document' },
            destinationNodeURI: { identifier: OTHER_DOC_ID, documentIdentifier: OTHER_DOC_ID, targetType: 'document' },
        };
        expect(getDocumentAssociationDirection(assoc, DOC_ID)).toBe('normal');
    });

    it('returns "reversed" when the document is the destination node', () => {
        const assoc = {
            associationType: 'exactMatchOf',
            originNodeURI: { identifier: OTHER_DOC_ID, documentIdentifier: OTHER_DOC_ID },
            destinationNodeURI: { identifier: DOC_ID, documentIdentifier: DOC_ID, targetType: 'document' },
        };
        expect(getDocumentAssociationDirection(assoc, DOC_ID)).toBe('reversed');
    });

    it('returns null for an item-to-item isPeerOf where an item belongs to the document', () => {
        // The bug: CULIN 044 (item in this doc) isPeerOf HOSP 100 (item in another doc)
        const assoc = {
            associationType: 'isPeerOf',
            originNodeURI: { identifier: 'f9197e8d-eee1-537d-bd94-d4fe629bea6e', documentIdentifier: DOC_ID, targetType: 'item' },
            destinationNodeURI: { identifier: 'eb521e91-b880-5586-8dc9-3af5d616a76e', documentIdentifier: OTHER_DOC_ID, targetType: 'item' },
        };
        expect(getDocumentAssociationDirection(assoc, DOC_ID)).toBeNull();
    });

    it('returns null when neither node is the document (both belong to other docs)', () => {
        const assoc = {
            associationType: 'isRelatedTo',
            originNodeURI: { identifier: 'other-item-a', documentIdentifier: OTHER_DOC_ID },
            destinationNodeURI: { identifier: 'other-item-b', documentIdentifier: OTHER_DOC_ID },
        };
        expect(getDocumentAssociationDirection(assoc, DOC_ID)).toBeNull();
    });

    it('returns null when both nodes are the document', () => {
        const assoc = {
            associationType: 'isRelatedTo',
            originNodeURI: { identifier: DOC_ID },
            destinationNodeURI: { identifier: DOC_ID },
        };
        expect(getDocumentAssociationDirection(assoc, DOC_ID)).toBeNull();
    });

    it('returns null when the document identifier is not provided', () => {
        const assoc = {
            associationType: 'isRelatedTo',
            originNodeURI: { identifier: DOC_ID },
            destinationNodeURI: { identifier: OTHER_DOC_ID },
        };
        expect(getDocumentAssociationDirection(assoc, null)).toBeNull();
        expect(getDocumentAssociationDirection(assoc, undefined)).toBeNull();
    });
});
