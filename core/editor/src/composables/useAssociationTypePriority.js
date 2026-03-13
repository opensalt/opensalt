import { unref } from 'vue';
import { ASSOCIATION_TYPES } from './useAssociationForm';

/**
 * Meaningful association types by source/target item kind.
 * Data derived from OpenSALT V4 MVP FRD tables.
 */
export const MEANINGFUL_ASSOCIATIONS = {
    // --- Organization Associations ---
    'organization->credential': ['ext:accepts', 'isRelatedTo', 'ext:offers'],
    'organization->default': ['exactMatchOf', 'isChildOf', 'hasSkillLevel', 'isRelatedTo'], // default = CFItem
    'organization->organization': ['exactMatchOf', 'isChildOf', 'isPeerOf', 'isRelatedTo', 'isTranslationOf', 'precedes', 'replacedBy'],
    'default->organization': ['isChildOf'], // CFItem isChildOf Organization
    'credential->organization': ['isChildOf'],
    'job->organization': ['isChildOf'],
    'course->organization': ['isChildOf'],
    'organization->job': ['ext:offers'],
    'organization->course': ['isRelatedTo', 'ext:offers'],

    // --- Credential Associations ---
    'credential->default': ['ext:accepts', 'hasSkillLevel', 'precedes'], // default = CFItem
    'credential->credential': ['ext:accepts', 'exactMatchOf', 'isChildOf', 'isPeerOf', 'isRelatedTo', 'isTranslationOf', 'precedes', 'replacedBy'],
    'credential->job': ['ext:accepts', 'isRelatedTo', 'precedes'],
    'credential->course': ['exactMatchOf', 'isChildOf', 'isRelatedTo', 'precedes'],
    'default->credential': ['isChildOf'], // CFItem isChildOf Credential

    // --- Course Associations ---
    'course->default': ['ext:accepts', 'ext:assesses', 'exactMatchOf', 'hasSkillLevel', 'isChildOf', 'isPeerOf', 'isRelatedTo', 'isTranslationOf', 'ext:offers', 'precedes', 'ext:teaches'], // default = CFItem
    'course->credential': ['ext:accepts', 'exactMatchOf', 'isChildOf', 'isRelatedTo', 'ext:offers', 'precedes', 'ext:teaches'],
    'course->job': ['ext:accepts', 'isChildOf', 'isRelatedTo', 'precedes', 'ext:teaches'],
    'course->course': ['ext:accepts', 'exactMatchOf', 'isChildOf', 'isPeerOf', 'isRelatedTo', 'isTranslationOf', 'precedes', 'replacedBy', 'ext:teaches'],
    'course->__cfdocument': ['exactMatchOf'],
    'default->course': ['isChildOf'] // CFItem isChildOf Course
};

// Known external link (exemplar) meaningful types based on the tables.
// These map to targetType non-CASE or associationType exemplar.
export const MEANINGFUL_EXEMPLAR_ASSOCIATIONS = {
    'organization': ['exemplar'],
    'credential': ['exactMatchOf', 'exemplar', 'hasSkillLevel', 'isPeerOf', 'isRelatedTo', 'precedes'],
    'course': ['ext:accepts', 'exactMatchOf', 'exemplar', 'isRelatedTo']
};

/**
 * Get the canonical item kind for prioritization logic.
 *
 * @param {Object} item - The tree node or association item data
 * @returns {string} The item kind
 */
export function getItemKind(item) {
    if (!item) return 'default';

    // 1. Check salt:type extension
    const saltType = item.extensions?.['salt:type'];
    if (saltType && saltType !== 'default') {
        return saltType;
    }

    // 2. Fallback to itemType processing
    const itemType = item.itemType || '';

    if (itemType.startsWith('Credential - ')) {
        return 'credential';
    }

    // 3. Document check
    if (itemType === 'document') {
        return '__cfdocument';
    }

    return 'default';
}

/**
 * Format an ext: type value into a human-readable title-cased label.
 * e.g., 'ext:prerequisiteFor' -> 'Prerequisite For'
 *
 * @param {string} extType - The starting ext string
 * @returns {string} The formatted label
 */
export function formatExtTypeLabel(extType) {
    if (!extType || !extType.startsWith('ext:')) return extType;

    // Remove "ext:" prefix
    let label = extType.substring(4);

    // Insert space before each uppercase letter
    label = label.replace(/([A-Z])/g, ' $1');

    // Capitalize the first letter
    label = label.charAt(0).toUpperCase() + label.slice(1);

    return label.trim();
}

/**
 * Returns an ordered array of association types prioritized by the
 * source and target item kinds.
 *
 * @param {Object|string} sourceItemOrKind - The origin/source item data or its canonical kind
 * @param {Object|string} targetItemOrKind - The destination/target item data or its canonical kind
 * @param {Object} options - Configuration options
 * @param {boolean} options.isEditing - Whether this is an edit modal
 * @param {string} options.currentType - The current association type (to preserve unknown/isChildOf types)
 * @param {boolean} options.isExemplarTarget - True if the target is an external link (exemplar)
 * @param {boolean} options.allowIsChildOf - True to allow selecting isChildOf in add/edit modes
 * @returns {Array} Array of options: { value, label, isMeaningful, isSeparator }
 */
export function getOrderedAssociationTypes(sourceItemOrKind, targetItemOrKind, options = {}) {
    const {
        isEditing = false,
        currentType = '',
        isExemplarTarget = false,
        allowIsChildOf = false
    } = options;

    const sourceKind = typeof sourceItemOrKind === 'string' ? sourceItemOrKind : getItemKind(unref(sourceItemOrKind));
    const targetKind = typeof targetItemOrKind === 'string' ? targetItemOrKind : getItemKind(unref(targetItemOrKind));

    const mappingKey = `${sourceKind}->${targetKind}`;

    // Determine the pool of meaningful types
    const meaningfulTypeValues = isExemplarTarget
        ? (MEANINGFUL_EXEMPLAR_ASSOCIATIONS[sourceKind] || [])
        : (MEANINGFUL_ASSOCIATIONS[mappingKey] || []);

    // Set up tracking
    const processedValues = new Set();
    const prioritizedOptions = [];
    const shouldIncludeIsChildOf = allowIsChildOf || (isEditing && currentType === 'isChildOf');

    // Always handle currentType in edit mode if it's unknown/imported (not ext:)
    const isExtCurrentType = currentType.startsWith('ext:');
    const isKnownStandardType = ASSOCIATION_TYPES.some(t => t.value === currentType);

    // If editing an unknown non-ext type, prepend it so it isn't lost
    if (isEditing && currentType && !isExtCurrentType && !isKnownStandardType) {
        prioritizedOptions.push({
            value: currentType,
            label: currentType,
            isMeaningful: false,
            isPrioritized: true
        });
        processedValues.add(currentType);
    }

    // 1. Add Meaningful Types
    const meaningfulOptions = [];
    for (const typeVal of meaningfulTypeValues) {
        if (processedValues.has(typeVal)) continue;

        const stdType = ASSOCIATION_TYPES.find(opt => opt.value === typeVal);
        let label = typeVal;

        if (stdType) {
            label = stdType.label;
        } else if (typeVal.startsWith('ext:')) {
            label = formatExtTypeLabel(typeVal);
        }

        // Hide isChildOf unless explicitly allowed or currently editing isChildOf.
        if (typeVal === 'isChildOf' && !shouldIncludeIsChildOf) {
            continue;
        }

        meaningfulOptions.push({
            value: typeVal,
            label,
            isMeaningful: true,
            isPrioritized: true
        });
        processedValues.add(typeVal);
    }

    // If we have meaningful options, append them and add a separator
    if (meaningfulOptions.length > 0) {
        prioritizedOptions.push(...meaningfulOptions);
        prioritizedOptions.push({
            value: 'SEPARATOR',
            label: '── Other Types ──',
            isSeparator: true,
            disabled: true
        });
    }

    // 2. Add remaining standard types
    for (const stdType of ASSOCIATION_TYPES) {
        if (stdType.value === 'other') continue; // Save 'other' for last
        if (processedValues.has(stdType.value)) continue;

        // Hide isChildOf unless explicitly allowed or currently editing isChildOf.
        if (stdType.value === 'isChildOf' && !shouldIncludeIsChildOf) {
            continue;
        }

        prioritizedOptions.push({
            value: stdType.value,
            label: stdType.label,
            isMeaningful: false,
            isPrioritized: false
        });
        processedValues.add(stdType.value);
    }

    // 3. Add "Other" type
    prioritizedOptions.push({
        value: 'other',
        label: 'Other',
        isMeaningful: false,
        isPrioritized: false
    });

    return prioritizedOptions;
}
