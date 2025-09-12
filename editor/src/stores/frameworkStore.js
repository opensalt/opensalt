import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useFrameworkStore = defineStore('framework', () => {
  // State
  const documents = ref([]);
  const currentDocument = ref(null);
  const loading = ref(false);
  const error = ref(null);
  const searchQuery = ref('');
  const selectedFilters = ref({
    itemType: '',
    subject: '',
    associationStatus: '',
    modifiedSince: ''
  });
  const selectedAssociationGroup = ref('all');
  const currentDocumentAssociationGroupings = ref([]);
  const currentDocumentDefinitions = ref({
    concepts: [],
    subjects: [],
    licenses: [],
    itemTypes: [],
    extensions: null
  });
  const currentDocumentRubrics = ref([]);
  const currentDocumentAssociations = ref([]);

  // View state
  const currentView = ref('tree'); // 'tree', 'association', 'log'

  // Getters
  const filteredDocuments = computed(() => {
    if (!searchQuery.value && !Object.values(selectedFilters.value).some(filter => filter)) {
      return documents.value;
    }

    return documents.value.filter(doc => {
      // Search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        if (!doc.title?.toLowerCase().includes(query) &&
            !doc.description?.toLowerCase().includes(query)) {
          return false;
        }
      }

      // Other filters can be added here as needed
      return true;
    });
  });

  const filteredItems = computed(() => {
    if (!currentDocument.value || !currentDocument.value.items) {
      return [];
    }

    if (!searchQuery.value && !Object.values(selectedFilters.value).some(filter => filter) && selectedAssociationGroup.value === 'all') {
      return currentDocument.value.items;
    }

    return filterItemsRecursively(currentDocument.value.items, searchQuery.value, selectedFilters.value, selectedAssociationGroup.value);
  });

  const availableSubjects = computed(() => {
    const subjects = new Set();
    documents.value.forEach(doc => {
      if (doc.subject && typeof doc.subject === 'string') {
        subjects.add(doc.subject);
      }
    });
    return Array.from(subjects).map(subject => ({
      id: subject.toLowerCase().replace(/\s+/g, '-'),
      title: subject
    }));
  });

  const documentsGroupedByCreator = computed(() => {
    const grouped = new Map();

    // Group documents by creator
    documents.value.forEach(doc => {
      const creator = doc.creator || 'Unknown Creator';
      if (!grouped.has(creator)) {
        grouped.set(creator, []);
      }
      grouped.get(creator).push(doc);
    });

    // Sort creators alphabetically
    const sortedCreators = Array.from(grouped.keys()).sort();

    // Sort documents within each creator group alphabetically by title
    const result = [];
    sortedCreators.forEach(creator => {
      const docs = grouped.get(creator).sort((a, b) => a.title.localeCompare(b.title));
      result.push({
        creator,
        documents: docs
      });
    });

    return result;
  });

  const associationGroups = computed(() => {
    const defaultGroups = [
      { id: 'all', title: 'All Groups', description: 'Show items from all association groups' },
      { id: 'default', title: 'Default Group', description: 'Default association group' }
    ];

    // Add groups from the current document's CFAssociationGroupings
    const packageGroups = currentDocumentAssociationGroupings.value.map(grouping => ({
      id: grouping.identifier,
      title: grouping.title || 'Untitled Group',
      description: grouping.description || '',
      uri: grouping.uri,
      lastChangeDateTime: grouping.lastChangeDateTime,
      extensions: grouping.extensions
    }));

    return [...defaultGroups, ...packageGroups];
  });

  // Actions
  async function fetchDocuments() {
    loading.value = true;
    error.value = null;

    try {
      const baseUrl = 'http://web.salt-default';
      const endpoint = '/api/v1/documents';
      const limit = 1000; // Adjust as needed
      let allDocuments = [];
      let cursor = null;
      let hasNextPage = true;

      // Get authentication token - replace with actual token retrieval logic
      const token = getAuthToken(); // Implement this function to retrieve Bearer token

      while (hasNextPage) {
        const params = new URLSearchParams({
          'page[size]': limit.toString(),
        });

        if (cursor) {
          params.append('page[after]', cursor);
        }

        const url = `${baseUrl}${endpoint}?${params.toString()}`;

        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }
        const response = await fetch(url, {
          method: 'GET',
          headers: headers,
        });

        if (!response.ok) {
          if (response.status === 401) {
            throw new Error('Authentication failed. Please check your token.');
          } else if (response.status === 403) {
            throw new Error('Access denied. You do not have permission to view documents.');
          } else {
            throw new Error(`Failed to fetch documents: ${response.status} ${response.statusText}`);
          }
        }

        const data = await response.json();

        if (!data.data || !Array.isArray(data.data)) {
          throw new Error('Invalid response format: expected data array');
        }

        // Transform the data to match our expected format
        const transformedDocs = data.data.map(doc => ({
          id: doc.identifier,
          title: doc.title || 'Untitled Document',
          description: doc.description || '',
          creator: doc.creator || '',
          subject: Array.isArray(doc.subject) ? doc.subject.join(', ') : (doc.subject || ''),
          status: doc.adoptionStatus || '',
          lastModified: doc.lastChangeDateTime || '',
          language: doc.language || '',
          version: doc.version || ''
        }));

        allDocuments = allDocuments.concat(transformedDocs);

        // Check pagination
        if (data.pagination && typeof data.pagination.hasNextPage === 'boolean') {
          hasNextPage = data.pagination.hasNextPage;
          cursor = data.pagination.nextCursor || null;
        } else {
          // If no pagination info, assume no more pages
          hasNextPage = false;
        }
      }

      documents.value = allDocuments;

    } catch (err) {
      error.value = err.message;
      console.error('Error fetching documents:', err);
      // Keep any previously loaded documents if there was an error
      if (documents.value.length === 0) {
        documents.value = [];
      }
    } finally {
      loading.value = false;
    }
  }

  // Helper function to get authentication token
  // Replace this with your actual token retrieval logic
  function getAuthToken() {
    // Example implementations:
    // return localStorage.getItem('authToken');
    // return store.getters.getAuthToken;
    // return config.apiToken;

    // For now, return a placeholder - replace with actual implementation
    const token = localStorage.getItem('saltApiToken') || null;
    /*
    if (!token) {
      throw new Error('No authentication token found. Please log in or configure API token.');
    }
     */

    return token;
  }

  async function fetchDocument(identifier) {
    console.log('[DEBUG] frameworkStore.fetchDocument called with identifier:', identifier);
    console.log('[DEBUG] Current currentDocument state before fetch:', currentDocument.value);
    loading.value = true;
    error.value = null;

    try {
      const response = await fetch(`http://web.salt-default/ims/case/v1p1/CFPackages/${identifier}`);
      if (!response.ok) {
        throw new Error(`Failed to fetch document: ${response.statusText}`);
      }

      const data = await response.json();

      // Extract CFDefinitions from the package
      const cfDefinitions = data.CFDefinitions || {};
      const cfAssociationGroupings = cfDefinitions.CFAssociationGroupings || [];
      currentDocumentAssociationGroupings.value = cfAssociationGroupings;

      // Store all definitions for potential use
      currentDocumentDefinitions.value = {
        concepts: cfDefinitions.CFConcepts || [],
        subjects: cfDefinitions.CFSubjects || [],
        licenses: cfDefinitions.CFLicenses || [],
        itemTypes: cfDefinitions.CFItemTypes || [],
        extensions: cfDefinitions.extensions || null
      };

      // Extract CFRubrics from the package
      currentDocumentRubrics.value = data.CFRubrics || [];

      // Extract CFAssociations from the package
      currentDocumentAssociations.value = data.CFAssociations || [];

      // Transform the CASE format to our internal format
      const cfDoc = data.CFDocument || {};
      const items = transformCASEItems(data.CFItems || [], data.CFAssociations || []);

      currentDocument.value = {
        id: cfDoc.identifier,
        uri: cfDoc.uri || '',
        title: cfDoc.title || 'Untitled',
        description: cfDoc.description || '',
        creator: cfDoc.creator || '',
        subject: cfDoc.subject || '',
        subjectURI: cfDoc.subjectURI || [],
        status: cfDoc.adoptionStatus || 'Draft',
        statusStartDate: cfDoc.statusStartDate || null,
        statusEndDate: cfDoc.statusEndDate || null,
        lastModified: cfDoc.lastChangeDateTime || '',
        language: cfDoc.language || '',
        version: cfDoc.version || '',
        officialSourceURL: cfDoc.officialSourceURL || '',
        publisher: cfDoc.publisher || '',
        licenseURI: cfDoc.licenseURI || null,
        notes: cfDoc.notes || '',
        frameworkType: cfDoc.frameworkType || '',
        caseVersion: cfDoc.caseVersion || '',
        extensions: cfDoc.extensions || null,
        CFPackageURI: cfDoc.CFPackageURI || null,
        items: items
      };

      return currentDocument.value;

    } catch (err) {
      error.value = err.message;
      console.error('Error fetching document:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function transformCASEItems(cfItems, cfAssociations) {
    const items = new Map();
    const children = new Map();

    // First pass: create all items
    cfItems.forEach(item => {
      items.set(item.identifier, {
        identifier: item.identifier,
        uri: item.uri || '',
        title: item.fullStatement || item.abbreviatedStatement || 'Untitled Item',
        fullStatement: item.fullStatement || '',
        abbreviatedTitle: item.abbreviatedStatement || item.fullStatement || 'Untitled Item',
        abbreviatedStatement: item.abbreviatedStatement || '',
        alternativeLabel: item.alternativeLabel || '',
        hcs: item.humanCodingScheme || '',
        humanCodingScheme: item.humanCodingScheme || '',
        listEnumeration: item.listEnumeration || '',
        lastChanged: item.lastChangeDateTime || '',
        itemType: item.CFItemType || 'item',
        CFItemTypeURI: item.CFItemTypeURI || null,
        conceptKeywords: item.conceptKeywords || [],
        conceptKeywordsURI: item.conceptKeywordsURI || null,
        notes: item.notes || '',
        language: item.language || '',
        educationLevel: item.educationLevel || [],
        licenseURI: item.licenseURI || null,
        statusStartDate: item.statusStartDate || null,
        statusEndDate: item.statusEndDate || null,
        subject: item.subject || [],
        subjectURI: item.subjectURI || [],
        extensions: item.extensions || null,
        CFDocumentURI: item.CFDocumentURI || null,
        children: [],
        sequenceNumber: 0,
      });
    });

    // Second pass: build parent-child relationships and store associations
    cfAssociations.forEach(assoc => {
      const originId = assoc.originNodeURI?.identifier || assoc.originNodeIdentifier;
      const destinationId = assoc.destinationNodeURI?.identifier || assoc.destinationNodeIdentifier;

      // Store association data on items
      if (items.has(originId)) {
        const originItem = items.get(originId);
        if (!originItem.associations) {
          originItem.associations = [];
        }
        originItem.associations.push({
          identifier: assoc.identifier,
          associationType: assoc.associationType,
          uri: assoc.uri,
          sequenceNumber: assoc.sequenceNumber,
          originNodeURI: assoc.originNodeURI,
          destinationNodeURI: assoc.destinationNodeURI,
          CFAssociationGroupingURI: assoc.CFAssociationGroupingURI,
          lastChangeDateTime: assoc.lastChangeDateTime,
          notes: assoc.notes,
          extensions: assoc.extensions,
          CFDocumentURI: assoc.CFDocumentURI
        });
      }

      if (items.has(destinationId)) {
        const destItem = items.get(destinationId);
        if (!destItem.associations) {
          destItem.associations = [];
        }
        destItem.associations.push({
          identifier: assoc.identifier,
          associationType: assoc.associationType,
          uri: assoc.uri,
          sequenceNumber: assoc.sequenceNumber,
          originNodeURI: assoc.originNodeURI,
          destinationNodeURI: assoc.destinationNodeURI,
          CFAssociationGroupingURI: assoc.CFAssociationGroupingURI,
          lastChangeDateTime: assoc.lastChangeDateTime,
          notes: assoc.notes,
          extensions: assoc.extensions,
          CFDocumentURI: assoc.CFDocumentURI
        });
      }

      // Handle parent-child relationships
      if (assoc.associationType === 'isChildOf') {
        if (items.has(originId) && items.has(destinationId)) {
          const child = items.get(originId);
          const parent = items.get(destinationId);

          child.sequenceNumber = assoc.sequenceNumber || 0;
          parent.children.push(child);
          children.set(originId, destinationId);
        }
      }
    });

    // Third pass: sort children by sequenceNumber
    items.forEach(item => {
      if (item.children && item.children.length > 0) {
        item.children.sort((a, b) => {
          const seqA = a.sequenceNumber || 0;
          const seqB = b.sequenceNumber || 0;

          if (seqA !== seqB) {
            return seqA - seqB;
          }

          const schemeA = a.hcs || '';
          const schemeB = b.hcs || '';
          if (schemeA !== schemeB) {
            return schemeA.localeCompare(schemeB);
          }

          const titleA = a.title || '';
          const titleB = b.title || '';
          return titleA.localeCompare(titleB);
        });
      }
    });

    // Get root items (those without parents)
    const rootItems = Array.from(items.values()).filter(item => !children.has(item.identifier));

    // Sort root items
    rootItems.sort((a, b) => {
      const seqA = a.sequenceNumber || 0;
      const seqB = b.sequenceNumber || 0;

      if (seqA !== seqB) {
        return seqA - seqB;
      }

      const schemeA = a.hcs || '';
      const schemeB = b.hcs || '';
      if (schemeA !== schemeB) {
        return schemeA.localeCompare(schemeB);
      }

      const titleA = a.title || '';
      const titleB = b.title || '';
      return titleA.localeCompare(titleB);
    });

    return rootItems;
  }

  function filterItemsRecursively(items, searchQuery, filters, selectedAssociationGroup = 'all') {
    const filtered = [];

    for (const item of items) {
      let matches = true;

      // Apply search filter
      if (searchQuery) {
        const query = searchQuery.toLowerCase();
        const title = item.title?.toLowerCase() || '';
        const abbreviatedTitle = item.abbreviatedTitle?.toLowerCase() || '';
        const hcs = item.hcs?.toLowerCase() || '';

        if (!title.includes(query) && !abbreviatedTitle.includes(query) && !hcs.includes(query)) {
          matches = false;
        }
      }

      // Apply item type filter
      if (matches && filters.itemType && item.itemType !== filters.itemType) {
        matches = false;
      }

      // Apply subject filter
      if (matches && filters.subject && item.subject !== filters.subject) {
        matches = false;
      }

      // Apply association status filter
      if (matches && filters.associationStatus) {
        const hasAssociations = item.associations && item.associations.length > 0;
        if (filters.associationStatus === 'has-associations' && !hasAssociations) {
          matches = false;
        }
        if (filters.associationStatus === 'no-associations' && hasAssociations) {
          matches = false;
        }
      }

      // Apply modified date filter
      if (matches && filters.modifiedSince && item.lastChanged) {
        const itemDate = new Date(item.lastChanged);
        const now = new Date();
        let cutoffDate;

        switch (filters.modifiedSince) {
          case 'today':
            cutoffDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            break;
          case 'week':
            cutoffDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            break;
          case 'month':
            cutoffDate = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
            break;
          case 'year':
            cutoffDate = new Date(now.getTime() - 365 * 24 * 60 * 60 * 1000);
            break;
        }

        if (itemDate < cutoffDate) {
          matches = false;
        }
      }

      // Apply association group filter
      if (matches && selectedAssociationGroup !== 'all') {
        // Check if item has associations in the selected group
        const hasAssociationInGroup = item.associations && item.associations.some(assoc =>
          assoc.groupId === selectedAssociationGroup
        );
        if (!hasAssociationInGroup) {
          matches = false;
        }
      }

      // If item matches, include it and recursively filter its children
      if (matches) {
        const filteredItem = { ...item };
        if (item.children && item.children.length > 0) {
          filteredItem.children = filterItemsRecursively(item.children, searchQuery, filters, selectedAssociationGroup);
        }
        filtered.push(filteredItem);
      } else if (item.children && item.children.length > 0) {
        // If item doesn't match but has children, check if any children match
        const filteredChildren = filterItemsRecursively(item.children, searchQuery, filters, selectedAssociationGroup);
        if (filteredChildren.length > 0) {
          const filteredItem = { ...item, children: filteredChildren };
          filtered.push(filteredItem);
        }
      }
    }

    return filtered;
  }

  function setSearchQuery(query) {
    searchQuery.value = query;
  }

  function setFilters(filters) {
    selectedFilters.value = { ...selectedFilters.value, ...filters };
  }

  function clearFilters() {
    selectedFilters.value = {
      itemType: '',
      subject: '',
      associationStatus: '',
      modifiedSince: ''
    };
  }

  function setSelectedAssociationGroup(groupId) {
    selectedAssociationGroup.value = groupId;
  }

  function selectDocument(document) {
    currentDocument.value = document;
  }

  function clearError() {
    error.value = null;
  }

  function setCurrentView(view) {
    currentView.value = view;
  }

  function updateItem(updatedItem) {
    if (!currentDocument.value || !updatedItem || !updatedItem.identifier) {
      console.warn('Cannot update item: missing document or item identifier');
      return false;
    }

    const updated = updateItemRecursively(currentDocument.value.items, updatedItem);
    if (updated) {
      console.log('Item updated successfully:', updatedItem.identifier);
    } else {
      console.warn('Item not found for update:', updatedItem.identifier);
    }
    return updated;
  }

  function updateItemRecursively(items, updatedItem) {
    if (!Array.isArray(items)) return false;

    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      if (item.identifier === updatedItem.identifier) {
        // Update the item properties, preserving structure
        Object.assign(item, updatedItem);
        // Ensure children and associations are preserved if not overwritten
        if (!updatedItem.children) item.children = item.children || [];
        if (!updatedItem.associations) item.associations = item.associations || [];
        return true;
      }
      if (item.children && updateItemRecursively(item.children, updatedItem)) {
        return true;
      }
    }
    return false;
  }

  function findItemByIdentifier(items, identifier) {
    if (!Array.isArray(items)) return null;
    for (const item of items) {
      if (item.identifier === identifier) return item;
      if (item.children) {
        const found = findItemByIdentifier(item.children, identifier);
        if (found) return found;
      }
    }
    return null;
  }

  function getMaxSequence(items) {
    if (!Array.isArray(items)) return 0;
    return Math.max(...items.map(item => item.sequenceNumber || 0), 0);
  }

  function addItem(newItem, parentIdentifier) {
    if (!currentDocument.value) {
      console.error('No current document to add item to');
      return false;
    }

    let targetArray = currentDocument.value.items;
    let sequenceNum = getMaxSequence(currentDocument.value.items) + 1;

    if (parentIdentifier) {
      const parent = findItemByIdentifier(currentDocument.value.items, parentIdentifier);
      if (!parent) {
        console.error('Parent item not found:', parentIdentifier);
        return false;
      }
      targetArray = parent.children;
      sequenceNum = getMaxSequence(parent.children) + 1;
    }

    newItem.sequenceNumber = sequenceNum;
    targetArray.push(newItem);

    // Sort the target array by sequenceNumber
    targetArray.sort((a, b) => (a.sequenceNumber || 0) - (b.sequenceNumber || 0));

    console.log('Item added successfully:', newItem.identifier);
    return true;
  }

  return {
    // State
    documents,
    currentDocument,
    loading,
    error,
    searchQuery,
    selectedFilters,
    selectedAssociationGroup,
    currentDocumentAssociationGroupings,
    currentDocumentDefinitions,
    currentDocumentRubrics,
    currentDocumentAssociations,
    currentView,

    // Getters
    filteredDocuments,
    filteredItems,
    availableSubjects,
    documentsGroupedByCreator,
    associationGroups,

    // Actions
    fetchDocuments,
    fetchDocument,
    setSearchQuery,
    setFilters,
    clearFilters,
    setSelectedAssociationGroup,
    setCurrentView,
    selectDocument,
    clearError,
    updateItem,
    addItem
  };
});
