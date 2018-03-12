/* global apx */
window.apx = window.apx||{};

/* global empty */
/* global op */

/* global render */
var render = require('render-md');

/////////////////////////////////////////////////////////////////////////////
apx.allDocs = {};
apx.allItemsHash = {};

/**
 * Class for representing/manipulating/using a document
 *
 * @class
 */
function ApxDocument(initializer) {
    var self = this;

    // record the initializer for use when the document is loaded
    self.initializer = initializer;

    // keep track of the current association group chosen for the document, which could be different on sides 1 (left) and 2 (right)
    self.currentAssocGroup = null;
    self.currentAssocGroup2 = null;
    self.setCurrentAssocGroup = function(assocGroup, side) {
        let $agm;
        if (empty(side) || side === 1) {
            self.currentAssocGroup = assocGroup;
            $agm = self.$assocGroupMenu;
        } else {
            self.currentAssocGroup2 = assocGroup;
            $agm = self.$assocGroupMenu2;
        }

        // set the assoc group menu properly
        if (!empty($agm)) {
            if (assocGroup == null) {
                $agm.val("default");
            } else {
                $agm.val(assocGroup);
            }
        }
    };

    /** keep track of the currently-selected item for the document (which could be the doc itself)
     *  Input can be an item, an identifier, or an lsItemId; in the latter case, null represents the document */
    self.currentItem = null;
    self.setCurrentItem = function(o) {
        self.currentItem = null;

        if (!empty(o.item)) {
            self.currentItem = o.item;
        } else if (!empty(o.identifier)) {
            if (o.identifier === self.doc.identifier) {
                self.currentItem = self.doc;
            } else {
                self.currentItem = self.itemHash[o.identifier];
            }
        } else if (!empty(o.lsItemId)) {
            self.currentItem = self.itemIdHash[o.lsItemId];
        } else {
            self.currentItem = self.doc;
        }

        // to check to see if the document is currently selected, use
        // (d.currentItem == d.doc)
    };

    /** Load the document via an ajax call */
    self.load = function(callbackFn) {
        let path = apx.path.doctree_retrieve_document.replace('ID', apx.lsDocId);
        let ajaxData = {};
        if (!empty(self.initializer.id)) {
            ajaxData.id = self.initializer.id;
        } else if (!empty(self.initializer.identifier)) {
            ajaxData.identifier = self.initializer.identifier;
        } else if (!empty(self.initializer.url)) {
            ajaxData.url = self.initializer.url;
        }

        console.log("loading document", self.initializer);

        $.ajaxq('docFetchQ', {
            url: path,
            method: 'GET',
            data: ajaxData
        }).done(function(data, textStatus, jqXHR) {
            if (empty(data) || typeof(data) !== "object" || empty(data.CFDocument)) {
                self.loadError(data);
                return;
            }

            // extract the pieces of the returned document

            // CFDocument comes in standard CASE format
            self.doc = data.CFDocument;

            // store this ApxDocument in apx.allDocs
            apx.allDocs[self.doc.identifier] = self;

            // some SALT-specific top-level things...

            // document id
            self.doc.id = data.lsDocId;

            // "baseDoc" -- normally "self", but if this is a "pure cross-walk" document, this will be a different document identifier
            self.baseDoc = data.baseDoc;

            // documents referenced by associations in this document
            self.associatedDocs = data.associatedDocs;
            if (empty(self.associatedDocs)) {
                self.associatedDocs = {};
            }

            // note that doc is a document
            self.doc.nodeType = "document";

            // extract uriBase from doc.uri
            //self.doc.uriBase = self.doc.uri.replace(/\/[^\/]+$/, "/");
            //self.doc.uriBase = self.doc.uri.replace(/CFDocuments\/$/, "CFItems/");
            // @todo: this assumes an OpenSALT instance on the other end that can use /uri/... URLs
            self.doc.uriBase = self.doc.uri.replace(/(https?:\/\/[^\/]+).*/, '$1/uri/');

            // add an assocs array for the document
            self.doc.assocs = [];

            self.items = op(data, "CFItems");
            if (empty(self.items)) {
                self.items = [];
            }

            self.assocs = op(data, "CFAssociations");
            if (empty(self.assocs)) {
                self.assocs = [];
            }

            self.itemTypes = op(data, "CFDefinitions", "CFItemTypes");
            self.subjects = op(data, "CFDefinitions", "CFSubjects");
            self.concepts = op(data, "CFDefinitions", "CFConcepts");
            self.licenses = op(data, "CFDefinitions", "CFLicenses");
            self.assocGroups = op(data, "CFDefinitions", "CFAssociationGroupings");
            // create hashes for assocGroups
            self.assocGroupHash = {};
            self.assocGroupIdHash = {};
            if (empty(self.assocGroups)) {
                self.assocGroups = [];
            } else {
                for (let i = 0; i < self.assocGroups.length; ++i) {
                    self.assocGroupHash[self.assocGroups[i].identifier] = self.assocGroups[i];
                    self.assocGroupIdHash[self.assocGroups[i].id] = self.assocGroups[i];
                }
            }

            // if we didn't get a "condensed" file (via the doctree's export function)...
            if (data.condensed !== true) {
                // then convert package data for items and assocs
                self.convertPackageData();
            }

            // process items
            // create hashes so we can reference items via their id's or identifiers
            self.itemHash = {};
            self.itemIdHash = {};
            let originalItems = self.items;
            self.items = [];
            for (let i = 0; i < originalItems.length; ++i) {
                self.addItem(originalItems[i]);
            }

            // add the document to the itemHash; this makes some other things more convenient
            self.itemHash[self.doc.identifier] = self.doc;

            // process associations
            // create hashes for assocs too, and tie assocs to their items
            self.assocHash = {};
            self.assocIdHash = {};
            let originalAssocLength = self.assocs.length;   // we will likely add new associations below, so length will increase
            for (let i = 0; i < originalAssocLength; ++i) {
                let a = self.assocs[i];
                self.assocHash[a.identifier] = a;
                if (!empty(a.id)) {
                    self.assocIdHash[a.id] = a;
                }

                // tie assoc to item
                if (!empty(a.origin.item)) {
                    // if we already know about this item, add it to the item's assocs array
                    if (!empty(self.itemHash[a.origin.item])) {
                        self.itemHash[a.origin.item].assocs.push(a);

                    // else...
                    } else {
                        // create an "assocsOnly" item to hold the assoc, so we can easily look it up later
                        self.itemHash[a.origin.item] = {
                            "assocsOnly": true,
                            assocs: []
                        };
                        self.itemHash[a.origin.item].assocs.push(a);
                    }
                }
                // note that this assoc is "owned" by this doc
                a.assocDoc = self.doc.identifier;

                // Construct inverse (e.g. "isParentOf") associations
                self.addInverseAssociation(a);
            }

            // when doc first loads, doc is selected
            self.setCurrentItem({"identifier": self.doc.identifier});

            // if we initialized by url, add to the document menus
            if (!empty(self.initializer.url)) {
                apx.addDocToMenus(self.doc.identifier, self.initializer.url, self.doc.title);
            }

            // if this isn't mainDoc,
            if (self !== apx.mainDoc) {
                // check to see if this doc is a reference (origin or dest) for any of mainDoc's
                self.updateMainDocAssocs();
            }

            if (!empty(callbackFn)) {
                callbackFn();
            }

            // if we're showing the association view, refresh it now, in case we just finished loading a document referred to in an association
            if (apx.viewMode.currentView === "assoc") {
                apx.viewMode.showAssocView("refresh");
            }

        }).fail(function(jqXHR, textStatus, errorThrown){
            self.loadError();
        });
    };

    self.loadError = function(data) {
        console.log("error loading document", self.initializer);
        if (!empty(data)) {
            console.log("data returned:", data);
        }

        // if the document was initialized via an identifier, note that we got an error loading the doc
        // TODO: we need to be able to keep track of documents opened via urls here too...
        if (!empty(self.initializer.identifier)) {
            apx.allDocs[self.initializer.identifier] = "loaderror";
        }

        apx.spinner.hideModal();
    };

    /** Convert a original package file from the standard CASE format into the condensed format we work with in the OpenSALT code */
    self.convertPackageData = function() {
        function changeKey(item, oldKey, newKey) {
            if (oldKey in item) {
                item[newKey] = item[oldKey];
                delete item[oldKey];
            }
        }

        // TODO: we may need to do some converting for assocGroups
        // if we have any assocGroups where we don't have an id, create an arbitrary id to use in the client
        // use negative id's (?)
        let lastNewId = 0;
        for (let i = 0; i < self.assocGroups.length; ++i) {
            let ag = self.assocGroups[i];
            if (empty(ag.id)) {
                --lastNewId;
                ag.id = lastNewId;
                self.assocGroupIdHash[ag.id] = ag;
            }
        }

        // items: some field names are abbreviated
        for (let i = 0; i < self.items.length; ++i) {
            let item = self.items[i];
            changeKey(item, "fullStatement", "fstmt");
            changeKey(item, "abbreviatedStatement", "astmt");
            changeKey(item, "humanCodingScheme", "hcs");
            changeKey(item, "listEnumInSource", "le");
            changeKey(item, "conceptKeywords", "ck");
            changeKey(item, "conceptKeywordsURI", "cku");
            changeKey(item, "notes");
            changeKey(item, "language", "lang");
            changeKey(item, "educationalAlignment", "el");
            changeKey(item, "itemType", "itp");
            changeKey(item, "lastChangeDateTime", "mod");
            // things that remain the same: identifier, notes
            // the condensed format will also include an id field
        }

        // CFAssociations: define a function for converting origin and dest data
        function assocTarget(assoc, oldKey, newKey) {
            // we don't know the document
            assoc[newKey] = {"doc": "?"};

            // put the "identifier" in "item"
            if (!empty(assoc[oldKey].identifier)) {
                assoc[newKey].item = assoc[oldKey].identifier;
            }

            // add the title if we got it (we should)
            if (!empty(assoc[oldKey].title)) {
                assoc[newKey].title = assoc[oldKey].title;
            }

            delete assoc[oldKey];
        }

        // then go through each association
        for (let i = 0; i < self.assocs.length; ++i) {
            let assoc = self.assocs[i];

            // if we get a CFAssociationGroupingURI, that should have a "title" and "identifier" for the group
            if (!empty(assoc.CFAssociationGroupingURI)) {
                let ag;
                let ago = assoc.CFAssociationGroupingURI;
                // if we at least have an identifier...
                if (!empty(ago) && typeof(ago) === "object" && !empty(ago.identifier)) {
                    ag = self.assocGroupHash[ago.identifier];

                    // if the group didn't already exist, create it now
                    if (empty(ag)) {
                        ag = {
                            "identifier": ago.identifier,
                            "id": ago.id,
                            "title": ago.title
                        };

                        // get a new id if necessary
                        if (empty(ag.id)) {
                            --lastNewId;
                            ag.id = lastNewId;
                        }

                        // generate a title if necessary
                        if (empty(ag.title)) {
                            ag.title = "Association Group " + (ag.id*1);
                        }

                        self.assocGroups.push(ag);
                        self.assocGroupHash[ag.identifier] = ag;
                        self.assocGroupIdHash[ag.id] = ag;
                    }

                    // now record the id as assoc.groupId
                    assoc.groupId = ag.id;
                }

                delete assoc.CFAssociationGroupingURI;
            }
            changeKey(assoc, "associationType", "type");
            changeKey(assoc, "sequenceNumber", "seq");
            changeKey(assoc, "lastChangeDateTime", "mod");
            assocTarget(assoc, "originNodeURI", "origin");
            assocTarget(assoc, "destinationNodeURI", "dest");

            // things that remain the same: identifier
            // the condensed format will also include an id field
            // TODO: it would be good to put the sequenceNumber field in OpenSALT exports
        }
    };

    /** Find, and try to load, any docs referenced by associations in this doc */
    self.findAssociatedDocs = function() {
        for (let i = 0; i < self.assocs.length; ++i) {
            let a = self.assocs[i];
            if (a.origin.doc !== "-" && a.origin.doc !== "?" && !(a.origin.doc in apx.allDocs)) {
                apx.allDocs[a.origin.doc] = "loading";
                new ApxDocument({"identifier": a.origin.doc}).load();
            }
            if (a.dest.doc !== a.origin.doc && a.dest.doc !== "-" && a.dest.doc !== "?" && !(a.dest.doc in apx.allDocs)) {
                apx.allDocs[a.dest.doc] = "loading";
                new ApxDocument({"identifier": a.dest.doc}).load();
            }
        }
    };

    /** If the mainDoc has any associations that reference items in this doc, update the assoc items */
    self.updateMainDocAssocs = function() {
        for (let i = 0; i < apx.mainDoc.assocs.length; ++i) {
            let a = apx.mainDoc.assocs[i];
            if (a.origin.doc === "?" && !empty(self.itemHash[a.origin.item])) {
                a.origin.doc = self.doc.identifier;
            }
            if (a.type !== "exemplar" && a.dest.doc === "?" && !empty(self.itemHash[a.dest.item])) {
                a.dest.doc = self.doc.identifier;
            }
        }
    };

    /** Create a fancytree data structure for the given assocGroup **/
    self.createTree = function(assocGroup, treeSide) {
        // Go through all items
        for (let i = 0; i < self.items.length; ++i) {
            // for treeSide1, make sure previously-saved ftNodeDatas are cleared from all items
            if (treeSide === 1) {
                self.items[i].ftNodeData = null;
            }
        }

        // start with the document
        let t = {
            "title": self.doc.title,
            "key": self.doc.identifier,
            "children": [],
            "active": true,
            "folder": true,
            "expanded": true,
            "checkbox": false,   // tree should not have a checkbox
            "unselectable": true,   // tree should not be selectable
            "ref": self.doc
        };

        /** Function to get the appropriate title for a tree item */
        function treeItemTitle(item) {
            // start with the standard title for the item
            let title = self.getItemTitle(item);

            // if we're in chooser mode...
            if (apx.query.mode === "chooser") {
                // don't include the link indicator

            } else {
                // if the item has an association other than isChildOf *in apx.mainDoc*, show an indicator to that effect
                let associationDisplay = "none";
                let ci2 = apx.mainDoc.itemHash[item.identifier];
                if (!empty(ci2)) {
                    for (let j = 0; j < ci2.assocs.length; ++j) {
                        let a = ci2.assocs[j];
                        if (a.type !== "isChildOf") {
                            associationDisplay = "block";
                            break;
                        }
                    }
                }

                // if we don't show it now, it's there in case we add an association later
                title = '<div class="treeHasAssociation" style="display:' + associationDisplay + '"><img src="/assets/img/association-icon.png" title="This item is the origin for one or more associations."></div>' + title;
            }

            return title;
        }

        /** recursive function to find isChildOf associations of a parent */
        function findChildren(parent) {
            // go through all associations
            for (let i = 0; i < self.assocs.length; ++i) {
                let a = self.assocs[i];
                // note that a.origin.item and a.dest.item will be identifiers (guids)

                // if we find a child of the parent that matches the assocGroup
                if ("isChildOf" === a.type
                    && true !== a.inverse
                    && a.dest.item === parent.key
                    && a.groupId == assocGroup // if the association is in the "default group", a.groupId will be undefined; we want to use == so that it matches null
                ) {
                    let child = {
                        "title": a.origin.item,
                        "key": a.origin.item,
                        "children": [],
                        "seq": a.seq,
                        "childOfAssocId": a.id,     // stash the assocId for use elsewhere
                        // we really shouldn't need to set a default ref like this, but just in case...
                        "ref": {
                            "nodeType": "item",
                            "item": "unknown",
                            "doc": self
                        }
                    };

                    // look for the child in itemHash (we should always find it)
                    let childItem = self.itemHash[a.origin.item];
                    if (!empty(childItem)) {
                        // set the title
                        child.title = treeItemTitle(childItem);

                        // and add the item's reference
                        child.ref = childItem;

                        // then link the ft node to the childItem if we're rendering the left side
                        if (treeSide === 1) {
                            childItem.ftNodeData = child;
                        }
                    }

                    // push child onto children array
                    parent.children.push(child);

                    // recurse to find this child's children
                    findChildren(child);

                    // if the child has children...
                    if (child.children.length > 0) {
                        // make the child node a folder in fancytree
                        child.folder = true;

                        // expand the folder if it's the currentItem, or if it was expanded previously
                        child.expanded = (childItem === self.currentItem || op(self.expandedFolders[treeSide], assocGroup, child.key) === true);
                    }
                }
            }

            // sort children of parent
            parent.children.sort(function(a,b) {
                // try to sort by a.seq
                let seqA = a.seq * 1;
                let seqB = b.seq * 1;
                if (isNaN(seqA)) seqA = 100000;
                if (isNaN(seqB)) seqB = 100000;
                // if seqA != seqB, sort by seq
                if (seqA !== seqB) {
                    return seqA - seqB;
                }

                // else try to sort by the item's listEnumeration field
                let leA = 100000;
                let leB = 100000;
                if (!empty(a.ref) && !empty(a.ref.le)) {
                    leA = a.ref.le*1;
                }
                if (!empty(b.ref) && !empty(b.ref.le)) {
                    leB = b.ref.le*1;
                }

                if (isNaN(leA)) leA = 100000;
                if (isNaN(leB)) leB = 100000;

                if (leA !== leB) {
                    return leA - leB;
                }

                // else try to sort by the item's human coding scheme

                let hcsA = op(a, "ref", "hcs");
                let hcsB = op(b, "ref", "hcs");

                if (empty(hcsA) && empty(hcsB)) return 0;
                if (empty(hcsB)) return -1;
                if (empty(hcsA)) return 1;

                let lang = (document.documentElement.lang !== "") ? document.documentElement.lang : undefined;

                return hcsA.localeCompare(hcsB, lang, { numeric: true, sensitivity: 'base' });
            });
        }

        // find the document's children (and its children's children, and so on)
        findChildren(t);

        // If we're showing the default association group look for any orphaned items
        if (empty(assocGroup)) {
            // flag all items as "orphaned"; we'll clear these flags below
            for (let i = 0; i < self.items.length; ++i) {
                self.items[i].orphaned = true;
            }

            // now go through all associations and clear orphaned flag for non-orphaned items
            for (let i = 0; i < self.assocs.length; ++i) {
                let a = self.assocs[i];
                // if this is an isChildOf...
                if (a.type === "isChildOf" && a.inverse !== true) {
                    // if the origin (child) item exists...
                    let childItem = self.itemHash[a.origin.item];
                    if (!empty(childItem)) {
                        // then if the parent (destination) item exists...
                        let parentItem = self.itemHash[a.dest.item];
                        if (!empty(parentItem)) {
                            // then the child isn't an orphan!
                            delete childItem.orphaned;
                        }
                    }
                }
            }

            // go back through the items and find the orphans
            let orphans = [];
            for (let i = 0; i < self.items.length; ++i) {
                let item = self.items[i];
                if (item.orphaned === true) {
                    orphans.push(item);
                }
            }

            // if we found any, push them onto the tree
            if (orphans.length > 0) {
                let orphanParent = {
                    "title": "*Orphaned Items*",
                    "key": "orphans",
                    "children": [],
                    "folder": true,
                    "seq": 100000,
                    "ref": {
                        "nodeType": "item",
                        "item": "orphanParent",
                        "title": "*Orphaned Items*",
                        "doc": self
                    }
                };
                for (let i = 0; i < orphans.length; ++i) {
                    let orphan = orphans[i];
                    let child = {
                        "title": treeItemTitle(orphan),
                        "key": orphan.identifier,
                        "children": [],
                        "seq": i,
                        "ref": orphan
                    };
                    // then link the ft node to the childItem if we're rendering the left side
                    if (treeSide === 1) {
                        orphan.ftNodeData = child;
                    }
                    orphanParent.children.push(child)
                }
                t.children.push(orphanParent);
            }
        }

        // for fancytree we need an array with the single document item
        return [t];
    };

    /** Record folders currently expanded in this document's tree, for each side and each assocGroup */
    self.expandedFolders = {1: {}, 2: {}};
    self.recordExpandedFolders = function(side) {
        let efo;
        if (side === 1) {
            efo = self.expandedFolders[1][self.currentAssocGroup] = {};
        } else {
            efo = self.expandedFolders[2][self.currentAssocGroup2] = {};
        }

        function findExpandedFolders(n) {
            if (!empty(n.expanded) && n.expanded === true) {
                efo[n.key] = true;
            }
            if (!empty(n.children)) {
                for (let i = 0; i < n.children.length; ++i) {
                    findExpandedFolders(n.children[i]);
                }
            }
        }
        findExpandedFolders(self.getFt(side).getNodeByKey(self.doc.identifier));
    };

    /** Determine if this document was loaded from a url (as opposed to loaded via an id) */
    self.loadedFromUrl = function() {
        return !empty(self.initializer.url);
    };

    /** Determine if this is an "external" doc -- loaded from a different server */
    self.isExternalDoc = function() {
        // it's an external doc if it's in the apx.mainDoc.associatedDocs array and its url doesn't start with "local"
        if (!empty(apx.mainDoc.associatedDocs) && !empty(apx.mainDoc.associatedDocs[self.doc.identifier]) && apx.mainDoc.associatedDocs[self.doc.identifier].url.search(/local/) != 0) {
            return true;
        } else {
            return false;
        }
    };

    // Not sure if we need this; plan is to always use the identifier (guid) as the key...
    self.getDocKey = function() {
        if (empty(self.doc)) return null;
        // use the document guid (identifier) as the key for the document
        return self.doc.identifier;
    };

    /** Retrieve an array of associations for the item, optionally checking only associations of type assocType and/or only group assocGroup */
    self.getAssocsForItem = function(item, assocType, assocGroup, inverse) {
        // if the assoc isn't inversed, a.inverse will === undefined; so if we got a "inverse" parameter of false or null, change it to undefined
        if (inverse === false || inverse === null) {
            inverse = undefined;
        }

        let assocs = [];
        for (let i = 0; i < item.assocs.length; ++i) {
            let a = item.assocs[i];
            if ((empty(assocType) || assocType == a.type) && inverse === a.inverse) {
                if (typeof(assocGroup) === "undefined" || assocGroup == a.groupId) {    // use == so null matches undefined
                    assocs.push(a);
                }
            }
        }
        return assocs;
    };

    /** Retrieve the association groups for the item, optionally checking only associations of type assocType */
    self.getAssocGroupsForItem = function(item, assocType) {
        let assocGroups = [];
        for (let i = 0; i < item.assocs.length; ++i) {
            let a = item.assocs[i];
            let groupId;
            if (empty(a.groupId)) {
                groupId = null;
            } else {
                groupId = a.groupId;
            }
            // add to assocGroups if groupId isn't already there
            if ($.inArray(groupId, assocGroups) === -1) {
                assocGroups.push(groupId);
            }
        }
        return assocGroups;
    };

    /** get the fancyTree object for this document on the given side */
    self.getFt = function(side) {
        return self["ft" + side].fancytree("getTree");
    };

    /** get the fancyTree node for an item on the given side */
    self.getFtNode = function(item, side) {
        return self.getFt(side).getNodeByKey(item.identifier);
    };

    self.getItemUri = function(item) {
        if (empty(self.doc.uriBase) || empty(item) || empty(item.identifier)) {
            return "?";
        } else {
            return self.doc.uriBase + item.identifier;
        }
    };

    self.getItemTitleBlock = function(item, requireFullStatement) {
        let title = self.getItemStatement(item, requireFullStatement);

        title = render.block(title);

        if (item !== self.doc) {
            // add humanCodingScheme to the start if we have one
            if (!empty(item.hcs)) {
                title = '<span class="item-humanCodingScheme">' + render.escaped(item.hcs) + '</span> ' + title;
            }
        }

        return title;
    };

    self.getItemTitle = function(item, requireFullStatement) {
        let title = self.getItemStatement(item, requireFullStatement);

        title = render.inline(title);

        if (item !== self.doc) {
            // add humanCodingScheme to the start if we have one
            if (!empty(item.hcs)) {
                title = '<span class="item-humanCodingScheme">' + render.escaped(item.hcs) + '</span> ' + title;
            }
        }

        return title;
    };

    self.getItemStatement = function(item, requireFullStatement) {
        let title = '';

        if (item === self.doc && !empty(item.title)) {
            // for the document, use title
            title = item.title;
        } else if (!empty(item.fstmt)) {
            // else it's an item
            // by default we'll use the fullStatement, which is a required field for CF items
            title = item.fstmt;

            // use abbreviatedStatement if we have one and requireFullStatement isn't true
            if (!empty(item.astmt) && requireFullStatement !== true) {
                title = item.astmt;
            }
        } else if (!empty(item.item) && 'orphanParent' === item.item) {
            // else it's an orphan
            title = '*Orphaned Items*';
        }

        return title;
    };

    self.getAssociationTypePretty = function(a) {
        let s = a.type[0].toUpperCase() + a.type.substr(1).replace(/([A-Z])/g, " $1");
        if (a.inverse === true) {
            // look for inverse assoc type
            for (let i = 0; i < apx.assocTypes.length; ++i) {
                if (apx.assocTypes[i] === s) {
                    s = apx.inverseAssocTypes[i];
                    // put an " (R)" after "Is Peer Of" to note that it's a reverse-is-peer-of association
                    if (s === "Is Peer Of") {
                        s += " (R)";
                    }
                    return s;
                }
            }
            s += " (REVERSE)";
        }
        return s;
    };

    self.getAssociationTypeCondensed = function(a) {
        return a.type[0].toLowerCase() + a.type.substr(1).replace(/ /g, "");    // convert type to camel case
    };

    /** render the association group menu for this document */
    self.$assocGroupMenu = null;
    self.$assocGroupMenu2 = null;
    self.renderAssocGroupMenu = function($menu, side) {
        if (empty(side)) {
            side = 1;
        }

        // record the menu for this document/side, so that we can set the menu properly in setCurrentAssocGroup
        if (side === 1) {
            self.$assocGroupMenu = $menu;
        } else {
            self.$assocGroupMenu2 = $menu;
        }

        // clear options out of the menu
        $menu.html('');

        // if we have any groups, build and show the menu
        if (self.assocGroups.length > 0) {
            // default group
            $menu.append('<option value="default">– Default Group –</option>');

            // other groups
            for (let i = 0; i < self.assocGroups.length; ++i) {
                let title = self.assocGroups[i].title;
                if (60 < title.length) {
                    title = title.substr(0, 58) + "\u2026";
                }
                $menu.append('<option value="' + self.assocGroups[i].id + '">' + render.escaped(title) + '</option>');
            }

            // show the menu
            $menu.closest(".assocGroupFilter").show();

            // and select the current group
            if (side === 1) {
                if (self.currentAssocGroup == null) {
                    $menu.val("default");
                } else {
                    $menu.val(self.currentAssocGroup + "");
                }
            } else {
                if (self.currentAssocGroup2 == null) {
                    $menu.val("default");
                } else {
                    $menu.val(self.currentAssocGroup2 + "");
                }
            }

        // otherwise hide the menu
        } else {
            $menu.closest(".assocGroupFilter").hide();
        }
    };

    /** An assocGroup was selected from the document's menu, on side 1 or 2 */
    self.assocGroupSelected = function(menu, side) {
        // get the menu val; convert "default" to null; setCurrentAssocGroup
        let val = $(menu).val();
        if (val === "default") {
            self.setCurrentAssocGroup(null, side);
        } else {
            self.setCurrentAssocGroup(val*1, side);
        }

        // render the fancytree on the appropriate side
        self["ftRender" + side]();

        // if this is the left side...
        if (side === 1) {
            // select the document
            apx.treeDoc1.setCurrentItem({"item": self.doc});

            // activate the item
            apx.treeDoc1.activateCurrentItem();

            // we also have to call showCurrentItem, because if the current item is the document it's already active
            apx.treeDoc1.showCurrentItem();

            // push history state
            apx.pushHistoryState();
        }
    };

    // UTILITIES FOR FANCYTREE ELEMENTS (LEFT OR RIGHT SIDE)
    // to get an item from a node (getItemFromNode), just use node.data.ref;

    self.isDocNode = function(node) {
        return (op(node, "data", "ref", "nodeType") === "document");
    };

    // Get tooltip content
    self.tooltipContent = function(node) {
        let content;
        if (self.isDocNode(node)) {
            content = "Document: " + render.block(node.title);
        } else {
            if (empty(node.data.ref)) {
                content = "Item: " + render.block(node.title);    // this shouldn't happen
            } else {
                content = self.getItemTitleBlock(node.data.ref, true);
            }
        }

        return content;
    };

    self.addAssociation = function(atts) {
        let assoc = {
            "id": atts.id,
            "type": atts.type,
            "inverse": atts.inverse
        };

        if (empty(atts.assocDoc)) {
            atts.assocDoc = self.doc.identifier;
        }
        assoc.assocDoc = atts.assocDoc;

        if (!empty(atts.identifier)) {
            assoc.identifier = atts.identifier; // this isn't really needed
        }

        if (!empty(atts.seq)) {
            assoc.seq = atts.seq;
        }

        if (!empty(atts.groupId)) {
            // note that if groupId is null (meaning default group), we don't store it; this is on purpose
            assoc.groupId = atts.groupId;
        }

        // if we got origin and/or dest already pre-formatted, just store them
        if (!empty(atts.origin)) {
            assoc.origin = atts.origin;
        }
        if (!empty(atts.dest)) {
            assoc.dest = atts.dest;
        }

        // otherwise we should have got an originItem and/or destItem
        if (!empty(atts.originItem)) {
            assoc.origin = {
                "doc": self.doc.identifier,
                "item": atts.originItem.identifier,
                "uri": atts.originItem.identifier
            };
        }
        if (!empty(atts.destItem)) {
            if (atts.destItem === self.doc) {
                assoc.dest = {
                    "doc": self.doc.identifier,
                    "item": self.doc.identifier,
                    "uri": self.doc.identifier
                };
            } else {
                assoc.dest = {
                    "doc": self.doc.identifier,
                    "item": atts.destItem.identifier,
                    "uri": atts.destItem.identifier
                };
            }
        }

        // push onto assocs array
        self.assocs.push(assoc);

        // add to assocIdHash
        self.assocIdHash[assoc.id] = assoc;

        // add to assocHash if we got an identifier
        if (!empty(assoc.identifier)) {
            self.assocHash[assoc.identifier] = assoc;
        }

        // add to the origin's item record in this document, creating an "assocsOnly" record if necessary
        if (empty(self.itemHash[assoc.origin.item])) {
            self.itemHash[assoc.origin.item] = {
                "assocsOnly": true,
                assocs: []
            }
        }
        self.itemHash[assoc.origin.item].assocs.push(assoc);

        return assoc;
    };

    self.addInverseAssociation = function(a) {
        if (a.type !== "exemplar" && !empty(a.dest.item)) {
            let destItem = apx.allItemsHash[a.dest.item];
            if (!empty(destItem) && !empty(destItem.doc)) {
                destItem.doc.addAssociation({
                    "inverse": true,
                    "id": a.id + "-R",
                    "identifier": a.identifier + "-R",
                    "groupId": a.groupId,
                    "assocDoc": self.doc.identifier,
                    "type": a.type,
                    "origin": a.dest,   // switch origin and dest
                    "dest": a.origin    // switch origin and dest
                });
            }
        }
    };

    self.deleteAssociation = function(assocId) {
        let assoc = self.assocIdHash[assocId];
        // if the assoc exists...
        if (!empty(assoc)) {
            // splice it from the self.assocs array and the hashes
            for (let j = 0; j < self.assocs.length; ++j) {
                let a = self.assocs[j];
                if (a === assoc) {
                    // delete it from assocHash and assocIdHash, and splice it from the assocs array
                    if (!empty(a.identifier)) {
                        delete self.assocHash[a.identifier];
                    }
                    delete apx.mainDoc.assocIdHash[a.id];
                    apx.mainDoc.assocs.splice(j, 1);
                    break;
                }
            }

            // and remove it from the originItem's assocs array
            let item = self.itemHash[assoc.origin.item];
            if (!empty(item)) {
                for (let j = 0; j < item.assocs.length; ++j) {
                    if (item.assocs[j] === assoc) {
                        item.assocs.splice(j, 1);
                        break;
                    }
                }
            }

            // also try to remove its inverse association
            assocId += "";
            if (assocId.indexOf("-R") > -1) {
                self.deleteAssociation(assocId.replace(/-R/, ""));
            } else {
                self.deleteAssociation(assocId + "-R");
            }
        }
    };

    self.openAssociationItem = function(el, fromAssocView) {
        let assocId = $(el).attr("data-association-id");
        let assocIdentifier = $(el).attr("data-association-identifier");
        let assocItem = $(el).attr("data-association-item");

        // try to find the assoc, in either mainDoc or treeDoc1
        let assoc;
        // look via the assocIdentifier
        if (!empty(assocIdentifier)) {
            assoc = apx.mainDoc.assocHash[assocIdentifier];
            if (empty(assoc)) {
                assoc = apx.treeDoc1.assocHash[assocIdentifier];
            }
        }

        // if we didn't find it via the assocIdentifier, look via assocId
        if (empty(assoc)) {
            assoc = apx.mainDoc.assocIdHash[assocId];
            if (empty(assoc)) {
                assoc = apx.treeDoc1.assocIdHash[assocId];
            }
        }
        console.log("openAssociationItem", assoc);
        if (!empty(assoc)) {
            // when the specified "item" is a url (this should be the case for exemplars, and possibly other items), open url in new window
            if (assoc[assocItem].item.search(/^http(s)?:/) === 0) {
                window.open(assoc[assocItem].item);
                // return false to signal that we opened in another window
                return false;

            // if the item is in the treeDoc1, redirect to the item
            } else if (assoc[assocItem].doc == apx.treeDoc1.doc.identifier) {
                let destItem = apx.treeDoc1.itemHash[assoc[assocItem].item];
                if (!empty(destItem)) {
                    // switch assocGroup if it's different than the current assocGroup
                    if (assoc.groupId != apx.treeDoc1.currentAssocGroup) {
                        apx.treeDoc1.setCurrentAssocGroup(assoc.groupId);
                        apx.treeDoc1.ftRender1();
                    }
                    apx.treeDoc1.setCurrentItem(destItem);
                    apx.pushHistoryState();
                    apx.treeDoc1.activateCurrentItem();
                }
                // return true to signal that we did something in this window
                return true;

            // else try to open the item in a new window
            } else {
                let doc = apx.allDocs[assoc[assocItem].doc];
                if (typeof(doc) === "object") {
                    let url = doc.getItemUri(doc.itemHash[assoc[assocItem].item]);
                    if (url !== "?") {
                        window.open(url);
                        // return false to signal that we opened in another window
                        return false;
                    }
                }
            }
        }

        // if we make it to here return false to signal that nothing happened
        return false;
    };


    self.addItem = function(item) {
        self.items.push(item);
        self.itemHash[item.identifier] = item;
        if (!empty(item.id)) {
            self.itemIdHash[item.id] = item;
        }

        // note that this is an item
        item.nodeType = "item";

        // add reference to the document
        item.doc = self;

        // add assocs array for the item
        item.assocs = [];

        // add to allItemsHash (if it's not already there -- it shouldn't be)
        if (empty(apx.allItemsHash[item.identifier])) {
            apx.allItemsHash[item.identifier] = item;
        } else {
            console.log("item already existed in allItemsHash", item);
        }

        return item;
    };

    self.addNewItemData = function(data) {
        if ("undefined" === typeof apx.mainDoc.itemIdHash[data.id]) {
            // add item and association, then reload tree (and current item?)
            let item = apx.mainDoc.addItem(data);

            // and create and add the isChildOf association and its inverse
            if ("undefined" !== typeof item.newAssoc) {
                let atts = {
                    "id": item.newAssoc.assocId,
                    "identifier": item.newAssoc.identifier,
                    "originItem": item,
                    "type": "isChildOf",
                    "destItem": apx.mainDoc.currentItem,
                    "groupId": apx.mainDoc.currentAssocGroup
                };
                if ("undefined" !== typeof item.newAssoc.assocDoc) {
                    atts['assocDoc'] = item.newAssoc.assocDoc;
                }
                if ("undefined" !== typeof item.newAssoc.dest) {
                    atts['dest'] = item.newAssoc.dest;
                    delete atts.destItem;
                }
                if ("undefined" !== typeof item.newAssoc.seq) {
                    atts['seq'] = item.newAssoc.seq;
                }
                if ("undefined" !== typeof item.newAssoc.groupId) {
                    atts['groupId'] = item.newAssoc.groupId;
                }

                delete item.newAssoc;

                let a = apx.mainDoc.addAssociation(atts);
                apx.mainDoc.addInverseAssociation(a);
            }

            // re-render the tree and re-activate the item
            if ("tree" === apx.viewMode.currentView) {
                apx.treeDoc1.ftRender1();
                apx.treeDoc1.activateCurrentItem();
            }
        }
    };

    self.treeCheckboxToggleAll = function(val, side) {
        let $cb = self["ft" + side].closest(".treeSide").find(".treeCheckboxControl");

        // if this is the first click for this tree, enable checkboxes on the tree
        if ($cb.data("checkboxesEnabled") !== "true") {
            self.treeCheckboxToggleCheckboxes(true, side);

        // else toggle select all
        } else {
            if (empty(val)) {
                val = $cb.is(":checked");
            }

            // determine if something is entered in the search bar
            let searchEntered = false;
            let $filter = self["ft" + side].closest("section").find(".treeFilter");
            if ($filter.length > 0) {
                searchEntered = ($filter.val() !== "");
            }

            // PW 10/11/2017: Only check the top-level items (issues #116 and #204)
            let topChildren = self.getFt(side).rootNode.children[0].children;
            if (!empty(topChildren)) {
                for (let i = 0; i < topChildren.length; ++i) {
                    let node = topChildren[i];
                    // don't select unselectable nodes; also don't select the "Orphaned Items" node
                    if (node.unselectable !== true && node.key !== "orphans") {
                        // if either (we're not filtering) or (the node matches the filter) or (val is false),
                        if (searchEntered === false || node.match === true || val == false) {
                            // set selected to val
                            node.setSelected(val);
                        }
                    }
                }
            }
        }
    };

    self.treeCheckboxToggleCheckboxes = function(val, side) {
        let $cb = self["ft" + side].closest(".treeSide").find(".treeCheckboxControl");
        if (val === true) {
            self["ft" + side].fancytree("getTree").rootNode.checkbox = true;
            self["ft" + side].fancytree("option", "checkbox", true);
            self["ft" + side].fancytree("option", "selectMode", 2);

            // show the menu
            $cb.closest(".input-group").find(".dropdown-toggle").show();

            // mark the cb as enabled
            $cb.data("checkboxesEnabled", "true");

            // reset cb to off
            $cb.prop("checked", false);

        } else {
            self["ft" + side].fancytree("option", "checkbox", false);
            self["ft" + side].fancytree("option", "selectMode", 1);

            // hide the menu
            $cb.closest(".input-group").find(".dropdown-toggle").hide();

            // mark the cb as not enabled
            $cb.data("checkboxesEnabled", "false");

            // reset cb to off
            $cb.prop("checked", false);
        }
    };

    /** restore checkboxes after tree has been redrawn */
    self.treeCheckboxRestoreCheckboxes = function(side) {
        let $cb = self["ft" + side].closest(".treeSide").find(".treeCheckboxControl");
        if ($cb.data("checkboxesEnabled") === "true") {
            self.treeCheckboxToggleCheckboxes(true, side);
        }
    };

    self.treeCheckboxMenuItemSelected = function($menu, side) {
        // get all selected items
        let items = [];
        self["ft" + side].fancytree("getTree").visit(function(node) {
            if (node.selected === true && node.unselectable !== true) {
                items.push(node.data.ref);
            }
        });

        let cmd = $menu.attr("data-cmd");
        if (cmd !== "hideCheckboxes" && items.length === 0) {
            alert("Select one or more items using the checkboxes before choosing a menu item.");
            return;
        }

        if (cmd === "edit") {
            alert("The ability to edit properties of multiple items at the same time is not yet implemented.");
        } else if (cmd === "delete") {
            apx.edit.deleteItems(items);
        } else if (cmd === "makeFolders") {
            self.toggleFolders(items, true);
        } else {    // hideCheckboxes
            // clear checkbox selections
            let $cb = self["ft" + side].closest(".treeSide").find(".treeCheckboxControl");
            self.treeCheckboxToggleAll(false, side);
            self.treeCheckboxToggleCheckboxes(false, side);
        }
    };

    self.initializeTreeFilter = function(side) {
        let debounce = (function() {
            let timeout = null;
            return function(callback, wait) {
                if (timeout) { clearTimeout(timeout); }
                timeout = setTimeout(callback, wait);
            };
        })();

        let $treeside = self["ft" + side].closest(".treeSide");

        $treeside.find(".treeFilter").off().on('keyup', function() {
            let $that = $(this);
            let $tree = self.getFt(side);
            debounce(function(){
                if ($that.val().trim().length > 0) {
                    $tree.filterNodes($that.val(), {
                        autoExpand: true,
                        leavesOnly: false,
                        highlight: false
                    });
                    $that.parent().find(".filterClear").show();

                } else {
                    $tree.clearFilter();
                    $that.parent().find(".filterClear").hide();
                }
            }, 500);
        });

        // clear buttons for search fields
        $treeside.find(".filterClear").off().on('click', function() {
            $(this).parent().find(".treeFilter").val("").trigger("keyup");
        });
    };

    /** Activate the currentItem in the left-side tree */
    self.activateCurrentItem = function(item) {
        self.getFt(1).activateKey(self.currentItem.identifier);
    };

    /** Show the currentItem on the right side */
    self.showCurrentItem = function() {
        // clear apx.unknownAssocsShowing
        apx.unknownAssocsShowing = {};

        let item = self.currentItem;

        let $jq = $("#itemInfo");

        function showDocument() {
            // show title and appropriate icon
            let title = render.block(item.title);
            if (!empty(item.version)) {
                title = '<span style="float:right" class="lessImportant">Version ' + render.escaped(item.version) + '</span>' + title;
            }
            $jq.find(".itemTitleSpan").html(title);
            $jq.find(".itemTitleIcon").attr("src", "/assets/img/doc.png");

            /////////////////////////////////////
            // Show item details
            let html = "";
            let key, attributes, val;
            for (key in attributes = {
                'officialSourceURL': 'Official URL',
                'uri': 'CASE Framework URL',
                'creator': 'Creator',
                'description': 'Description',
                'subjects': 'Subject',
                'language': 'Language',
                'adoptionStatus': 'Adoption Status',
                'note': 'Notes'
            }) {
                if (!empty(item[key])) {
                    val = item[key];
                    // TODO: check these exceptions
                    if (key === 'creator' && !empty(item.publisher)) {
                        val += '<span class="lessImportant">Publisher: ' + render.escaped(item.publisher) + '</span>';
                    } else if (key === 'adoptionStatus') {
                        if (!empty(item.statusStart)) {
                            val += '<span class="lessImportant">From: ' + render.escaped(item.statusStart) + '</span>';
                        }
                        if (!empty(item.statusEnd)) {
                            val += '<span class="lessImportant">Until: ' + render.escaped(item.statusEnd) + '</span>';
                        }
                    } else if (key === 'subjects') {
                        val = "";
                        for (let subject in val) {
                            if (val !== "") {
                                val += ", ";
                            }
                            val += render.escaped(subject.title);
                        }
                    } else if (key === 'officialSourceURL') {
                        val = render.inlineLinked(val);

                        // add target=_blank
                        let $val = $('<div>' + val + '</div>');
                        $('a', $val).attr('target', '_blank');
                        val = $val.html();
                    } else if (key === 'uri') {
                        val = render.inlineLinked(self.getItemUri(item));

                        // add target=_blank
                        let $val = $('<div>' + val + '</div>');
                        $('a', $val).attr('target', '_blank');
                        val = $val.html();
                    } else {
                        val = render.escaped(val);
                    }

                    html += '<li class="list-group-item">'
                        + '<strong>' + attributes[key] + ':</strong> '
                        + val
                        + '</li>'
                    ;
                }
            }

            // render license information in case of framework has one.
            if ("undefined" !== typeof self.licenses && self.licenses.length > 0) {
                let licenseDoc = self.licenses;
                let licenseText = licenseDoc[0].licenseText;

                html += '<li class="list-group-item">'
                + '<strong>License:</strong> '
                + render.escaped(licenseDoc[0].title);

                if ( licenseText.length > 0) {
                    html += ' - <i>'
                    + render.escaped(licenseText)
                    + '</i>';
                }

                html += '</li>';
            }
            $jq.find("ul").html(html);

            // kill any existing associations from the dom
            $(".lsItemAssociations").html("");

            // show documentOptions and hide itemOptions and more info link
            $("#itemOptions").hide();
            $(".lsItemDetailsMoreInfoLink").hide();

            if ("undefined" !== typeof apx.locks) {
                $.each([
                    '#editDocModal',
                    '#manageAssocGroupsModal',
                    '#addNewChildModal',
                    '#addChildrenModal',
                    '#updateFrameworkModal'
                ], function (i, button) {
                    if ("undefined" !== typeof apx.locks['docs'][apx.lsDocId]
                        && false !== apx.locks['docs'][apx.lsDocId]) {
                        $('button[data-target="' + button + '"]')
                            .attr('disabled', 'disabled')
                            .addClass('disabled');
                    } else {
                        $('button[data-target="' + button + '"]')
                            .removeAttr('disabled')
                            .removeClass('disabled');
                    }
                });
            }

            $("#documentOptions").show();
        }

        function showItem() {
// show title and appropriate icon
            $jq.find(".itemTitleSpan").html(self.getItemTitle(item));
            if (item.setToParent === true || (!empty(item.ftNodeData) && item.ftNodeData.children.length > 0)) {
                $jq.find(".itemTitleIcon").attr("src", "/assets/img/folder.png");
            } else {
                $jq.find(".itemTitleIcon").attr("src", "/assets/img/item.png");
            }

            // show item details
            let html = "";
            let key, attributes, val;
            for (key in attributes = {
                'fstmt': 'Full Statement',
                'ck': 'Concept Keywords',
                'el': 'Education Level',
                'itp': 'Type',
                'notes': 'Notes'
            }) {
                if (!empty(item[key])) {
                    val = item[key];
                    if (key === 'fstmt' || key === 'notes') {
                        html += '<li class="list-group-item markdown-body">'
                            + '<strong>' + attributes[key] + ':</strong> '
                            + render.block(val)
                            + '</li>'
                        ;
                    } else {
                        // TODO: deal with ck, el, itp
                        html += '<li class="list-group-item">'
                            + '<strong>' + attributes[key] + ':</strong> '
                            + render.escaped(val)
                            + '</li>'
                        ;
                    }
                }
            }

            for (key in attributes = {
                'uri': 'CASE Item URI',
                'le': 'List Enumeration in Source',
                'cku': 'Concept Keywords URI',
                'lang': 'Language',
                'licenceUri': 'Licence URI',
                'mod': 'Last Changed'
            }) {
                if (!empty(item[key]) || key === "uri") {
                    val = item[key];

                    // TODO: deal with cku, licenceUri
                    // for uri, get it from the ApxDocument
                    if (key === "uri") {
                        val = self.getItemUri(item);
                        html += '<li class="list-group-item lsItemDetailsExtras">'
                            + '<strong>' + attributes[key] + ':</strong> '
                            + render.inlineLinked(val)
                            + '</li>'
                        ;
                    } else {
                        html += '<li class="list-group-item lsItemDetailsExtras">'
                            + '<strong>' + attributes[key] + ':</strong> '
                            + render.escaped(val)
                            + '</li>'
                        ;
                    }
                }
            }
            $jq.find("ul").html(html);

            /////////////////////////////////////
            // Show associations

            // first create an array with a combination of the this item's associations from this document,
            // along with any associations with this item as the origin in mainDoc (if mainDoc != self)
            let assocs = [];
            for (let i = 0; i < item.assocs.length; ++i) {
                assocs.push(item.assocs[i]);
            }
            if (self !== apx.mainDoc) {
                let mdi = apx.mainDoc.itemHash[item.identifier];
                if (!empty(mdi)) {
                    for (let i = 0; i < mdi.assocs.length; ++i) {
                        assocs.push(mdi.assocs[i]);
                    }
                }
            }

            // now if we have any assocs go through them...
            html = "";
            if (assocs.length > 0) {
                // first sort the assocs by type; put isChildOf at the end
                assocs.sort(function (a, b) {
                    if (a.type === b.type && a.inverse === b.inverse) { return 0; }
                    if (a.type === "isChildOf") { return 1; }
                    if (b.type === "isChildOf") { return -1; }
                    if (a.inverse === true && b.inverse !== true) { return 1; }
                    if (b.inverse === true && a.inverse !== true) { return -1; }
                    if (a.type < b.type) { return -1; }
                    if (a.type > b.type) { return 1; }
                    return 0;   // shouldn't get to here
                });

                // to simplify the list, we only use one association type header for each type
                let lastType = "";
                let lastInverse = -1;
                for (let i = 0; i < assocs.length; ++i) {
                    let a = assocs[i];

                    if (a.type === 'isChildOf') {
                        continue;
                    }

                    if (a.type !== lastType || a.inverse !== lastInverse) {
                        // close previous type section if we already opened one
                        if (lastType !== "") {
                            html += '</div></div></div></section>';
                        }

                        // open type section
                        let title = self.getAssociationTypePretty(a);
                        let icon = "";
                        if (a.type !== "isChildOf") {
                            icon = '<img class="association-panel-icon" src="/assets/img/association-icon.png">';
                        }
                        html += '<section class="panel panel-default panel-component item-component">'
                            + '<div class="panel-heading">' + icon + render.escaped(title) + '</div>'
                            + '<div class="panel-body"><div><div class="list-group">'
                        ;

                        lastType = a.type;
                        lastInverse = a.inverse;
                    }

                    // now the associated item

                    // determine if the origin item is a member of the edited doc or an other doc
                    let originDoc = "edited";
                    let removeBtn = $("#associationRemoveBtn").html();  // remove association button (only for editors)
                    if (a.assocDoc !== apx.mainDoc.doc.identifier) {
                        originDoc = "other";
                        // if it's another doc, no remove btn
                        removeBtn = "";
                    }

                    // assocGroup if assigned -- either in self or mainDoc
                    if (!empty(a.groupId)) {
                        let groupName = "Group " + a.groupId;
                        if (originDoc === "edited") {
                            if (!empty(apx.mainDoc.assocGroupIdHash[a.groupId])) {
                                groupName = self.assocGroupIdHash[a.groupId].title;
                            }

                        } else {
                            if (!empty(self.assocGroupIdHash[a.groupId])) {
                                groupName = self.assocGroupIdHash[a.groupId].title;
                            }
                        }
                        html += '<span class="label label-default">' + render.escaped(groupName) + '</span>';
                    }

                    html += '<a data-association-id="' + a.id + '" data-association-identifier="' + a.identifier + '" data-association-item="dest" class="list-group-item lsassociation lsitem clearfix lsassociation-' + originDoc + '-doc">'
                        + removeBtn
                        + '<span class="itemDetailsAssociationTitle">'
                        + self.associationDestItemTitle(a)
                        + '</span>'
                        + '</a>'
                    ;
                }
                // close final type section
                html += '</div></div></div></section>';
            }
            // End of code composing associations
            $(".lsItemAssociations").html(html);

            // hide enable hidden fields if necessary and restore last more info link state
            if (!self.moreInfoShowing) {
                $jq.find(".lsItemDetailsExtras").hide();
            }
            apx.toggleMoreInfo("restore");

            // make sure make folder and create new item buttons are set appropriately
            self.toggleItemCreationButtons();

            // enable association links
            $jq.find("[data-association-identifier]").on('click', function (e) {
                apx.treeDoc1.openAssociationItem(this, false);
            });

            // enable remove association button(s)
            $jq.find(".btn-remove-association").on('click', function (e) {
                e.preventDefault();

                // get the assocId from the association link
                let $target = $(e.target);
                let $item = $target.parents('.lsassociation');
                let assocId = $item.attr('data-association-id');

                // call edit.deleteAssociation; on callback, re-show the current item
                apx.edit.deleteAssociation(assocId, function () {
                    apx.treeDoc1.showCurrentItem();
                });
                return false;
            });

            // hide documentOptions and show itemOptions and the more info link
            $("#documentOptions").hide();

            if ("undefined" !== typeof apx.locks) {
                $.each([
                    '#editItemModal',
                    '#addNewChildModal',
                    '#addExemplarModal'
                ], function (i, button) {
                    if ("undefined" !== typeof apx.locks['items'][item.id]
                        && false !== apx.locks['items'][item.id]) {
                        $('button[data-target="' + button + '"]')
                            .attr('disabled', 'disabled')
                            .addClass('disabled');
                    } else {
                        $('button[data-target="' + button + '"]')
                            .removeAttr('disabled')
                            .removeClass('disabled');
                    }
                });
                $.each([
                    '#deleteItemBtn',
                    '#toggleFolderBtn'
                ], function (i, button) {
                    if ("undefined" !== typeof apx.locks['items'][item.id]
                        && false !== apx.locks['items'][item.id]) {
                        $(button)
                            .attr('disabled', 'disabled')
                            .addClass('disabled');
                    } else {
                        $(button)
                            .removeAttr('disabled')
                            .removeClass('disabled');
                    }
                });
            }
            $("#itemOptions").show();

            $(".lsItemDetailsMoreInfoLink").show();
        }

        if (item.nodeType === "document") {
            showDocument();
        } else {
            showItem();
        }
    };

    /** Compose the title for the destination of an association item in the item details view */
    self.associationDestItemTitle = function(a) {
        // set default title
        let title;
        if (!empty(a.dest.uri)) {
            title = a.dest.uri;

            if (0 === title.lastIndexOf("data:text/x-", 0)) {
                // If the destination is a data URI then try to handle it nicer
                let uri = title.substring(12);
                let data = uri.split(',', 2);

                if (/;base64[;,]/.test(data[0])) {
                    title = decodeURIComponent(atob(data[1]).split('').map(function(c) {
                        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                    }).join(''));
                } else {
                    title = decodeURIComponent(data[1]);
                }
            }
        } else if (!empty(a.dest.item)) {
            title = a.dest.item;
        } else if (!empty(a.dest.title)) {
            title = a.dest.title;
        } else {
            title = "Destination";
        }

        let doc = null;

        // if the assoc is an exemplar, title is always the uri
        if (a.type === "exemplar") {
            title = a.dest.uri;

        // else see if the "item" is actually a document
        } else if (!empty(apx.allDocs[a.dest.item]) && typeof(apx.allDocs[a.dest.item]) !== "string") {
            title = "Document: " + apx.allDocs[a.dest.item].doc.title;

        // else if we know about this item via allItemsHash...
        } else if (!empty(apx.allItemsHash[a.dest.item])) {
            let destItem = apx.allItemsHash[a.dest.item];
            title = self.getItemTitle(destItem, true);
            doc = destItem.doc;

        // else we don't (currently at least) know about this item...
        } else {
            // so add the association to apx.unknownAssocsShowing; if info about the item is loaded later, it'll get filled in
            apx.unknownAssocsShowing[a.id] = a;

            if (a.dest.doc !== "?") {
                // look for document in allDocs
                doc = apx.allDocs[a.dest.doc];

                // if we tried to load this document and failed, note that
                if (doc === "loaderror") {
                    title += " (document could not be loaded)";

                // else if we know we're still in the process of loading that doc, note that
                } else if (doc === "loading") {
                    title += " (loading document...)";

                // else we have the doc -- this shouldn't normally happen, because if we know about the doc,
                // we should have found the item in apx.allItemsHash above
                } else if (typeof(doc) === "object") {
                    title += " (item not found in document)";
                }
            }
        }

        // if item comes from another doc, note that
        if (!empty(doc) && typeof(doc) === "object" && doc !== self) {
            let docTitle = doc.doc.title;
            if (docTitle.length > 30) {
                docTitle = docTitle.substr(0, 35);
                docTitle = docTitle.replace(/\w+$/, "");
                docTitle += "…";
            }
            title += ' <span class="label label-default">' + render.escaped(docTitle) + '</span>';
        }

        return title;
    };

    self.toggleItemCreationButtons = function() {
        // if mainDoc isn't showing in tree1 slot, don't do this
        if (apx.treeDoc1 != apx.mainDoc) {
            return;
        }

        let $jq = $("#itemInfo");
        let item = self.currentItem;
        // if item already has children
        if (!empty(item.ftNodeData) && item.ftNodeData.children.length > 0) {
            // hide "Make this item a folder" button
            $jq.find("[id=toggleFolderBtn]").hide();
            // and show the "Add a new child item" button
            $jq.find("[id=addChildBtn]").show();

        // else item doesn't have children
        } else {
            // show "Make this item a folder" button
            $jq.find("[id=toggleFolderBtn]").show();

            // set the text of the toggleFolderBtn and visibility of the addChildBtn appropriately
            if (item.setToParent === true) {
                $jq.find("[id=toggleFolderBtn]").text("Make This Item a Child");
                $jq.find("[id=addChildBtn]").show();
            } else {
                $jq.find("[id=toggleFolderBtn]").text("Make This Item a Parent");
                $jq.find("[id=addChildBtn]").hide();
            }
        }
    };

    self.toggleFolders = function(items, val) {
        if (!$.isArray(items)) {
            items = [self.currentItem];
        }
        if (typeof(val) !== "boolean") {
            val = "toggle";
        }
        for (let i = 0; i < items.length; ++i) {
            let item = items[i];
            // can't change anything that has children already
            if (item.ftNodeData.children.length > 0) {
                continue;
            }

            if (val === "toggle") {
                item.setToParent = !(item.setToParent === true);
            } else {
                item.setToParent = val;
            }
            let ftNode = self.getFtNode(item, 1);
            ftNode.folder = item.setToParent;
            ftNode.render();
            if (item.setToParent === true) {
                $(ftNode.li).find(".fancytree-icon").addClass("fancytree-force-folder");
            } else {
                $(ftNode.li).find(".fancytree-icon").removeClass("fancytree-force-folder");
            }

            // if this is the currentItem, update the icon
            if (item == self.currentItem) {
                let src;
                if (item.setToParent) {
                    src = "/assets/img/folder.png";
                } else {
                    src = "/assets/img/item.png";
                }
                $("#itemInfo").find(".itemTitleIcon").attr("src", src);
            }
        }

        self.toggleItemCreationButtons();
    };
}
/* global apx */
window.apx = window.apx||{};

/* global empty */
/* global ApxDocument */

/** Prepare menus for selecting documents for the left- and right-side trees */
apx.prepareDocumentMenus = function() {
    // The original menu will be rendered into div #ls_doc_list and select #ls_doc_list_lsDoc, on the right side

    // Mark this document in the menu
    let docList = $("#ls_doc_list_lsDoc");
    let $opt = docList.find("[value=" + apx.mainDoc.doc.id + "]");
    $opt.html($opt.html() + " (• DOCUMENT BEING EDITED •)");

    // add item to menu for loading from another server
    docList.append('<optgroup class="externalDocsOptGroup" label="EXTERNAL DOCUMENTS"><option value="url">Load an “external” document by url…</option></optgroup>');

    // go through each provided "associatedDoc" and add it to the "externalDocsOptGroup" option group if it's an external doc
    if (!empty(apx.mainDoc.associatedDocs)) {
        for (let identifier in apx.mainDoc.associatedDocs) {
            let ad = apx.mainDoc.associatedDocs[identifier];
            // non-external docs have urls that start with "local"
            if (ad.url.search(/local/) !== 0) {
                apx.addDocToMenus(identifier, ad.url, ad.title);
            }
        }
    }

    // now get the div and update the id's
    let $rightDiv = $("#ls_doc_list");
    $rightDiv.addClass("ls_doc_list");
    $rightDiv.find("[type=hidden]").remove();
    $rightDiv.attr("id", "ls_doc_list_right");
    $rightDiv.find("select").attr("id", "ls_doc_list_lsDoc_right");

    // clone the div for the left side, update the id's there, and insert it in place
    let $leftDiv = $rightDiv.clone();
    $leftDiv.attr("id", "ls_doc_list_left");
    $leftDiv.find("select").attr("id", "ls_doc_list_lsDoc_left");
    $("#tree1SelectorDiv").find(".row").append($leftDiv);

    // enable the select menus
    $("#ls_doc_list_lsDoc_left").on('change', function() { apx.docSelectedForTree(this, 1); });
    $("#ls_doc_list_lsDoc_right").on('change', function() { apx.docSelectedForTree(this, 2); });

    // change tree buttons
    $(".changeTree1DocumentBtn").on('click', function() { apx.tree1ChangeButtonClicked(); });
    $(".changeTree2DocumentBtn").on('click', function() { apx.tree2ChangeButtonClicked(); });

    // prepare the modal for loading an external document
    let $modal = $('#loadExternalDocumentModal');
    $modal.find('.btn-save').on('shown.bs.modal', function(e){
        $("#loadExternalDocumentUrlInput").focus().select();
    }).on('click', function(e) {
        let url = $("#loadExternalDocumentUrlInput").val();
        $modal.modal('hide');
        if (!empty(url)) {
            apx.docSelectedForTree(url);
        }
    });
};

/** Add a document loaded from an external server to the document select menus */
apx.addDocToMenus = function(identifier, url, title) {
    // add to mainDoc.associatedDocs if necessary
    if (empty(apx.mainDoc.associatedDocs[identifier])) {
        apx.mainDoc.associatedDocs[identifier] = {"url": url, "title": title, "autoLoad": "false"};
    }

    // and add to menus if necessary
    if ($(".externalDocsOptGroup [value=" + identifier + "]").length === 0) {
        $(".externalDocsOptGroup").prepend('<option value="' + identifier + '">' + apx.mainDoc.associatedDocs[identifier].title + ' (' + identifier + ')</option>');
    }
};

apx.docSelectedForTree = function(menuOrUrl, side) {
    let lsDocId, initializationKey;

    // if menuOrUrl is a string, its a URL that the user entered
    if (typeof(menuOrUrl) === "string") {
        initializationKey = "url";
        lsDocId = menuOrUrl;

        // retrieve stashed side
        side = apx.docSelectedForTreeSide;

    } else {
        initializationKey = "id";
        // get the selected document id
        lsDocId = $(menuOrUrl).val();

        // if user selects to load a new document by URL, get the URL now
        if (lsDocId === "url") {
            $("#loadExternalDocumentModal").modal();
            $(menuOrUrl).val("");

            // stash side so we can retrieve it if the user chooses a URL
            apx.docSelectedForTreeSide = side;
            return;

        // if user selects the blank item in the menu, go back to the currently-loaded document
        } else if (lsDocId === "") {
            $(menuOrUrl).val(apx["treeDoc" + side].doc.id);
            return;

        // else if lsDocId isn't a number, it's an identifier for an external document
        } else if (isNaN(lsDocId*1)) {
            initializationKey = "identifier";
        }
    }

    // if we get to here we should have an initializationKey and an lsDocId

    // destroy previus ft if there
    try {
        apx["treeDoc" + side]["ft" + side].fancytree("destroy");
    } catch(e) {
        // Ignore error
    }
    $('#viewmode_tree' + side).html("");

    // check to see if we already have the document info; if so we don't need to reload
    for (let identifier in apx.allDocs) {
        let d = apx.allDocs[identifier];

        // if this document errored when loading, continue through the loop; if this is what the user is trying to load now, let them retry
        if (d === "loaderror") {
            continue;
        }

        // if *any* documents are still autoloading, make the user wait, because the document they're requesting here might be the one that's loading
        if (d === "loading") {
            apx.spinner.showModal("Loading document");
            setTimeout(function() { apx.docSelectedForTree(menuOrUrl, side); }, 1000);
            return;
        }

        apx.spinner.hideModal();

        // if we found the document that was requested here...
        if ((initializationKey === "identifier" && identifier === lsDocId)
            || (initializationKey === "id" && !d.isExternalDoc() && d.doc.id === lsDocId)) {
            // set treeDoc1 or treeDoc2
            apx["treeDoc" + side] = d;
            // and call the side's treeDocLoadCallback function
            apx["treeDocLoadCallback" + side]();
            return;
        }
    }

    // if we get to here, initialize and load the document
    let o = {};
    o[initializationKey] = lsDocId;
    apx["treeDoc" + side] = new ApxDocument(o);

    // load the document
    apx.spinner.showModal("Loading document");
    apx["treeDoc" + side].load(function() {
        apx["treeDocLoadCallback" + side]();
        apx.spinner.hideModal();
    });

};

/** Define a function that will "synch" association destinations as documents get loaded when the application is starting up */
apx.unknownAssocsShowing = null;
apx.checkUnknownAssociationDestinationsInterval = setInterval(function() {
    // if unknownAssocsShowing hasn't been set yet, just return
    if (apx.unknownAssocsShowing === null) {
        return;
    }

    // go through any current unknownAssocsShowing
    for (var id in apx.unknownAssocsShowing) {
        var assoc = apx.unknownAssocsShowing[id];
        // if we know about this association's destination item now
        if (!empty(apx.allItemsHash[assoc.dest.item])) {
            var title = apx.treeDoc1.associationDestItemTitle(assoc);
            $("[data-association-id=" + id + "] .itemDetailsAssociationTitle").html(title);
        }
    }

    // if all documents have been loaded, clear the interval
    var allLoaded = true;
    for (var identifier in apx.allDocs) {
        if (apx.allDocs[identifier] === "loading") {
            allLoaded = false;
            break;
        }
    }
    if (allLoaded) {
        clearInterval(apx.checkUnknownAssociationDestinationsInterval);
    }

}, 1000);


///////////////////////////////////////////////////////////////////////////////
// LEFT-SIDE TREE
apx.treeDoc1 = null;

apx.tree1ChangeButtonClicked = function() {
    // clear viewmode_tree1 and hide tree1SectionControls and assocGroupFilter
    $("#viewmode_tree1").html("");
    $("#tree1SectionControls").hide();
    $("#treeSideLeft").find(".assocGroupFilter").hide();

    // clear selection in ls_doc_list_lsDoc_left
    $("#ls_doc_list_lsDoc_left").val("");

    // and show tree1SelectorDiv
    $("#tree1SelectorDiv").show();

    // reset instructions
    $("#tree1InitialInstructions").show();
    $("#tree1SectionThisDocInstructions").hide();
    $("#tree1SectionOtherDocInstructions").hide();

    // make sure the noItemsInstructions div is hidden
    $("#noItemsInstructions").hide();
};


// define function to run when treeDoc1 is loaded
apx.treeDocLoadCallback1 = function() {
    // define treeDoc1's ftRender function
    apx.treeDoc1.ftRender1 = function() {
        // first process the tree, using treeDoc1's current association group
        let ftData = apx.treeDoc1.createTree(apx.treeDoc1.currentAssocGroup, 1);

        let viewmodeTree1 = $('#viewmode_tree1');
        // destroy the existing tree if necessary
        if (viewmodeTree1.children().length > 0) {
            viewmodeTree1.fancytree("destroy");
        }

        // establish the fancytree widget
        apx.treeDoc1.ft1 = viewmodeTree1.fancytree({
            extensions: ['filter', 'dnd'],
            source: ftData,
            quicksearch: true,
            filter:{
                autoApply: true,  // Re-apply last filter if lazy data is loaded
                counter: true,  // Show a badge with number of matching child nodes near parent icons
                fuzzy: false,  // Match single characters in order, e.g. 'fb' will match 'FooBar'
                hideExpandedCounter: true,  // Hide counter badge, when parent is expanded
                highlight: true,  // Highlight matches by wrapping inside <mark> tags
                mode: "hide"  // Grayout unmatched nodes (pass "hide" to remove unmatched node instead)
            },

            /*
            renderTitle: function(event, data) {
                console.log(event, data);
                title = render.inline(apx.treeDoc1.getItemTitle(data.node.data.ref, true));
                return title;
            },
            */

            // function called after the node is rendered
            renderNode: function(event, data) {
                let $span = $(data.node.span),
                    $title = $span.find('> span.fancytree-title'),
                    ref = data.node.data.ref
                ;
                let title = '';
                if (ref.title) {
                    title = render.inline(ref.title);
                } else if (ref.astmt) {
                    title = render.inline(ref.astmt);
                } else if (ref.fstmt) {
                    title = render.inline(ref.fstmt);
                }

                if (ref.hcs) {
                    title = '<span class="item-humanCodingScheme">' + render.escaped(ref.hcs) + '</span> ' + title;
                }

                $title.html(title);

                $('body > .tooltip').tooltip('hide');
            },
            
            click: function(event, data) {
                // if we're in chooser mode, show display to allow this item to be chosen
                // (this won't have any effect if we're not in chooser mode)
                apx.chooserMode.itemClicked(data.node);
            },

            // when item is activated (user clicks on it or activateKey() is called), show details for the item
            activate: function(event, data) {
                let item = data.node.data.ref;

                // if this isn't already the currentItem...
                if (item !== apx.treeDoc1.currentItem) {
                    // setCurrentItem
                    apx.treeDoc1.setCurrentItem({"identifier": item.identifier});
                    // push history state...
                    apx.pushHistoryState();
                }

                // launching comment system depending of the item id.
                if ('undefined' !== typeof(CommentSystem)) {
                    CommentSystem.init(item);
                }

                // hide tree2 and show the item details section; this will call showCurrentItem
                apx.setRightSideMode("itemDetails");
            },

            // if user doubleclicks on a node, open the node, then simulate clicking the "Edit" button for it
            dblclick: function(event, data) {
                if (apx.treeDoc1.isDocNode(data.node)) {
                    $(".btn[data-target='#editDocModal']").click();
                } else {
                    $(".btn[data-target='#editItemModal']").click();
                }

                // return false to cancel default processing (i.e. opening folders)
                return false;
            },

            expand: function() {
                apx.treeDoc1.recordExpandedFolders(1);
            },

            // drag-and-drop functionality - https://github.com/mar10/fancytree/wiki/ExtDnd
            dnd: {
                dragExpand: function() { return false; },   // don't autoexpand folders when you drag over them; this makes things confusing

                smartRevert: true,

                // this function seems to need to be defined for the dnd functionality to work
                dragStart: function(node, data) {
                    // don't allow dragging when we can't edit
                    if (!apx.enableEdit) {
                        return false;
                    }

                    // and don't allow drag if edit.moveEnabled is false
                    if (true !== apx.edit.moveEnabled) {
                        return false;
                    }

                    // don't allow the document to be dragged
                    if (true === apx.treeDoc1.isDocNode(node)) {
                        return false;
                    }

                    // don't allow the orphan directory to be dragged
                    if ('orphans' === node.key) {
                        return false;
                    }

                    // disable tooltips
                    $('#treeView').on('show.bs.tooltip', function() { return false; });
                },

                dragStop: function(node, data) {
                    // re-enable tooltip
                    $('#treeView').off('show.bs.tooltip');
                },

                initHelper: function(node, data) {
                    // Helper was just created: modify markup
                    let helper = data.ui.helper;
                    let tree = node.tree;
                    let sourceNodes = data.tree.getSelectedNodes();

                    // Store a list of active + all selected nodes
                    if (!node.isSelected()) {
                        sourceNodes.unshift(node);
                    }
                    helper.data("sourceNodes", sourceNodes);

                    // Mark selected nodes also as drag source (active node is already)
                    $(".fancytree-active,.fancytree-selected", tree.$container).addClass("fancytree-drag-source");

                    // Add a counter badge to helper if dragging more than one node
                    if (sourceNodes.length > 1) {
                        helper.append($("<span class='fancytree-childcounter'/>").text("+" + (sourceNodes.length - 1)));
                    }
                },

                dragEnter: function(droppedNode, data) {
                    let draggedNode = data.otherNode;

                    // determine if this is inter- or intra-tree drag
                    let treeDraggedFrom = "tree1";
                    if (droppedNode.tree !== draggedNode.tree) {
                        treeDraggedFrom = "tree2";
                    }

                    // Do not allow dropping in Orphaned Items folder
                    let parent = droppedNode;
                    while (null !== parent) {
                        if ('orphans' === parent.key) {
                            return false;
                        }
                        parent = parent.getParent();
                    }

                    // intra-tree drag
                    if (treeDraggedFrom === "tree1") {
                        // Don't allow dropping *over* a non-folder node (this would make it too easy to accidentally create a child).
                        if (true === droppedNode.folder) {
                            // also don't allow dropping before or after the document -- only "over" allowed in this case
                            if (apx.treeDoc1.isDocNode(droppedNode)) {
                                return "over";
                            } else {
                                return true;
                            }
                        } else {
                            return ["before", "after"];     // , "over"
                        }

                        // drag from tree2 to tree1
                    } else {
                        // if we're in associate mode, only allow drags *over*, not between, items
                        if (apx.rightSideMode === "addAssociation") {
                            // and don't allow any drops onto the document
                            if (apx.treeDoc1.isDocNode(droppedNode)) {
                                return false;
                            } else {
                                return 'over';
                            }
                        // else we're in copy mode; use same thing here as moving within the tree
                        } else {
                            // don't allow dropping before or after the document -- only "over" allowed in this case
                            if (apx.treeDoc1.isDocNode(droppedNode)) {
                                return "over";
                            } else if (droppedNode.folder === true) {
                                return true;
                            } else {
                                return ["before", "after"];
                            }
                        }
                    }
                },

                dragDrop: function(droppedNode, data){
                    // determine if this is inter- or intra-tree drag
                    let treeDraggedFrom = "tree1";
                    if (droppedNode.tree !== data.otherNode.tree) {
                        treeDraggedFrom = "tree2";
                    }

                    let draggedNodes = data.ui.helper.data("sourceNodes");

                    // intra-tree drag - move the item(s) in the tree
                    if (treeDraggedFrom === "tree1") {
                        apx.edit.moveItems(draggedNodes, droppedNode, data.hitMode);

                    // inter-tree drag (from tree2)
                    } else {
                        // if we're in associate mode, show choice for what type of association to add
                        if (apx.rightSideMode === "addAssociation") {
                            // remember dragged and dropped nodes while we make the call to open the form
                            apx.edit.createAssociationNodes = {
                                "draggedNodes": draggedNodes,
                                "droppedNode": droppedNode
                            };

                            // then open the modal form to allow the user to choose the association type
                            $('#associateModal').modal();

                        // else if we're in copy mode; copy node(s) to new tree
                        } else if (apx.rightSideMode === "copyItem") {
                            // apx.edit.copyItem(draggedNode, droppedNode, data.hitMode);
                            apx.edit.copyItems(draggedNodes, droppedNode, data.hitMode);
                        }
                    }
                }   // end of dragDrop function
            }
        });
        // end of fancytree widget initialization

        // if we're not showing mainDoc on the left, set a background color to indicate that
        if (apx.treeDoc1 !== apx.mainDoc) {
            $("#tree1Section").addClass("otherDoc");
        } else {
            $("#tree1Section").removeClass("otherDoc");
        }

        // restore checkbox state
        apx.treeDoc1.treeCheckboxRestoreCheckboxes(1);

        // if this document is also showing on the right side, re-render there too
        if (apx.treeDoc1 === apx.treeDoc2 && !empty(apx.treeDoc2.ftRender2)) {
            apx.treeDoc2.ftRender2();
        }
    };
    // end of ftRender1 definition

    ///////////////////////////////////
    // Things we need to do when a new doc is loaded for treeDoc1

    // hide tree1SelectorDiv
    $("#tree1SelectorDiv").hide();

    // render treeDoc1's assocGroup menu
    apx.treeDoc1.renderAssocGroupMenu($("#treeSideLeft").find(".assocGroupSelect"));

    // render the fancytree for treeDoc1
    apx.treeDoc1.ftRender1();

    // initialize tree search bar
    apx.treeDoc1.initializeTreeFilter(1);

    // show instructions and controls properly
    $("#tree1InitialInstructions").hide();

    // if we're showing the mainDoc...
    if (apx.treeDoc1 === apx.mainDoc) {
        $("#tree1SectionThisDocInstructions").show();
        $("#tree1SectionOtherDocInstructions").hide();

        // In the right-side control group, enable the "Copy Items" button
        $("#rightSideCopyItemsBtn").attr("disabled", false);

        // show the buttons (e.g. delete, create new) that aren't operable when viewing a different doc
        $("[data-alt-document-disabled]").show();

        // show the tree1SectionBulkControls div
        $("#tree1SectionBulkControls").show();

        // activate the current (initial) item (which might be the document)
        apx.treeDoc1.activateCurrentItem();

        // we also have to call showCurrentItem, because if the current item is the document it's already active
        apx.treeDoc1.showCurrentItem();

    // else we're showing a different doc...
    } else {
        $("#tree1SectionOtherDocInstructions").show();
        $("#tree1SectionThisDocInstructions").hide();

        // In the right-side control group, disable the "Copy Items" buttons
        $("#rightSideCopyItemsBtn").attr("disabled", true);

        // hide the buttons (e.g. delete, create new) that aren't operable when viewing a different doc
        $("[data-alt-document-disabled]").hide();

        // hide the tree1SectionBulkControls div; you can't use these controls when viewing a different doc
        $("#tree1SectionBulkControls").hide();
    }

    // call setRightSideMode
    apx.setRightSideMode("itemDetails");

    // if this is the mainDoc and it contains no items, show the noItemsInstructions
    if (apx.treeDoc1 == apx.mainDoc && apx.mainDoc.items.length === 0) {
        $("#noItemsInstructions").show();
    } else {
        $("#noItemsInstructions").hide();
    }
};

/** Toggle more item details in item details display */
// @param {*} [arg] - if true or false, set to that value; if "restore", restore last-used value; otherwise toggle
apx.moreInfoShowing = false;
apx.toggleMoreInfo = function(arg) {
    if (arg == null) {
        apx.moreInfoShowing = !apx.moreInfoShowing;
    } else if (arg !== "restore") {
        apx.moreInfoShowing = arg;
    }

    if (apx.moreInfoShowing) {
        $(".lsItemDetailsExtras").slideDown(100);
        $(".lsItemDetailsMoreInfoLink a").text("Less Info");
    } else {
        $(".lsItemDetailsExtras").slideUp(100);
        $(".lsItemDetailsMoreInfoLink a").text("More Info");
    }
};



///////////////////////////////////////////////////////////////////////////////
// RIGHT-SIDE TREE

apx.rightSideMode = "itemDetails";
apx.setRightSideMode = function(newMode) {
    if (newMode === "itemDetails") {
        $("#tree2SectionControls").hide();
        $("#tree2Section").hide();
        $("#itemSection").show();
        $(".js-comments-container").show();

        $("#rightSideItemDetailsBtn").addClass("btn-primary").removeClass("btn-default");
        $("#rightSideCopyItemsBtn").removeClass("btn-primary").addClass("btn-default");
        $("#rightSideCreateAssociationsBtn").removeClass("btn-primary").addClass("btn-default");

        // re-show the item details in case anything got updated
        apx.treeDoc1.showCurrentItem();

    } else {
        if (!empty(apx.treeDoc2)) {
            $("#tree2SectionControls").show();
            $("#tree2InitialInstructions").hide();
        } else {
            $("#tree2SectionControls").hide();
            $("#tree2InitialInstructions").show();
        }
        $("#tree2Section").show();
        $("#itemSection").hide();
        $(".js-comments-container").hide();

        if (newMode === "addAssociation") {
            if (!empty(apx.treeDoc2)) {
                $("#tree2SectionCopyInstructions").hide();
                $("#tree2SectionRelationshipInstructions").show();
            }

            $("#rightSideItemDetailsBtn").removeClass("btn-primary").addClass("btn-default");
            $("#rightSideCopyItemsBtn").removeClass("btn-primary").addClass("btn-default");
            $("#rightSideCreateAssociationsBtn").addClass("btn-primary").removeClass("btn-default");

        } else {    // newMode == "copyItem"
            if (!empty(apx.treeDoc2)) {
                $("#tree2SectionCopyInstructions").show();
                $("#tree2SectionRelationshipInstructions").hide();
            }

            $("#rightSideItemDetailsBtn").removeClass("btn-primary").addClass("btn-default");
            $("#rightSideCopyItemsBtn").addClass("btn-primary").removeClass("btn-default");
            $("#rightSideCreateAssociationsBtn").removeClass("btn-primary").addClass("btn-default");
        }
    }

    apx.rightSideMode = newMode;
};

apx.tree2ChangeButtonClicked = function() {
    // clear viewmode_tree2 and hide tree2SectionControls and assocGroupFilter
    $("#viewmode_tree2").html("");
    $("#tree2SectionControls").hide();
    $("#treeSideRight").find(".assocGroupFilter").hide();

    // clear selection in ls_doc_list_lsDoc_right
    $("#ls_doc_list_lsDoc_right").val("");

    // and show tree2SelectorDiv
    $("#tree2SelectorDiv").show();

    // reset instructions
    $("#tree2InitialInstructions").show();
    $("#tree2SectionCopyInstructions").hide();
    $("#tree2SectionRelationshipInstructions").hide();
};

// document showing on the right side
apx.treeDoc2 = null;

// define function to run when treeDoc2 is loaded
apx.treeDocLoadCallback2 = function() {
    // define treeDoc2's ftRender function
    apx.treeDoc2.ftRender2 = function() {
        // process the tree, using current association group
        let ftData = apx.treeDoc2.createTree(apx.treeDoc2.currentAssocGroup2, 2);

        let viewmodeTree2 = $('#viewmode_tree2');

        // make sure viewmode_tree2 is cleared and showing
        if (viewmodeTree2.find(".ui-fancytree").length > 0) {
            viewmodeTree2.fancytree("destroy");
        }
        viewmodeTree2.html("").show();

        // then initialize (or re-initialize) fancytree
        apx.treeDoc2.ft2 = viewmodeTree2.fancytree({
            extensions: ['filter', 'dnd'],
            source: ftData,
            quicksearch: true,
            filter:{
                autoApply: true,  // Re-apply last filter if lazy data is loaded
                counter: true,  // Show a badge with number of matching child nodes near parent icons
                fuzzy: false,  // Match single characters in order, e.g. 'fb' will match 'FooBar'
                hideExpandedCounter: true,  // Hide counter badge, when parent is expanded
                highlight: true,  // Highlight matches by wrapping inside <mark> tags
                mode: "hide"  // Grayout unmatched nodes (pass "hide" to remove unmatched node instead)
            },

            renderNode: function(event, data) {
            },

            expand: function() {
                apx.treeDoc2.recordExpandedFolders(2);
            },

            click: function() {
                // if we're in copy mode and treeDoc2 is an external doc, stop the user right here; no copying allowed from external docs at this time
                if (apx.rightSideMode === "copyItem" && apx.treeDoc2.isExternalDoc()) {
                    alert("You cannot currently copy an item from a document on another server.");
                    return false;
                }
            },

            // drag-and-drop functionality - https://github.com/mar10/fancytree/wiki/ExtDnd
            dnd: {
                // focusOnClick: true,
                dragExpand: function() { return false; },   // don't autoexpand folders when you drag over them; this makes things confusing

                // modify default jQuery draggable options
                draggable: {
                    // disable auto-scrolling, though I'm not sure this does much good
                    scroll: false,
                    // append the draggable helper item to the body, so that you'll see it when you drag over tree2
                    appendTo: "body"
                },

                // define dragStart on tree2 to allow dragging from this tree
                dragStart: function(node, data) {
                    // when we start dragging, activate the key so it'll be highlighted
                    apx.treeDoc2.getFt(2).activateKey(node.key);

                    // don't allow the document to be dragged
                    if (true === apx.treeDoc2.isDocNode(node)) {
                        return false;
                    }

                    // don't allow the orphan directory to be dragged
                    if ('orphans' === node.key) {
                        return false;
                    }

                    // disable tooltips
                    $('#treeView').on('show.bs.tooltip', function() { return false; });
                },

                initHelper: function(node, data) {
                    // Helper was just created: modify markup
                    let helper = data.ui.helper;
                    let tree = node.tree;
                    let sourceNodes = data.tree.getSelectedNodes();

                    // Store a list of active + all selected nodes
                    if (!node.isSelected()) {
                        sourceNodes.unshift(node);
                    }
                    helper.data("sourceNodes", sourceNodes);

                    // Mark selected nodes also as drag source (active node is already)
                    $(".fancytree-active,.fancytree-selected", tree.$container).addClass("fancytree-drag-source");

                    // Add a counter badge to helper if dragging more than one node
                    if (sourceNodes.length > 1) {
                        helper.append($("<span class='fancytree-childcounter'/>").text("+" + (sourceNodes.length - 1)));
                    }
                },

                dragStop: function(node, data) {
                    // re-enable tooltip
                    $('#treeView').off('show.bs.tooltip');
                },

                // this function needs to be defined for the dnd functionality to work...
                dragEnter: function(node, data) {
                    // but you can't drag from tree2 to tree2, so return false here to prevent this
                    // the logic for dragging into tree1 is in the other fancytree definer
                    return false;
                },
                dragDrop: function(node, data){
                    // we should never get here, because we only allow drags from tree2 to tree1
                    console.log('tree2 dragDrop (' + apx.rightSideMode + '): ' + draggedItemId + ' to ' + hitMode + ' ' + droppedItemId);
                }
            }
        });

        // restore checkbox state
        apx.treeDoc2.treeCheckboxRestoreCheckboxes(2);
    };
    // end of ftRender function

    // show instructions properly by re-calling setRightSideMode
    if ($("#rightSideCopyItemsBtn").hasClass("btn-primary")) {
        apx.setRightSideMode("copyItem");
    } else {
        apx.setRightSideMode("addAssociation");
    }

    // and hide tree2SelectorDiv
    $("#tree2SelectorDiv").hide();

    // set the currentAssocGroup
    apx.treeDoc2.setCurrentAssocGroup(null, 2);

    // render the treeDoc2's assocGroup menu
    apx.treeDoc2.renderAssocGroupMenu($("#treeSideRight").find(".assocGroupSelect"), 2);

    // process the tree and render
    apx.treeDoc2.ftRender2();

    // initialize tree search bar
    apx.treeDoc2.initializeTreeFilter(2);
};

/* global apx */
window.apx = window.apx||{};

/* global empty */

//////////////////////////////////////////////////////
// EDIT THE DOCUMENT OR AN ITEM
apx.edit = {};

/** Edit the document data */
apx.edit.prepareDocEditModal = function() {
    let $modal = $('#editDocModal');
    $modal.find('.modal-body').html(apx.spinner.html("Loading Form"));
    $modal.on('shown.bs.modal', function(e){
        $modal.data('mode', 'open');
        $modal.find('.modal-footer .btn-save').hide();
        $modal.find('.modal-body').load(
            apx.path.lsdoc_edit.replace('ID', apx.lsDocId),
            null,
            function(responseText, textStatus, jqXHR){
                $('#ls_doc_licence').select2entity({dropdownParent: $('#ls_doc_licence').closest('div')});
                let $docSubjects = $('#ls_doc_subjects');
                $docSubjects.select2entity({dropdownParent: $docSubjects.closest('div')});
                if ($modal.find('form[name="ls_doc"]').length) {
                    $modal.find('.modal-footer .btn-save').show();
                }
            }
        );
    }).on('hide.bs.modal', function(e){
        $('#ls_doc_subjects').select2('destroy');
        $('#ls_doc_licence').select2('destroy');

        if ('open' === $modal.data('mode')) {
            $.ajax({
                url: apx.path.lsdoc_unlock.replace('ID', apx.lsDocId),
                method: 'POST'
            });
        }
        $modal.data('mode', 'close');
    }).on('hidden.bs.modal', function(e){
        $modal.find('.modal-body').html(apx.spinner.html("Loading Form"));
    });
    $modal.find('.btn-save').on('click', function(e){
        $modal.data('mode', 'save');
        apx.spinner.showModal("Updating document");
        $.ajax({
            url: apx.path.lsdoc_edit.replace('ID', apx.lsDocId),
            method: 'POST',
            data: $modal.find('form[name=ls_doc]').serialize()
        }).done(function(data, textStatus, jqXHR){
            $modal.modal('hide');
            // on successful update, reload the doc
            window.location.reload();
            /*
               var updatedData = {
               "title": $("#ls_doc_title").val(),
               "version": $("#ls_doc_version").val(),
               "adoptionStatus": $("#ls_doc_adoptionStatus").val(),
               };
               */
        }).fail(function(jqXHR, textStatus, errorThrown){
            apx.spinner.hideModal();
            $modal.find('.modal-body').html(jqXHR.responseText);
        });
    });
};

apx.edit.prepareDocDeleteModal = function() {
    let $modal = $('#deleteFrameworkModal');
    let $ack = $modal.find('#deleteFrameworkAcknowledgement');
    let isDelete = /^"?DELETE"?$/;
    let $btnDelete = $modal.find('.btn-delete');
    $modal.on('shown.bs.modal', function(e){
        $ack.val('');
        $modal.find('.errors').html('');
        $btnDelete.addClass('btn-disabled').attr('disabled', 'disabled');
        $ack.on('change keyup', function(e){
            if (isDelete.test($ack.val())) {
                $btnDelete.removeClass('btn-disabled').removeAttr('disabled');
            } else {
                $btnDelete.addClass('btn-disabled').attr('disabled', 'disabled');
            }
        });
        $btnDelete.on('click', function(e){
            $.ajax({
                url: apx.path.lsdoc_delete.replace('ID', apx.mainDoc.doc.id),
                method: 'POST',
                data: {
                    _method: 'DELETE',
                    token: $btnDelete.data('token')
                },
                dataType: 'json'
            }).done(function(data, textStatus, jqXHR){
                window.location.href = apx.path.doc_index;
            }).fail(function(jqXHR, textStatus, errorThrown){
                $modal.find('.errors').html('<p class="text-danger">Error: '+jqXHR.responseJSON.error.message+'</p>');
            });
        });
    }).on('hidden.bs.modal', function(e) {
        $ack.val('');
        $ack.off('change keyup');
        $btnDelete.off('click');
        $btnDelete.addClass('btn-disabled').attr('disabled', 'disabled');
    });
};

/** Edit an item */
apx.edit.prepareItemEditModal = function() {
    let $modal = $('#editItemModal');
    let statementMde, notesMde;
    $modal.find('.modal-body').html(apx.spinner.html("Loading Form"));
    $modal.on('shown.bs.modal', function(e){
        $modal.data('mode', 'open');
        $modal.find('.modal-footer .btn-save').hide();
        $modal.find('.modal-body').load(
            apx.path.lsitem_edit.replace('ID', apx.mainDoc.currentItem.id),
            null,
            function(responseText, textStatus, jqXHR) {
                if ($modal.find('form[name="ls_item"]').length) {
                    $modal.find('.modal-footer .btn-save').show();
                }
                $('#ls_item_educationalAlignment').multiselect({
                    optionLabel: function(element) {
                        return $(element).html() + ' - ' + $(element).data('title');
                    },
                    numberDisplayed: 20
                });
                $('#ls_item_itemType').select2entity({dropdownParent: $('#ls_item_itemType').closest('div')});
                statementMde = render.mde($('#ls_item_fullStatement')[0]);
                notesMde = render.mde($('#ls_item_notes')[0]);
            }
        );
    }).on('hide.bs.modal', function(e){
        $('#ls_item_itemType').select2('destroy');

        if ('open' === $modal.data('mode')) {
            $.ajax({
                url: apx.path.lsitem_unlock.replace('ID', apx.mainDoc.currentItem.id),
                method: 'POST'
            });

            let id = apx.mainDoc.currentItem.id;
            if ("undefined" !== typeof apx.locks && "undefined" !== typeof apx.locks.mine && "undefined" !== typeof apx.locks.mine.items[id] && "number" === typeof apx.locks.mine.items[id].warning) {
                clearTimeout(apx.locks.mine.items[id].warning);
                apx.locks.mine.items[id].timeout = 0;
                if ("undefined" !== typeof apx.locks.mine.warnings[id] && $.isFunction(apx.locks.mine.warnings[id].close)) {
                    apx.locks.mine.warnings[id].close();
                }
            }
        }
        $modal.data('mode', 'close');
    }).on('hidden.bs.modal', function(e){
        $modal.find('.modal-body').html(apx.spinner.html("Loading Form"));
        if (null !== statementMde) {
            statementMde.toTextArea();
            statementMde = null;
            notesMde.toTextArea();
            notesMde = null;
        }
    });
    $modal.find('.btn-save').on('click', function(e){
        $modal.data('mode', 'save');
        apx.spinner.showModal("Updating item");
        statementMde.toTextArea();
        statementMde = null;
        notesMde.toTextArea();
        notesMde = null;
        $.ajax({
            url: apx.path.lsitem_edit.replace('ID', apx.mainDoc.currentItem.id),
            method: 'POST',
            data: $modal.find('form[name=ls_item]').serialize()
        }).done(function(data, textStatus, jqXHR){
            let id = apx.mainDoc.currentItem.id;
            if ("undefined" !== typeof apx.locks && "undefined" !== typeof apx.locks.mine && "undefined" !== typeof apx.locks.mine.items[id] && "number" === typeof apx.locks.mine.items[id].warning) {
                clearTimeout(apx.locks.mine.items[id].warning);
                apx.locks.mine.items[id].timeout = 0;
                if ("undefined" !== typeof apx.locks.mine.warnings[id] && $.isFunction(apx.locks.mine.warnings[id].close)) {
                    apx.locks.mine.warnings[id].close();
                }
            }

            apx.spinner.hideModal();
            $modal.modal('hide');

            // on successful edit, update the item
            let item = apx.mainDoc.currentItem;

            // first delete existing attributes (in case they were cleared)
            for (let key in item) {
                if (key !== "nodeType" && key !== "assocs" && key !== "setToParent") {
                    delete item[key];
                }
            }
            // then (re-)set attributes
            for (let key in data) {
                item[key] = data[key];
            }

            // then re-render the tree and re-activate the item
            apx.treeDoc1.ftRender1();
            apx.treeDoc1.activateCurrentItem();
        }).fail(function(jqXHR, textStatus, errorThrown){
            apx.spinner.hideModal();
            $modal.find('.modal-body').html(jqXHR.responseText);
            $('#ls_item_educationalAlignment').multiselect({
                optionLabel: function(element) {
                    return $(element).html() + ' - ' + $(element).data('title');
                },
                numberDisplayed: 20
            });
            statementMde = render.mde($('#ls_item_fullStatement')[0]);
            notesMde = render.mde($('#ls_item_notes')[0]);
        });
    });
};

/** Add a new child item, to the top level doc or to an item */
apx.edit.prepareAddNewChildModal = function() {
    function getPath() {
        let path;
        if (apx.mainDoc.currentItem === apx.mainDoc.doc) {
            path = apx.path.lsitem_new.replace('DOC', apx.lsDocId);
        } else {
            path = apx.path.lsitem_new.replace('DOC', apx.lsDocId).replace('PARENT', apx.mainDoc.currentItem.id);
        }

        // if we have an assocGroup other than default selected, add that to the path
        if (apx.mainDoc.currentAssocGroup !== null) {
            path += "/" + apx.mainDoc.currentAssocGroup;
        }

        return path;
    }

    let statementMde,
        notesMde
    ;
    let $modal = $('#addNewChildModal');
    $modal.find('.modal-body').html(apx.spinner.html("Loading Form"));
    $modal.on('shown.bs.modal', function(e){
        $modal.find('.modal-body').load(
            getPath(),
            null,
            function(responseText, textStatus, jqXHR){
                $('#ls_item_educationalAlignment').multiselect({
                    optionLabel: function(element) {
                        return $(element).html() + ' - ' + $(element).data('title');
                    },
                    numberDisplayed: 20
                });
                $('#ls_item_itemType').select2entity({dropdownParent: $('#ls_item_itemType').closest('div')});
                statementMde = render.mde($('#ls_item_fullStatement')[0]);
                notesMde = render.mde($('#ls_item_notes')[0]);
            }
        );
    }).on('hide.bs.modal', function(e){
        $('#ls_item_itemType').select2('destroy');
    }).on('hidden.bs.modal', function(e){
        $modal.find('.modal-body').html(apx.spinner.html("Loading Form"));
        if (null !== statementMde) {
            statementMde.toTextArea();
            statementMde = null;
            notesMde.toTextArea();
            notesMde = null;
        }
    });
    $modal.find('.btn-save').on('click', function(e) {
        apx.spinner.showModal("Creating item");
        statementMde.toTextArea();
        statementMde = null;
        notesMde.toTextArea();
        notesMde = null;
        $.ajax({
            url: getPath(),
            method: 'POST',
            data: $modal.find('form[name=ls_item]').serialize()
        }).done(function(data, textStatus, jqXHR) {
            apx.spinner.hideModal();
            $modal.modal('hide');

            apx.mainDoc.addNewItemData(data);

            // make sure the noItemsInstructions div is hidden
            $("#noItemsInstructions").hide();
        }).fail(function(jqXHR, textStatus, errorThrown){
            apx.spinner.hideModal();
            $modal.find('.modal-body').html(jqXHR.responseText);
            $('#ls_item_educationalAlignment').multiselect({
                optionLabel: function(element) {
                    return $(element).html() + ' - ' + $(element).data('title');
                },
                numberDisplayed: 20
            });
            statementMde = render.mde($('#ls_item_fullStatement')[0]);
            notesMde = render.mde($('#ls_item_notes')[0]);
        });
    });
};

/** Delete one or more items */
apx.edit.deleteItems = function(items) {
    let completed = 0;

    function itemDeleted() {
        // if we're done hide the spinner and re-render the tree
        ++completed;
        if (completed === items.length) {
            apx.spinner.hideModal();
            apx.treeDoc1.setCurrentItem({"item": apx.mainDoc.doc});
            apx.treeDoc1.ftRender1();
            apx.treeDoc1.showCurrentItem();
            apx.pushHistoryState();
        }
    }

    function deleteItemsInternal(items) {
        // show "Deleting" spinner
        apx.spinner.showModal("Deleting");

        let lsItems = null;
        for (let i = 0; i < items.length; ++i) {
            let item = items[i];

            if (item !== null) {
                // Check to see if the item exists in a different assocGroup than the assocGroup currently selected
                let itemExistsInAnotherGroup = false;
                let assocIdToDelete = null;

                // go through all the assocs for this item
                for (let j = 0; j < item.assocs.length; ++j) {
                    let a = item.assocs[j];
                    // when we find the ischildof...
                    if (a.type === "isChildOf" && a.inverse !== true) {
                        // then if it matches the currentAssocGroup...
                        if (a.groupId == apx.mainDoc.currentAssocGroup) {
                            // (Note that we want != here for assocGroup comparison so that null matches undefined)
                            // Record the association id, in case we need to delete this association
                            if (empty(lsItems)) {
                                lsItems = {};
                            }
                            lsItems[item.id] = {
                                "originalKey": "x", // not needed; this is legacy from old code
                                "deleteChildOf": {
                                    "assocId": a.id
                                }
                            };
                            assocIdToDelete = a.id;

                        // else this is an isChildOf association for a different group
                        } else {
                            itemExistsInAnotherGroup = true;
                        }
                    }
                }

                // if item exists in another group, use update_items service to delete the isChildOf association only
                if (itemExistsInAnotherGroup) {
                    if (empty(lsItems)) {
                        console.log("Possible delete error: lsItems is empty");
                        itemDeleted();

                    } else {
                        // delete the assoc first, then make the ajax call
                        apx.mainDoc.deleteAssociation(assocIdToDelete);

                        $.ajax({
                            url: apx.path.doctree_update_items.replace('ID', apx.lsDocId),
                            method: 'POST',
                            data: {"lsItems": lsItems}
                        }).done(function (data, textStatus, jqXHR) {
                            itemDeleted();

                        }).fail(function (jqXHR, textStatus, errorThrown) {
                            alert("An error occurred.");
                            // console.log(jqXHR.responseText);
                        });
                    }

                // else use delete service to delete item
                } else {
                    // delete all assocs for the item
                    for (let j = item.assocs.length-1; j >= 0; --j) {
                        let a = item.assocs[j];
                        apx.mainDoc.deleteAssociation(a.id);
                    }

                    // find the item in mainDoc.items
                    for (let j = 0; j < apx.mainDoc.items.length; ++j) {
                        if (apx.mainDoc.items[j] === item) {
                            // delete it from itemHash and itemIdHash, and splice it from the items array
                            delete apx.mainDoc.itemHash[item.identifier];
                            delete apx.mainDoc.itemIdHash[item.id];
                            apx.mainDoc.items.splice(j, 1);
                            break;
                        }
                    }

                    $.ajax({
                        // for now at least, we always send "1" in for the "CHILDREN" parameter
                        url: apx.path.lsitem_tree_delete.replace('ID', item.id).replace('CHILDREN', 1),
                        method: 'POST'
                    }).done(function (data, textStatus, jqXHR) {
                        itemDeleted();

                    }).fail(function (jqXHR, textStatus, errorThrown) {
                        alert("An error occurred.");
                        // console.log(jqXHR.responseText);
                    });
                }
            } else {
                ++completed;
            }
        }
    }

    // if items isn't an array, use current item
    if (!$.isArray(items)) {
        items = [apx.mainDoc.currentItem];
    }

    // make user confirm
    let modalId;
    if (items.length === 1) {
        if (items[0].ftNodeData.children.length > 0) {
            modalId = '#deleteItemAndChildrenModal';
        } else {
            modalId = '#deleteOneItemModal';
        }
    } else {
        // fill count of deleted items in to deleteMultipleItemsModalCount
        $("#deleteMultipleItemsModalCount").text(items.length);
        modalId = '#deleteMultipleItemsModal';
    }

    $(modalId).modal().one('click', '.btn-delete', function() {
        $(this).closest('.modal').modal('hide');
        deleteItemsInternal(items);
    });
};

/** Add an examplar for an item */
apx.edit.prepareExemplarModal = function() {
    let $exemplarModal = $('#addExemplarModal');
    $exemplarModal.on('show.bs.modal', function(e){
        let title = apx.mainDoc.getItemTitle(apx.mainDoc.currentItem);
        $("#addExemplarOriginTitle").html(title);
        $exemplarModal.find('.modal-body .errors').removeClass('alert').removeClass('alert-danger').html('');
    });
    $exemplarModal.find('.btn-save').on('click', function(e){
        let ajaxData = {
            exemplarUrl: $("#addExemplarFormUrl").val(),
            exemplarDescription: $("#addExemplarFormDescription").val(),
            associationType: "Exemplar"
        };

        if (ajaxData.exemplarUrl === "") {
            $exemplarModal.find('.modal-body .errors').addClass('alert').addClass('alert-danger').html("You must enter a URL to create an exemplar.");
            return;
        }

        if (ajaxData.exemplarUrl.length > 300) {
            $exemplarModal.find('.modal-body .errors').addClass('alert').addClass('alert-danger').html("The URL must be 300 characters or less.");
            return;
        }

        apx.spinner.showModal("Saving exemplar");

        // construct path for this association
        let path = apx.path.lsassociation_tree_new_exemplar;
        path = path.replace('ORIGIN_ID', apx.mainDoc.currentItem.id);

        $.ajax({
            url: path,
            method: 'POST',
            data: ajaxData
        }).done(function(data, textStatus, jqXHR) {
            apx.spinner.hideModal();
            $exemplarModal.modal('hide');

            // add the association
            apx.mainDoc.addAssociation({
                "id": data.id,
                "identifier": data.identifier,
                "originItem": apx.mainDoc.currentItem,
                "type": "exemplar",
                "dest": {
                    "doc": "-",
                    "uri": ajaxData.exemplarUrl
                }
                // Note that exemplars are currently not added to association groups
                //, "groupId": apx.mainDoc.currentAssocGroup
            });

            // clear form fields
            $("#addExemplarFormUrl").val("");
            $("#addExemplarFormDescription").val("");
            $exemplarModal.find('.modal-body .errors').removeClass('alert').removeClass('alert-danger').html('');

            // re-show current item
            apx.mainDoc.showCurrentItem();

        }).fail(function(jqXHR, textStatus, errorThrown){
            apx.spinner.hideModal();
            $exemplarModal.find('.modal-body .errors').addClass('alert').addClass('alert-danger').html(jqXHR.responseJSON.error.message);
        });
    });
};

/** Add an association */
apx.edit.prepareAssociateModal = function() {
    // add an option for each association type to the associationFormType select
    for (let i = 0; i < apx.assocTypes.length; ++i) {
        if (apx.assocTypes[i] !== "Exemplar" && apx.assocTypes[i] !== "Is Child Of") {
            $("#associationFormType").append('<option value="' + apx.assocTypes[i] + '">' + apx.assocTypes[i] + '</option>');
        }
    }

    // prepare switch direction button
    $("#lsAssociationSwitchDirection").on('click', function() {
        $("#lsAssociationDirection").toggleClass("lsAssociationDirectionSwitched");
    });

    let $associateModal = $('#associateModal');
    $associateModal.on('shown.bs.modal', function(e){
        let originItem = apx.edit.createAssociationNodes.droppedNode.data.ref;
        let destItem = apx.edit.createAssociationNodes.draggedNodes[0].data.ref;

        // show the origin and destination statements
        let destination = apx.mainDoc.getItemTitle(destItem);
        if (apx.edit.createAssociationNodes.draggedNodes.length > 1) {
            destination += " <b>+" + (apx.edit.createAssociationNodes.draggedNodes.length-1) + " additional item(s)</b>";
        }
        let origin = apx.mainDoc.getItemTitle(originItem);
        $("#lsAssociationDestinationDisplay").html(destination);
        $("#lsAssociationOriginDisplay").html(origin);

        // add association group menu if we have one and there's more than one item (the first item is always "default") in the menu
        let agMenu = $("#treeSideLeft").find(".assocGroupSelect");
        if (agMenu.find("option").length > 1) {
            agMenu = agMenu.clone();
            agMenu.attr("id", "associationFormGroup");
            $("#associationFormGroupHolder").html("").append(agMenu);
            $("#associationFormGroupHolderOuter").show();

            // if an assocGroup other than default is selected, select that group in the menu
            if (apx.mainDoc.currentAssocGroup != null) {
                $("#associationFormGroup").val(apx.mainDoc.currentAssocGroup);
            }
        }
    });

    // when save button is clicked, create the association(s)
    $associateModal.find('.btn-save').on('click', function(e) {
        // var ajaxData = $associateModal.find('form[name=ls_association_tree]').serialize();

        apx.spinner.showModal("Saving association(s)");

        // go through all the draggedNodes
        let completed = 0;
        for (let i = 0; i < apx.edit.createAssociationNodes.draggedNodes.length; ++i) {
            let ajaxData = {
                "type": $("#associationFormType").val()
            };

            // the "origin" refers to the node that's 'receiving' the association -- so this is the droppedNode
            // the "destination" refers to the node that's being associated with the origin node -- so this is the draggedNode
            let originItem = apx.edit.createAssociationNodes.droppedNode.data.ref;
            let destItem = apx.edit.createAssociationNodes.draggedNodes[i].data.ref;

            // ... that is, unless the user has clicked to switch directions, in which case we switch the items
            if ($("#lsAssociationDirection").hasClass("lsAssociationDirectionSwitched")) {
                let temp = originItem;
                originItem = destItem;
                destItem = temp;
            }

            if (originItem.doc.isExternalDoc()) {
                ajaxData.origin = {
                    "identifier": originItem.identifier,
                    "externalDoc": originItem.doc.doc.identifier
                };
            } else if (!empty(originItem.id)) {
                ajaxData.origin = {"id": originItem.id};
            } else {
                ajaxData.origin = {"identifier": originItem.identifier};
            }

            if (destItem.doc.isExternalDoc()) {
                ajaxData.dest = {
                    "identifier": destItem.identifier,
                    "externalDoc": destItem.doc.doc.identifier
                };
            } else if (!empty(destItem.id)) {
                ajaxData.dest = {"id": destItem.id};
            } else {
                ajaxData.dest = {"identifier": destItem.identifier};
            }

            // if an assocGroup is selected via associationFormGroup and isn't default, add it
            let agMenu = $("#associationFormGroup");
            if (agMenu.length > 0 && agMenu.val() !== "default") {
                ajaxData.assocGroup = agMenu.val();
            }

            $.ajax({
                url: apx.path.lsassociation_tree_new,
                method: 'POST',
                data: ajaxData,
                context: {
                    "origin": originItem,
                    "dest": destItem,
                    "type": ajaxData.type,
                    "assocGroup": ajaxData.assocGroup
                }
            }).done(function(assocId, textStatus, jqXHR) {
                // "this" will refer to context

                // increment completed counter
                ++completed;

                // add new assoc object and its inverse
                let type = apx.mainDoc.getAssociationTypeCondensed(this);
                let atts = {
                    "id": assocId,
                    "origin": {
                        "doc": this.origin.doc.doc.identifier,
                        "item": this.origin.identifier,
                        "uri": this.origin.uri
                    },
                    "type": type,
                    "dest": {
                        "doc": this.dest.doc.doc.identifier,
                        "item": this.dest.identifier,
                        "uri": this.dest.identifier
                    },
                    "groupId": this.assocGroup
                };
                let a = apx.mainDoc.addAssociation(atts);
                apx.mainDoc.addInverseAssociation(a);

                // if the origin item is currently showing in treeDoc1 and this wasn't a childOf assoc, show the association marker
                if (type !== "isChildOf") {
                    let oi = apx.treeDoc1.itemHash[this.origin.identifier];
                    if (!empty(oi) && !empty(oi.identifier)) {
                        $(apx.treeDoc1.getFtNode(oi, 1).li).find(".treeHasAssociation").show();
                    }
                }

                // note that the assocView is no longer fresh, so that if the user clicks to view the association view it will refresh.
                if (apx.viewMode.assocViewStatus !== "not_written") {
                    apx.viewMode.assocViewStatus = "stale";
                }

                // if all are completed, finish up
                if (completed === apx.edit.createAssociationNodes.draggedNodes.length) {
                    apx.spinner.hideModal();
                    $associateModal.modal('hide');

                    // clear createAssociationNodes
                    apx.edit.createAssociationNodes = null;
                }

                // we don't need to update the item details here, because that will happen if/when the user clicks the toggle button to show the item details

            }).fail(function(jqXHR, textStatus, errorThrown){
                apx.spinner.hideModal();
                alert("An error occurred when attempting to save the association.");
            });
        }

    });
};

apx.edit.deleteAssociation = function(assocId, callbackFn) {
    if (!confirm("Are you sure you want to remove this association? This can’t be undone.")) {
        return;
    }

    apx.spinner.showModal("Removing association");
    $.ajax({
        url: apx.path.lsassociation_remove.replace('ID', assocId),
        method: 'POST'
    }).done(function(data, textStatus, jqXHR){
        apx.spinner.hideModal();
        apx.edit.performDeleteAssociation(assocId, callbackFn);
    }).fail(function(jqXHR, textStatus, errorThrown){
        apx.spinner.hideModal();
        alert("An error occurred.");
    });
};

apx.edit.performDeleteAssociation = function(assocId, callbackFn) {
    if ("undefined" === typeof apx.mainDoc.assocIdHash[assocId]) {
        // call callbackFn if specified
        if (callbackFn != null) {
            callbackFn();
        }

        return;
    }

    let identifier = apx.mainDoc.assocIdHash[assocId].origin.item;

    // after deletion, delete the association from the data structure
    apx.mainDoc.deleteAssociation(assocId);

    // if the origin item is currently showing in treeDoc1, hide the association marker if necessary
    let oi = apx.mainDoc.itemHash[identifier];
    if (!empty(oi)) {
        let showAssociationIcon = false;
        for (let i = 0; i < oi.assocs.length; ++i) {
            let a = oi.assocs[i];
            if (a.type !== "isChildOf") {
                showAssociationIcon = true;
                break;
            }
        }
        $jq = $(apx.treeDoc1.getFtNode(apx.treeDoc1.itemHash[identifier], 1).li).find(".treeHasAssociation").first();
        if (showAssociationIcon) {
            $jq.show();
        } else {
            $jq.hide();
        }
    }

    // note that the assocView is no longer fresh, so that if the user clicks to view the association view it will refresh.
    if (apx.viewMode.assocViewStatus !== "not_written") {
        apx.viewMode.assocViewStatus = "stale";
    }

    if ('tree' === apx.viewMode.currentView) {
        apx.treeDoc1.ftRender1();
        apx.treeDoc1.activateCurrentItem();
        apx.mainDoc.showCurrentItem();
    }

    if ('assoc' === apx.viewMode.currentView) {
        apx.viewMode.showAssocView('refresh');
    }

    // then call callbackFn if specified
    if (callbackFn != null) {
        callbackFn();
    }
};


apx.edit.copyItems = function(draggedNodes, droppedNode, hitMode) {
    for (let j = 0; j < draggedNodes.length; ++j) {
        draggedNodes[j].copyTo(droppedNode, hitMode, function(n) {
            // temporarily add "copy" to the start of the key
            n.key = "copy-" + n.key;
        });
    }

    // now, after a few milliseconds to let the copyTo(s) complete...
    setTimeout(function() {
        // make sure droppedNode is expanded if hitMode is "over"
        if (hitMode === "over") {
            droppedNode.setExpanded(true);
            droppedNode.render();
        }

        // construct ajax call to insert the new item(s) and reorder their siblings
        let lsItems = {};
        // get siblings of the copy of the first dragged item (all the dragged items will be included in this siblings array)
        let siblings = apx.mainDoc.getFt(1).getNodeByKey("copy-" + draggedNodes[0].key).parent.children;
        for (let i = 0; i < siblings.length; ++i) {
            // get the key for this node
            let key = siblings[i].key;

            // start creating the object for the lsItems hash
            let o = {"originalKey": key};

            // if this is a new node...
            if (key.indexOf("copy-") === 0) {
                // get the copied item record
                let copiedItem = siblings[i].data.ref;

                // if we have an assocGroup other than default selected, add that
                if (apx.mainDoc.currentAssocGroup != null) {
                    o.assocGroup = apx.mainDoc.currentAssocGroup;
                }

                // if we're copying from the same document...
                if (apx.mainDoc.doc.id == apx.treeDoc2.doc.id) {
                    // If the *same* assocGroup is chosen on both sides, always create a new instance of the item
                    if (apx.mainDoc.currentAssocGroup == apx.treeDoc2.currentAssocGroup2) {
                        // set copyFromId flag so that updateItemAction will copy the item
                        o.copyFromId = copiedItem.id;
                        o.addCopyToTitle = "true";

                    // else *different* assocGroups are chosen on both sides, so:
                    } else {
                        // If the item already has an isChildOf association for the left-side assocGroup, create a new instance of the item
                        let assocs = apx.treeDoc2.getAssocsForItem(copiedItem, "isChildOf", apx.mainDoc.currentAssocGroup);
                        if (assocs.length > 0) {
                            // set copyFromId flag so that updateItemAction will copy the item
                            o.copyFromId = copiedItem.id;
                            o.addCopyToTitle = "true";

                        // Else the item does not have an isChildOf association for this assocGroup,
                        // so create a new isChildOf relationship for the assocGroup (as directed below), but do *not* create a new instance the item.
                        } else {
                            console.log("item doesn't exist");
                            // in this case we want to use copiedItem.id as the key for the object in the lsItems hash
                            key = copiedItem.id;

                            // TODO: in this case, it doesn't "copy" children of a "copied" folder...
                        }
                    }

                // else if different documents, but the other document is on this server...
                } else if (!copiedItem.doc.isExternalDoc()) {
                    // set copyFromId flag so that updateItemAction will copy the item
                    o.copyFromId = copiedItem.id;

                // else different documents, and the treeDoc2 is on a different server...
                } else {
                    // TODO: deal with copies from an external document??? In this case we would need to send in the full item, and we'd have to take care of copying children here
                    alert("You cannot currently copy an item from a document on another server.");
                    return;
                }

                // create a new childOf relationship regardless of whether or not we're actually creating a copy
                o.newChildOf = {
                    "sequenceNumber": (i + 1)
                };

                // set parentId and parentType
                // if parent is the document...
                if (apx.mainDoc.isDocNode(siblings[i].parent)) {
                    // note the docId, and the fact that it's a document
                    o.newChildOf.parentId = apx.mainDoc.doc.id;
                    o.newChildOf.parentType = "doc";

                // otherwise the parent is an item
                } else {
                    o.newChildOf.parentId = siblings[i].parent.data.ref.id;
                    o.newChildOf.parentType = "item";
                }

            // else it's a sibling of the new item, so just update the sequenceNumber
            } else {
                // here we want the key to be the item's lsItemId
                key = siblings[i].data.ref.id;

                // skip the item if it doesn't have an id (e.g. "orphaned items")
                if (empty(key)) {
                    continue;
                }

                o.updateChildOf = {
                    "assocId": siblings[i].data.childOfAssocId,
                    "sequenceNumber": (i + 1)
                };
            }

            // now add o to the lsItems hash with key
            lsItems[key] = o;
        }

        // ajax call to submit changes
        apx.spinner.showModal("Copying item(s)");
        $.ajax({
            url: apx.path.doctree_update_items.replace('ID', apx.lsDocId),
            method: 'POST',
            data: {"lsItems": lsItems}
        }).done(function(data, textStatus, jqXHR){
            // hide spinner
            apx.spinner.hideModal();
            apx.edit.updateItemsAjaxDone(data);

        }).fail(function(jqXHR, textStatus, errorThrown){
            apx.spinner.hideModal();
            alert("An error occurred.");
            console.log(jqXHR, textStatus, errorThrown);
        });
    }, 10);    // end of anonymous setTimeout function
};

apx.moveEnabled = false;
apx.edit.enableMove = function(cb) {
    apx.edit.moveEnabled = $(cb).is(':checked');
};

/** Move already-existing items in the tree structure */
apx.edit.moveItems = function(draggedNodes, droppedNode, hitMode) {
    // make sure droppedNode is expanded if hitMode is "over"
    if (hitMode === "over") {
        droppedNode.setExpanded(true);
        droppedNode.render();
    }

    // go through each of the draggedNodes, constructing a hash with items to update
    let lsItems = {};
    for (let j = 0; j < draggedNodes.length; ++j) {
        let draggedNode = draggedNodes[j];

        // note original parent
        let originalParent = draggedNode.parent;

        // move the item in the tree
        draggedNode.moveTo(droppedNode, hitMode);

        let item = draggedNode.data.ref;

        // initialize the lsItems object for this item
        lsItems[item.id] = {"originalKey": item.identifier};

        // delete the old childOf relationship for the draggedNode
        lsItems[item.id].deleteChildOf = {
            "assocId": draggedNode.data.childOfAssocId
        };

        // and create a newChildOf attribute. if parent is the document...
        if (apx.mainDoc.isDocNode(draggedNode.parent)) {
            // note the docId, and the fact that it's a document
            lsItems[item.id].newChildOf = {
                "parentId": apx.mainDoc.doc.id,
                "parentType": "doc"
            }
        } else {
            // otherwise the parent is an item
            lsItems[item.id].newChildOf = {
                "parentId": draggedNode.parent.data.ref.id,
                "parentType": "item"
            }
        }
        // (we'll fill in the sequenceNumber for newChildOf below)

        // note: the draggedNode's original parent may now have a "hole" in its children's sequenceNumbers,
        // but that's fine; they will still be in the right order
    }

    // now go through *all* siblings of the dragged node(s) (including the dragged nodes themselves)...
    let siblings = draggedNodes[0].parent.children;
    for (let i = 0; i < siblings.length; ++i) {
        let item = siblings[i].data.ref;

        // skip the item if it doesn't have an id (e.g. "orphaned items")
        if (empty(item.id)) {
            continue;
        }

        // if this isn't a draggedNode...
        if (!(item.id in lsItems)) {
            // initialize the lsItems object
            lsItems[item.id] = {"originalKey": item.identifier};

            // then we just have to update the sequenceNumber
            lsItems[item.id].updateChildOf = {
                "assocId": siblings[i].data.childOfAssocId,
                "sequenceNumber": (i + 1)
            };

        // else it's a draggedNode, so...
        } else {
            // set the proper sequence number for the newChildOf relationship
            lsItems[item.id].newChildOf.sequenceNumber = (i + 1);
        }

        // if we have an assocGroup other than default selected, add the assocGroup to the lsItems object
        if (apx.mainDoc.currentAssocGroup != null) {
            lsItems[item.id].assocGroup = apx.mainDoc.currentAssocGroup;
        }
    }

    // ajax call to submit changes
    apx.spinner.showModal("Reordering item(s)");
    $.ajax({
        url: apx.path.doctree_update_items.replace('ID', apx.lsDocId),
        method: 'POST',
        data: {"lsItems": lsItems}
    }).done(function(data, textStatus, jqXHR){
        apx.spinner.hideModal();
        apx.edit.updateItemsAjaxDone(data);

    }).fail(function(jqXHR, textStatus, errorThrown){
        apx.spinner.hideModal();
        alert("An error occurred.");
    });
};

apx.edit.updateItemsAjaxDone = function(data) {
    // remove stray tooltips
    setTimeout(function() {
        $('body').tooltip('hide');
        $('#treeView').tooltip('hide');
        $('#assocView').tooltip('hide');
    }, 1000);

    let copiedItem = false;
    for (let i = 0; i < data.length; ++i) {
        let o = data[i];
        let n = apx.mainDoc.getFt(1).getNodeByKey(o.originalKey+'');
        if (n === null) {
            console.log("couldn't get node for " + o.originalKey);
        } else {
            var item;

            // if this is a copied item...
            if (o.originalKey.indexOf("copy-") === 0) {
                copiedItem = true;

                // then if the copied item had children -- which will have also been copied -- we need to refresh the mainDoc entirely,
                // because we don't get back from the server any information about the copied children
                if (!empty(n.children) && n.children.length > 0) {
                    apx.mainDoc.refreshFromServer();
                    return;
                }

                item = apx.mainDoc.itemIdHash[o.lsItemId];

                // if the item was actually copied, make a copy of the item attached to the copied node and add it to mainDoc
                if (empty(item)) {
                    item = apx.mainDoc.addItem({
                        // the first three attributes come back from the server
                        "id": o.lsItemId,
                        "identifier": o.lsItemIdentifier,
                        "fstmt": o.fullStatement,
                        // the rest come from the original item
                        "hcs": n.data.ref.hcs,
                        "le": n.data.ref.le,
                        "astmt": n.data.ref.astmt,
                        "ck": n.data.ref.ck,
                        "cku": n.data.ref.cku,
                        "notes": n.data.ref.notes,
                        "lang": n.data.ref.lang,
                        "el": n.data.ref.el,
                        "itp": n.data.ref.itp
                    });
                }

            } else {
                item = n.data.ref;
            }

            // if we got back deleteChildOf, it's the assocId of the deleted association; delete it
            if (!empty(o.deleteChildOf)) {
                apx.mainDoc.deleteAssociation(o.deleteChildOf);
            }

            // if we got back sequenceNumber, we added or updated an isChildOf association; we should always get o.assocId as well
            if (!empty(o.sequenceNumber)) {
                let existingAssoc = apx.mainDoc.assocIdHash[o.assocId];
                if (empty(existingAssoc)) {
                    let atts = {
                        "id": o.assocId,
                        "seq": o.sequenceNumber*1,
                        "originItem": item,
                        "type": "isChildOf",
                        "destItem": n.parent.data.ref,  // parent item is the node's parent's ref
                        "groupId": apx.mainDoc.currentAssocGroup
                    };
                    let a = apx.mainDoc.addAssociation(atts);
                    apx.mainDoc.addInverseAssociation(a);

                } else {
                    existingAssoc.seq = o.sequenceNumber * 1;
                }
            }
        }
    }

    // re-render the tree
    apx.treeDoc1.ftRender1();
    // unless we just copied an item, re-activate the current item
    if (!copiedItem) {
        apx.treeDoc1.activateCurrentItem();
    }
};


/////////////////////////////////////////////////////
// ASSOCIATION GROUP EDITING
apx.edit.initializeManageAssocGroupButtons = function() {
    // initialize buttons in association group modal
    $('#manageAssocGroupsModal')
        .off('click', ".assocgroup-edit-btn").on('click', ".assocgroup-edit-btn", function() { apx.edit.editAssocGroup(this); })
        .off('click', ".assocgroup-delete-btn").on('click', ".assocgroup-delete-btn", function() { apx.edit.deleteAssocGroup(this); });
};

apx.edit.prepareAddAssocGroupModal = function() {
    let $addAssocGroupModal = $('#addAssocGroupModal');
    let $manageAssocGroupsModal = $("#manageAssocGroupsModal");
    $addAssocGroupModal.find('.modal-body').html(apx.spinner.html("Loading Form"));
    $addAssocGroupModal.on('show.bs.modal', function(e){
        $manageAssocGroupsModal.modal('hide');
    }).on('shown.bs.modal', function(e){
        $('#addAssocGroupModal').find('.modal-body').load(
            apx.path.lsdef_association_grouping_new,
            null,
            function(responseText, textStatus, jqXHR) {
                // select this document from the document select menu, then hide the menu
                $("#ls_def_association_grouping_lsDoc").val(apx.lsDocId);
                $("#ls_def_association_grouping_lsDoc").closest(".form-group").hide();
            }
        )
    }).on('hidden.bs.modal', function(e){
        $('#addAssocGroupModal').find('.modal-body').html(apx.spinner.html("Loading Form"));
    });
    $addAssocGroupModal.find('.btn-save').on('click', function(e) {
        apx.spinner.showModal("Creating item");
        $.ajax({
            url: apx.path.lsdef_association_grouping_new,
            method: 'POST',
            data: $addAssocGroupModal.find('form[name=ls_def_association_grouping]').serialize()
        }).done(function(data, textStatus, jqXHR) {
            // returned data will be the new item id

            apx.spinner.hideModal();

            // on successful add, add the item to the assocGroups list
            let newAssocGroupId = data;
            let ag = {
                "id": newAssocGroupId,
                "title": $("#ls_def_association_grouping_title").val(),
                "description": $("#ls_def_association_grouping_description").val(),
                "lsDocId": apx.mainDoc.doc.id
            };
            apx.mainDoc.assocGroups.push(ag);
            apx.mainDoc.assocGroupIdHash[ag.id] = ag;

            // and add it to the manage groups modal
            let html = '<tr data-assocgroupid="' + newAssocGroupId + '">';
            html += '<td>' + render.escaped(ag.title) + '</td>';
            html += '<td>';
            html += '<button class="assocgroup-edit-btn btn btn-default btn-xs pull-right">Edit</button>';
            html += '<button class="assocgroup-delete-btn btn btn-default btn-xs pull-right" style="margin-right:5px">Delete</button>';
            html += '<span class="assocgroup-description">' + render.escaped(ag.description) + '</span>';
            html += '</td>';
            html += '</tr>';
            $manageAssocGroupsModal.find("tbody").append(html);

            // re-render the select menu(s)
            apx.mainDoc.renderAssocGroupMenu($("#treeSideLeft").find(".assocGroupSelect"), 1);
            if (apx.mainDoc == apx.treeDoc2) {
                apx.mainDoc.renderAssocGroupMenu($("#treeSideRight").find(".assocGroupSelect"), 2);
            }

            // hide the add modal and show the manage modal
            $addAssocGroupModal.modal('hide');
            $manageAssocGroupsModal.modal('show');

        }).fail(function(jqXHR, textStatus, errorThrown){
            apx.spinner.hideModal();
            $addAssocGroupModal.find('.modal-body').html(jqXHR.responseText);
        });
    });

    // if you cancel the new assoc group modal, re-open the manage modal
    $addAssocGroupModal.find('.modal-footer .btn-default').on('click', function(e) {
        $("#manageAssocGroupsModal").modal('show');
    });
};

apx.edit.editAssocGroup = function(btn) {
    // get assocGroup to delete
    var assocGroupId = $(btn).closest("[data-assocgroupid]").attr("data-assocgroupid");

    // hide the manage modal
    $("#manageAssocGroupsModal").modal('hide');

    var $editAssocGroupModal = $('#editAssocGroupModal');
    $editAssocGroupModal.find('.modal-body').html(apx.spinner.html("Loading Form"));
    $editAssocGroupModal.modal('show').on('shown.bs.modal', function(e){
        $('#editAssocGroupModal').find('.modal-body').load(
            apx.path.lsdef_association_grouping_edit.replace('ID', assocGroupId),
            null,
            function(responseText, textStatus, jqXHR) {
                // select this document from the document select menu, then hide the menu
                $("#ls_def_association_grouping_lsDoc").val(apx.lsDocId);
                $("#ls_def_association_grouping_lsDoc").closest(".form-group").hide();
            }
        )
    }).on('hidden.bs.modal', function(e){
        $('#editAssocGroupModal').find('.modal-body').html(apx.spinner.html("Loading Form"));
    });
    $editAssocGroupModal.find('.btn-save').off().on('click', function(e){
        apx.spinner.showModal("Updating group");
        $.ajax({
            url: apx.path.lsdef_association_grouping_edit.replace('ID', assocGroupId),
            method: 'POST',
            data: $editAssocGroupModal.find('form[name=ls_def_association_grouping]').serialize()
        }).done(function(data, textStatus, jqXHR){
            apx.spinner.hideModal();
            // on successful edit, update the item...
            var title = $("#ls_def_association_grouping_title").val();
            var description = $("#ls_def_association_grouping_description").val();
            if (description == "") description = "—";

            // in the modal
            var $tr = $("tr[data-assocgroupid=" + assocGroupId + "]");
            $tr.find("td").first().html(title);
            $tr.find(".assocgroup-description").html(description);

            // and in the mainDoc assocGroups array
            apx.mainDoc.assocGroupIdHash[assocGroupId].title = title;
            apx.mainDoc.assocGroupIdHash[assocGroupId].description = description;

            // re-render the select menu(s)
            apx.mainDoc.renderAssocGroupMenu($("#treeSideLeft").find(".assocGroupSelect"), 1);
            if (apx.mainDoc == apx.treeDoc2) {
                apx.mainDoc.renderAssocGroupMenu($("#treeSideRight").find(".assocGroupSelect"), 2);
            }

            // hide assoc group edit modal; show manage modal
            $editAssocGroupModal.modal('hide');
            $("#manageAssocGroupsModal").modal('show');

        }).fail(function(jqXHR, textStatus, errorThrown){
            apx.spinner.hideModal();
            $editAssocGroupModal.find('.modal-body').html(jqXHR.responseText);
        });
    });

    // if you cancel the edit assoc group modal, re-open the manage modal
    $editAssocGroupModal.find('.modal-footer .btn-default').on('click', function(e) {
        $("#manageAssocGroupsModal").modal('show');
    });
};

apx.edit.deleteAssocGroup = function(btn) {
    // get assocGroup to delete
    var assocGroupId = $(btn).closest("[data-assocgroupid]").attr("data-assocgroupid");

    // hide the manage modal
    $("#manageAssocGroupsModal").modal('hide');

    // show confirmation modal
    $("#deleteAssocGroupModal").modal()
    .one('click', '.btn-delete', function() {
        $(this).closest('.modal').modal('hide');

        // show "Deleting" spinner
        apx.spinner.showModal("Deleting");

        $.ajax({
            url: apx.path.lsdef_association_grouping_tree_delete.replace('ID', assocGroupId),
            method: 'POST'
        }).done(function (data, textStatus, jqXHR) {
            // hide the spinner
            apx.spinner.hideModal();

            // remove from the assocGroups array/hash
            for (var i = 0; i < apx.mainDoc.assocGroups.length; ++i) {
                if (apx.mainDoc.assocGroups[i].id == assocGroupId) {
                    apx.mainDoc.assocGroups.splice(i, 1);
                    break;
                }
            }
            delete apx.mainDoc.assocGroupIdHash[assocGroupId];

            // re-render the assocGroup menu(s) (this will hide them if necessary)
            apx.mainDoc.renderAssocGroupMenu($("#treeSideLeft").find(".assocGroupSelect"), 1);
            if (apx.mainDoc == apx.treeDoc2) {
                apx.mainDoc.renderAssocGroupMenu($("#treeSideRight").find(".assocGroupSelect"), 2);
            }

            // remove from the manage modal, then reshow it
            $("tr[data-assocgroupid=" + assocGroupId + "]").remove();
            $("#manageAssocGroupsModal").modal('show');

        }).fail(function (jqXHR, textStatus, errorThrown) {
            alert(jqXHR.responseJSON.error.message);
            apx.spinner.hideModal();
            $("#manageAssocGroupsModal").modal('show');
        });
    }).one('hidden.bs.modal', function(e){
        $(this).off('click', '.btn-delete');
    });
};

/* global apx */
window.apx = window.apx||{};

/* global empty */

/////////////////////////////////////////////////////
// TREE VIEW / ASSOCIATIONS VIEW MODES
apx.viewMode = {};

apx.viewMode.initialView = "tree";
apx.viewMode.currentView = "tree";
apx.viewMode.lastViewButtonPushed = "tree";

apx.viewMode.showTreeView = function(context) {
    apx.viewMode.currentView = "tree";

    // if the user clicked the button to show this view, or clicked an item from the associations table
    if (context === "button" || context === "avTable") {
        // if the user clicked the button and the last view button pushed wasn't tree...
        if (context === "button" && apx.viewMode.lastViewButtonPushed !== "tree") {
            // then the user must have been in the assoc view, then clicked the button to go to the tree view, so push a history state
            apx.pushHistoryState();
        }
        // set viewMode.lastViewButtonPushed to "tree" (so if we got back to the tree view via clicking on an item from the assoc table, we "simulate" clicking the tree view button)
        apx.viewMode.lastViewButtonPushed = "tree";
    }

    // set buttons appropriately
    $(".view-btn").removeClass("btn-primary").blur();
    $("#displayTreeBtn").addClass("btn-primary").blur();

    // hide the assocView and show the treeView
    $(".main-view").hide();
    $("#treeView").show();
    apx.treeDoc1.ftRender1();
    apx.treeDoc1.activateCurrentItem();
};

apx.viewMode.avFilters = {
    "avShowChild": false,
    "avShowExact": false,
    "avShowExemplar": true,
    "avShowIsRelatedTo": true,
    "avShowPrecedes": true,
    "avShowReplacedBy": false,
    "avShowHasSkillLevel": false,
    "avShowIsPeerOf": false,
    "avShowIsPartOf": false,
    "groups": []
};
apx.viewMode.assocViewStatus = "not_written";
apx.viewMode.showAssocView = function(context) {
    // can't show the assocView until all docs have been loaded
    for (let identifier in apx.allDocs) {
        if (apx.allDocs[identifier] === "loading") {
            apx.spinner.showModal("Loading associated document(s)");
            setTimeout(function() { apx.viewMode.showAssocView(context); }, 1000);
            return;
        }
    }
    apx.spinner.hideModal();

    apx.viewMode.currentView = "assoc";

    // currentItem is always the doc in assocView
    apx.mainDoc.setCurrentItem({"item": apx.mainDoc.doc});

    // if we're refreshing the view
    if (context === "refresh") {
        // set viewMode.assocViewStatus to "stale" so we make sure to reload it
        apx.viewMode.assocViewStatus = "stale";

    // else if the user clicked the button to load this view
    } else if (context === "button") {
        // unless the user has now clicked the Associations button twice in a row, push a history state
        if (apx.viewMode.lastViewButtonPushed !== "assoc") {
            apx.pushHistoryState();
        }

        // note that this was the last button pushed
        apx.viewMode.lastViewButtonPushed = "assoc";
    }

    // if viewMode.assocViewStatus isn't "current", re-write the table
    if (apx.viewMode.assocViewStatus !== "current") {
        // destroy previous table if we already created it
        if (apx.viewMode.assocViewStatus !== "not_written") {
            $("#assocViewTable").DataTable().destroy();
        }

        // make sure viewMode.avFilters.groups is set up to use included groups
        let gft = [];
        for (let i = 0; i < apx.mainDoc.assocGroups.length; ++i) {
            let group = apx.mainDoc.assocGroups[i];
            if (!empty(apx.viewMode.avFilters.groups[group.id])) {
                gft[group.id] = apx.viewMode.avFilters.groups[group.id];
            } else {
                gft[group.id] = true;
            }
        }

        // add a value for the default group; item 0
        if (!empty(apx.viewMode.avFilters.groups[0])) {
            gft[0] = apx.viewMode.avFilters.groups[0];
        } else {
            gft[0] = true;
        }
        apx.viewMode.avFilters.groups = gft;

        function avGetItemCell(a, key) {
            // set default title
            let title;
            if (!empty(a[key].uri)) {
                title = a[key].uri;
            } else if (!empty(a[key].item)) {
                title = a[key].item;
            } else if (!empty(a[key].title)) {
                title = a[key].title;
            } else {
                title = key;
            }
            let doc = null;

            // for the dest of an exemplar, we just use .uri
            if ("dest" === key && a.type === "exemplar") {
                title = a[key].uri;
            } else if (!empty(apx.allDocs[a[key].item]) && typeof(apx.allDocs[a[key].item]) !== "string") {
                // else see if the "item" is actually a document
                title = "Document: " + apx.allDocs[a[key].item].doc.title;
            } else if (!empty(apx.allItemsHash[a[key].item])) {
                // else if we know about this item via allItemsHash...
                let destItem = apx.allItemsHash[a[key].item];
                title = apx.mainDoc.getItemTitle(destItem, false);
                doc = destItem.doc;
            } else {
                // else we don't (currently at least) know about this item...
                if (a[key].doc !== "?") {
                    // look for document in allDocs
                    doc = apx.allDocs[a[key].doc];

                    // if we tried to load this document and failed, note that
                    if (doc === "loaderror") {
                        title += " (document could not be loaded)";

                    // else if we know we're still in the process of loading that doc, note that
                    } else if (doc === "loading") {
                        title += " (loading document...)";

                    // else we have the doc -- this shouldn't normally happen, because if we know about the doc,
                    // we should have found the item in apx.allItemsHash above
                    } else if (typeof(doc) === "object") {
                        title += " (item not found in document)";
                    }
                }
            }

            // if item comes from another doc, note that
            if (!empty(doc) && typeof(doc) === "object" && doc !== apx.mainDoc) {
                let docTitle = doc.doc.title;
                if (docTitle.length > 30) {
                    docTitle = docTitle.substr(0, 35);
                    docTitle = docTitle.replace(/\w+$/, "");
                    docTitle += "…";
                }
                title += ' <span style="color:red">' + docTitle + '</span>';
            }

            return '<div data-association-id="' + a.id + '" data-association-identifier="' + a.identifier + '" data-association-item="' + key + '" class="assocViewTitle">'
                + title
                + '</div>'
            ;
        }

        // compose datatables data array
        let dataSet = [];
        for (let i = 0; i < apx.mainDoc.assocs.length; ++i) {
            let assoc = apx.mainDoc.assocs[i];

            // skip associations (probably inverse associations) from other docs
            if (assoc.assocDoc !== apx.mainDoc.doc.identifier) {
                continue;
            }

            // skip types if filters dictate
            switch (assoc.type) {
                case "isChildOf":
                    if (!apx.viewMode.avFilters.avShowChild) {
                        continue;
                    }
                    break;
                case "exactMatchOf":
                    if (!apx.viewMode.avFilters.avShowExact) {
                        continue;
                    }
                    break;
                case "exemplar":
                    if (!apx.viewMode.avFilters.avShowExemplar) {
                        continue;
                    }
                    break;
                case "isRelatedTo":
                    if (!apx.viewMode.avFilters.avShowIsRelatedTo) {
                        continue;
                    }
                    break;
                case "precedes":
                    if (!apx.viewMode.avFilters.avShowPrecedes) {
                        continue;
                    }
                    break;
                case "replacedBy":
                    if (!apx.viewMode.avFilters.avShowReplacedBy) {
                        continue;
                    }
                    break;
                case "hasSkillLevel":
                    if (!apx.viewMode.avFilters.avShowHasSkillLevel) {
                        continue;
                    }
                    break;
                case "isPeerOf":
                    if (!apx.viewMode.avFilters.avShowIsPeerOf) {
                        continue;
                    }
                    break;
                case "isPartOf":
                    if (!apx.viewMode.avFilters.avShowIsPartOf) {
                        continue;
                    }
                    break;
            }

            // skip groups if filters dictate
            if ("groupId" in assoc) {
                if (!apx.viewMode.avFilters.groups[assoc.groupId]) {
                    continue;
                }
            } else {
                if (!apx.viewMode.avFilters.groups[0]) {
                    continue;
                }
            }

            // determine groupForLinks
            let groupForLinks = "default";
            if ("groupId" in assoc) {
                groupForLinks = assoc.groupId;
            }

            // get text to show in origin and destination column
            let origin = avGetItemCell(assoc, "origin");
            let dest = avGetItemCell(assoc, "dest");

            // get type cell, with remove association button (only for editors)
            let type = apx.mainDoc.getAssociationTypePretty(assoc) + $("#associationRemoveBtn").html();

            // construct array for row
            let arr = [origin, type, dest];

            // add group to row array if we have any groups
            if (apx.mainDoc.assocGroups.length > 0) {
                if ("groupId" in assoc) {
                    arr.push(apx.mainDoc.assocGroupIdHash[assoc.groupId].title);
                } else {
                    arr.push("– Default –");
                }
            }

            // push row array onto dataSet array
            dataSet.push(arr);
        }

        // set up columns
        let columns = [
            { "title": "Origin", "className": "avTitleCell" },
            { "title": "Association Type", "className": "avTypeCell" },
            { "title": "Destination", "className": "avTitleCell" }
        ];
        // add group if we have any
        if (apx.mainDoc.assocGroups.length > 0) {
            columns.push({"title": "Association Group", "className": "avGroupCell"});
        }

        // populate the table
        $("#assocViewTable").DataTable({
            "data": dataSet,
            "columns": columns,
            "stateSave": true,
            "lengthMenu": [ [ 25, 100, 500, -1 ], [25, 100, 500, "All"]],
            "pageLength": 100,
            //"select": true
        });

        // add filters
        $("#assocViewTable_wrapper").find(".dataTables_length").prepend($("#assocViewTableFilters").html());

        // enable type filters
        for (let filter in apx.viewMode.avFilters) {
            $("#assocViewTable_wrapper").find("input[data-filter=" + filter + "]").prop("checked", apx.viewMode.avFilters[filter])
                .on('change', function() {
                    apx.viewMode.avFilters[$(this).attr("data-filter")] = $(this).is(":checked");
                    apx.viewMode.showAssocView("refresh");
                    // TODO: save this value in localStorage?
                });
        }

        // enable group filters if we have any groups
        if (apx.mainDoc.assocGroups.length > 0) {
            let $gf = $("#assocViewTable_wrapper").find(".assocViewTableGroupFilters");
            for (let groupId in apx.viewMode.avFilters.groups) {
                if (groupId != 0) {
                    $gf.append('<label class="avGroupFilter"><input type="checkbox" data-group-id="' + groupId + '"> ' + apx.mainDoc.assocGroupIdHash[groupId].title + '</label><br>');
                }
                $("#assocViewTable_wrapper").find(".avGroupFilter input[data-group-id=" + groupId + "]").prop("checked", apx.viewMode.avFilters.groups[groupId])
                    .on('change', function() {
                        apx.viewMode.avFilters.groups[$(this).attr("data-group-id")] = $(this).is(":checked");
                        apx.viewMode.showAssocView("refresh");
                        // TODO: save this value in localStorage?
                    });
            }
            $gf.css("display", "inline-block");
        }

        // enable remove buttons
        $("#assocViewTable_wrapper").find(".btn-remove-association").on('click', function(e) {
            e.preventDefault();
            let assocId = $(this).closest("tr").find("[data-association-id]").attr("data-association-id");
            console.log("delete " + assocId);

            apx.edit.deleteAssociation(assocId, function() {
                // refresh the table after deleting the association
                apx.viewMode.showAssocView("refresh");
            });
            return false;
        });

        // tooltips for items with titles
        $('#assocView').tooltip({
            selector: '.assocViewTitle',
            "title": function() { return $(this).html(); },
            "delay": { "show": 200, "hide": 100 },
            "placement": "top",
            "html": true,
            "container": "body"
        });

        // click on items to open them
        $(".assocViewTitle").on('click', function(e) {
            // if openAssociationItem returns true, it means that we opened an item in this document
            if (apx.mainDoc.openAssociationItem(this, true)) {
                // so switch to tree view mode
                apx.viewMode.showTreeView("avTable");
            }
        });

        apx.viewMode.assocViewStatus = "current";

    // end of code for writing table
    }

    // set mode toggle buttons appropriately
    $(".view-btn").removeClass("btn-primary").blur();
    $("#displayAssocBtn").addClass("btn-primary").blur();

    // hide the treeView and show the assocView
    $(".main-view").hide();
    $("#assocView").show();
};

apx.viewMode.showLogView = function(context) {
    apx.viewMode.currentView = "log";

    // if the user clicked the button to show this view, or clicked an item from the associations table
    if (context === "button") {
        // if the user clicked the button and the last view button pushed wasn't tree...
        if (context === "button" && apx.viewMode.lastViewButtonPushed !== "log") {
            // then the user must have been in another view, then clicked the button to go to this view, so push a history state
            apx.pushHistoryState();
        }
        // set viewMode.lastViewButtonPushed to "log"
        apx.viewMode.lastViewButtonPushed = "log";
    }

    // set buttons appropriately
    $(".view-btn").removeClass("btn-primary").blur();
    $("#displayLogBtn").addClass("btn-primary").blur();

    // hide the assocView and show the treeView
    $(".main-view").hide();
    $("#logView").show();

    $('#logViewExport').attr('href', apx.path.doc_revisions_export.replace('ID', apx.mainDoc.doc.id));

    if ($.fn.dataTable.isDataTable('#logTable')) {
        $('#logTable').DataTable().clear().ajax.reload();
    } else {
        $('#logTable').DataTable({
            ajax: apx.path.doc_revisions.replace('ID', apx.lsDocId),
            dataSrc: 'data',
            columns: [
                //{ data: 'rev' },
                {
                    data: 'changed_at',
                    render: function(data, type, row) {
                        if ("display" !== type && "filter" !== type) {
                            return data;
                        }

                        function addZero(num) {
                            return (num >=0 && num < 10) ? "0" + num : num + "";
                        }

                        let ts = new Date(data.replace(" ", "T").replace(/\..*$/, "Z"));
                        return [
                            [ts.getFullYear(), addZero(ts.getMonth() + 1), addZero(ts.getDate())].join('-'),
                            [addZero(ts.getHours()), addZero(ts.getMinutes()), addZero(ts.getSeconds())].join(':')
                        ].join(" ");
                    }
                },
                { data: 'description' },
                { data: 'username' }
            ],
            retrieve: true
        });
    }
};

////////////////////////////////////////////////
// "CHOOSER" MODE

apx.chooserMode = {};
apx.chooserMode.active = function() {
    // we're in chooser mode if "mode=chooser" is in the query string
    return (apx.query.mode === "chooser");
};

apx.chooserMode.initialize = function() {
    // add some margin to the body
    $("body").css("margin", "0 15px");

    // hide header, footer, docTitleRow, instructions, and some other things
    $("header").hide();
    $("footer").hide();
    $("#docTitleRow").hide();
    $("#tree1Instructions").hide();
    $("#treeRightSideMode").hide();
    $("#itemOptionsWrapper").hide();
    
    // PW: Added 12/8/2017 to compensate for updated "container toggles"
    $("#treeSideLeft").width("100%");
    $(".toggle-container-left").hide();
    $(".treeSideLeftInner").height("100%");
    $(".toggle-container-right").hide();
    // $(".treeSideRightInner").height("100%");
    $(".rightTreeSideRightInner").height("100%");
    // cancel window resizer
    $(window).off("resize");

    // unless we have "associations=true" in the query string, hide associations from the item details
    if (apx.query.associations !== "true") {
        $(".lsItemAssociations").hide();
    }

    // set treeSideLeft to class col-sm-12 instead of col-sm-6
    $("#treeSideLeft").removeClass("col-sm-6").addClass("chooserModeDocTree");

    // for treeSideRight, remove class col-sm6 and add class chooserModeItemDetails
    $("#treeSideRight").removeClass("col-sm-6").addClass("chooserModeItemDetails");

    // click event on chooserModeTreeSideRightBackground
    $("#chooserModeTreeSideRightBackground").on("click", function() { apx.chooserMode.hideDetails(); });

    // show and enable chooserModeButtons
    $("#chooserModeButtons").show();
    $("#chooserModeItemDetailsChooseBtn").on("click", function() { apx.chooserMode.choose(); });
    $("#chooserModeItemDetailsCloseDetailsBtn").on("click", function() { apx.chooserMode.hideDetails(); });
};

/** Enable item chooser buttons; this will be called each time an item is activated in the fancytree */
apx.chooserMode.itemClicked = function(node) {
    if (apx.query.mode === "chooser") {
        // remove previously-shown interface if there
        $("#chooserModeShowForChoosing").remove();

        // add new interface
        let html = '<div id="chooserModeShowForChoosing" style="position:fixed; z-index:10; top:5px; right:1px; border:2px solid #444; border-radius:5px; background-color:#eee; padding:10px; width:350px;">';
        html += '<span id="chooserModeShowForChoosingClose" class="glyphicon glyphicon-remove" title="Close" style="float:right; margin-left:10px; cursor:pointer;"></span>'
        // full item title
        html += apx.mainDoc.getItemTitle(node.data.ref, true);
        html += '<div style="text-align:right; margin-top:10px">'
        // buttons
        html += '<button class="chooserModeShowDetailsBtn btn btn-default btn-sm">Show Details</button>';
        html += '&nbsp;&nbsp;';
        html += '<button class="chooserModeChooseBtn btn btn-primary btn-sm">Choose</button>';
        html += '</div>';
        html += '</div>';
        $('body').append(html);

        // enable buggons
        $("#chooserModeShowForChoosing").click(function () {
            console.log("clicked");
            $("#chooserModeShowForChoosing").remove();
        });

        $(".chooserModeShowDetailsBtn").on("click", function () {
            console.log("details button clicked");
            apx.chooserMode.showDetails();
        });

        $(".chooserModeChooseBtn").on("click", function () {
            console.log("chooser button clicked");
            apx.chooserMode.choose();
        });
    }
};

/** User clicked to show details for an item */
apx.chooserMode.showDetails = function() {
    $("#chooserModeTreeSideRightBackground").show();
    $("#treeSideRight").animate({"right": "10px"}, 200);

    // remove stray tooltips
    setTimeout(function() {
        $('body').tooltip('hide');
        $('#treeView').tooltip('hide');
        $('#assocView').tooltip('hide');
    }, 100);
};

/** Hide details */
apx.chooserMode.hideDetails = function() {
    $("#chooserModeTreeSideRightBackground").hide();
    $("#treeSideRight").animate({"right": "-600px"}, 200);
};

/** Item is chosen... */
apx.chooserMode.choose = function() {
    // compose data to send back about chosen item
    let i = apx.mainDoc.currentItem;
    let data = {
        "item": {
            "identifier": i.identifier,
            "saltId": i.id,
            "fullStatement": i.fstmt,
            "abbreviatedStatement": i.astmt,
            "humanCodingScheme": i.hcs,
            "listEnumInSource": i.le,
            "conceptKeywords": i.ck,
            "conceptKeywordsURI": i.cku,
            "notes": i.notes,
            "language": i.lang,
            "educationalAlignment": i.el,
            "itemType": i.itp,
            "lastChangeDateTime": i.mod
        }
    };

    // append a token if provided
    if (!empty(apx.query.choosercallbacktoken)) {
        data.token = apx.query.choosercallbacktoken;
    }

    console.log(data);

    apx.spinner.showModal("Item chosen");

    // if a callback url is given in the query string, send the chosen item back to that url
    if (!empty(apx.query.choosercallbackurl)) {
        let url = apx.query.choosercallbackurl + "?data=" + encodeURIComponent(JSON.stringify(data));
        window.location = url;
        /*
        $.ajax({
            url: apx.query.choosercallbackurl,
            method: 'GET',
            data: data
        }).done(function(data, textStatus, jqXHR) {
            console.log("OpenSALT item chooser callback function executed.");
            apx.spinner.hideModal();

        }).fail(function(jqXHR, textStatus, errorThrown){
            apx.spinner.hideModal();
            console.log(errorThrown);
            alert("Error submitting chosen item.");
        });
        */

        return;

    // else if a callback function is given, try to call it
    } else if (!empty(apx.query.choosercallbackfn)) {
        try {
            apx.query.choosercallbackfn(data);
        } catch(e) {
            apx.spinner.hideModal();
            console.log(e);
            alert("Callback function “" + apx.query.choosercallbackfn + "” did not execute.");
        }
        return;
    }

    apx.spinner.hideModal();
    alert("Item chosen: " + itemData.fullStatement + "\n\nTo send items to a callback URL or function, provide a “choosercallbackurl” or “choosercallbackfn” in the query string.");
};
/* global apx */
window.apx = window.apx||{};

/* global ApxDocument */
/* global render */

///////////////////////////////////////////////////////////////////////////////
// UTILITY FUNCTIONS

/** Checks whether a value is empty, defined as null or undefined or "".
 *  Note that 0 is defined as not empty.
 *  @param {*} val - value to check
 *  @returns {boolean}
 */
function empty(val) {
    // note that we need === because (0 == "") evaluates to true
    return ('undefined' === typeof(val) || null === val || "" === val);
}

/** Get the value of an object property,
 *  checking first to see if the object exists, and where the property may be nested several layers deep
 *  argument 0 should be the top-level object we're checking
 *  arguments 1... are keys to check
 *
 *  SHORTCUT: op()
 *
 *  @returns {*}
 */
function objectProperty() {
    let o = arguments[0];
    // go through keys in the arguments
    for (let i = 1; i < arguments.length; ++i) {
        // if o is empty or is not an object, we can't get any property, so return undefined
        if (empty(o) || typeof(o) !== "object") {
            return undefined;
        }
        // get the next level down, which might be another object to check a property of (in which case we'll loop again)
        // or might be the final value to return, which could itself be an object (in which case the loop will end here)
        o = o[arguments[i]];
    }
    // if we get here return whatever we came up with.  It might be an object or a scalar
    return o;
}
// shortcut
var op = objectProperty;

//////////////////////////////////////////////////
// INITIALIZE WEB APP

apx.initialize = function() {
    // prepare edit modals/functions
    apx.edit.prepareDocEditModal();
    apx.edit.prepareDocDeleteModal();
    apx.edit.prepareItemEditModal();
    apx.edit.prepareAddNewChildModal();
    apx.edit.prepareExemplarModal();
    apx.edit.prepareAssociateModal();

    // prepare assocGroup modals/functions
    apx.edit.prepareAddAssocGroupModal();
    apx.edit.initializeManageAssocGroupButtons();

    apx.markLogsAsRead();
    apx.copyFramework.init();

    $('#treeView').tooltip({
        'selector': '.fancytree-title',
        // "content": content,  // this is for popover
        "title": function() {
            let node = $.ui.fancytree.getNode(this);
            return  apx.treeDoc1.tooltipContent(node);
        },
        "delay": { "show": 200, "hide": 100 },
        "placement": "top",
        "html": true,
        "container": "body",
        "trigger": "hover"   // this is for popover
    });

    // right-side buttongroup
    $("#rightSideItemDetailsBtn").on('click', function() { apx.setRightSideMode("itemDetails"); });
    $("#rightSideCopyItemsBtn").on('click', function() { apx.setRightSideMode("copyItem"); });
    $("#rightSideCreateAssociationsBtn").on('click', function() { apx.setRightSideMode("addAssociation"); });

    let treeSideLeft = $("#treeSideLeft");
    let treeSideRight = $("#treeSideRight");

    // Tree checkboxes/menus
    treeSideLeft.find(".treeCheckboxControlBtn").on('click', function(e) { apx.treeDoc1.treeCheckboxToggleAll(null, 1); e.stopPropagation(); });
    treeSideLeft.find(".treeCheckboxMenuItem").on('click', function() { apx.treeDoc1.treeCheckboxMenuItemSelected($(this), 1); });
    treeSideRight.find(".treeCheckboxControlBtn").on('click', function(e) { apx.treeDoc2.treeCheckboxToggleAll(null, 2); e.stopPropagation(); });
    treeSideRight.find(".treeCheckboxMenuItem").on('click', function() { apx.treeDoc2.treeCheckboxMenuItemSelected($(this), 2); });

    // popovers on export modal
    $('#exportModal').find('[data-toggle="popover"]').popover();

    // change event on assocGroup menus
    treeSideLeft.find(".assocGroupSelect").off().on('change', function() { apx.treeDoc1.assocGroupSelected(this, 1); });
    treeSideRight.find(".assocGroupSelect").off().on('change', function() { apx.treeDoc2.assocGroupSelected(this, 2); });

    // links/buttons on item info panel
    // enable more info link
    $(".lsItemDetailsMoreInfoLink a").on('click', function() { apx.toggleMoreInfo(); });

    // enable deleteItem button
    $("#deleteItemBtn").on('click', apx.edit.deleteItems);

    // enable toggleFolder button
    $("#toggleFolderBtn").on('click', function() { apx.treeDoc1.toggleFolders(); } );

    // doc view/tree view buttongroup
    $("#displayTreeBtn").on('click', function() { apx.viewMode.showTreeView("button"); });
    $("#displayAssocBtn").on('click', function() { apx.viewMode.showAssocView("button"); });
    $("#displayLogBtn").on('click', function() { apx.viewMode.showLogView("button"); });

    // implement enableMoveCheckbox
    $("#enableMoveCheckbox").on('click', function() { apx.edit.enableMove(this); });

    // make sure initialAssocGroup is a number if it's not null
    if (!empty(apx.initialAssocGroup)) {
        apx.initialAssocGroup *= 1;
    }

    // parse query string
    apx.query = {};
    let arr = document.location.search.substr(1).split("&");
    for (let i = 0; i < arr.length; ++i) {
        let line = arr[i].split("=");
        apx.query[line[0]] = line[1];
    }

    // if we're in chooserMode, initialize
    if (apx.chooserMode.active()) {
        apx.chooserMode.initialize();
    } else {
        // else show docTitleRow, header, and footer
        $("header, #docTitleRow, footer").show();
    }

    ///////////////////////////////////////////////////////////////////////////////
    // MAINDOC

    // lsDocId could be an integer, in which case it's a SALT database ID; or we could be loading by url
    if (apx.lsDocId === 'url') {
        // if we're loading by url, the url should be in the search string, i.e. "url=http://example.com"
        apx.mainDoc = new ApxDocument({"url": decodeURIComponent(apx.query.url)});
    } else {
        apx.mainDoc = new ApxDocument({"id": apx.lsDocId});
    }

    // establish and load the main document -- apx.mainDoc
    apx.spinner.showModal("Loading document");
    apx.mainDoc.load(function() {
        apx.spinner.hideModal();

        // show the treeView div now that the document has been loaded
        $("#treeView").show();

        // fill in document title, in case we loaded from url
        $("#docTitle").html(apx.mainDoc.doc.title);
        window.document.title = apx.mainDoc.doc.title;

        // Prepare menus for choosing documents on each side (we have to do this after we've gotten mainDoc.associatedDocs)
        apx.prepareDocumentMenus();

        // go through each provided "associatedDoc"
        for (let identifier in apx.mainDoc.associatedDocs) {
            let ed = apx.mainDoc.associatedDocs[identifier];
            // and start loading now any associatedDocs that have the "autoLoad" flag set to "true" (unless we've already loaded it)
            // we have to do this because associations to items in these docs don't specify the doc id
            if (ed.autoLoad === "true" && !(identifier in apx.allDocs)) {
                console.log("loading doc " + ed.title);
                apx.allDocs[identifier] = "loading";
                new ApxDocument({"identifier": identifier}).load();
            }
        }

        // find any other docs referenced by associations in mainDoc
        apx.mainDoc.findAssociatedDocs();

        // if we got an initialLsItemId, set it (the document will be selected by default)
        if (!empty(apx.initialLsItemId)) {
            let item = apx.mainDoc.itemIdHash[apx.initialLsItemId];
            if (!empty(item)) {
                apx.mainDoc.setCurrentItem({"identifier": item.identifier});

                // If an item is initially selected, get appropriate initialAssocGroup
                // first get all assocGroups for isChildOf relationships for this item
                let assocGroups = apx.mainDoc.getAssocGroupsForItem(apx.mainDoc.currentItem, "isChildOf");

                // if initialAssocGroup is empty (null, meaning the default group) OR it isn't one of the available isChildOf relationships for this item...
                if (empty(apx.initialAssocGroup) || $.inArray(apx.initialAssocGroup, assocGroups) === -1) {
                    // then if the item has no isChildOf relationship or has an isChildOf relationship for the default group, use default
                    if (assocGroups.length === 0 || $.inArray(null, assocGroups) > -1) {
                        apx.initialAssocGroup = null;

                    // else use the first-listed assocGroup
                    } else {
                        apx.initialAssocGroup = assocGroups[0];
                    }
                }
            }
        }

        // set the initialAssocGroup
        apx.mainDoc.setCurrentAssocGroup(apx.initialAssocGroup);

        // Now, by default we show mainDoc's item tree on the left side, so initialize treeDoc1 now
        apx.treeDoc1 = apx.mainDoc;
        apx.treeDocLoadCallback1();

        // if the url ends with "av", toggle to association view
        if (window.location.href.search(/\/av$/) > -1) {
            apx.viewMode.showAssocView("pageLoaded");
            apx.viewMode.lastViewButtonPushed = "assoc";
            apx.viewMode.initialView = "assocView";
        } else if (window.location.href.search(/\/lv$/) > -1) {
            apx.viewMode.showLogView("pageLoaded");
            apx.viewMode.lastViewButtonPushed = "log";
            apx.viewMode.initialView = "logView";
        } else {
            apx.viewMode.initialView = "treeView";
        }
    });

    /** Sometimes we need to refresh the mainDoc entirely from the server... */
    apx.mainDoc.refreshFromServer = function() {
        apx.spinner.showModal("Refreshing document");
        let currentItemIdentifier = apx.mainDoc.currentItem.identifier;
        let currentAssocGroup = apx.mainDoc.currentAssocGroup;
        apx.mainDoc.load(function() {
            // if we're showing mainDoc on the left side, refresh it now
            if (apx.mainDoc === apx.treeDoc1) {
                apx.treeDoc1.setCurrentItem({"identifier": currentItemIdentifier});
                apx.treeDoc1.setCurrentAssocGroup(currentAssocGroup);
                apx.treeDoc1.ftRender1();
                apx.treeDoc1.activateCurrentItem();
                apx.treeDoc1.showCurrentItem();
            }
            apx.spinner.hideModal();
        });
    };

    if (window.firebase) {
        apx.initializeFirebase();
    }
};

//////////////////////////////////////////////////////
/**
 * Using Firebase to synchronize changes
 */
apx.initializeFirebase = function() {
    window.firebase.initializeApp(window.firebaseConfig);
    let notificationsRef = window.firebase.database()
        .ref('/' + apx.firebasePrefix + '/doc/' + apx.lsDocId + '/notification')
        .orderByChild('at')
        .startAt(apx.startTime);
    notificationsRef.on('child_added', function(snapshot) {
        apx.notifications.notify(snapshot.val());
    });

    let enabled = apx.notifications.isEnabled();
    $('#notifications-switch-location').html('<a id="notifications-switch" href="#" class="btn btn-lg"><i class="material-icons">notifications'+(enabled?'':'_off')+'</i></a>');
    $('#notifications-switch').on('click', apx.notifications.toggle);
    apx.notifications.enableMonitor();

    $.each(apx.locks.docs, function (id, tm) {
        if ("number" !== typeof tm || tm < apx.startTime) {
            return;
        }
        apx.locks.docs[id] = setTimeout(function (id) {
            delete apx.locks.docs[id];
            let changes = {};
            changes[id] = 'x';
            apx.notifications.notify({
                msgId: 'LockTimeout',
                msg: 'The document is no longer being edited',
                at: (new Date()).getTime(),
                by: '',
                changes: {
                    'doc-ul': changes
                }
            });
        }, tm - apx.startTime, id);
    });
    $.each(apx.locks.items, function (id, tm) {
        if ("number" !== typeof tm || tm < apx.startTime) {
            return;
        }
        apx.locks.items[id] = setTimeout(function (id) {
            delete apx.locks.items[id];
            let changes = {};
            changes[id] = 'x';
            let item;
            if ("undefined" === typeof apx.mainDoc.itemIdHash[id]) {
                item = apx.mainDoc.itemIdHash[id].fstmt;
            } else {
                item = "unknown";
            }
            let itemName = render.inline(item.substr(0, 60));
            apx.notifications.notify({
                msgId: 'LockTimeout',
                msg: 'The item "' + itemName + '" is no longer being edited',
                at: (new Date()).getTime(),
                by: '',
                changes: {
                    'item-ul': changes
                }
            });
        }, tm - apx.startTime, id);
    });
    apx.locks.mine = {
        docs: {},
        items: {},
        warnings: {}
    };
};

apx.refreshPage = function () {
    if ("tree" === apx.viewMode.currentView) {
        apx.treeDoc1.ftRender1();
        apx.treeDoc1.activateCurrentItem();
        apx.mainDoc.showCurrentItem();
    }

    if ('assoc' === apx.viewMode.currentView) {
        apx.viewMode.showAssocView('refresh');
    }
};

apx.notifications = apx.notifications||{};
apx.notifyCheck = apx.notifyCheck||{};

$.notifyDefaults({
    type: 'info',
    mouse_over: 'pause',
    z_index: 1075,
    url_target: '_self'
});

apx.notifications.isEnabled = function() {
    let cookie = document.cookie.match(new RegExp('(^| )n=([^;]+)'));
    if (cookie) {
        if ("n" === cookie[2]) {
            return false;
        }
    }

    return true;
};

apx.notifications.enableMonitor = function() {
    let current = apx.notifications.isEnabled();

    setInterval(function () {
        if (current !== apx.notifications.isEnabled()) {
            current = !current;
            apx.notifications.displayToggle(current);
        }
    }, 500);
};

apx.notifications.displayToggle = function(isEnabled) {
    let ns = $('#notifications-switch');

    if (isEnabled) {
        ns.find('i').html('notifications');
    } else {
        ns.find('i').html('notifications_off');
    }
};

apx.notifications.toggle = function(e) {
    e.stopPropagation();
    e.preventDefault();

    let enabled = apx.notifications.isEnabled();
    enabled = !enabled;

    if (enabled) {
        document.cookie = "n=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/";
    } else {
        document.cookie = "n=n; path=/";
    }

    apx.notifications.displayToggle(enabled);
};

$.extend(apx.notifications, {
    'assoc-a': function (list) {
        $.each(list, function(id, identifier) {
            apx.notifications.loadAssociation(id);
        });
    },

    'assoc-u': function (list) {
        let load = function(data) {
            let a = apx.mainDoc.assocIdHash[data.id];
            // most common (only?) change is sequence number
            if ("undefined" !== typeof a) {
                a.seq = data.seq;
                a.mod = data.mod;
            }

            apx.refreshPage();
        };

        $.each(list, function(id, identifier) {
            if ("undefined" !== apx.notifications.updates['assocs'][id]) {
                load(apx.notifications.updates['assocs'][id]);
            } else {
                $.ajax({
                    url: apx.path.doc_tree_association_json.replace('ID', id),
                    method: 'GET'
                }).done(function (data, textStatus, jqXHR) {
                    load(data);
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    // Ignore for now
                });
            }
        });
    },

    'assoc-d': function (list) {
        $.each(list, function(id, identifier) {
            apx.edit.performDeleteAssociation(id);
        });

        apx.refreshPage();
    },

    'item-l': function(list, msg) {
        if ("undefined" === typeof apx.locks) {
            // If locks are disabled then ignore
            return;
        }

        let timeout = 300000;
        let warningTime = 60000;

        $.each(list, function(id, identifier) {
            if (msg.by === apx.me) {
                apx.locks['items'][id] = false;

                let warning = setTimeout(function (id) {
                    let item;
                    if ("undefined" !== typeof apx.mainDoc.itemIdHash[id]) {
                        item = apx.mainDoc.itemIdHash[id].fstmt;
                    } else {
                        item = "unknown";
                    }
                    let title = render.inline(item.substr(0, 60));

                    apx.locks.mine.warnings[id] = apx.notifications.notify({
                        msgId: 'LockTimeoutWarning',
                        msg: 'Your lock for item "' + title + '" is about to expire<br/>&nbsp;&nbsp;&nbsp;&nbsp;<strong>Click here to extend the lock</strong>',
                        at: (new Date()).getTime(),
                        by: '',
                        changes: {
                        },
                        delay: warningTime,
                        msgType: 'warning',
                        icon: 'glyphicon glyphicon-warning-sign',
                        url: 'javascript:apx.notifications.relockItem(' + id + ');',
                        onClose: function(e) {
                            if (0 === apx.locks.mine.items[id].timeout) {
                                return;
                            }
                            if (apx.locks.mine.items[id].timeout > (new Date()).getTime()) {
                                return;
                            }

                            delete apx.locks.items[id];

                            $('#editItemModal').find('.modal-footer .btn-save').hide();

                            let changes = {};
                            changes[id] = 'x';
                            apx.notifications.notify({
                                msgId: 'LockTimeout',
                                msg: 'Your lock for item "' + title + '" has expired',
                                at: (new Date()).getTime(),
                                by: '',
                                changes: {
                                    'item-ul': changes
                                },
                                delay: 0,
                                msgType: 'danger',
                                icon: 'glyphicon glyphicon-alert'
                            });
                        }
                    });
                }, timeout - warningTime, id);

                apx.locks.mine.items[id] = {
                    warning: warning,
                    timeout: msg.at + timeout + apx.timeDiff - 2000
                };
            } else {
                if ("number" === typeof apx.locks.items[id]) {
                    // remove any existing timer
                    clearTimeout(apx.locks.items[id]);
                }

                apx.locks.items[id] = setTimeout(function (id) {
                    delete apx.locks.items[id];

                    let item;
                    if ("undefined" !== typeof apx.mainDoc.itemIdHash[id]) {
                        item = apx.mainDoc.itemIdHash[id].fstmt;
                    } else {
                        item = "unknown";
                    }
                    let title = render.inline(item.substr(0, 60));

                    let changes = {};
                    changes[id] = 'x';
                    apx.notifications.notify({
                        msgId: 'LockTimeout',
                        msg: 'The item "' + title + '" is no longer being edited',
                        at: (new Date()).getTime(),
                        by: '',
                        changes: {
                            'item-ul': changes
                        }
                    });
                }, timeout, id);
            }
        });

        apx.refreshPage();
    },

    'item-ul': function(list) {
        if ("undefined" === typeof apx.locks) {
            // If locks are disabled then ignore
            return;
        }

        $.each(list, function(id, identifier) {
            if ("number" === typeof apx.locks['items'][id]) {
                clearTimeout(apx.locks['items'][id]);
                delete apx.locks['items'][id];
            }
            if ("undefined" !== typeof apx.locks.mine.items[id] && "number" === typeof apx.locks.mine.items[id].warning) {
                clearTimeout(apx.locks.mine.items[id].warning);
                apx.locks.mine.items[id].timeout = 0;
                if ("undefined" !== typeof apx.locks.mine.warnings[id] && $.isFunction(apx.locks.mine.warnings[id].close)) {
                    apx.locks.mine.warnings[id].close();
                }
            }
        });

        apx.refreshPage();
    },

    relockItem: function(id) {
        // set new lock first
        apx.locks.mine.items[id].timeout = (new Date()).getTime() + 5000;
        apx.locks.mine.warnings[id].close();
        $.ajax({
            url: apx.path.lsitem_lock.replace('ID', id),
            method: 'POST'
        }).done(function (data, textStatus, jqXHR) {
        });
    },

    'item-a': function(list) {
        $.each(list, function(id, identifier) {
            apx.notifications.loadItem(id);
        });
    },

    'item-u': function(list) {
        $.each(list, function(id, identifier) {
            $.ajax({
                url: apx.path.lsitem_tree_json.replace('ID', id),
                method: 'GET'
            }).done(function(data, textStatus, jqXHR){
                let item = apx.mainDoc.itemIdHash[id];

                // first delete existing attributes (in case they were cleared)
                for (let key in item) {
                    if (key !== "nodeType" && key !== "assocs" && key !== "setToParent") {
                        delete item[key];
                    }
                }
                // then (re-)set attributes
                for (let key in data) {
                    item[key] = data[key];
                }

                apx.refreshPage();
            }).fail(function(jqXHR, textStatus, errorThrown){
                // Ignore for now
            });
        });
    },

    'item-d': function(list) {
        $.each(list, function(id, identifier) {
            let item = apx.mainDoc.itemIdHash[id];

            if ("object" === typeof item.assocs) {
                $.each(item.assocs, function (i, assoc) {
                    if (true !== assoc.inverse) {
                        apx.edit.performDeleteAssociation(assoc.id);
                    }
                });
            }

            // find the item in mainDoc.items
            for (let i = 0; i < apx.mainDoc.items.length; ++i) {
                if (apx.mainDoc.items[i] === item) {
                    // delete it from itemHash and itemIdHash, and splice it from the items array
                    delete apx.mainDoc.itemHash[item.identifier];
                    delete apx.mainDoc.itemIdHash[item.id];
                    apx.mainDoc.items.splice(i, 1);
                    break;
                }
            }
        });

        apx.refreshPage();
    },

    'doc-l': function(list, msg) {
        if ("undefined" === typeof apx.locks) {
            // If locks are disabled then ignore
            return;
        }

        let timeout = 300000;
        let warningTime = 60000;

        $.each(list, function(id, identifier) {
            if (apx.lsDocId.toString() !== id.toString()) {
                return;
            }

            if (msg.by === apx.me) {
                apx.locks['docs'][id] = false;

                let warning = setTimeout(function (id) {
                    apx.locks.mine.warnings[id] = apx.notifications.notify({
                        msgId: 'LockTimeoutWarning',
                        msg: 'Your lock for the document is about to expire<br/>&nbsp;&nbsp;&nbsp;&nbsp;<strong>Click here to extend the lock</strong>',
                        at: (new Date()).getTime(),
                        by: '',
                        changes: {
                        },
                        delay: warningTime,
                        msgType: 'warning',
                        icon: 'glyphicon glyphicon-warning-sign',
                        url: 'javascript:apx.notifications.relockDoc(' + id + ');',
                        onClose: function(e) {
                            if (0 === apx.locks.mine.docs[id].timeout) {
                                return;
                            }
                            if (apx.locks.mine.docs[id].timeout > (new Date()).getTime()) {
                                return;
                            }

                            delete apx.locks.docs[id];

                            $('#editDocModal').find('.modal-footer .btn-save').hide();

                            let changes = {};
                            changes[id] = 'x';
                            apx.notifications.notify({
                                msgId: 'LockTimeout',
                                msg: 'Your lock for the document has expired',
                                at: (new Date()).getTime(),
                                by: '',
                                changes: {
                                    'doc-ul': changes
                                },
                                delay: 0,
                                msgType: 'danger',
                                icon: 'glyphicon glyphicon-alert'
                            });
                        }
                    });
                }, timeout - warningTime, id);

                apx.locks.mine.docs[id] = {
                    warning: warning,
                    timeout: msg.at + timeout + apx.timeDiff - 2000
                };
            } else {
                if ("number" === typeof apx.locks.docs[id]) {
                    // remove any existing timer
                    clearTimeout(apx.locks.docs[id]);
                }

                apx.locks.docs[id] = setTimeout(function (id) {
                    delete apx.locks.docs[id];

                    let changes = {};
                    changes[id] = 'x';
                    apx.notifications.notify({
                        msgId: 'LockTimeout',
                        msg: 'The document is no longer being edited',
                        at: (new Date()).getTime(),
                        by: '',
                        changes: {
                            'doc-ul': changes
                        }
                    });
                }, timeout, id);
            }
        });

        apx.refreshPage();
    },

    'doc-ul': function(list) {
        if ("undefined" === typeof apx.locks) {
            // If locks are disabled then ignore
            return;
        }

        $.each(list, function(id, identifier) {
            if (apx.lsDocId.toString() === id.toString()) {
                if ("number" === typeof apx.locks['docs'][id]) {
                    clearTimeout(apx.locks['docs'][id]);
                    delete apx.locks['docs'][id];
                }
                if ("undefined" !== typeof apx.locks.mine.docs[id] && "number" === typeof apx.locks.mine.docs[id].warning) {
                    clearTimeout(apx.locks.mine.docs[id].warning);
                    apx.locks.mine.docs[id].timeout = 0;
                    if ("undefined" !== typeof apx.locks.mine.warnings[id] && $.isFunction(apx.locks.mine.warnings[id].close)) {
                        apx.locks.mine.warnings[id].close();
                    }
                }
            }
        });

        apx.refreshPage();
    },

    relockDoc: function(id) {
        // set new lock first
        apx.locks.mine.docs[id].timeout = (new Date()).getTime() + 5000;
        apx.locks.mine.warnings[id].close();
        $.ajax({
            url: apx.path.lsdoc_lock.replace('ID', id),
            method: 'POST'
        }).done(function (data, textStatus, jqXHR) {
        });
    },

    'doc-a': function(list) {
        // Document Add should not occur
    },

    'doc-u': function(list) {
        $.each(list, function(id, identifier) {
            $.ajax({
                url: apx.path.lsdoc_tree_json.replace('ID', id),
                method: 'GET'
            }).done(function(data, textStatus, jqXHR){
                $.each([
                    'title',
                    'officialSourceURL',
                    'creator',
                    'publisher',
                    'description',
                    'language',
                    'adoptionStatus',
                    'statusStart',
                    'statusEnd',
                    'note',
                    'version',
                    'lastChangeDateTime'
                ], function(i, key) {
                    if ("undefined" !== typeof data[key] && null !== data[key]) {
                        apx.mainDoc.doc[key] = data[key];
                    } else {
                        delete apx.mainDoc.doc[key];
                    }
                });

                apx.refreshPage();
            }).fail(function(jqXHR, textStatus, errorThrown){
                // Ignore for now
            });
        });
    },

    'doc-d': function(list) {
        $.each(list, function(id, identifier) {
            // Means we can't do any editing anymore....
            if (id === apx.mainDoc.doc.id) {
                window.location.replace('/');
            }
        });
    },

    reload: function (list) {
        window.location.reload(true);
    },

    redirect: function (list) {
        window.location.replace(list);
    },

    displayNotification: function(msg) {
        // Do not display messages to yourself
        if ('string' === typeof msg.by && msg.by === apx.me) {
            return;
        }

        if ('boolean' === typeof msg.show && msg.show === false) {
            return;
        }

        if (!apx.notifications.isEnabled()) {
            return;
        }

        if ('function' === typeof apx.notifyCheck[msg.msgId]) {
            if (false === apx.notifyCheck[msg.msgId](msg)) {
                return;
            }
        }

        if (msg.url && "LockTimeoutWarning" !== msg.msgId) {
            msg.msg += '<span class="notification-url-indicator">Show</span>';
        }

        return $.notify({
            message: msg.msg,
            url: msg.url ? msg.url : null,
            icon: msg.icon ? msg.icon : null,
            target: msg.target ? msg.target : '_self'
        }, {
            delay: ('number' === typeof msg.delay) ? msg.delay : 30000,
            allow_dismiss: ('boolean' === typeof msg.dismissible) ? !msg.dismissible : true,
            type: ('string' === typeof msg.msgType) ? msg.msgType : 'info',
            onShow: msg.onShow ? msg.onShow : null,
            onShown: msg.onShown ? msg.onShown : null,
            onClose: msg.onClose ? msg.onClose : null,
            onClosed: msg.onClosed ? msg.onClosed : null
        });
    },

    notify: function(msg) {
        let notification = apx.notifications.displayNotification(msg);

        if ("object" === typeof msg.changes) {
            let changed = {};
            $.each(msg.changes, function(key, list) {
                let type = key.replace(/-.*/, '');
                if ("undefined" === typeof changed[type]) {
                    changed[type] = [];
                }
                $.each(list, function(id, identifier) {
                    changed[type].push(id);
                });
            });

            $.ajax({
                url: apx.path.doc_tree_multi_changes.replace('ID', apx.mainDoc.doc.id),
                method: 'POST',
                data: changed
            }).done(function (data, textStatus, jqXHR) {

                apx.notifications.updates = data;

                $.each(msg.changes, function (key, list) {
                    if ($.isFunction(apx.notifications[key])) {
                        apx.notifications[key](list, msg);
                    } else {
                        console.log("function not found", key, list);
                    }
                });
            });
        }

        return notification;
    },

    loadAssociation: function(assocId) {
        // Get association info
        let load = function(data) {
            if ("undefined" !== typeof apx.mainDoc.assocIdHash[data.id]) {
                // Already exists
                return;
            }

            let a = apx.mainDoc.addAssociation(data);
            apx.mainDoc.addInverseAssociation(a);

            apx.refreshPage();
        };

        if ("undefined" !== apx.notifications.updates['assocs'][assocId]) {
            load(apx.notifications.updates['assocs'][assocId]);
        } else {
            $.ajax({
                url: apx.path.doc_tree_association_json.replace('ID', assocId),
                method: 'GET'
            }).done(function (data, textStatus, jqXHR) {
                load(data);
            }).fail(function (jqXHR, textStatus, errorThrown) {
                // Ignore for now
            });
        }
    },

    loadItem: function(itemId) {
        if ("undefined" === typeof apx.mainDoc.itemIdHash[itemId]) {
            $.ajax({
                url: apx.path.lsitem_tree_json.replace('ID', itemId),
                method: 'GET'
            }).done(function(data, textStatus, jqXHR){
                apx.mainDoc.addNewItemData(data);

                apx.refreshPage();
            });
        }
    }
});

$.extend(apx.notifyCheck, {
    // See if the lock/unlock message should be shown
    /** @return {boolean} */
    'D04': function(msg) {
        // document lock
        let display = false;
        $.each(msg.changes['doc-l'], function(id, identifier) {
           if ("number" !== typeof apx.locks['docs'][id] && false !== apx.locks['docs'][id]) {
               display = true;
           }
        });

        return display;
    },
    /** @return {boolean} */
    'D05': function(msg) {
        // document unlock
        let display = false;
        $.each(msg.changes['doc-ul'], function(id, identifier) {
            if ("number" === typeof apx.locks['docs'][id]) {
                display = true;
            }
        });

        return display;
    },
    /** @return {boolean} */
    'I06': function(msg) {
        // item lock
        let display = false;
        $.each(msg.changes['item-l'], function(id, identifier) {
            if ("number" !== typeof apx.locks['items'][id] && false !== apx.locks['items'][id]) {
                msg.url = apx.path.lsItem.replace('ID', id);
                display = true;
            }
        });

        return display;
    },
    /** @return {boolean} */
    'I07': function(msg) {
        // item unlock
        let display = false;
        $.each(msg.changes['item-ul'], function(id, identifier) {
            if ("number" === typeof apx.locks['items'][id]) {
                msg.url = apx.path.lsItem.replace('ID', id);
                display = true;
            }
        });

        return display;
    },

    // Add links to items
    /** @return {boolean} */
    'I01': function(msg) {
        // item add
        let display = false;
        $.each(msg.changes['item-a'], function(id, identifier) {
            msg.url = apx.path.lsItem.replace('ID', id);
            display = true;
        });

        return display;
    },

    /** @return {boolean} */
    'I03': function(msg) {
        // item copied
        let display = false;
        $.each(msg.changes['item-a'], function(id, identifier) {
            msg.url = apx.path.lsItem.replace('ID', id);
            display = true;
        });

        return display;
    },

    /** @return {boolean} */
    'I08': function(msg) {
        // item modified
        let display = false;
        $.each(msg.changes['item-u'], function(id, identifier) {
            if ("undefined" !== typeof apx.mainDoc.itemIdHash[id]) {
                msg.url = apx.path.lsItem.replace('ID', id);
                display = true;
            }
        });

        return display;
    },
});

//////////////////////////////////////////////////////
/**
 * "Spinner" for indicating when something is loading
 */
apx.spinner = {};
apx.spinner.html = function(msg) {
    return '<div class="spinnerOuter"><span class="glyphicon glyphicon-cog spinning spinnerCog"></span><span class="spinnerText">' + msg + '</span></div>';
};

apx.spinner.showModal = function(msg) {
    $("#modalSpinnerMessage").html(apx.spinner.html(msg));
    $("#modalSpinner").show();
};

apx.spinner.hideModal = function() {
    $("#modalSpinner").hide();
};

//////////////////////////////////////////////////////
// BROWSER HISTORY MAINTENANCE

apx.popStateActivate = false;

// set onpopstate event to restore state when user clicks the browser back/forward button
window.onpopstate = function(event) {
    // set popStateActivate so we don't re-push this history state
    apx.popStateActivate = true;

    let lsItemId, assocGroup, view;
    // if event.state is null, we're back to the initial values...
    if (event.state == null) {
        lsItemId = apx.initialLsItemId;
        assocGroup = apx.initialAssocGroup;
        view = apx.viewMode.initialView;
    } else {
        lsItemId = event.state.lsItemId;
        assocGroup = event.state.assocGroup;
        view = event.state.view;
    }

    // now if we're moving to assocView, show it
    if (view === "assoc") {
        apx.viewMode.showAssocView("history");
    } else if (view === "log") {
        apx.viewMode.showLogView("history");
    } else {
        // else assume tree view
        apx.viewMode.showTreeView("history");
        if ('undefined' !== typeof apx.treeDoc1) {
            // restore assocGroup if necessary
            if (assocGroup != apx.treeDoc1.currentAssocGroup) {
                apx.treeDoc1.setCurrentAssocGroup(assocGroup);
                apx.treeDoc1.ftRender();
            }
            // set and activate the current item
            apx.treeDoc1.setCurrentItem({"lsItemId": lsItemId});
            apx.treeDoc1.activateCurrentItem();
        }
    }
};

/** Function to update the history state */
apx.pushHistoryState = function() {
    // no history if we loaded the mainDoc from a url, or if we're in chooser mode
    if (apx.mainDoc.loadedFromUrl() || apx.query.mode === "chooser") {
        return;
    }

    // if we just called this after the user clicked back or forward, though, don't push a new state
    if (apx.popStateActivate !== true) {
        // For now, at least, if we're not showing the mainDoc on the left side, don't push a new state
        if (apx.mainDoc !== apx.treeDoc1) {
            return;
        }

        let path;
        let state = {
            "view": apx.viewMode.currentView
        };

        // if currentItem is the document...
        if (apx.treeDoc1.currentItem === apx.treeDoc1.doc) {
            path = apx.path.lsDoc.replace('ID', apx.lsDocId);
            if (apx.viewMode.currentView === "assoc") {
                // add "/av" to path if the association view
                path += "/av";
            }
            if (apx.viewMode.currentView === "log") {
                // add "/lv" to path if the log view
                path += "/lv";
            }
            state.lsItemId = null;
        } else {
            // else the currentItem is an item
            path = apx.path.lsItem.replace('ID', apx.treeDoc1.currentItem.id);
            state.lsItemId = apx.treeDoc1.currentItem.id;
        }

        // add assocGroup to path if necessary
        if (apx.treeDoc1.currentAssocGroup != null) {
            if (apx.viewMode.currentView !== "assoc"
                && apx.viewMode.currentView !== "log") {
                path += "/" + apx.treeDoc1.currentAssocGroup;
            }
            state.assocGroup = apx.treeDoc1.currentAssocGroup;
        } else {
            state.assocGroup = null;
        }

        window.history.pushState(state, "Competency Framework", path);
    }
    // clear popStateActivate
    apx.popStateActivate = false;
};

apx.markLogsAsRead = function() {
    $('.modal#seeImportLogs button.btn-link#mark-logs-as-read').on('click', function(){
        $.post('/cfdoc/'+apx.lsDocId+'/import_logs/mark_as_read')
            .done(function(data){
                $('.modal#seeImportLogs .modal-body .list-group').fadeOut();
                $(this).attr('disabled', 'disabled');
                $('#seeImportLogs').modal('toggle');
            });
    });
};
/* global global, apx */
global.apx = apx;
