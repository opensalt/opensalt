// Export all Pinia stores for easy import
export { useDocumentStore } from './documentStore';
export { useCurrentDocumentStore } from './currentDocumentStore';
export { useFilterStore } from './filterStore';
export { useViewStore } from './viewStore';
export { useItemStore } from './itemStore';
export { useAssociationStore } from './associationStore';

// Export for backward compatibility with existing imports
export { useDocumentStore as useFrameworkStore } from './documentStore';
