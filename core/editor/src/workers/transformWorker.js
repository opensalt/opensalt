/**
 * Web Worker for CASE item transformation.
 * Moves the heavy tree-building computation off the main thread.
 *
 * Receives a serialized contextSnapshot that replaces all contextStore references:
 * - itemRegistry: { [identifier]: { item, frameworkId } }
 * - loadedPackages: [ [frameworkId, { CFItems, CFAssociations, CFDocument }], ... ]
 * - endpointResolutions: { [key]: { entityType, entity, frameworkId } }
 */

self.onmessage = function (e) {
  const { cfItems, cfAssociations, docId, contextSnapshot } = e.data;

  try {
    const result = transformCASEItems(cfItems, cfAssociations, docId, contextSnapshot);
    self.postMessage({ success: true, data: result });
  } catch (error) {
    self.postMessage({
      success: false,
      error: { message: error.message, stack: error.stack }
    });
  }
};

function transformCASEItems(cfItems, cfAssociations, docId, contextSnapshot) {
  const {
    itemRegistry = {},
    loadedPackages = [],
    endpointResolutions = {}
  } = contextSnapshot || {};

  function getRegistryItem(identifier) {
    return itemRegistry[identifier] || null;
  }

  function resolveEndpoint(key) {
    return endpointResolutions[key] || null;
  }

  const items = new Map();
  const inDocumentIds = new Set();
  const parentByChild = new Map();
  const itemsWithParentItem = new Set();

  function normalizeAssociationGroupId(assoc) {
    return assoc.CFAssociationGroupingURI?.identifier || assoc.CFAssociationGroupingURI?.uri || 'default';
  }

  function normalizeAssociationNodeLink(rawLink, fallbackIdentifier) {
    if (!rawLink && !fallbackIdentifier) return undefined;

    if (rawLink && typeof rawLink === 'object') {
      return {
        ...rawLink,
        identifier: rawLink.identifier || fallbackIdentifier || '',
        title: rawLink.title || '',
        uri: rawLink.uri || ''
      };
    }

    if (typeof rawLink === 'string') {
      return {
        identifier: fallbackIdentifier || rawLink,
        title: '',
        uri: rawLink
      };
    }

    return fallbackIdentifier
      ? {
          identifier: fallbackIdentifier,
          title: '',
          uri: ''
        }
      : undefined;
  }

  function getAssociationOriginId(assoc) {
    return assoc.originNodeURI?.identifier || assoc.originNodeIdentifier;
  }

  function getAssociationDestinationId(assoc) {
    return assoc.destinationNodeURI?.identifier || assoc.destinationNodeIdentifier;
  }

  function getAssociationOriginLink(assoc) {
    return normalizeAssociationNodeLink(assoc.originNodeURI, getAssociationOriginId(assoc));
  }

  function getAssociationDestinationLink(assoc) {
    return normalizeAssociationNodeLink(assoc.destinationNodeURI, getAssociationDestinationId(assoc));
  }

  function isUnresolvedCrossFrameworkPlaceholder(item) {
    if (!item?.isCrossFramework) return false;

    const hasFrameworkIdentity = !!(
      item.CFDocumentURI?.identifier ||
      item.CFDocumentURI?.uri ||
      item.documentId
    );
    if (hasFrameworkIdentity) return false;

    const displayValue =
      item.fullStatement ||
      item.abbreviatedStatement ||
      item.title ||
      '';

    return !displayValue || displayValue === 'Loading...';
  }

  function resolveRegisteredItem(identifier, link) {
    const registered = getRegistryItem(identifier);
    if (registered?.item && !isUnresolvedCrossFrameworkPlaceholder(registered.item)) {
      return {
        item: registered.item,
        frameworkId: registered.frameworkId
      };
    }

    const resolved = resolveEndpoint(link?.uri || identifier);
    if (resolved?.entityType === 'item' && !isUnresolvedCrossFrameworkPlaceholder(resolved.entity)) {
      return {
        item: resolved.entity,
        frameworkId: resolved.frameworkId
      };
    }

    return { item: null, frameworkId: null };
  }

  function resolveItemFrameworkId(item, fallbackFrameworkId) {
    if (fallbackFrameworkId === undefined) fallbackFrameworkId = null;
    if (!item) return fallbackFrameworkId;

    return (
      item.CFDocumentURI?.identifier ||
      resolveEndpoint(item.CFDocumentURI?.uri || '')?.frameworkId ||
      getRegistryItem(item.identifier)?.frameworkId ||
      resolveEndpoint(item.uri || item.identifier || '')?.frameworkId ||
      fallbackFrameworkId
    ) ?? null;
  }

  function applyAuthoritativeItemData(node, sourceItem, sourceFrameworkId) {
    if (sourceFrameworkId === undefined) sourceFrameworkId = null;
    if (!sourceItem) return;

    const authoritativeTitle =
      sourceItem.fullStatement?.trim() ||
      sourceItem.abbreviatedStatement?.trim() ||
      sourceItem.title?.trim() ||
      node.title ||
      node.identifier;
    const resolvedFrameworkId = resolveItemFrameworkId(sourceItem, sourceFrameworkId);

    node.id = sourceItem.id ?? node.id;
    node.identifier = sourceItem.identifier || node.identifier;
    node.uri = sourceItem.uri || node.uri || '';
    node.title = authoritativeTitle;
    node.fullStatement = sourceItem.fullStatement ?? node.fullStatement ?? authoritativeTitle;
    node.abbreviatedTitle = sourceItem.abbreviatedStatement ?? sourceItem.fullStatement ?? node.abbreviatedTitle ?? authoritativeTitle;
    node.abbreviatedStatement = sourceItem.abbreviatedStatement ?? node.abbreviatedStatement;
    node.alternativeLabel = sourceItem.alternativeLabel ?? node.alternativeLabel ?? '';
    node.humanCodingScheme = sourceItem.humanCodingScheme ?? node.humanCodingScheme;
    node.listEnumeration = sourceItem.listEnumeration ?? sourceItem.listEnumInSource ?? node.listEnumeration;
    node.lastChanged = sourceItem.lastChangeDateTime ?? node.lastChanged;
    node.lastChangeDateTime = sourceItem.lastChangeDateTime ?? node.lastChangeDateTime;
    node.itemType = sourceItem.CFItemType ?? sourceItem.itemType ?? node.itemType;
    node.CFItemTypeURI = sourceItem.CFItemTypeURI ?? node.CFItemTypeURI;
    node.conceptKeywords = sourceItem.conceptKeywords ?? node.conceptKeywords;
    node.conceptKeywordsURI = sourceItem.conceptKeywordsURI ?? node.conceptKeywordsURI;
    node.notes = sourceItem.notes ?? node.notes;
    node.language = sourceItem.language ?? node.language;
    node.educationLevel = sourceItem.educationLevel ?? node.educationLevel;
    node.licenseURI = sourceItem.licenseURI ?? node.licenseURI;
    node.statusStartDate = sourceItem.statusStartDate ?? node.statusStartDate;
    node.statusEndDate = sourceItem.statusEndDate ?? node.statusEndDate;
    node.subject = sourceItem.subject ?? node.subject;
    node.subjectURI = sourceItem.subjectURI ?? node.subjectURI;
    node.extensions = sourceItem.extensions ?? node.extensions;

    if (resolvedFrameworkId) {
      node.documentId = resolvedFrameworkId;
      node.isCrossFramework = resolvedFrameworkId !== docId;
      if (node.isCrossFramework) {
        node.crossFrameworkUri = sourceItem.uri || node.crossFrameworkUri || node.uri;
      } else {
        node.crossFrameworkUri = undefined;
      }
    }
  }

  function isGenericAssociationNodeTitle(title) {
    if (!title) return true;
    const normalized = title.trim().toLowerCase();
    return normalized === 'origin node' || normalized === 'destination node';
  }

  function getRegistryTitle(identifier) {
    const registered = resolveRegisteredItem(identifier);
    if (registered.item) {
      const item = registered.item;
      return item.fullStatement || item.abbreviatedStatement || item.title || item.humanCodingScheme || null;
    }

    const resolved = resolveEndpoint(identifier);
    if (!resolved) return null;

    if (resolved.entityType === 'item') {
      const item = resolved.entity;
      return item.fullStatement || item.abbreviatedStatement || item.title || item.humanCodingScheme || null;
    }

    if (resolved.entityType === 'document') {
      const doc = resolved.entity;
      return doc.title || null;
    }

    return null;
  }

  function getPlaceholderTitle(identifier, link) {
    const registryTitle = getRegistryTitle(identifier);
    if (registryTitle) return registryTitle;

    if (link?.title && !isGenericAssociationNodeTitle(link.title)) {
      return link.title;
    }

    return 'Loading...';
  }

  // First pass: create all items
  cfItems.forEach(item => {
    inDocumentIds.add(item.identifier);
    items.set(item.identifier, {
      id: 0,
      identifier: item.identifier,
      uri: item.uri || '',
      title: item.fullStatement || item.abbreviatedStatement || 'Untitled Item',
      fullStatement: item.fullStatement || '',
      abbreviatedTitle: item.abbreviatedStatement || item.fullStatement || 'Untitled Item',
      abbreviatedStatement: item.abbreviatedStatement || undefined,
      alternativeLabel: item.alternativeLabel || '',
      humanCodingScheme: item.humanCodingScheme || undefined,
      listEnumeration: item.listEnumeration || undefined,
      lastChanged: item.lastChangeDateTime || '',
      lastChangeDateTime: item.lastChangeDateTime || '',
      itemType: item.CFItemType || undefined,
      CFItemTypeURI: item.CFItemTypeURI || undefined,
      conceptKeywords: item.conceptKeywords || [],
      conceptKeywordsURI: item.conceptKeywordsURI || undefined,
      notes: item.notes || undefined,
      language: item.language || undefined,
      educationLevel: item.educationLevel || [],
      licenseURI: item.licenseURI || undefined,
      statusStartDate: item.statusStartDate || undefined,
      statusEndDate: item.statusEndDate || undefined,
      subject: item.subject || [],
      subjectURI: item.subjectURI || [],
      extensions: item.extensions || undefined,
      CFDocumentURI: undefined,
      documentId: docId || null,
      children: [],
      sequenceNumber: 0,
      groupIds: new Set(),
      associations: []
    });
  });

  function ensureEditorAssociation(item, assoc, groupId) {
    if (!item.associations) item.associations = [];
    if (!item.associations.some(existing => existing.identifier === assoc.identifier)) {
      const editorAssoc = {
        ...assoc,
        groupId: groupId
      };
      item.associations.push(editorAssoc);
    }
    if (!item.groupIds) item.groupIds = new Set();
    item.groupIds.add(groupId);
  }

  function getOrCreatePlaceholder(link, groupId, assoc, sourceItem, sourceFrameworkId) {
    if (sourceItem === undefined) sourceItem = null;
    if (sourceFrameworkId === undefined) sourceFrameworkId = null;
    const identifier = link?.identifier;
    if (!identifier) return null;
    const placeholderTitle = getPlaceholderTitle(identifier, link);
    const registered = resolveRegisteredItem(identifier, link);
    const authoritativeItem = sourceItem || registered.item;
    const authoritativeFrameworkId = sourceFrameworkId || registered.frameworkId;

    let node = items.get(identifier);
    if (!node) {
      node = {
        id: 0,
        identifier,
        uri: link?.uri || '',
        title: placeholderTitle,
        fullStatement: placeholderTitle,
        abbreviatedTitle: placeholderTitle,
        abbreviatedStatement: undefined,
        alternativeLabel: '',
        humanCodingScheme: undefined,
        listEnumeration: undefined,
        lastChanged: '',
        lastChangeDateTime: '',
        itemType: undefined,
        CFItemTypeURI: undefined,
        conceptKeywords: [],
        conceptKeywordsURI: undefined,
        notes: undefined,
        language: undefined,
        educationLevel: [],
        licenseURI: undefined,
        statusStartDate: undefined,
        statusEndDate: undefined,
        subject: [],
        subjectURI: [],
        extensions: undefined,
        CFDocumentURI: assoc.CFDocumentURI,
        documentId: null,
        children: [],
        sequenceNumber: 0,
        isCrossFramework: true,
        crossFrameworkUri: link?.uri,
        groupIds: new Set(),
        associations: []
      };
      items.set(identifier, node);
    }

    if (authoritativeItem) {
      applyAuthoritativeItemData(node, authoritativeItem, authoritativeFrameworkId);
    } else {
      if (!node.crossFrameworkUri && link?.uri) node.crossFrameworkUri = link.uri;
      const resolvedTitle = getPlaceholderTitle(identifier, link);
      if ((!node.title || node.title === 'Loading...' || isGenericAssociationNodeTitle(node.title)) && resolvedTitle !== 'Loading...') {
        node.title = resolvedTitle;
        node.fullStatement = resolvedTitle;
        node.abbreviatedTitle = resolvedTitle;
      }
    }

    if (!node.groupIds) node.groupIds = new Set();
    node.groupIds.add(groupId);
    return node;
  }

  function wouldCreateCycle(childId, parentId) {
    if (childId === parentId) return true;

    const visited = new Set();
    let currentParentId = parentId;

    while (currentParentId && !visited.has(currentParentId)) {
      if (currentParentId === childId) return true;
      visited.add(currentParentId);
      currentParentId = parentByChild.get(currentParentId);
    }

    return false;
  }

  function attachChildToParent(child, parentId, parent, assoc, groupId) {
    const existingParentId = parentByChild.get(child.identifier);
    if (existingParentId && existingParentId !== parentId) {
      return false;
    }

    if (wouldCreateCycle(child.identifier, parentId)) {
      return false;
    }

    child.sequenceNumber = assoc.sequenceNumber ?? child.sequenceNumber ?? 0;
    child.childOfAssocId = 0;
    ensureEditorAssociation(child, assoc, groupId);

    if (parent && parentId !== docId) {
      if (!parent.children.some(node => node.identifier === child.identifier)) {
        parent.children.push(child);
      }
      itemsWithParentItem.add(child.identifier);
    } else if (parentId === docId) {
      itemsWithParentItem.delete(child.identifier);
    }

    parentByChild.set(child.identifier, parentId);
    return true;
  }

  function markAnchoredBranch(item, anchoredIds, visited) {
    if (!visited) visited = new Set();
    if (visited.has(item.identifier) || anchoredIds.has(item.identifier)) return;

    visited.add(item.identifier);
    anchoredIds.add(item.identifier);
    item.children.forEach(child => markAnchoredBranch(child, anchoredIds, visited));
    visited.delete(item.identifier);
  }

  function buildAnchoredIdSet() {
    const anchoredIds = new Set();
    const roots = Array.from(items.values()).filter(item => !itemsWithParentItem.has(item.identifier));
    roots.forEach(root => markAnchoredBranch(root, anchoredIds));
    return anchoredIds;
  }

  function enrichAnchoredExternalBranches() {
    const externalPackages = loadedPackages
      .filter(function (entry) { return entry[0] !== docId; });

    if (externalPackages.length === 0) return;

    const anchoredIds = buildAnchoredIdSet();
    let progressed = true;

    while (progressed) {
      progressed = false;

      externalPackages.forEach(function (entry) {
        const frameworkId = entry[0];
        const pkg = entry[1];
        const packageItems = new Map((pkg.CFItems || []).map(item => [item.identifier, item]));

        (pkg.CFAssociations || []).forEach(function (assoc) {
          if (assoc.associationType !== 'isChildOf') return;

          const originId = getAssociationOriginId(assoc);
          const destinationId = getAssociationDestinationId(assoc);
          if (!originId || !destinationId) return;

          const destinationIsDoc = docId !== null && destinationId === docId;
          if (!destinationIsDoc && !anchoredIds.has(destinationId) && !inDocumentIds.has(destinationId)) {
            return;
          }

          const groupId = normalizeAssociationGroupId(assoc);
          const destinationLink = getAssociationDestinationLink(assoc);
          const originLink = getAssociationOriginLink(assoc);
          const destinationRegistry = resolveRegisteredItem(destinationId, destinationLink);
          const destinationSource =
            packageItems.get(destinationId) ||
            destinationRegistry.item;
          const destinationFrameworkId =
            resolveItemFrameworkId(destinationSource, frameworkId) ||
            destinationRegistry.frameworkId;

          let parent = null;
          if (!destinationIsDoc) {
            parent = items.get(destinationId) || getOrCreatePlaceholder(
              destinationLink,
              groupId,
              assoc,
              destinationSource,
              destinationFrameworkId
            );
            if (!parent) return;
            applyAuthoritativeItemData(parent, destinationSource, destinationFrameworkId);
          }

          const originRegistry = resolveRegisteredItem(originId, originLink);
          const originSource =
            packageItems.get(originId) ||
            originRegistry.item;
          const originFrameworkId =
            resolveItemFrameworkId(originSource, frameworkId) ||
            originRegistry.frameworkId;
          const child = items.get(originId) || getOrCreatePlaceholder(
            originLink,
            groupId,
            assoc,
            originSource,
            originFrameworkId
          );
          if (!child) return;

          applyAuthoritativeItemData(child, originSource, originFrameworkId);

          if (attachChildToParent(child, destinationId, parent, assoc, groupId) && !anchoredIds.has(originId)) {
            markAnchoredBranch(child, anchoredIds);
            progressed = true;
          }
        });
      });
    }
  }

  // Associate everything (including non-isChildOf) with local origin items for filtering.
  cfAssociations.forEach(function (assoc) {
    const originId = getAssociationOriginId(assoc);
    if (originId && items.has(originId)) {
      const item = items.get(originId);
      const groupId = normalizeAssociationGroupId(assoc);
      ensureEditorAssociation(item, assoc, groupId);
    }
  });

  var ChildEdge;

  var immediateEdges = [];
  var deferredEdges = [];

  cfAssociations.forEach(function (assoc) {
    if (assoc.associationType !== 'isChildOf') return;

    const originId = getAssociationOriginId(assoc);
    const destinationId = getAssociationDestinationId(assoc);
    if (!originId || !destinationId) return;
    const groupId = normalizeAssociationGroupId(assoc);

    const originInDoc = inDocumentIds.has(originId);
    const destInDoc = inDocumentIds.has(destinationId);
    const destinationIsDoc = docId !== null && destinationId === docId;

    if (originInDoc || destInDoc || destinationIsDoc) {
      immediateEdges.push({ assoc: assoc, originId: originId, destinationId: destinationId, groupId: groupId });
    } else {
      deferredEdges.push({ assoc: assoc, originId: originId, destinationId: destinationId, groupId: groupId });
    }
  });

  // Pass 1: process all edges that directly touch the viewed framework.
  immediateEdges.forEach(function (edge) {
    const { assoc, originId, destinationId, groupId } = edge;
    const originInDoc = inDocumentIds.has(originId);
    const destInDoc = inDocumentIds.has(destinationId);
    const destinationIsDoc = docId !== null && destinationId === docId;

    if (originInDoc && destInDoc) {
      const child = items.get(originId);
      const parent = items.get(destinationId);
      attachChildToParent(child, destinationId, parent, assoc, groupId);
    } else if (originInDoc && !destInDoc) {
      const child = items.get(originId);
      if (destinationIsDoc) {
        attachChildToParent(child, destinationId, null, assoc, groupId);
      } else {
        const parent = getOrCreatePlaceholder(getAssociationDestinationLink(assoc), groupId, assoc);
        if (parent) {
          attachChildToParent(child, destinationId, parent, assoc, groupId);
        }
      }
    } else if (!originInDoc && destInDoc) {
      const parent = items.get(destinationId);
      const child = getOrCreatePlaceholder(getAssociationOriginLink(assoc), groupId, assoc);
      if (child) {
        attachChildToParent(child, destinationId, parent, assoc, groupId);
      }
    } else if (!originInDoc && !destInDoc && destinationIsDoc) {
      const child = getOrCreatePlaceholder(getAssociationOriginLink(assoc), groupId, assoc);
      if (child) {
        attachChildToParent(child, destinationId, null, assoc, groupId);
      }
    }
  });

  // Pass 2: resolve deferred external->external edges only when destination is anchored.
  let unresolved = deferredEdges;
  let progressed = true;
  while (progressed && unresolved.length > 0) {
    progressed = false;
    const nextUnresolved = [];

    unresolved.forEach(function (edge) {
      const destinationIsAnchored =
        edge.destinationId === docId ||
        parentByChild.has(edge.destinationId) ||
        inDocumentIds.has(edge.destinationId);

      if (!destinationIsAnchored) {
        nextUnresolved.push(edge);
        return;
      }

      const parent = items.get(edge.destinationId) ||
        getOrCreatePlaceholder(getAssociationDestinationLink(edge.assoc), edge.groupId, edge.assoc);
      const child = getOrCreatePlaceholder(getAssociationOriginLink(edge.assoc), edge.groupId, edge.assoc);

      if (!parent || !child) {
        nextUnresolved.push(edge);
        return;
      }

      attachChildToParent(child, edge.destinationId, parent, edge.assoc, edge.groupId);
      progressed = true;
    });

    unresolved = nextUnresolved;
  }

  enrichAnchoredExternalBranches();

  function compareBySegment(a, b) {
    const segmentsA = a.split(/[^a-zA-Z0-9]+/).filter(function (s) { return s !== ''; });
    const segmentsB = b.split(/[^a-zA-Z0-9]+/).filter(function (s) { return s !== ''; });
    const maxLen = Math.max(segmentsA.length, segmentsB.length);

    for (let i = 0; i < maxLen; i++) {
      const segA = segmentsA[i] || '';
      const segB = segmentsB[i] || '';
      const isNumA = /^\d+$/.test(segA);
      const isNumB = /^\d+$/.test(segB);

      if (isNumA && isNumB) {
        const numA = parseInt(segA, 10);
        const numB = parseInt(segB, 10);
        if (numA !== numB) return numA < numB ? -1 : 1;
      } else {
        const cmp = segA.localeCompare(segB);
        if (cmp !== 0) return cmp;
      }
    }
    return 0;
  }

  function itemSortComparator(a, b) {
    // 1. sequenceNumber
    const seqA = a.sequenceNumber ?? 0;
    const seqB = b.sequenceNumber ?? 0;
    if (seqA !== seqB) return seqA - seqB;

    // 2. humanCodingScheme (by segments)
    const schemeA = a.humanCodingScheme || '';
    const schemeB = b.humanCodingScheme || '';
    const schemeCmp = compareBySegment(schemeA, schemeB);
    if (schemeCmp !== 0) return schemeCmp;

    // 3. listEnumeration
    const enumA = a.listEnumeration || '';
    const enumB = b.listEnumeration || '';
    if (enumA !== enumB) return enumA.localeCompare(enumB);

    // 4. abbreviatedStatement / abbreviatedTitle
    const abbrA = a.abbreviatedStatement || a.abbreviatedTitle || '';
    const abbrB = b.abbreviatedStatement || b.abbreviatedTitle || '';
    if (abbrA !== abbrB) return abbrA.localeCompare(abbrB);

    // 5. fullStatement
    const fullA = a.fullStatement || '';
    const fullB = b.fullStatement || '';
    return fullA.localeCompare(fullB);
  }

  // Third pass: sort children
  items.forEach(function (item) {
    if (item.children && item.children.length > 0) {
      item.children.sort(itemSortComparator);
    }
  });

  // Get root items: items NOT placed into another item's children array
  const rootItems = Array.from(items.values()).filter(function (item) {
    return !itemsWithParentItem.has(item.identifier);
  });

  // Sort root items
  rootItems.sort(itemSortComparator);

  return rootItems;
}
