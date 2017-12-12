/* global apx */
window.apx = window.apx||{};

/* global apxDocument */

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
    var o = arguments[0];
    // go through keys in the arguments
    for (var i = 1; i < arguments.length; ++i) {
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
    apx.edit.prepareItemEditModal();
    apx.edit.prepareAddNewChildModal();
    apx.edit.prepareExemplarModal();
    apx.edit.prepareAssociateModal();

    // prepare assocGroup modals/functions
    apx.edit.prepareAddAssocGroupModal();
    apx.edit.initializeManageAssocGroupButtons();

    apx.markLogsAsRead();

    // right-side buttongroup
    $("#rightSideItemDetailsBtn").on('click', function() { apx.setRightSideMode("itemDetails"); });
    $("#rightSideCopyItemsBtn").on('click', function() { apx.setRightSideMode("copyItem"); });
    $("#rightSideCreateAssociationsBtn").on('click', function() { apx.setRightSideMode("addAssociation"); });

    // Tree checkboxes/menus
    $("#treeSideLeft").find(".treeCheckboxControlBtn").on('click', function(e) { apx.treeDoc1.treeCheckboxToggleAll(null, 1); e.stopPropagation(); });
    $("#treeSideLeft").find(".treeCheckboxMenuItem").on('click', function() { apx.treeDoc1.treeCheckboxMenuItemSelected($(this), 1); });
    $("#treeSideRight").find(".treeCheckboxControlBtn").on('click', function(e) { apx.treeDoc2.treeCheckboxToggleAll(null, 2); e.stopPropagation(); });
    $("#treeSideRight").find(".treeCheckboxMenuItem").on('click', function() { apx.treeDoc2.treeCheckboxMenuItemSelected($(this), 2); });

    // popovers on export modal
    $('#exportModal').find('[data-toggle="popover"]').popover();

    // change event on assocGroup menus
    $("#treeSideLeft").find(".assocGroupSelect").off().on('change', function() { apx.treeDoc1.assocGroupSelected(this, 1); });
    $("#treeSideRight").find(".assocGroupSelect").off().on('change', function() { apx.treeDoc2.assocGroupSelected(this, 2); });

    // links/buttons on item info panel
    // enable more info link
    $(".lsItemDetailsMoreInfoLink a").on('click', function(e) { apx.toggleMoreInfo(); });

    // enable deleteItem button
    $("#deleteItemBtn").on('click', apx.edit.deleteItems);

    // enable toggleFolder button
    $("#toggleFolderBtn").on('click', function() { apx.treeDoc1.toggleFolders(); } );

    // doc view/tree view buttongroup
    $("#displayTreeBtn").on('click', function() { apx.viewMode.showTreeView("button"); });
    $("#displayAssocBtn").on('click', function() { apx.viewMode.showAssocView("button"); });

    // implement enableMoveCheckbox
    $("#enableMoveCheckbox").on('click', function() { apx.edit.enableMove(this); });

    // make sure initialAssocGroup is a number if it's not null
    if (!empty(apx.initialAssocGroup)) {
        apx.initialAssocGroup *= 1;
    }
    
    // parse query string
    apx.query = {};
    var arr = document.location.search.substr(1).split("&");
    for (var i = 0; i < arr.length; ++i) {
        var line = arr[i].split("=");
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
        apx.mainDoc = new apxDocument({"url": decodeURIComponent(apx.query.url)});
    } else {
        apx.mainDoc = new apxDocument({"id": apx.lsDocId});
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
        for (var identifier in apx.mainDoc.associatedDocs) {
            var ed = apx.mainDoc.associatedDocs[identifier];
            // and start loading now any associatedDocs that have the "autoLoad" flag set to "true" (unless we've already loaded it)
            // we have to do this because associations to items in these docs don't specify the doc id
            if (ed.autoLoad === "true" && !(identifier in apx.allDocs)) {
                console.log("loading doc " + ed.title);
                apx.allDocs[identifier] = "loading";
                new apxDocument({"identifier": identifier}).load();
            }
        }

        // find any other docs referenced by associations in mainDoc
        apx.mainDoc.findAssociatedDocs();

        // if we got an initialLsItemId, set it (the document will be selected by default)
        if (!empty(apx.initialLsItemId)) {
            var item = apx.mainDoc.itemIdHash[apx.initialLsItemId];
            if (!empty(item)) {
                apx.mainDoc.setCurrentItem({"identifier": item.identifier});

                // If an item is initially selected, get appropriate initialAssocGroup
                // first get all assocGroups for isChildOf relationships for this item
                var assocGroups = apx.mainDoc.getAssocGroupsForItem(apx.mainDoc.currentItem, "isChildOf");

                // if initialAssocGroup is empty (null, meaning the default group) OR it isn't one of the available isChildOf relationships for this item...
                if (empty(apx.initialAssocGroup) || $.inArray(apx.initialAssocGroup, assocGroups) == -1) {
                    // then if the item has no isChildOf relationship or has an isChildOf relationship for the default group, use default
                    if (assocGroups.length == 0 || $.inArray(null, assocGroups) > -1) {
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
        } else {
            apx.viewMode.initialView = "treeView";
        }
    });

    /** Sometimes we need to refresh the mainDoc entirely from the server... */
    apx.mainDoc.refreshFromServer = function() {
        apx.spinner.showModal("Refreshing document");
        var currentItemIdentifier = apx.mainDoc.currentItem.identifier;
        var currentAssocGroup = apx.mainDoc.currentAssocGroup;
        apx.mainDoc.load(function() {
            // if we're showing mainDoc on the left side, refresh it now
            if (apx.mainDoc == apx.treeDoc1) {
                apx.treeDoc1.setCurrentItem({"identifier": currentItemIdentifier});
                apx.treeDoc1.setCurrentAssocGroup(currentAssocGroup);
                apx.treeDoc1.ftRender1();
                apx.treeDoc1.activateCurrentItem();
                apx.treeDoc1.showCurrentItem();
            }
            apx.spinner.hideModal();
        });
    };
};


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

    var lsItemId, assocGroup, view;
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
    if (view == "assoc") {
        apx.viewMode.showAssocView("history");

    // else show the relevant item
    } else {
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
    if (apx.mainDoc.loadedFromUrl() || apx.query.mode == "chooser") {
        return;
    }
    
    // if we just called this after the user clicked back or forward, though, don't push a new state
    if (apx.popStateActivate != true) {
        // For now, at least, if we're not showing the mainDoc on the left side, don't push a new state
        if (apx.mainDoc !== apx.treeDoc1) {
            return;
        }

        var path;
        var state = {
            "view": apx.viewMode.currentView
        };

        // if currentItem is the document...
        if (apx.treeDoc1.currentItem == apx.treeDoc1.doc) {
            path = apx.path.lsDoc.replace('ID', apx.lsDocId);
            // add "/av" to path if necessary
            if (apx.viewMode.currentView == "assoc") {
                path += "/av";
            }
            state.lsItemId = null;

        // else the currentItem is an item
        } else {
            path = apx.path.lsItem.replace('ID', apx.treeDoc1.currentItem.id);
            state.lsItemId = apx.treeDoc1.currentItem.id;
        }

        // add assocGroup to path if necessary
        if (apx.treeDoc1.currentAssocGroup != null) {
            if (apx.viewMode.currentView != "assoc") {
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
