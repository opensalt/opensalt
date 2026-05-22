/**
 * CASE (Competencies and Academic Standards Exchange) TypeScript Types
 *
 * These types are based on the IMS Global CASE v1p1 OpenAPI specification.
 * https://purl.imsglobal.org/spec/case/v1p1/schema/openapi/imscasev1p1_openapi3_v1p0.yaml
 *
 * This file contains the type definitions for the core CASE objects used in the OpenSALT editor.
 */

// ============================================================================
// Base Types & Enums
// ============================================================================

/**
 * UUID pattern for CASE identifiers
 * Format: xxxxxxxx-xxxx-1xxx-8xxx-xxxxxxxxxxxx
 */
export type UUID = string;

/**
 * DateTime in ISO 8601 format
 */
export type DateTime = string;

/**
 * Date in ISO 8601 format (YYYY-MM-DD)
 */
export type Date = string;

/**
 * URI string (network-resolvable)
 */
export type URI = string;

/**
 * Language code (ISO 639-2)
 */
export type Language = string;

/**
 * Association type enumeration (CASE 1.1)
 */
export type AssociationType =
  | 'isChildOf'
  | 'isPeerOf'
  | 'isPartOf'
  | 'exactMatchOf'
  | 'precedes'
  | 'isRelatedTo'
  | 'replacedBy'
  | 'exemplar'
  | 'hasSkillLevel'
  | 'isTranslationOf'
  | string; // Allow custom extension types (ext:)

/**
 * CASE version
 */
export type CaseVersion = '1.1';

/**
 * Framework type (CASE 1.1)
 */
export type FrameworkType = string;

/**
 * Extension type for proprietary extensions
 */
export type ExtensionObject = Record<string, unknown>;

// ============================================================================
// Link Types
// ============================================================================

/**
 * A container for the information that is used to achieve the link data reference.
 * Uses a generic identifier (not necessarily UUID)
 */
export interface LinkGenURI {
  /** A human readable title for the associated object */
  title: string;
  /** An unambiguous, synthetic, globally unique identifier for the associated object */
  identifier: string;
  /** A network-resolvable URI pointing to the authoritative reference */
  uri: URI;
  /** Type of the referenced association (CASE 1.1+) */
  targetType?: 'CASE' | string;
}

/**
 * A container for the information that is used to achieve the link data reference.
 * Uses a UUID identifier
 */
export interface LinkURI {
  /** A human readable title for the associated object */
  title: string;
  /** An unambiguous, synthetic, globally unique identifier (UUID) for the associated object */
  identifier: UUID;
  /** A network-resolvable URI pointing to the authoritative reference */
  uri: URI;
}

// ============================================================================
// CFDocument - Competency Framework Document
// ============================================================================

/**
 * The container for the data about a competency framework document (CFDocument).
 * A CFDocument is the root for the creation of a learning standard/competency.
 */
export interface CFDocument {
  /** An unambiguous, synthetic, globally unique identifier for the CFDocument */
  identifier: UUID;
  /** An unambiguous reference to the CFDocument using a network-resolvable URI */
  uri: URI;
  /** This attribute allows framework creators to indicate what type of framework this is (CASE 1.1+) */
  frameworkType?: FrameworkType;
  /** Denotes the version of the CFDocument. If present it MUST have a value of '1.1' (CASE 1.1+) */
  caseVersion?: CaseVersion;
  /** The entity with authority that promulgates the competency framework */
  creator: string;
  /** The title of the CFDocument */
  title: string;
  /** A system generated timestamp of the most recent change to this record */
  lastChangeDateTime: DateTime;
  /** The URL link to the formal citation of the original achievement standards document */
  officialSourceURL?: URI;
  /** The entity responsible for making the learning standards document available */
  publisher?: string;
  /** A human readable description of the CFDocument */
  description?: string;
  /** The topic or academic subject of the Document */
  subject?: string[];
  /** A set of Link URIs denoting the set of subjects for the document */
  subjectURI?: LinkURI[];
  /** The default language of the text used for the content */
  language?: Language;
  /** Defines the revision of the document */
  version?: string;
  /** The publication status of the document */
  adoptionStatus?: string;
  /** The date the CFDocument status started */
  statusStartDate?: Date;
  /** The date the CFDocument status ended or changed to another status */
  statusEndDate?: Date;
  /** Link to the license */
  licenseURI?: LinkURI;
  /** Any text used to comment on the published CFDocument */
  notes?: string;
  /** Link to the CFPackage (CASE 1.1+) */
  CFPackageURI?: LinkURI;
  /** Proprietary extensions */
  extensions?: ExtensionObject;
}

/**
 * CFDocument with additional runtime properties for tree visualization
 */
export interface CFDocumentNode extends CFDocument {
  /** Child items in the framework hierarchy */
  items?: CFItemNode[];
  /** Associations involving this document */
  associations?: CFAssociation[];
  /** UI-specific properties */
  children?: CFItemNode[];
}

// ============================================================================
// CFItem - Competency Framework Item
// ============================================================================

/**
 * The container for the CFItem data.
 * This is the content that either describes a specific competency or describes
 * a grouping of competencies within the taxonomy of a Competency Framework Document.
 */
export interface CFItem {
  /** An unambiguous, synthetic, globally unique identifier for the CFItem */
  identifier: UUID;
  /** The text of the statement */
  fullStatement: string;
  /** An alternate 'term' for Competency */
  alternativeLabel?: string;
  /** The textual label identifying the class of the statement */
  CFItemType?: string;
  /** An unambiguous reference to the CFItem using a network-resolvable URI */
  uri: URI;
  /** A human-referenceable code designated by the publisher to identify the item */
  humanCodingScheme?: string;
  /** Alphanumeric characters denoting the positioning in a sequential listing */
  listEnumeration?: string;
  /** An abbreviated version of the Full Statement */
  abbreviatedStatement?: string;
  /** The significant topicality using free-text keywords and phrases */
  conceptKeywords?: string[];
  /** Link URI for concept keywords */
  conceptKeywordsURI?: LinkURI;
  /** Information about the derivation of a CFItem statement */
  notes?: string;
  /** The topic or academic subject of the Item (CASE 1.1+) */
  subject?: string[];
  /** Link URIs for subjects (CASE 1.1+) */
  subjectURI?: LinkURI[];
  /** The default language of the text used for the content */
  language?: Language;
  /** The education level, grade level or primary instructional level */
  educationLevel?: string[];
  /** Link URI for item type */
  CFItemTypeURI?: LinkURI;
  /** Link URI for license */
  licenseURI?: LinkURI;
  /** The date the CFItem status started */
  statusStartDate?: Date;
  /** The date the CFItem status ended or changed to another status */
  statusEndDate?: Date;
  /** A system generated timestamp of the most recent change to this record */
  lastChangeDateTime: DateTime;
  /** Link to the CFDocument (CASE 1.1+) */
  CFDocumentURI?: LinkURI;
  /** Proprietary extensions */
  extensions?: ExtensionObject;
}

/**
 * CFItem with additional runtime properties for tree visualization
 */
export interface CFItemNode extends CFItem {
  /** Child items in the hierarchy */
  children?: CFItemNode[];
  /** Direct associations for this item */
  associations?: CFAssociation[];
  /** Sequence number for ordering */
  sequenceNumber?: number;
}

// ============================================================================
// CFAssociation - Competency Framework Association
// ============================================================================

/**
 * The container for the data about the relationship between two CFDocuments
 * or between two CFItems.
 */
export interface CFAssociation {
  /** An unambiguous, synthetic, globally unique identifier for the CFAssociation */
  identifier: UUID;
  /** The type of association */
  associationType: AssociationType;
  /** Used to order associated objects */
  sequenceNumber?: number;
  /** An unambiguous reference to the CFAssociation using a network-resolvable URI */
  uri: URI;
  /** The origin node of the association */
  originNodeURI: LinkGenURI;
  /** The destination node of the association */
  destinationNodeURI: LinkGenURI;
  /** Link to the association grouping */
  CFAssociationGroupingURI?: LinkURI;
  /** A system generated timestamp of the most recent change to this record */
  lastChangeDateTime: DateTime;
  /** Information about the CFAssociation (CASE 1.1+) */
  notes?: string;
  /** Link to the CFDocument (CASE 1.1+) */
  CFDocumentURI?: LinkURI;
  /** Proprietary extensions */
  extensions?: ExtensionObject;
}

// ============================================================================
// CFAssociationGrouping - Competency Framework Association Grouping
// ============================================================================

/**
 * The container for the data about the grouping of associations.
 */
export interface CFAssociationGrouping {
  /** An unambiguous, synthetic, globally unique identifier */
  identifier: UUID;
  /** The title of the CFAssociationGrouping */
  title: string;
  /** An unambiguous reference using a network-resolvable URI */
  uri: URI;
  /** A system generated timestamp of the most recent change to this record */
  lastChangeDateTime: DateTime;
  /** A human readable description */
  description?: string;
  /** Proprietary extensions */
  extensions?: ExtensionObject;
}

// ============================================================================
// CFSubject - Competency Framework Subject
// ============================================================================

/**
 * The container for the definition of a topic or academic subject
 * which is addressed by the competency framework.
 */
export interface CFSubject {
  /** An unambiguous, synthetic, globally unique identifier */
  identifier: UUID;
  /** An unambiguous reference using a network-resolvable URI */
  uri: URI;
  /** The title of the CFSubject */
  title: string;
  /** A human-referenceable code designated by the publisher to identify the item in the hierarchy */
  hierarchyCode: string;
  /** A human readable description */
  description?: string;
  /** A system generated timestamp of the most recent change to this record */
  lastChangeDateTime: DateTime;
  /** Proprietary extensions */
  extensions?: ExtensionObject;
}

/**
 * Container for a set of CFSubjects
 */
export interface CFSubjectSet {
  CFSubjects: CFSubject[];
}

// ============================================================================
// CFConcept - Competency Framework Concept
// ============================================================================

/**
 * The container for the definition of a competency framework concept.
 */
export interface CFConcept {
  /** An unambiguous, synthetic, globally unique identifier */
  identifier: UUID;
  /** An unambiguous reference using a network-resolvable URI */
  uri: URI;
  /** The title of the CFConcept */
  title: string;
  /** A human-referenceable code */
  hierarchyCode?: string;
  /** A human readable description */
  description?: string;
  /** The keywords associated with concept (CASE 1.1+) */
  keywords?: string;
  /** A system generated timestamp of the most recent change to this record */
  lastChangeDateTime: DateTime;
  /** Proprietary extensions */
  extensions?: ExtensionObject;
}

// ============================================================================
// CFLicense - Competency Framework License
// ============================================================================

/**
 * The container for the definition of a license.
 */
export interface CFLicense {
  /** An unambiguous, synthetic, globally unique identifier */
  identifier: UUID;
  /** The title of the CFLicense */
  title: string;
  /** An unambiguous reference using a network-resolvable URI */
  uri: URI;
  /** A system generated timestamp of the most recent change to this record */
  lastChangeDateTime: DateTime;
  /** Legal license text or link to license */
  licenseText: string;
  /** Proprietary extensions */
  extensions?: ExtensionObject;
}

// ============================================================================
// CFItemType - Competency Framework Item Type
// ============================================================================

/**
 * The container for the definition of an item type.
 */
export interface CFItemType {
  /** An unambiguous, synthetic, globally unique identifier */
  identifier: UUID;
  /** An unambiguous reference using a network-resolvable URI */
  uri: URI;
  /** The title of the CFItemType */
  title: string;
  /** A human-referenceable code */
  hierarchyCode: string;
  /** A human readable description */
  description: string;
  /** A system generated timestamp of the most recent change to this record */
  lastChangeDateTime: DateTime;
  /** The type code (CASE 1.1+) */
  typeCode?: string;
  /** Proprietary extensions */
  extensions?: ExtensionObject;
}

// ============================================================================
// CFRubric - Competency Framework Rubric
// ============================================================================

/**
 * The container for the definition of a rubric which is addressed by the competency framework.
 */
export interface CFRubric {
  /** An unambiguous, synthetic, globally unique identifier */
  identifier: UUID;
  /** An unambiguous reference using a network-resolvable URI */
  uri: URI;
  /** The title of the CFRubric */
  title?: string;
  /** A human readable description */
  description?: string;
  /** A system generated timestamp of the most recent change to this record */
  lastChangeDateTime: DateTime;
  /** The set of CFRubricCriteria */
  CFRubricCriteria?: CFRubricCriterion[];
  /** Proprietary extensions */
  extensions?: ExtensionObject;
}

/**
 * The container for the definition of a rubric criterion.
 */
export interface CFRubricCriterion {
  /** An unambiguous, synthetic, globally unique identifier */
  identifier: UUID;
  /** An unambiguous reference using a network-resolvable URI */
  uri: URI;
  /** A textual label for category */
  category?: string;
  /** A human readable description */
  description?: string;
  /** Link to the associated CFItem */
  CFItemURI?: LinkURI;
  /** A numeric weight for scored rubrics */
  weight?: number;
  /** Numeric value representing position in the criteria list */
  position?: number;
  /** UUID for the parent CFRubric */
  rubricId?: UUID;
  /** A system generated timestamp */
  lastChangeDateTime: DateTime;
  /** The set of CFRubricCriterionLevels */
  CFRubricCriterionLevels?: CFRubricCriterionLevel[];
  /** Proprietary extensions */
  extensions?: ExtensionObject;
}

/**
 * The container for the definition of a criterion level.
 */
export interface CFRubricCriterionLevel {
  /** An unambiguous, synthetic, globally unique identifier */
  identifier: UUID;
  /** An unambiguous reference using a network-resolvable URI */
  uri: URI;
  /** A human readable description */
  description?: string;
  /** A qualitative description for column headers or row labels */
  quality?: string;
  /** The points awarded for achieving this level */
  score?: number;
  /** Pre-defined feedback text */
  feedback?: string;
  /** Numeric value representing position in the list */
  position?: number;
  /** UUID for the parent CFRubricCriterion */
  rubricCriterionId?: UUID;
  /** A system generated timestamp */
  lastChangeDateTime: DateTime;
  /** Proprietary extensions (CASE 1.1+) */
  extensions?: ExtensionObject;
}

// ============================================================================
// CFPackage - Competency Framework Package
// ============================================================================

/**
 * The container for all of the data for a Competency Framework Package.
 * This is the root CFDocument and ALL of the corresponding components.
 */
export interface CFPackage {
  /** The root CFDocument */
  CFDocument: CFPckgDocument;
  /** The set of CFItems (direct or indirect children) */
  CFItems?: CFPckgItem[];
  /** The set of CFAssociations */
  CFAssociations?: CFPckgAssociation[];
  /** The set of CFDefinitions */
  CFDefinitions?: CFDefinitions;
  /** The set of CFRubrics */
  CFRubrics?: CFRubric[];
  /** Proprietary extensions */
  extensions?: ExtensionObject;
}

/**
 * CFDocument within a package context
 */
export interface CFPckgDocument {
  identifier: UUID;
  uri: URI;
  frameworkType?: FrameworkType;
  caseVersion?: CaseVersion;
  creator: string;
  title: string;
  lastChangeDateTime: DateTime;
  officialSourceURL?: URI;
  publisher?: string;
  description?: string;
  subject?: string[];
  subjectURI?: LinkURI[];
  language?: Language;
  version?: string;
  adoptionStatus?: string;
  statusStartDate?: Date;
  statusEndDate?: Date;
  licenseURI?: LinkURI;
  notes?: string;
  extensions?: ExtensionObject;
}

/**
 * CFItem within a package context
 */
export interface CFPckgItem {
  identifier: UUID;
  fullStatement: string;
  alternativeLabel?: string;
  CFItemType?: string;
  uri: URI;
  humanCodingScheme?: string;
  listEnumeration?: string;
  abbreviatedStatement?: string;
  conceptKeywords?: string[];
  conceptKeywordsURI?: LinkURI;
  notes?: string;
  subject?: string[];
  subjectURI?: LinkURI[];
  language?: Language;
  educationLevel?: string[];
  CFItemTypeURI?: LinkURI;
  licenseURI?: LinkURI;
  statusStartDate?: Date;
  statusEndDate?: Date;
  lastChangeDateTime: DateTime;
  extensions?: ExtensionObject;
}

/**
 * CFAssociation within a package context
 */
export interface CFPckgAssociation {
  identifier: UUID;
  associationType: AssociationType;
  sequenceNumber?: number;
  uri: URI;
  originNodeURI: LinkGenURI;
  destinationNodeURI: LinkGenURI;
  CFAssociationGroupingURI?: LinkURI;
  lastChangeDateTime: DateTime;
  notes?: string;
  extensions?: ExtensionObject;
}

/**
 * Container for all CF definitions
 */
export interface CFDefinitions {
  CFAssociationGroupings?: CFAssociationGrouping[];
  CFSubjects?: CFSubject[];
  CFConcepts?: CFConcept[];
  CFLicenses?: CFLicense[];
  CFItemTypes?: CFItemType[];
  /** Proprietary extensions (CASE 1.1+) */
  extensions?: ExtensionObject;
}

// ============================================================================
// API Response Types
// ============================================================================

/**
 * Standard API response wrapper for CASE objects
 */
export interface CaseApiResponse<T> {
  data: T;
}

/**
 * API response for document list
 */
export interface CaseDocumentListResponse {
  data: CFDocument[];
  pagination?: {
    hasNextPage: boolean;
    nextCursor?: string;
  };
}

// ============================================================================
// Utility Types
// ============================================================================

/**
 * Type for creating a new CFItem (identifier generated client-side)
 */
export type CreateCFItem = Omit<CFItem, 'identifier' | 'lastChangeDateTime'> & {
  identifier?: UUID;
};

/**
 * Type for creating a new CFDocument (identifier generated client-side)
 */
export type CreateCFDocument = Omit<CFDocument, 'identifier' | 'lastChangeDateTime'> & {
  identifier?: UUID;
};

/**
 * Type for updating a CFItem
 */
export type UpdateCFItem = Partial<CreateCFItem>;

/**
 * Type for updating a CFDocument
 */
export type UpdateCFDocument = Partial<CreateCFDocument>;

// ============================================================================
// UI-Specific Types (for the editor application)
// ============================================================================

/**
 * Tree node representation for the document tree editor
 */
export interface TreeNode {
  id: string;
  identifier: UUID;
  title: string;
  fullStatement?: string;
  humanCodingScheme?: string;
  children: TreeNode[];
  depth: number;
  expanded: boolean;
  hasChildren: boolean;
  isLoading?: boolean;
  item: CFItemNode;
}

/**
 * Drag and drop position
 */
export type DropPosition = 'before' | 'after' | 'inside';

/**
 * Item move operation
 */
export interface MoveOperation {
  draggedItem: CFItemNode;
  targetItem: CFItemNode | CFDocumentNode;
  position: DropPosition;
}
