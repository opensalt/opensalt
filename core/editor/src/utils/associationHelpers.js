/**
 * Association type label mapping for display
 */
const ASSOCIATION_TYPE_LABELS = {
  isChildOf: 'Is Child Of',
  isPeerOf: 'Is Peer Of',
  isRelatedTo: 'Is Related To',
  isPartOf: 'Is Part Of',
  exactMatchOf: 'Exact Match Of',
  hasSkillLevel: 'Has Skill Level',
  exemplar: 'Exemplar',
  isTranslationOf: 'Is Translation Of',
  replacedBy: 'Replaced By',
  precedes: 'Precedes'
};

/**
 * Get a human-readable label for an association type
 * @param {string} type - Association type string
 * @returns {string} - Human-readable label
 */
export function getAssociationTypeLabel(type) {
  if (!type) return 'Unknown';
  if (type.startsWith('ext:')) return type;
  return ASSOCIATION_TYPE_LABELS[type] || type || 'Unknown';
}

/**
 * Format an association type for display by splitting camelCase
 * and handling ext: prefix
 * @param {string} type - Association type string
 * @returns {string} - Formatted type string
 */
export function formatAssociationType(type) {
  if (!type) return 'Unknown';
  let displayType = type;
  if (type.match(/^ext:/)) {
    displayType = type.replace(/^ext:/, '');
  }
  return displayType
    .replace(/([A-Z])/g, ' $1')
    .replace(/^./, str => str.toUpperCase())
    .trim();
}

/**
 * Get a Bootstrap icon class for an association type
 * @param {string} type - Association type string
 * @returns {string} - Bootstrap icon CSS class
 */
export function getAssociationIcon(type) {
  const iconMap = {
    'isChildOf': 'bi bi-diagram-3',
    'isPeerOf': 'bi bi-share',
    'isPartOf': 'bi bi-puzzle',
    'exactMatchOf': 'bi bi-check-circle',
    'precedes': 'bi bi-arrow-right',
    'isRelatedTo': 'bi bi-link',
    'replacedBy': 'bi bi-arrow-clockwise',
    'exemplar': 'bi bi-star',
    'hasSkillLevel': 'bi bi-bar-chart',
    'isTranslationOf': 'bi bi-translate'
  };
  return iconMap[type] || 'bi bi-link-45deg';
}
