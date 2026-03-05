import { describe, it, expect } from 'vitest';
import {
    getItemKind,
    formatExtTypeLabel,
    getOrderedAssociationTypes
} from '../../src/composables/useAssociationTypePriority';

describe('useAssociationTypePriority', () => {
    describe('getItemKind', () => {
        it('returns salt:type if available', () => {
            expect(getItemKind({ extensions: { 'salt:type': 'organization' } })).toBe('organization');
            expect(getItemKind({ extensions: { 'salt:type': 'assessment' } })).toBe('assessment');
        });

        it('falls back to itemType if salt:type is missing or default', () => {
            expect(getItemKind({ itemType: 'Credential - Badge' })).toBe('credential');
            expect(getItemKind({ itemType: 'Something Else' })).toBe('default');
            expect(getItemKind({ extensions: { 'salt:type': 'default' }, itemType: 'Credential' })).toBe('default');
        });

        it('identifies documents implicitly by type property', () => {
            expect(getItemKind({ itemType: 'document' })).toBe('__cfdocument');
        });

        it('returns default for unknown/missing properties', () => {
            expect(getItemKind({})).toBe('default');
            expect(getItemKind(null)).toBe('default');
        });
    });

    describe('formatExtTypeLabel', () => {
        it('converts camelCase ext string to Title Case', () => {
            expect(formatExtTypeLabel('ext:prerequisiteFor')).toBe('Prerequisite For');
            expect(formatExtTypeLabel('ext:accepts')).toBe('Accepts');
            expect(formatExtTypeLabel('ext:isRelatedTo')).toBe('Is Related To');
        });

        it('ignores non-ext strings', () => {
            expect(formatExtTypeLabel('exactMatchOf')).toBe('exactMatchOf');
        });
    });

    describe('getOrderedAssociationTypes', () => {
        it('prioritizes meaningful types for organization -> credential', () => {
            const options = getOrderedAssociationTypes('organization', 'credential');

            // Should have ext:accepts, isRelatedTo, ext:offers, then a separator
            expect(options.length).toBeGreaterThan(12); // Meaningful + Sep + Standard + Other

            expect(options[0].value).toBe('ext:accepts');
            expect(options[0].isMeaningful).toBe(true);

            expect(options[1].value).toBe('isRelatedTo');
            expect(options[1].isMeaningful).toBe(true);

            expect(options[2].value).toBe('ext:offers');
            expect(options[2].isMeaningful).toBe(true);

            expect(options[3].isSeparator).toBe(true);

            // The rest are standard types, then 'other'
            expect(options[options.length - 1].value).toBe('other');
        });

        it('does not include isChildOf unless explicitly asked in edit mode', () => {
            const optionsNoEdit = getOrderedAssociationTypes('organization', 'organization');
            expect(optionsNoEdit.find(o => o.value === 'isChildOf')).toBeUndefined();

            const optionsEditWithChild = getOrderedAssociationTypes('organization', 'organization', {
                isEditing: true,
                currentType: 'isChildOf'
            });
            const isChildOpt = optionsEditWithChild.find(o => o.value === 'isChildOf');
            expect(isChildOpt).toBeDefined();
            expect(isChildOpt.isMeaningful).toBe(true);
        });

        it('prepends unknown non-ext types when editing', () => {
            const options = getOrderedAssociationTypes('default', 'default', {
                isEditing: true,
                currentType: 'unknownFrameworkType'
            });

            expect(options[0].value).toBe('unknownFrameworkType');
            expect(options[0].isPrioritized).toBe(true);
        });

        it('does NOT prepend unknown ext types', () => {
            const options = getOrderedAssociationTypes('default', 'default', {
                isEditing: true,
                currentType: 'ext:someCustomType'
            });

            expect(options[0].value).not.toBe('ext:someCustomType');
        });

        it('defaults to standard list when no meaningful types exist', () => {
            // For general default -> default, no meaningful types exist
            const options = getOrderedAssociationTypes('default', 'default');

            const separator = options.find(o => o.isSeparator);
            expect(separator).toBeUndefined(); // shouldn't have separator if no meaningful types

            // First type should normally be standard
            expect(options[0].value).toBe('isRelatedTo');
        });
    });
});
