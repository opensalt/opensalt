/**
 * Central registry of extension keys that are owned by the editor and must NOT
 * be exposed as editable extension rows. These keys are mapped to dedicated form
 * fields or used internally by editor features.
 *
 * NOTE: `salt:type` is the item "kind" discriminator and is reserved for ALL
 * items; it is added implicitly by getReservedKeys() for the 'item' scope.
 */
export const RESERVED_KEYS = {
  item: {
    general: [],
    assessment: ['ceterms:deliveryType', 'ceterms:subjectWebpage'],
    course: ['ceterms:deliveryType', 'ceterms:subjectWebpage'],
    credential: ['ob3'],
    job: ['ceterms:subjectWebpage'],
    organization: [
      'ceterms:agentType',
      'ceterms:image',
      'sdo:legalName',
      'ceterms:ctid',
      'ceterms:subjectWebpage',
      'ceterms:jurisdiction'
    ],
    public_key: ['salt:kid'],
    identifier: ['salt:idType']
  },
  document: ['salt:relatedFrameworks'],
  association: [
    'crosswalk:status',
    'crosswalk:confidence',
    'crosswalk:subtype',
    'sourceDocumentIdentifier',
    'sourceDocumentURI'
  ]
};

/** The item "kind" discriminator — reserved for every item. */
export const ITEM_TYPE_EXTENSION_KEY = 'salt:type';

/**
 * Return the list of reserved (hidden, preserved) extension keys for a scope/kind.
 * @param {'item'|'document'|'association'} scope
 * @param {string} [kind] item kind (ignored for document/association)
 * @returns {string[]}
 */
export function getReservedKeys(scope, kind = '') {
  if (scope === 'item') {
    const kindKeys = RESERVED_KEYS.item[kind] || [];
    return [ITEM_TYPE_EXTENSION_KEY, ...kindKeys];
  }
  if (scope === 'document') return [...RESERVED_KEYS.document];
  if (scope === 'association') return [...RESERVED_KEYS.association];
  return [];
}

/**
 * Split a raw extensions object into editable and reserved copies.
 * Does not mutate the input.
 * @param {Record<string, unknown>|null|undefined} rawExtensions
 * @param {'item'|'document'|'association'} scope
 * @param {string} [kind]
 * @returns {{ editable: Record<string, unknown>, reserved: Record<string, unknown> }}
 */
export function getEditableExtensions(rawExtensions, scope, kind = '') {
  const reserved = {};
  const editable = {};
  const reservedSet = new Set(getReservedKeys(scope, kind));
  const src = rawExtensions && typeof rawExtensions === 'object' ? rawExtensions : {};
  for (const [key, value] of Object.entries(src)) {
    if (reservedSet.has(key)) {
      reserved[key] = value;
    } else {
      editable[key] = value;
    }
  }
  return { editable, reserved };
}