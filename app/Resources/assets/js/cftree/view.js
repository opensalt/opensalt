//////////////////////////////////////////////////////
// INITIALIZATION
window.app = window.app||{}; /* global app */
app.initialize = function() {
    if ("undefined" === typeof(app.initialLsItemId)) {
        app.initialLsItemId = null;
    }
    if ("undefined" === typeof(app.initialAssocGroup)) {
        app.initialAssocGroup = null;
    }
    if ("undefined" === typeof(app.allAssocGroups)) {
        app.allAssocGroups = {};
    }
    // render the document tree
    app.renderTree1();

    // prepare modals
    app.prepareEditDocModal();
    app.prepareEditItemModal();
    app.prepareAssociateModal();
    app.prepareExemplarModal();
    app.prepareAddNewChildModal();
    app.prepareAddAssocGroupModal();
    // initialize popovers on export modal
    $('#exportModal').find('[data-toggle="popover"]').popover();
    // "Copy from another CF Package" button
    $("[data-target=copyItem]").on('click', app.copyItemInitiate);
    // When user selects a document from the document list, load tree2
    // PW: not sure if we actually need a button here; does the menu always load with a blank item listed first??
    // $("#loadTree2Btn").on('click', app.tree2Selected);
    $("#ls_doc_list_lsDoc").on('change', app.tree2Selected);

    // right-side buttongroup
    $("#rightSideItemDetailsBtn").on('click', function() { app.tree2Toggle(false); });
    $("#rightSideCopyItemsBtn").on('click', function() { app.copyItemInitiate(); });
    $("#rightSideCreateAssociationsBtn").on('click', function() { app.addAssociation(); });

    // Tree checkboxes
    $(".treeCheckboxControlBtn").on('click', function(e) { app.treeCheckboxToggleAll($(this)); e.stopPropagation(); });
    $(".treeCheckboxMenuItem").on('click', function() { app.treeCheckboxMenuItemSelected($(this)); });

    // tree2 change tree buttons
    $(".changeTree2DocumentBtn").on('click', function() { app.changeTree2() });

    // Prepare filters
    app.filterOnTrees();

    // initialize association groups
    app.initializeAssocGroups();

    // if we got an initialLsItemId, activate it (and expand it)
    if (null !== app.initialLsItemId) {
        var key = app.keyFromLsItemId(app.initialLsItemId, app.initialAssocGroup);
        // check to see if this key is valid
        if (app.ft.fancytree("getTree").getNodeByKey(key) === null) {
            // if not valid, look for the item in other assocGroups
            var foundAssocGroup = false;
            for (var assocGroup in app.allAssocGroups) {
                key = app.keyFromLsItemId(app.initialLsItemId, assocGroup);
                // if we find it...
                if (app.ft.fancytree("getTree").getNodeByKey(key) !== null) {
                    // set selectedAssocGroup and break
                    app.selectedAssocGroup = assocGroup;
                    foundAssocGroup = true;
                    break;
                }
            }
            // if we didn't find a valid key, alert the user (but this shouldn't happen)
            if (!foundAssocGroup) {
                alert("An item with id " + app.initialLsItemId + " could not be found.");
                key = null;
            }
        }
        if (null !== key) {
            app.ft.fancytree("getTree").activateKey(key).setExpanded(true);
        }
    }

    // show itemSection to reveal either the document or item details
    $("#itemSection").show();
};

//////////////////////////////////////////////////////
// SPINNER
app.spinnerHtml = function(msg) {
    return '<div class="spinnerOuter"><span class="glyphicon glyphicon-cog spinning spinnerCog"></span><span class="spinnerText">' + msg + '</span></div>';
};

app.showModalSpinner = function(msg) {
    $("#modalSpinnerMessage").html(app.spinnerHtml(msg));
    $("#modalSpinner").show();
};

app.hideModalSpinner = function() {
    $("#modalSpinner").hide();
};

//////////////////////////////////////////////////////
// BROWSER HISTORY MAINTENANCE

// set onpopstate event to restore state when user clicks the browser back/forward button
window.onpopstate = function(event) {
    // set popStateActivate so we don't re-push this history state
    app.popStateActivate = true;

    var lsItemId, assocGroup;
    // if event.state is null, we're back to the initial values...
    if (event.state == null) {
        lsItemId = app.initialLsItemId;
        assocGroup = app.initialAssocGroup;
    } else {
        lsItemId = event.state.lsItemId;
        assocGroup = event.state.assocGroup;
    }

    // restore assocGroup if necessary
    if (assocGroup != app.selectedAssocGroup) {
        app.selectedAssocGroup = assocGroup;
        app.processAssocGroups("tree1");
    }

    app.ft.fancytree("getTree").activateKey(app.keyFromLsItemId(lsItemId, assocGroup));
};

// Function to update the history state; called when a tree node is activated
app.pushHistoryState = function(lsItemId, assocGroup) {
    // if we just called this after the user clicked back or forward, though, don't push a new state
    if (app.popStateActivate != true) {
        var path;
        if (lsItemId == null) {
            path = app.path.lsDoc.replace('ID', app.lsDocId);
        } else {
            path = app.path.lsItem.replace('ID', lsItemId);
        }

        var state = {
            "lsItemId": lsItemId,
            "assocGroup": assocGroup
        };
        // add assocGroup to path if necessary
        if (assocGroup != null && assocGroup != "default") {
            path += "/" + assocGroup;
        }

        window.history.pushState(state, "Competency Framework", path);
    }
    // clear popStateActivate
    app.popStateActivate = false;
};

//////////////////////////////////////////////////////
// ITEM DETAIL SUMMARIES
// Get a jquery reference to the specified item's details element
app.getLsItemDetailsJq = function(lsItemId) {
    return $(".itemInfo[data-item-lsItemId=" + lsItemId + "]");
};

// Load details for the specified item
app.loadItemDetails = function(item) {
    var n, lsItemId;
    if ("object" === typeof(item)) {
        n = item;
        lsItemId = app.lsItemIdFromNode(n);
    } else {
        lsItemId = item;
        n = app.getNodeFromLsItemId(item);
    }
    // clone the itemInfoTemplate
    $jq = $("#itemInfoTemplate").clone();
    $jq.removeAttr('id');

    // add lsItemId
    $jq.attr("data-item-lsItemId", lsItemId);

    // fill in the title, which we can get from the item's tree node
    var itemTitle;
    if (n.folder === true) {
        itemTitle = '<img class="itemTitleIcon" src="/assets/img/folder.png">';
    } else {
        itemTitle = '<img class="itemTitleIcon" src="/assets/img/item.png">';
    }
    itemTitle += '<span class="itemTitleSpan">' + app.titleFromNode(n) + '</span>';
    $jq.find(".itemTitle").html(itemTitle);
    $jq.find('.itemDetails').html(app.spinnerHtml("Loading Item Details"));

    // append and show the shell details div
    $("#items").append($jq);
    $jq.show();

    // ajax call to get the full item details
    $jq.find('.itemDetails').load(
        app.path.lsItemDetails.replace('ID', lsItemId),
        null,
        function(responseText, textStatus, jqXHR) {
            // details should be loaded
            console.log("item " + lsItemId + " loaded");

            // enable hidden fields
            $jq.find(".lsItemDetailsExtras").hide();

            // enable more info link
            $jq.find(".lsItemDetailsMoreInfoLink a").on('click', function(e) { app.toggleMoreInfo(); });

            // restore last more info state
            app.toggleMoreInfo("restore");

            // enable deleteItem button
            $jq.find("[id=deleteItemBtn]").on('click', app.deleteItems);

            // enable toggleFolder button
            $jq.find("[id=toggleFolderBtn]").on('click', app.toggleFolders);

            // hide/enable make folder and create new item buttons appropriately
            app.toggleItemCreationButtons();

            // enable remove association button(s)
            $jq.find(".btn-remove-association").on('click', function(e) { app.deleteAssociation(e); });

            // new item button doesn't need to be enabled because it shows a dialog
        }
    );
};

// Clear item details for the specified item
app.clearItemDetails = function(lsItemId) {
    app.getLsItemDetailsJq(lsItemId).remove();
};

// Toggle more item details.
// @param {*} [arg] - if true or false, set to that value; if "restore", restore last-used value; otherwise toggle
app.moreInfoShowing = false;
app.toggleMoreInfo = function(arg) {
    if (arg == null) {
        app.moreInfoShowing = !app.moreInfoShowing;
    } else if (arg != "restore") {
        app.moreInfoShowing = arg;
    }

    if (app.moreInfoShowing) {
        $(".lsItemDetailsExtras").slideDown(100);
        $(".lsItemDetailsMoreInfoLink a").text("Less Info");
    } else {
        $(".lsItemDetailsExtras").slideUp(100);
        $(".lsItemDetailsMoreInfoLink a").text("More Info");
    }
};

//////////////////////////////////////////////////////
// TREE LOADING AND FUNCTIONALITY

app.processTree = function(tree, isTopNode) {
    // first make sure the node's title attribute is filled in and matches what will appear in the window
    tree.title = app.titleFromNode(tree);

    // if isTopNode is true, it's the document, which should not be selectable or have a checkbox
    if (isTopNode == true) {
        tree.hideCheckbox = true;
        tree.unselectable = true;
    }

    // if tree has any children
    if (tree.children != null && tree.children.length > 0) {
        // sort children by sequenceNumber
        tree.children.sort(function(a,b) {
            var leA = a.sequenceNumber * 1;
            var leB = b.sequenceNumber * 1;
            if (isNaN(leA)) leA = 100000;
            if (isNaN(leB)) leB = 100000;
            return leA - leB;
        });

        // then order any children of each child
        for (var i = 0; i < tree.children.length; ++i) {
            app.processTree(tree.children[i]);
        }
    }
};

// Render the tree for the document we're editing
app.renderTree1 = function() {
    // first process the tree
    app.processTree(app.tree1[0], true);

    // establish the fancytree widget
    app.ft = $('#viewmode_tree').fancytree({
        extensions: ['filter', 'dnd'],
        source: app.tree1,
        quicksearch: true,
        renderTitle: function(event, data) {
            return app.titleFromNode(data.node, "ftTitleSpan");
        },
        filter:{
            autoApply: true,  // Re-apply last filter if lazy data is loaded
            counter: true,  // Show a badge with number of matching child nodes near parent icons
            fuzzy: false,  // Match single characters in order, e.g. 'fb' will match 'FooBar'
            hideExpandedCounter: true,  // Hide counter badge, when parent is expanded
            highlight: true,  // Highlight matches by wrapping inside <mark> tags
            mode: "hide"  // Grayout unmatched nodes (pass "hide" to remove unmatched node instead)
        },

        renderNode: function(event, data) {
            app.treeItemTooltip(data.node);
        },

        // when item is activated (user clicks on it or activateKey() is called), show details for the item
        activate: function(event, data) {
            app.tree1Activate(data.node);
        },
        // I don't think we really want to do this on click; that calls the tree1Activate function when you click the arrow to expand the item
        click: function(event, data) {
            //app.ft.fancytree("getTree").activateKey(data.node.key);
            //app.tree1Activate(data.node);
        },

        // if user doubleclicks on a node, open the node, then simulate clicking the "Edit" button for it
        dblclick: function(event, data) {
            console.log("dblclick");
            var lsItem = app.lsItemIdFromNode(data.node);
            setTimeout(app.treeDblClicked, 50, lsItem);

            // return false to cancel default processing (i.e. opening folders)
            return false;
        },

        // drag-and-drop functionality - https://github.com/mar10/fancytree/wiki/ExtDnd
        dnd: {
            dragExpand: function() { return false; },   // don't autoexpand folders when you drag over them; this makes things confusing

            smartRevert: true,
            // focusOnClick: true,
            // this function seems to need to be defined for the dnd functionality to work
            dragStart: function(node, data) {
                // don't allow the document to be dragged
                var lsItemId = app.lsItemIdFromNode(node);
                return lsItemId !== null;
            },

            initHelper: function(node, data) {
                // Helper was just created: modify markup
                var helper = data.ui.helper;
                var tree = node.tree;
                var sourceNodes = data.tree.getSelectedNodes();

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

            //dragStop: function(node, data){ console.log('dragStop'); },
            //updateHelper: function(){ console.log('updateHelper'); },

            dragEnter: function(droppedNode, data) {
                var draggedNode = data.otherNode;

                // determine if this is inter- or intra-tree drag
                var treeDraggedFrom = "tree1";
                if (droppedNode.tree != draggedNode.tree) {
                    treeDraggedFrom = "tree2";
                }

                // intra-tree drag
                if (treeDraggedFrom == "tree1") {
                    // Don't allow dropping *over* a non-folder node (this would make it too easy to accidentally create a child).
                    if (droppedNode.folder == true) {
                        // also don't allow dropping before or after the document -- only "over" allowed in this case
                        if (app.isDocNode(droppedNode)) {
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
                    if (app.tree2Mode == "addAssociation") {
                        // and don't allow any drops onto the document
                        if (app.lsItemIdFromNode(droppedNode) == null) {
                            return false;
                        } else {
                            return 'over';
                        }
                        // else we're in copy mode; use same thing here as moving within the tree
                    } else {
                        // don't allow dropping before or after the document -- only "over" allowed in this case
                        if (app.isDocNode(droppedNode)) {
                            return "over";
                        } else if (droppedNode.folder == true) {
                            return true;
                        } else {
                            return ["before", "after"];
                        }
                    }
                }
            },

            dragDrop: function(droppedNode, data){
                // USE SOURCENODES INSTEAD OF THIS
                // var draggedNode = data.otherNode;

                /*
                var draggedItemId = app.lsItemIdFromNode(draggedNode);
                var droppedItemId = app.lsItemIdFromNode(droppedNode);
                var hitMode = data.hitMode;
                console.log('tree1 dragDrop from ' + treeDraggedFrom + ' (tree2Mode: ' + app.tree2Mode + '): ' + draggedItemId + ' to ' + hitMode + ' ' + droppedItemId);
                */

                // determine if this is inter- or intra-tree drag
                var treeDraggedFrom = "tree1";
                if (droppedNode.tree != data.otherNode.tree) {
                    treeDraggedFrom = "tree2";
                }

                var sourceNodes = data.ui.helper.data("sourceNodes");

                for (var i = 0; i < sourceNodes.length; ++i) {
                    var draggedNode = sourceNodes[i];

                    // intra-tree drag
                    if (treeDraggedFrom === "tree1") {
                        // move the item in the tree
                        app.reorderItems(draggedNode, droppedNode, data.hitMode);

                    // inter-tree drag (from tree2)
                    } else {
                        // if we're in associate mode, show choice for what type of association to add
                        if (app.tree2Mode === "addAssociation") {
                            app.createAssociation(sourceNodes, droppedNode);
                            // in this case we just want to do it once, so break out of the loop
                            break;

                            // else if we're in copy mode; copy node to new tree
                        } else if (app.tree2Mode === "copyItem") {
                            app.copyItem(draggedNode, droppedNode, data.hitMode);
                        }
                    }
                }
            }
        }

        // we don't currently need the below functions
        // beforeSelect: function(event, data){console.log(event, data);},
        // select: function(event, data){console.log(event, data);},

        // debugLevel:2
    });
};

app.getTreeFromInput = function($jq) {
    return $("#" + $jq.closest(".treeSide").find(".treeDiv").attr("id"));
};

app.treeCheckboxToggleCheckboxes = function($tree, val) {
    var $cb = $tree.closest(".treeSide").find(".treeCheckboxControl");
    if (val == true) {
        $tree.fancytree("getTree").rootNode.hideCheckbox = true;
        $tree.fancytree("option", "checkbox", true);
        $tree.fancytree("option", "selectMode", 2);

        // show the menu
        $cb.closest(".input-group").find(".dropdown-toggle").show();

        // mark the cb as enabled
        $cb.data("checkboxesEnabled", "true");

        // reset cb to off
        $cb.prop("checked", false);

    } else {
        $tree.fancytree("option", "checkbox", false);
        $tree.fancytree("option", "selectMode", 1);

        // hide the menu
        $cb.closest(".input-group").find(".dropdown-toggle").hide();

        // mark the cb as not enabled
        $cb.data("checkboxesEnabled", "false");

        // reset cb to off
        $cb.prop("checked", false);
    }
}

app.treeCheckboxToggleAll = function($input, val) {
    var $tree = app.getTreeFromInput($input);
    var $cb = $tree.closest(".treeSide").find(".treeCheckboxControl");

    // if this is the first click for this tree, enable checkboxes on the tree
    if ($cb.data("checkboxesEnabled") != "true") {
        app.treeCheckboxToggleCheckboxes($tree, true);
        // then call processAssocGroups to re-hide things appropriately by group
        app.processAssocGroups($tree.attr("id"));

    // else toggle select all
    } else {
        if ("undefined" === typeof(val)) {
            val = $cb.is(":checked");
        }

        // determine if something is entered in the search bar
        var searchEntered = false;
        var $filter = $tree.closest("section").find(".treeFilter");
        if ($filter.length > 0) {
            searchEntered = ($filter.val() != "");
        }

        $tree.fancytree("getTree").visit(function(node) {
            // if the node isn't unselectable and it's association group is showing
            if (node.unselectable != true && node.data.assocGroupShowing == true) {
                // if either (we're not filtering) or (the node matches the filter) or (val is false),
                if (searchEntered == false || node.match == true || val == false) {
                    // set selected to val
                    node.setSelected(val);
                }
            }
        });
    }
};

app.treeCheckboxMenuItemSelected = function($menu) {
    var $tree = app.getTreeFromInput($menu);

    // get all selected items
    var itemIds = [];
    $tree.fancytree("getTree").visit(function(node) {
        if (node.selected == true && node.unselectable != true) {
            itemIds.push(app.lsItemIdFromKey(node.key));
        }
    });

    var cmd = $menu.attr("data-cmd");
    if (cmd != "hideCheckboxes" && itemIds.length == 0) {
        alert("Select one or more items using the checkboxes before choosing a menu item.");
        return;
    }

    if (cmd == "edit") {
        alert("The ability to edit properties of multiple items at the same time will be coming soon.");
    } else if (cmd == "delete") {
        app.deleteItems(itemIds);
    } else if (cmd == "makeFolders") {
        app.toggleFolders(itemIds, true);
    } else {    // hideCheckboxes
        // clear checkbox selections
        var $cb = $tree.closest(".treeSide").find(".treeCheckboxControl");
        app.treeCheckboxToggleAll($cb, false);
        app.treeCheckboxToggleCheckboxes($tree, false);
    }
};

// The user first clicks a button to copy an item or add an association, then selected a document from the dropdown list
app.tree2Selected = function() {
    // get the selected document id
    var lsDoc2Id = $("#ls_doc_list_lsDoc").val();

    // if user selects the blank item in the menu, go back to the currently-loaded document
    if (lsDoc2Id == "") {
        $("#ls_doc_list_lsDoc").val(app.lsDoc2Id);
        return;
    }

    // destroy previus ft2 if there
    if (app.ft2 != null) {
        app.ft2.fancytree("destroy");
    }
    $('#viewmode_tree2').html(app.spinnerHtml("Loading Document"));

    // ajax call to load the document json
    $.ajax({
        url: app.path.doctree_render_document.replace('ID', lsDoc2Id),
        method: 'GET'
    }).done(function(data, textStatus, jqXHR) {
        // on success, set lsDoc2Id and tree2, then call renderTree2
        app.lsDoc2Id = lsDoc2Id;
        app.tree2 = data;
        app.renderTree2();

        // also show instructions properly by re-calling copyItemInitiate or addAssociation
        if ($("#rightSideCopyItemsBtn").hasClass("btn-primary")) {
            app.copyItemInitiate();
        } else {
            app.addAssociation();
        }

        // and hide tree2SelectorDiv
        $("#tree2SelectorDiv").hide();

        // process association groups for tree2
        app.selectedAssocGroupTree2 = null;
        app.processAssocGroups('tree2');

    }).fail(function(jqXHR, textStatus, errorThrown){
        $('#viewmode_tree2').html("ERROR:" + jqXHR.responseText);
        $('#ls_doc_list_lsDoc').val("");
    });
};

app.changeTree2 = function() {
    // clear viewmode_tree2 and hide tree2SectionControls and assocGroupFilter
    $("#viewmode_tree2").html("");
    $("#tree2SectionControls").hide();
    $("#treeSideRight .assocGroupFilter").hide();

    // clear selection in ls_doc_list_lsDoc
    $("#ls_doc_list_lsDoc").val("");

    // and show tree2SelectorDiv
    $("#tree2SelectorDiv").show();

    // reset instructions
    $("#tree2InitialInstructions").show();
    $("#tree2SectionCopyInstructions").hide();
    $("#tree2SectionRelationshipInstructions").hide();
};

// Render tree2 to copy items or create associations
app.renderTree2 = function() {
    // first process the tree
    app.processTree(app.tree2[0]);

    // clear and hide viewmode_tree2
    $('#viewmode_tree2').html("").show();

    app.ft2 = $('#viewmode_tree2').fancytree({
        extensions: ['filter', 'dnd'],
        source: app.tree2,

        renderTitle: function(event, data){
            return app.titleFromNode(data.node, "ftTitleSpan");
        },
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
            app.treeItemTooltip(data.node);
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
                app.ft2.fancytree("getTree").activateKey(node.key);

                // also show its tooltip
                $(node.span).find(".fancytree-title").data('bs.tooltip').options.trigger = 'manual';
                $(node.span).find(".fancytree-title").tooltip('show');

                // don't allow the document to be dragged
                var lsItemId = app.lsItemIdFromNode(node);
                return lsItemId !== null;
            },

            initHelper: function(node, data) {
                // Helper was just created: modify markup
                var helper = data.ui.helper;
                var tree = node.tree;
                var sourceNodes = data.tree.getSelectedNodes();

                // Store a list of active + all selected nodes
                if (!node.isSelected()) {
                    sourceNodes.unshift(node);
                }
                helper.data("sourceNodes", sourceNodes);

                console.log(helper.html());

                // Mark selected nodes also as drag source (active node is already)
                $(".fancytree-active,.fancytree-selected", tree.$container).addClass("fancytree-drag-source");

                // Add a counter badge to helper if dragging more than one node
                if (sourceNodes.length > 1) {
                    helper.append($("<span class='fancytree-childcounter'/>").text("+" + (sourceNodes.length - 1)));
                }
            },

            dragStop: function(node, data) {
                // reset trigger on node's tooltip
                $(node.span).find(".fancytree-title").tooltip('hide');
                $(node.span).find(".fancytree-title").data('bs.tooltip').options.trigger = 'hover focus';
            },

            // this function needs to be defined for the dnd functionality to work...
            dragEnter: function(node, data) {
                // but you can't drag from tree2 to tree2, so return false here to prevent this
                // the logic for dragging into tree1 is in the other fancytree definer
                return false;
            },
            dragDrop: function(node, data){
                // we should never get here, because we only allow drags from tree2 to tree1
                console.log('tree2 dragDrop (' + app.tree2Mode + '): ' + draggedItemId + ' to ' + hitMode + ' ' + droppedItemId);
            }
        }
    });
};

// Toggle visibility of tree2 / the item details section
app.tree2Showing = false;
app.tree2Toggle = function(showTree2) {
    if (showTree2 === true || showTree2 === false) {
        app.tree2Showing = showTree2;
    } else {
        app.tree2Showing = !app.tree2Showing;
    }

    if (app.tree2Showing) {
        if (app.ft2 != null) {
            $("#tree2SectionControls").show();
        }
        $("#tree2Section").show();
        $("#itemSection").hide();
        // caller should also set app.tree2Mode

    } else {
        $("#tree2SectionControls").hide();
        $("#tree2Section").hide();
        $("#itemSection").show();

        // if we're hiding tree2, set app.tree2Mode to none
        app.tree2Mode = "none";

        // also change rightSideItemDetailsBtn to primary and other two rightSide buttons to default
        $("#rightSideItemDetailsBtn").addClass("btn-primary").removeClass("btn-default");
        $("#rightSideCopyItemsBtn").removeClass("btn-primary").addClass("btn-default");
        $("#rightSideCreateAssociationsBtn").removeClass("btn-primary").addClass("btn-default");
    }
};

// Determine if a node is the main document node
app.isDocNode = function(n) {
    return ("undefined" === typeof(n.parent) || null === n.parent || "undefined" === typeof(n.parent.parent) || null === n.parent.parent);
};

// Given an lsItemId, return the corresponding ft node
app.getNodeFromLsItemId = function(lsItemId, tree) {
    var key;
    if ("tree2" === tree) {
        tree = app.ft2;
        key = app.keyFromLsItemId(lsItemId, app.selectedAssocGroupTree2);
    } else {
        tree = app.ft;
        key = app.keyFromLsItemId(lsItemId, app.selectedAssocGroup);
    }

    return tree.fancytree("getTree").getNodeByKey(key);
};

// Given a node, return the lsItemId as derived from the key -- or null if it's the doc node
app.lsItemIdFromNode = function(n) {
    if ("object" !== typeof(n) || app.isDocNode(n)) {
        return null;
    } else {
        return app.lsItemIdFromKey(n.key);
    }
};

app.lsItemIdFromKey = function(key) {
    // if key isn't a number, it's the document, so return null
    if (isNaN(key*1)) {
        return null;
    } else {
        // else return Math.floor of the key, to remove any assocGroup if there
        return Math.floor(key);
    }
};

app.keyFromLsItemId = function(lsItemId, assocGroup) {
    var key = lsItemId;
    // if lsItemId is == null (null or undefined), we're look for the document node
    if ("undefined" === typeof(key) || null === key) {
        key = "doc-" + app.lsDocId;
    } else {
        // else we're looking for an item node...
        // if assocGroup is set, the key should have ".x" on the end for the assocGroup
        if ("undefined" !== typeof(assocGroup) && null !== assocGroup && "" !== assocGroup && "default" !== assocGroup) {
            key += "." + assocGroup;
        } else {
            key += ".0";
        }
    }
    return key + "";
};

// Given a node, return the title html we want to show for the node
app.titleFromNode = function(node, format) {
    var data;
    if ("undefined" !== typeof(node.data) && null !== node.data) {
        data = node.data;
    } else {
        data = node;
    }

    var title;
    // document -- for some reason the title is in node and other data is in node.data
    if ("undefined" !== typeof(node.title) && null !== node.title) {
        title = node.title;
    } else {
        if ("undefined" !== typeof(data.abbrStmt) && null !== data.abbrStmt && "" !== data.abbrStmt) {
            title = data.abbrStmt;
        } else {
            title = data.fullStmt;
        }
        // if we have a humanCoding for the node, show it first in bold
        if ("undefined" !== typeof(data.humanCoding) && null !== data.humanCoding && "" !== data.humanCoding) {
            title = '<span class="item-humanCodingScheme">' + data.humanCoding + '</span> ' + title;
        }
    }
    // if format is "ftTitleSpan", return wrapped in the fancytree-title span
    if (format === "ftTitleSpan") {
        return '<span class="fancytree-title">' + title + '</span>';
        // if format is "textOnly", extract a text only version
    } else if (format === "textOnly") {
        return $('<div>' + title + '</div>').text();

        // otherwise return as is
    } else {
        return title;
    }
};

// Initialize a tooltip for a tree item
app.treeItemTooltip = function(node) {
    var $jq = $(node.span);

    var content;
    if (app.isDocNode(node)) {
        content = "Document: " + node.title;
    } else {
        content = node.data.fullStmt;
        if ("undefined" !== typeof(node.data.humanCoding) && null !== node.data.humanCoding) {
            content = '<span class="item-humanCodingScheme">' + node.data.humanCoding + '</span> ' + content;
        }
    }

    // Note: we need to make the tooltip appear on the title, not the whole node, so that we can have it persist
    // when you drag from tree2 into tree1
    $jq.find(".fancytree-title").tooltip({
        // "content": content,  // this is for popover
        "title": content,   // this is for tooltip
        "delay": { "show": 500, "hide": 100 },
        "placement": "bottom",
        "html": true,
        "container": "body"
        // "trigger": "hover"   // this is for popover
    });
};

// Show an item (usually called when user clicks the item in tree1)
app.tree1Activate = function(n) {
    // hide tree2 and show the item details section
    app.tree2Toggle(false);

    var lsItemId = app.lsItemIdFromNode(n);

    // if this item is already showing, return now (after making sure the item details, rather than tree2, is showing)
    if (lsItemId === app.lsItemId) {
        return;
    }

    // replace app.lsItemId and push history state
    app.lsItemId = lsItemId;
    app.pushHistoryState(app.lsItemId, app.selectedAssocGroup);

    // if this is the lsDoc node
    if ("undefined" === typeof(app.lsItemId) || null === app.lsItemId) {
        // show documentInfo and hide all itemInfos
        $(".itemInfo").hide();
        $("#documentInfo").show();

        // set appropriate class on itemSection
        $("#itemSection").removeClass("lsItemItemSection").addClass("docStatus-{{ lsDoc.adoptionStatus|default('Draft') }}");
    } else {
        // else it's an lsItem
        // hide documentInfo and all itemInfos
        $(".itemInfo").hide();
        $("#documentInfo").hide();

        // set appropriate class on itemSection
        $("#itemSection").removeClass("docStatus-{{ lsDoc.adoptionStatus|default('Draft') }}").addClass("lsItemItemSection");

        // if we already have an item div loaded for this item, just show it
        if (app.getLsItemDetailsJq(app.lsItemId).length > 0) {
            app.getLsItemDetailsJq(app.lsItemId).show();

            // make sure make folder and create new item buttons are set appropriately
            app.toggleItemCreationButtons();

            // else...
        } else {
            // construct and show it
            app.loadItemDetails(n);
        }
    }
};

//////////////////////////////////////////////////////
// REORDER ITEMS IN TREE1

// Called after the user has dragged-and-dropped an item
app.reorderItems = function(draggedNode, droppedNode, hitMode) {
    // note original parent
    var originalParent = draggedNode.parent;

    // move the item in the tree
    draggedNode.moveTo(droppedNode, hitMode);

    // make sure droppedNode is expanded
    droppedNode.setExpanded(true);
    droppedNode.render();

    // now saveItemOrder
    app.saveItemOrder(draggedNode, originalParent);
};

app.saveItemOrder = function(node, originalParent) {
    // update the sequenceNumber fields for the node's (possibly new) parent's children
    // (the former parent's children will still be in order, though we might want to "clean up" those sequenceNumbers too)
    var siblings = node.parent.children;
    var lsItems = {};
    for (var i = 0; i < siblings.length; ++i) {
        var key = siblings[i].key;

        // skip siblings that aren't in the current assocGroup
        if (!app.assocGroupsMatch(siblings[i].data.assoc.group, app.selectedAssocGroup)) {
            continue;
        }

        var lsItemId = app.lsItemIdFromKey(key);
        lsItems[lsItemId] = {"originalKey": key};

        // if we have an assocGroup other than default selected, add that
        if (app.selectedAssocGroup != "default") {
            lsItems[lsItemId].assocGroup = app.selectedAssocGroup;
        }

        // if we got to the dragged node and (the parent changed OR this is a just-created item, which doesn't have an assoc.id)...
        if (key === node.key && (node.parent !== originalParent || typeof(node.data.assoc.id) === "undefined") || node.data.assoc.id === null) {
            // delete the old childOf relationship for the dragged node
            lsItems[lsItemId].deleteChildOf = {
                "assocId": siblings[i].data.assoc.id
            };

            if (typeof(lsItems[lsItemId].deleteChildOf.assocId) === "undefined" || lsItems[lsItemId].deleteChildOf.assocId === null) {
                lsItems[lsItemId].deleteChildOf.assocId = "all";
            }

            // then create a new childOf relationship
            lsItems[lsItemId].newChildOf = {
                "sequenceNumber": (i + 1)
            };

            // if parent is the document...
            if (node.parent.key.search(/^doc-(.+)/) > -1) {
                // note the docId, and the fact that it's a document
                lsItems[lsItemId].newChildOf.parentId = RegExp.$1;
                lsItems[lsItemId].newChildOf.parentType = "doc";
            } else {
                // otherwise the parent is an item
                lsItems[lsItemId].newChildOf.parentId = app.lsItemIdFromKey(node.parent.key);
                lsItems[lsItemId].newChildOf.parentType = "item";
            }

            // also, in this case we should update sequenceNumber's for the original parent, in case we took the node out
            if (typeof(originalParent.children) !== "undefined" && originalParent.children !== null && originalParent.children.length > 0) {
                for (var j = 0; j < originalParent.children.length; ++j) {
                    var lsItemIdX = app.lsItemIdFromKey(originalParent.children[j].key);

                    // skip lsItemId and siblings that aren't in the current assocGroup
                    if (lsItemIdX == lsItemId || !app.assocGroupsMatch(originalParent.children[j].data.assoc.group, app.selectedAssocGroup)) {
                        continue;
                    }

                    // else parent hasn't changed, so just update the sequenceNumber
                    lsItems[lsItemIdX] = {"originalKey": originalParent.children[j].key};
                    lsItems[lsItemIdX].updateChildOf = {
                        "assocId": originalParent.children[j].data.assoc.id,
                        "sequenceNumber": (j + 1)
                    };
                }
            }
        } else {
            // else parent hasn't changed, so just update the sequenceNumber
            lsItems[lsItemId].updateChildOf = {
                "assocId": siblings[i].data.assoc.id,
                "sequenceNumber": (i + 1)
            };
        }
    }

    // ajax call to submit changes
    app.showModalSpinner("Reordering Item(s)");
    $.ajax({
        url: app.path.doctree_update_items.replace('ID', app.lsDocId),
        method: 'POST',
        data: {"lsItems": lsItems}
    }).done(function(data, textStatus, jqXHR){
        app.hideModalSpinner();
        app.updateItemsAjaxDone(data);

    }).fail(function(jqXHR, textStatus, errorThrown){
        app.hideModalSpinner();
        alert("An error occurred.");
    });
};

app.updateItemsAjaxDone = function(data) {
    for (var i = 0; i < data.length; ++i) {
        var o = data[i];
        var n = app.ft.fancytree("getTree").getNodeByKey(o.originalKey+'');
        if (n === null) {
            console.log("couldn't get node for " + o.originalKey);
        } else {
            // update key if we got back an lsItemId
            if (typeof(o.lsItemId) !== "undefined" && o.lsItemId !== null) {
                n.key = app.keyFromLsItemId(o.lsItemId, app.selectedAssocGroup);
                console.log("updating key: " + o.lsItemId + " / " + n.key);
            }

            // update fullStatement if we got it back
            if (typeof(o.fullStatement) !== "undefined" && o.fullStatement !== null) {
                n.data.fullStmt = o.fullStatement;
                n.title = null;
                n.setTitle(app.titleFromNode(n));
            }

            // update association data if we got back a sequenceNumber
            if (typeof(o.sequenceNumber) !== "undefined" && o.sequenceNumber !== null) {
                if (typeof(n.data.assoc) === "undefined" || n.data.assoc === null) {
                    n.data.assoc = {};
                }
                n.data.assoc.group = app.selectedAssocGroup;
                n.data.assoc.sequenceNumber = o.sequenceNumber;
                if (typeof(o.assocId) !== "undefined" && o.assocId !== null) {
                    n.data.assoc.id = o.assocId;
                }
            }

            // re-render node
            n.render();
        }
    }

    // remove stray tooltips
    $(".tooltip").remove();
}

//////////////////////////////////////////////////////
// COPY AN ITEM FROM TREE2 TO TREE1

// Initiate copying items from tree 2 to tree 1
app.copyItemInitiate = function() {
    app.tree2Toggle(true);
    app.tree2Mode = "copyItem";

    // if an lsItem is active, make sure it's a folder, and open it
    /*
    if (app.lsItemId != null) {
        var node = app.getNodeFromLsItemId(app.lsItemId);
        node.folder = true;
        node.setExpanded(true);
        node.render();
    }
    */

    if (app.ft2) {
        $("#tree2InitialInstructions").hide();
        $("#tree2SectionCopyInstructions").show();
        $("#tree2SectionRelationshipInstructions").hide();
    }

    // also set rightSide buttons appropriately
    $("#rightSideItemDetailsBtn").removeClass("btn-primary").addClass("btn-default");
    $("#rightSideCopyItemsBtn").addClass("btn-primary").removeClass("btn-default");
    $("#rightSideCreateAssociationsBtn").removeClass("btn-primary").addClass("btn-default");
};

app.copyItem = function(draggedNode, droppedNode, hitMode) {
    var copiedLsItemId = app.lsItemIdFromNode(draggedNode);

    draggedNode.copyTo(droppedNode, hitMode, function(n) {
        // temporarily set key to "copiedItem"
        n.key = "copiedItem";
    });

    // now, after a few milliseconds to let the copyTo complete...
    setTimeout(function() {
        // make sure droppedNode is expanded
        droppedNode.setExpanded(true);
        droppedNode.render();

        // construct ajax call to insert the new item and reorder its siblings
        var newNode = app.ft.fancytree("getTree").getNodeByKey("copiedItem");
        var siblings = newNode.parent.children;
        var lsItems = {};
        for (var i = 0; i < siblings.length; ++i) {
            var key = siblings[i].key;

            // skip siblings that aren't in the current assocGroup
            if (key != newNode.key && !app.assocGroupsMatch(siblings[i].data.assoc.group, app.selectedAssocGroup)) {
                continue;
            }

            // if we got to the new node...
            if (key == newNode.key) {
                lsItems[key] = {"originalKey": key};

                // if we have an assocGroup other than default selected, add that
                if (app.selectedAssocGroup != "default") {
                    lsItems[key].assocGroup = app.selectedAssocGroup;
                }

                // if we're copying from the same document...
                if (app.lsDocId == app.lsDoc2Id) {
                    // If the *same* assocGroup is chosen on both sides, always create a new instance of the item
                    if (app.selectedAssocGroup == app.selectedAssocGroupTree2) {
                        // set copyFromId flag so that updateItemAction will copy the item
                        lsItems[key].copyFromId = copiedLsItemId;
                        lsItems[key].addCopyToTitle = "true";
                    } else {
                        // else *different* assocGroups are chosen on both sides, so:
                        // If the item already has an isChildOf association for this assocGroup, create a new instance of the item
                        var copiedKey = app.keyFromLsItemId(copiedLsItemId, app.selectedAssocGroup);
                        console.log("copying " + copiedKey);
                        if (app.ft.fancytree("getTree").getNodeByKey(copiedKey) != null) {
                            // set copyFromId flag so that updateItemAction will copy the item
                            lsItems[key].copyFromId = copiedLsItemId;
                            lsItems[key].addCopyToTitle = "true";

                        // Else the item does not have an isChildOf association for this assocGroup,
                        // so create a new isChildOf relationship for the assocGroup, but do *not* create a new instance the item.
                        } else {
                            console.log("item doesn't exist");
                            // send an lsItems array item with copiedLsItemId
                            lsItems[copiedLsItemId] = lsItems[key];
                            delete lsItems[key];
                            key = copiedLsItemId;
                        }
                    }
                } else {
                    // else different documents
                    // set copyFromId flag so that updateItemAction will copy the item
                    lsItems[key].copyFromId = copiedLsItemId;
                }

                // create a new childOf relationship regardless of whether or not we're actually creating a copy
                lsItems[key].newChildOf = {
                    "sequenceNumber": (i + 1)
                };

                // set parentId and parentType
                // if parent is the document...
                if (newNode.parent.key.search(/^doc-(.+)/) > -1) {
                    // note the docId, and the fact that it's a document
                    lsItems[key].newChildOf.parentId = RegExp.$1;
                    lsItems[key].newChildOf.parentType = "doc";
                    // otherwise the parent is an item
                } else {
                    lsItems[key].newChildOf.parentId = app.lsItemIdFromKey(newNode.parent.key);
                    lsItems[key].newChildOf.parentType = "item";
                }

            // else it's a sibling of the new item, so just update the sequenceNumber
            } else {
                var lsItemId = app.lsItemIdFromKey(key);
                lsItems[lsItemId] = {"originalKey": key};
                lsItems[lsItemId].updateChildOf = {
                    "assocId": siblings[i].data.assoc.id,
                    "sequenceNumber": (i + 1)
                };
            }
        }

        // console.log(lsItems);

        // ajax call to submit changes
        app.showModalSpinner("Copying Item(s)");
        $.ajax({
            url: app.path.doctree_update_items.replace('ID', app.lsDocId),
            method: 'POST',
            data: {"lsItems": lsItems}
        }).done(function(data, textStatus, jqXHR){
            // hide spinner
            app.hideModalSpinner();
            app.updateItemsAjaxDone(data);

            // re-render
            newNode.render();

        }).fail(function(jqXHR, textStatus, errorThrown){
            app.hideModalSpinner();
            alert("An error occurred.");
            console.log(jqXHR, textStatus, errorThrown);
        });
    }, 50);    // end of anonymous setTimeout function
};

//////////////////////////////////////////////////////
// EDIT THE DOCUMENT OR AN ITEM

// when user double-clicks an item, wait until the item is showing on the left, then click the edit button
app.treeDblClicked = function(lsItemId) {
    // for doc, the edit button is there on page load
    if (lsItemId == null) {
        $(".btn[data-target='#editDocModal']").click();

        // for items, we can't click the button until the item details have been loaded...
    } else {
        var $btn = app.getLsItemDetailsJq(lsItemId).find(".btn[data-target='#editItemModal']");
        // so if the button is there, click it
        if ($btn.length > 0) {
            $btn.click();
            // otherwise wait 200 ms and try again
        } else {
            setTimeout(app.treeDblClicked, 200, lsItemId);
        }
    }
};

//////////////////////////////////////////////////////
// ADD A NEW CHILD TO A DOCUMENT OR ITEM
app.toggleItemCreationButtons = function() {
    // console.log("toggleItemCreationButtons");

    var $jq = $("[data-item-lsItemId=" + app.lsItemId + "]");
    var node = app.getNodeFromLsItemId(app.lsItemId);
    // if item already has children
    if ($.isArray(node.children) && node.children.length > 0) {
        // hide "Make this item a folder" button
        $jq.find("[id=toggleFolderBtn]").hide();
        // and show the "Add a new child item" button
        $jq.find("[id=addChildBtn]").show();

        // else item doesn't have children
    } else {
        // show "Make this item a folder" button
        $jq.find("[id=toggleFolderBtn]").show();

        // set the text of the toggleFolderBtn and visibility of the addChildBtn appropriately
        if (node.folder == true) {
            $jq.find("[id=toggleFolderBtn]").text("Make This Item a Child");
            $jq.find("[id=addChildBtn]").show();
        } else {
            $jq.find("[id=toggleFolderBtn]").text("Make This Item a Parent");
            $jq.find("[id=addChildBtn]").hide();
        }
    }
};

app.toggleFolders = function(itemIds, val) {
    if (!$.isArray(itemIds)) {
        itemIds = [app.lsItemId];
    }
    if (typeof(val) !== "boolean") {
        val = "toggle";
    }
    for (var i = 0; i < itemIds.length; ++i) {
        var node = app.getNodeFromLsItemId(itemIds[i]);
        if (val == "toggle") {
            node.folder = !(node.folder == true);
        } else {
            node.folder = val;
        }
        node.render();

        var src;
        if (node.folder) {
            src = "/assets/img/folder.png";
        } else {
            src = "/assets/img/item.png";
        }
        $("[data-item-lsitemid=" + itemIds[i] + "] .itemTitleIcon").attr("src", src);
    }

    app.toggleItemCreationButtons();
};

app.getAddNewChildPath = function() {
    var path;
    // if we don't have an lsItemId, we're showing/editing the doc
    if (typeof(app.lsItemId) === "undefined" || app.lsItemId === null) {
        path = app.path.lsitem_new.replace('DOC', app.lsDocId);
    } else {
        // else we're showing/editing an item
        path = app.path.lsitem_new.replace('DOC', app.lsDocId).replace('PARENT', app.lsItemId);
    }

    // if we have an assocGroup other than default selected, add that to the path
    if (app.selectedAssocGroup !== "default") {
        path += "/" + app.selectedAssocGroup;
    }

    return path;
};

app.prepareAddNewChildModal = function() {
    var $addNewChildModal = $('#addNewChildModal');
    $addNewChildModal.find('.modal-body').html(app.spinnerHtml("Loading Form"));
    $addNewChildModal.on('shown.bs.modal', function(e){
        $('#addNewChildModal').find('.modal-body').load(
            app.getAddNewChildPath(),
            null,
            function(responseText, textStatus, jqXHR){
                $('#ls_item_educationalAlignment').multiselect({
                    optionLabel: function(element) {
                        return $(element).html() + ' - ' + $(element).data('title');
                    },
                    numberDisplayed: 20
                });
                $('#ls_item_itemType').select2entity({dropdownParent: $('#addNewChildModal')});
            }
        )
    }).on('hidden.bs.modal', function(e){
        $('#addNewChildModal').find('.modal-body').html(app.spinnerHtml("Loading Form"));
    });
    $addNewChildModal.find('.btn-save').on('click', function(e) {
        app.showModalSpinner("Creating Item");
        $.ajax({
            url: app.getAddNewChildPath(),
            method: 'POST',
            data: $addNewChildModal.find('form[name=ls_item]').serialize()
        }).done(function(data, textStatus, jqXHR) {
            app.hideModalSpinner();
            // on successful add, add the item to the tree
            // returned data will be the path for the new item, which gives us the id
            var newChildData = {
                "key": app.keyFromLsItemId(data.replace(/.*\/(.*)$/, "$1"), app.selectedAssocGroup),
                "fullStmt": $("#ls_item_fullStatement").val(),
                "humanCoding": $("#ls_item_humanCodingScheme").val(),
                "abbrStmt": $("#ls_item_abbreviatedStatement").val(),
            };

            $addNewChildModal.modal('hide');

            app.addNewChild(newChildData);

        }).fail(function(jqXHR, textStatus, errorThrown){
            app.hideModalSpinner();
            $addNewChildModal.find('.modal-body').html(jqXHR.responseText);
            $('#ls_item_educationalAlignment').multiselect({
                optionLabel: function(element) {
                    return $(element).html() + ' - ' + $(element).data('title');
                },
                numberDisplayed: 20
            });
            $('#ls_item_itemType').select2entity({dropdownParent: $('#addNewChildModal')});
        });
    });
};

// Add new child item to tree1
app.addNewChild = function(data) {
    // construct the title
    data.title = app.titleFromNode(data);

    // add assoc
    if (app.selectedAssocGroup != "default") {
        data.assoc = {"group": app.selectedAssocGroup};
    } else {
        data.assoc = {"group": ""};
    }

    // get the parentNode (current item) and add the child to the parent
    var parentNode = app.getNodeFromLsItemId(app.lsItemId);
    parentNode.addChildren([data]);

    if (!parentNode.folder) {
        parentNode.folder = true;
        parentNode.setExpanded(true);
        parentNode.render();
    }

    // enable the tooltip on the new child
    var newNode = app.getNodeFromLsItemId(app.lsItemIdFromKey(data.key));
    app.treeItemTooltip(newNode);

    // and now we have to saveItemOrder
    app.saveItemOrder(newNode, parentNode);

    // hide/enable make folder and create new item buttons on parent appropriately
    app.toggleItemCreationButtons();
};

//////////////////////////////////////////////////////
// EDIT THE DOCUMENT OR AN ITEM

app.prepareEditDocModal = function() {
    var $editDocModal = $('#editDocModal');
    $editDocModal.find('.modal-body').html(app.spinnerHtml("Loading Form"));
    $editDocModal.on('shown.bs.modal', function(e){
        $('#editDocModal').find('.modal-body').load(
            app.path.lsdoc_edit.replace('ID', app.lsDocId),
            null,
            function(responseText, textStatus, jqXHR){
                $('#ls_doc_subjects').select2entity({dropdownParent: $('#editDocModal')});
            }
        )
    }).on('hidden.bs.modal', function(e){
        $('#editDocModal').find('.modal-body').html(app.spinnerHtml("Loading Form"));
    });
    $editDocModal.find('.btn-save').on('click', function(e){
        app.showModalSpinner("Updating Document");
        $.ajax({
            url: app.path.lsdoc_edit.replace('ID', app.lsDocId),
            method: 'POST',
            data: $editDocModal.find('form[name=ls_doc]').serialize()
        }).done(function(data, textStatus, jqXHR){
            $editDocModal.modal('hide');
            // on successful update, reload the doc; too hard to dynamically update everything here.
            window.location.reload();
            /*
               var updatedData = {
               "title": $("#ls_doc_title").val(),
               "version": $("#ls_doc_version").val(),
               "adoptionStatus": $("#ls_doc_adoptionStatus").val(),
               };
               */

        }).fail(function(jqXHR, textStatus, errorThrown){
            $editDocModal.find('.modal-body').html(jqXHR.responseText);
            $('#ls_doc_subjects').select2entity({dropdownParent: $('#editDocModal')});
        });
    });
};

app.prepareEditItemModal = function() {
    var $editItemModal = $('#editItemModal');
    $editItemModal.find('.modal-body').html(app.spinnerHtml("Loading Form"));
    $editItemModal.on('shown.bs.modal', function(e){
        $('#editItemModal').find('.modal-body').load(
            app.path.lsitem_edit.replace('ID', app.lsItemId),
            null,
            function(responseText, textStatus, jqXHR) {
                $('#ls_item_educationalAlignment').multiselect({
                    optionLabel: function(element) {
                        return $(element).html() + ' - ' + $(element).data('title');
                    },
                    numberDisplayed: 20
                });
                $('#ls_item_itemType').select2entity({dropdownParent: $('#editItemModal')});
            }
        )
    }).on('hidden.bs.modal', function(e){
        $('#editItemModal').find('.modal-body').html(app.spinnerHtml("Loading Form"));
    });
    $editItemModal.find('.btn-save').on('click', function(e){
        app.showModalSpinner("Updating Item");
        $.ajax({
            url: app.path.lsitem_edit.replace('ID', app.lsItemId),
            method: 'POST',
            data: $editItemModal.find('form[name=ls_item]').serialize()
        }).done(function(data, textStatus, jqXHR){
            app.hideModalSpinner();
            // on successful edot, update the item to the tree
            var updatedData = {
                "fullStmt": $("#ls_item_fullStatement").val(),
                "humanCoding": $("#ls_item_humanCodingScheme").val(),
                "abbrStmt": $("#ls_item_abbreviatedStatement").val(),
            };
            $editItemModal.modal('hide');
            app.updateEditedItem(updatedData);

        }).fail(function(jqXHR, textStatus, errorThrown){
            app.hideModalSpinner();
            $editItemModal.find('.modal-body').html(jqXHR.responseText);
            $('#ls_item_educationalAlignment').multiselect({
                optionLabel: function(element) {
                    return $(element).html() + ' - ' + $(element).data('title');
                },
                numberDisplayed: 20
            });
            $('#ls_item_itemType').select2entity({dropdownParent: $('#editItemModal')});
        });
    });
};

app.updateEditedItem = function(data) {
    var node = app.getNodeFromLsItemId(app.lsItemId);

    // update node.data and set title
    node.data.fullStmt = data.fullStmt;
    node.data.humanCoding = data.humanCoding;
    node.data.abbrStmt = data.abbrStmt;
    node.setTitle(app.titleFromNode(data));

    // update tree tooltip
    app.treeItemTooltip(node);

    // clear details and reload
    app.clearItemDetails(app.lsItemId);
    app.loadItemDetails(app.lsItemId);
};

//////////////////////////////////////////////////////
// CREATE/DELETE ASSOCIATIONS BETWEEN ITEMS

// Prepare the modal dialog used to select the type of relationship to be formed
app.prepareAssociateModal = function() {
    var $associateModal = $('#associateModal');
    $associateModal.find('.modal-body').html(app.spinnerHtml("Loading Form"));
    $associateModal.on('shown.bs.modal', function(e){
        // we need a path using the first draggedNode
        var path = app.path.lsassociation_tree_new;
        path = path.replace('ORIGIN_ID', app.lsItemIdFromNode(app.createAssociationNodes.droppedNode));
        path = path.replace('DESTINATION_ID', app.lsItemIdFromNode(app.createAssociationNodes.draggedNodes[0]));

        // if we have an assocGroup other than default selected, add that to the path
        if (app.selectedAssocGroup != "default") {
            path += "/" + app.selectedAssocGroup;
        }

        $('#associateModal').find('.modal-body').load(
            path,
            null,
            // Call app.createAssociationModalLoaded when modal is loaded
            function(responseText, textStatus, jqXHR){ app.createAssociationModalLoaded() }
        )
    }).on('hidden.bs.modal', function(e){
        $('#associateModal').find('.modal-body').html(app.spinnerHtml("Loading Form"));
    });
    $associateModal.find('.btn-save').on('click', function(e){ app.createAssociationRun(); });
};

// initiate adding an association from tree2 to tree1
app.addAssociation = function() {
    app.tree2Toggle(true);
    app.tree2Mode = "addAssociation";

    if (app.tree2Showing) {
        $("#tree2InitialInstructions").hide();
        $("#tree2SectionCopyInstructions").hide();
        $("#tree2SectionRelationshipInstructions").show();
    }

    // also change rightSideItemDetailsBtn to primary and other two rightSide buttons to default
    $("#rightSideItemDetailsBtn").removeClass("btn-primary").addClass("btn-default");
    $("#rightSideCopyItemsBtn").removeClass("btn-primary").addClass("btn-default");
    $("#rightSideCreateAssociationsBtn").addClass("btn-primary").removeClass("btn-default");
};

// called when user drags and drops an item from tree2 to tree1 to create an association
app.createAssociation = function(draggedNodes, droppedNode) {
    // remember dragged and dropped nodes while we make the call to open the form
    app.createAssociationNodes = {
        "draggedNodes": draggedNodes,
        "droppedNode": droppedNode
    };

    // then open the modal form
    $('#associateModal').modal();
};

// callback after ajax to load associate form
app.createAssociationModalLoaded = function() {
    // show origin and destination
    var destination = app.titleFromNode(app.createAssociationNodes.draggedNodes[0]);
    if (app.createAssociationNodes.draggedNodes.length > 1) {
        destination += " <b>+" + (app.createAssociationNodes.draggedNodes.length-1) + " additional item(s)</b>";
    }
    var origin = app.titleFromNode(app.createAssociationNodes.droppedNode);
    $("#lsAssociationDestinationDisplay").html(destination);
    $("#lsAssociationOriginDisplay").html(origin);
    
    // add association group menu if we have one and there's more than one item (the first item is always "default") in the menu
    var agMenu = $("#treeSideLeft").find(".assocGroupSelect");
    if (agMenu.find("option").length > 1) {
        agMenu = agMenu.clone();
        agMenu.attr("id", "ls_association_tree_group");
        $("#ls_association_tree").append(
              '<div id="ls_association_tree_group_form_group" class="form-group">'
            + '<label class="col-sm-2 control-label required" for="ls_association_tree_group">Association Group</label>'
            + '<div class="col-sm-10" id="ls_association_tree_group_holder"></div>'
            + '</div>'
        );
        $("#ls_association_tree_group_holder").append(agMenu);
        
        // if an assocGroup other than default is selected, select that group in the menu
        if (app.selectedAssocGroup != "default") {
            $("#ls_association_tree_group").val(app.selectedAssocGroup);
        }
    }
};

app.createAssociationRun = function() {
    var $associateModal = $('#associateModal');
    var ajaxData = $associateModal.find('form[name=ls_association_tree]').serialize();

    app.showModalSpinner("Saving Association(s)");

    // go through all the draggedNodes
    var completed = 0;
    for (var i = 0; i < app.createAssociationNodes.draggedNodes.length; ++i) {
        // construct path for this association
        var path = app.path.lsassociation_tree_new;
        // the "origin" refers to the node that's 'receiving' the association -- so this is the droppedNode
        // the "destination" refers to the node that's being associated with the origin node -- so this is the draggedNode
        path = path.replace('ORIGIN_ID', app.lsItemIdFromNode(app.createAssociationNodes.droppedNode));
        path = path.replace('DESTINATION_ID', app.lsItemIdFromNode(app.createAssociationNodes.draggedNodes[i]));

        // if an assocGroup is selected via ls_association_tree_group and isn't default, add that to the path
        var agMenu = $("#ls_association_tree_group");
        if (agMenu.length > 0 && agMenu.val() != "default") {
            path += "/" + agMenu.val();
        }

        $.ajax({
            url: path,
            method: 'POST',
            data: ajaxData
        }).done(function(data, textStatus, jqXHR) {
            // increment completed counter
            ++completed;

            // if all are completed, finish up
            if (completed == app.createAssociationNodes.draggedNodes.length) {
                app.hideModalSpinner();
                $associateModal.modal('hide');

                // clear and reload item details for droppedNode
                var lsItemId = app.lsItemIdFromNode(app.createAssociationNodes.droppedNode);
                app.clearItemDetails(lsItemId);
                app.loadItemDetails(lsItemId);

                // clear createAssociationNodes
                app.createAssociationNodes = null;
            }

        }).fail(function(jqXHR, textStatus, errorThrown){
            app.hideModalSpinner();
            $associateModal.find('.modal-body').html(jqXHR.responseText);
        });
    }
};

app.deleteAssociation = function(e) {
    e.preventDefault();

    var $target = $(e.target);
    var $item = $target.parents('.lsassociation');

    app.showModalSpinner("Removing Association");
    $.ajax({
        url: app.path.lsassociation_remove.replace('ID', $item.data('associationId')),
        method: 'POST'
    }).done(function(data, textStatus, jqXHR){
        app.hideModalSpinner();
        // after deletion, clear and reload item details
        app.clearItemDetails(app.lsItemId);
        app.loadItemDetails(app.lsItemId);

    }).fail(function(jqXHR, textStatus, errorThrown){
        app.hideModalSpinner();
        alert("An error occurred.");
    });
};

//////////////////////////////////////////////////////
// CREATE/DELETE EXEMPLARS FOR ITEMS

// Prepare the modal dialog
app.prepareExemplarModal = function() {
    var $exemplarModal = $('#addExemplarModal');
    $exemplarModal.on('shown.bs.modal', function(e){
        var title = app.titleFromNode(app.getNodeFromLsItemId(app.lsItemId));
        $("#addExemplarOriginTitle").html(title);
    });
    $exemplarModal.find('.btn-save').on('click', function(e){ app.createExemplarRun(); });
};

app.createExemplarRun = function() {
    var $exemplarModal = $('#addExemplarModal');
    // var ajaxData = $exemplarModal.find('form[name=ls_association_tree]').serialize();

    // TODO: send ajax request to create the exemplar
    var ajaxData = {
        exemplarUrl: $("#addExemplarFormUrl").val(),
        exemplarDescription: $("#addExemplarFormDescription").val(),
        associationType: "Exemplar"
    };
    if (ajaxData.exemplarUrl == "") {
        alert("You must enter a URL to create an exemplar.");
        return;
    }

    app.showModalSpinner("Saving Exemplar");

    // construct path for this association
    var path = app.path.lsassociation_tree_new_exemplar;
    path = path.replace('ORIGIN_ID', app.lsItemId);

    $.ajax({
        url: path,
        method: 'POST',
        data: ajaxData
    }).done(function(data, textStatus, jqXHR) {
        app.hideModalSpinner();
        $exemplarModal.modal('hide');

        // clear form fields
        $("#addExemplarFormUrl").val("");
        $("#addExemplarFormDescription").val("");

        // clear and reload item details for the selected item
        app.clearItemDetails(app.lsItemId);
        app.loadItemDetails(app.lsItemId);

    }).fail(function(jqXHR, textStatus, errorThrown){
        app.hideModalSpinner();
        $exemplarModal.find('.modal-body').html(jqXHR.responseText);
    });
};

//////////////////////////////////////////////////////
// DELETE AN ITEM

app.deleteItems = function(itemIds) {
    var deleteItemsInternal = function(itemIds) {
        // activate document node
        app.getNodeFromLsItemId(null, "tree1").setActive();

        // show "Deleting" spinner
        app.showModalSpinner("Deleting");

        var completed = 0;
        for (var i = 0; i < itemIds.length; ++i) {
            console.log("deleting " + itemIds[i]);

            var node = app.getNodeFromLsItemId(itemIds[i]);
            if (node !== null) {
                // delete node and set some properties of parent if we have one. It would be better to do this after the ajax has returned,
                // but it's tricky to know which item was deleted inside the .done function. If there was an error in the deletion process,
                // the user is probably going to reload the browser anyway.
                var parentNode = node.parent;
                node.remove();
                if (!app.isDocNode(parentNode) && (!$.isArray(parentNode.children) || parentNode.children.length === 0)) {
                    parentNode.folder = false;
                    parentNode.setExpanded(false);
                    parentNode.render();
                }

                // if the item exists in a different assocGroup, only delete the association, not the item
                var itemExistsInAnotherGroup = false;
                var lsItems = null;
                for (var assocGroupId in app.allAssocGroups) {
                    if (app.ft.fancytree("getTree").getNodeByKey(app.keyFromLsItemId(itemIds[i], assocGroupId)) != null) {
                        itemExistsInAnotherGroup = true;
                        lsItems = {};
                        lsItems[itemIds[i]] = {
                            "originalKey": node.key,
                            "deleteChildOf": {
                                "assocId": node.data.assoc.id
                            }
                        };
                        break;
                    }
                }

                // if item exists in another group, use update_items service to delete association
                if (itemExistsInAnotherGroup) {
                    $.ajax({
                        url: app.path.doctree_update_items.replace('ID', app.lsDocId),
                        method: 'POST',
                        data: {"lsItems": lsItems}
                    }).done(function (data, textStatus, jqXHR) {
                        // if we're done hide the spinner
                        ++completed;
                        console.log("completed: " + completed);
                        if (completed === itemIds.length) {
                            app.hideModalSpinner();
                        }

                    }).fail(function (jqXHR, textStatus, errorThrown) {
                        alert("An error occurred.");
                        // console.log(jqXHR.responseText);
                    });


                // else use delete service to delete item
                } else {
                    $.ajax({
                        // for now at least, we always send "1" in for the "CHILDREN" parameter
                        url: app.path.lsitem_tree_delete.replace('ID', itemIds[i]).replace('CHILDREN', 1),
                        method: 'POST'
                    }).done(function (data, textStatus, jqXHR) {
                        // if we're done hide the spinner
                        ++completed;
                        console.log("completed: " + completed);
                        if (completed === itemIds.length) {
                            app.hideModalSpinner();
                        }

                    }).fail(function (jqXHR, textStatus, errorThrown) {
                        alert("An error occurred.");
                        // console.log(jqXHR.responseText);
                    });
                }
            } else {
                ++completed;
            }
        }
    };

    // if itemIds isn't an array, use selected item
    if (!$.isArray(itemIds)) {
        itemIds = [app.lsItemId];
    }

    // make user confirm
    var modalId;
    if (itemIds.length === 1) {
        var node = app.getNodeFromLsItemId(itemIds[0]);
        if ($.isArray(node.children) && node.children.length > 0) {
            modalId = '#deleteItemAndChildrenModal';
        } else {
            modalId = '#deleteOneItemModal'
        }
    } else {
        // fill count of deleted items in to deleteMultipleItemsModalCount
        $("#deleteMultipleItemsModalCount").text(itemIds.length);
        modalId = '#deleteMultipleItemsModal';
    }

    $(modalId).modal()
    .one('click', '.btn-delete', function() {
        $(this).closest('.modal').modal('hide');
        deleteItemsInternal(itemIds);
    });
};

/////////////////////////////////////////////////////
// FILTER ON TREES

app.filterOnTrees = function() {
    var debounce = (function() {
        var timeout = null;
        return function(callback, wait) {
            if (timeout) { clearTimeout(timeout); }
            timeout = setTimeout(callback, wait);
        };
    })();

    $(".treeFilter").on('keyup', function() {
        var $that = $(this);
        $tree = app.getTreeFromInput($that).fancytree("getTree");
        debounce(function(){
            if ($that.val().trim().length > 0) {
                $tree.filterNodes($that.val(), {
                    autoExpand: true,
                    leavesOnly: false
                });
                console.log("Show filterClear");
                $that.parent().find(".filterClear").show();

            } else {
                $tree.clearFilter();
                console.log("Hide filterClear");
                $that.parent().find(".filterClear").hide();
            }
        }, 500);
    });

    // clear buttons for search fields
    $(".filterClear").on('click', function() {
        $(this).parent().find(".treeFilter").val("").trigger("keyup");
    });
};

/////////////////////////////////////////////////////
// TAXONOMIES (ASSOCIATION GROUPS)

app.initializeAssocGroups = function() {
    app.initializeManageAssocGroupButtons();

    // change event on assocGroup menus
    $("#treeSideLeft").find(".assocGroupSelect").off().on('change', function() { app.processAssocGroups('tree1', this); });
    $("#treeSideRight").find(".assocGroupSelect").off().on('change', function() { app.processAssocGroups('tree2', this); });

    // if we got an initialAssocGroup, set app.selectedAssocGroup
    if (null !== app.initialAssocGroup) {
        app.selectedAssocGroup = app.initialAssocGroup;
    } else {
        // else get assocGroup from currently-loaded item
        if ("undefined" !== typeof(app.lsItemId)) {
            app.selectedAssocGroup = app.getNodeFromLsItemId(app.lsItemId).data.assoc.group;
        } else {
            app.selectedAssocGroup = "default";
        }

        // if it's "all", the document is loaded, so use default
        if (typeof(app.selectedAssocGroup) === "undefined" || app.selectedAssocGroup === null || app.selectedAssocGroup === "" || app.selectedAssocGroup === "all") {
            app.selectedAssocGroup = "default";
        }
    }

    // process association groups for tree1
    app.processAssocGroups('tree1');
};

app.initializeManageAssocGroupButtons = function() {
    // initialize buttons in association group modal
    $(".assocgroup-edit-btn").off('click').on('click', function() { app.assocGroupEdit(this); });
    $(".assocgroup-delete-btn").off('click').on('click', function() { app.assocGroupDelete(this); });
};

app.assocGroupsMatch = function(ag1, ag2) {
    // null, "default", and "all" are all the same thing
    if (typeof(ag1) === "undefined" || ag1 === null || ag1 === "" || ag1 === "default" || ag1 === "all") {
        ag1 = "default";
    }
    if (typeof(ag2) === "undefined" || ag2 === null || ag2 === "" || ag2 === "default" || ag2 === "all") {
        ag2 = "default";
    }
    return (ag1+"" === ag2+"");
};

app.assocGroupEdit = function(btn) {
    // get assocGroup to delete
    var assocGroupId = $(btn).closest("[data-assocgroupid]").attr("data-assocgroupid");
    console.log(assocGroupId);

    // hide the manage modal
    $("#manageAssocGroupsModal").modal('hide');

    var $editAssocGroupModal = $('#editAssocGroupModal');
    $editAssocGroupModal.find('.modal-body').html(app.spinnerHtml("Loading Form"));
    $editAssocGroupModal.modal('show').on('shown.bs.modal', function(e){
        $('#editAssocGroupModal').find('.modal-body').load(
            app.path.lsdef_association_grouping_edit.replace('ID', assocGroupId),
            null,
            function(responseText, textStatus, jqXHR) {
                // select this document from the document select menu, then hide the menu
                $("#ls_def_association_grouping_lsDoc").val(app.lsDocId);
                $("#ls_def_association_grouping_lsDoc").closest(".form-group").hide();
            }
        )
    }).on('hidden.bs.modal', function(e){
        $('#editAssocGroupModal').find('.modal-body').html(app.spinnerHtml("Loading Form"));
    });
    $editAssocGroupModal.find('.btn-save').off().on('click', function(e){
        app.showModalSpinner("Updating Group");
        $.ajax({
            url: app.path.lsdef_association_grouping_edit.replace('ID', assocGroupId),
            method: 'POST',
            data: $editAssocGroupModal.find('form[name=ls_def_association_grouping]').serialize()
        }).done(function(data, textStatus, jqXHR){
            app.hideModalSpinner();
            // on successful edit, update the item...
            var title = $("#ls_def_association_grouping_title").val();
            var description = $("#ls_def_association_grouping_description").val();
            if (description == "") description = "—";

            // in the modal
            var $tr = $("tr[data-assocgroupid=" + assocGroupId + "]");
            $tr.find("td").first().html(title);
            $tr.find(".assocgroup-description").html(description);

            // in the allAssocGroups array
            app.allAssocGroups[assocGroupId] = {
                "id": assocGroupId,
                "title": title,
                "lsDocId": app.lsDocId
            };

            // and in the select menu
            $("#treeSideLeft .assocGroupSelect option[value=" + assocGroupId + "]").html(title);

            // hide assoc group edit modal; show manage modal
            $editAssocGroupModal.modal('hide');
            $("#manageAssocGroupsModal").modal('show');
        }).fail(function(jqXHR, textStatus, errorThrown){
            app.hideModalSpinner();
            $editAssocGroupModal.find('.modal-body').html(jqXHR.responseText);
        });
    });

    // if you cancel the edit assoc group modal, re-open the manage modal
    $editAssocGroupModal.find('.modal-footer .btn-default').on('click', function(e) {
        $("#manageAssocGroupsModal").modal('show');
    });
};

app.assocGroupDelete = function(btn) {
    // get assocGroup to delete
    var assocGroupId = $(btn).closest("[data-assocgroupid]").attr("data-assocgroupid");

    // hide the manage modal
    $("#manageAssocGroupsModal").modal('hide');

    // show confirmation modal
    $("#deleteAssocGroupModal").modal()
    .one('click', '.btn-delete', function() {
        $(this).closest('.modal').modal('hide');

        // show "Deleting" spinner
        app.showModalSpinner("Deleting");

        $.ajax({
            url: app.path.lsdef_association_grouping_tree_delete.replace('ID', assocGroupId),
            method: 'POST'
        }).done(function (data, textStatus, jqXHR) {
            // hide the spinner
            app.hideModalSpinner();
            // remove from the allAssocGroups array
            delete app.allAssocGroups[assocGroupId];

            // $("#treeSideLeft .assocGroupSelect option[value=" + assocGroupId + "]").remove();
            app.processAssocGroups("tree1");

            // remove from the manage modal, then reshow it
            $("tr[data-assocgroupid=" + assocGroupId + "]").remove();
            // re-show the manage modal
            $("#manageAssocGroupsModal").modal('show');
        }).fail(function (jqXHR, textStatus, errorThrown) {
            alert("An error occurred.");
            // console.log(jqXHR.responseText);
        });
    });
};

app.processAssocGroups = function(tree, menu) {
    var assocGroup = null, $treeSide, ft;
    var includedAssocGroups = [];
    var showMenu = false;

    // if menu provided, get assocGroup from it
    if (typeof(menu) !== "undefined" && menu !== null) {
        assocGroup = $(menu).val();
    }

    // for left-side tree...
    if (tree === "tree1" || tree === "viewmode_tree") {
        // if no menu passed in, use current selectedAssocGroup
        if ("undefined" === typeof(assocGroup) || "" === assocGroup || null === assocGroup) {
            assocGroup = app.selectedAssocGroup;
        } else {
            // else set app.selectedAssocGroup
            app.selectedAssocGroup = assocGroup;
        }

        // if the currently-showing item isn't part of this group, select the document in the tree
        var n = app.getNodeFromLsItemId(app.lsItemId);
        if (typeof(n) === "undefined" || n === null || !app.assocGroupsMatch(n.data.assoc.group, app.selectedAssocGroup)) {
            app.getNodeFromLsItemId(null, "tree1").setActive();
        }

        // if we're processing tree1, all assocGroups listed as belonging to that document should be shown,
        // even if there aren't any items associated with the group
        for (var assocGroupId in app.allAssocGroups) {
            if (app.allAssocGroups[assocGroupId].lsDocId === app.lsDocId) {
                includedAssocGroups.push(assocGroupId + "");
                showMenu = true;
            }
        }

        $treeSide = $("#treeSideLeft");
        ft = app.ft;

        // if item was selected from the menu, push a history state
        if (typeof(menu) !== "undefined" && menu !== null) {
            app.pushHistoryState(app.lsItemId, app.selectedAssocGroup);
        }
    } else {
        // else right-side tree...
        // if no menu passed in, use current selectedAssocGroupTree2
        if (typeof(assocGroup) === "undefined" || assocGroup === null || assocGroup === "") {
            assocGroup = app.selectedAssocGroupTree2;
        }
        // and if we still don't have a value, use default
        if (typeof(assocGroup) === "undefined" || assocGroup === null || assocGroup === "") {
            assocGroup = "default";
        }
        app.selectedAssocGroupTree2 = assocGroup;

        $treeSide = $("#treeSideRight");
        ft = app.ft2;
    }

    // everything is going to be unselected here, so deselect treeCheckboxControl
    $treeSide.find(".treeCheckboxControl").prop("checked", false);

    // hide all items in the tree that don't match the given assocGroup
    // and construct a list of assocGroups used
    function processChildren(parent) {
        // make sure parent isn't selected (so that if you had things selected via checkboxes and then used the menu to change groups,
        // those selections would be cleared)
        parent.setSelected(false);

        if (parent.data.assoc.group !== "all") {
            var pag = parent.data.assoc.group + "";
            if ("all" === pag || "" === pag) {
                pag = "default";
            }
            // update includedAssocGroups list
            if ($.inArray(pag, includedAssocGroups) === -1 && pag !== "default") {
                includedAssocGroups.push(pag);
                showMenu = true;
            }

            if (app.assocGroupsMatch(assocGroup, pag)) {
                // matches assoc group, so show it
                $(parent.li).show();
                parent.data.assocGroupShowing = true;
                //console.log("show " + parent.title);
            } else {
                // doesn't match assoc group, so hide it
                $(parent.li).hide();
                parent.data.assocGroupShowing = false;
                //console.log("hide " + parent.title);
            }
        }

        // now process children of parent
        if ($.isArray(parent.children)) {
            for (var i = 0; i < parent.children.length; ++i) {
                processChildren(parent.children[i]);
            }
        }
    }
    processChildren(ft.fancytree("getTree").getFirstChild());

    // clear the assocGroup menu
    $treeSide.find(".assocGroupSelect").html('');

    // if we should be showing the menu...
    if (showMenu) {
        $treeSide.find(".assocGroupSelect").append('<option value="default">– Default Group –</option>');

        for (var i = 0; i < includedAssocGroups.length; ++i) {
            if (includedAssocGroups[i] == "") continue;

            var ag = app.allAssocGroups[includedAssocGroups[i]];
            $treeSide.find(".assocGroupSelect").append('<option value="' + ag.id + '">' + ag.title + '</option>');
        }

        // then show the menu
        $treeSide.find(".assocGroupFilter").show();

        // and select this assocGroup's menu item
        $treeSide.find(".assocGroupSelect").val(assocGroup)

    // else hide the menu
    } else {
        $treeSide.find(".assocGroupFilter").hide();
    }
};

app.prepareAddAssocGroupModal = function() {
    var $addAssocGroupModal = $('#addAssocGroupModal');
    $addAssocGroupModal.find('.modal-body').html(app.spinnerHtml("Loading Form"));
    $addAssocGroupModal.on('show.bs.modal', function(e){
        $("#manageAssocGroupsModal").modal('hide');
    }).on('shown.bs.modal', function(e){
        $('#addAssocGroupModal').find('.modal-body').load(
            app.path.lsdef_association_grouping_new,
            null,
            function(responseText, textStatus, jqXHR) {
                // select this document from the document select menu, then hide the menu
                $("#ls_def_association_grouping_lsDoc").val(app.lsDocId);
                $("#ls_def_association_grouping_lsDoc").closest(".form-group").hide();
            }
        )
    }).on('hidden.bs.modal', function(e){
        $('#addAssocGroupModal').find('.modal-body').html(app.spinnerHtml("Loading Form"));
    });
    $addAssocGroupModal.find('.btn-save').on('click', function(e) {
        app.showModalSpinner("Creating Item");
        $.ajax({
            url: app.path.lsdef_association_grouping_new,
            method: 'POST',
            data: $addAssocGroupModal.find('form[name=ls_def_association_grouping]').serialize()
        }).done(function(data, textStatus, jqXHR) {
            // returned data will be the new item id

            app.hideModalSpinner();

            // on successful add, add the item to the allAssocGroups list
            var newAssocGroupId = data;
            var title = $("#ls_def_association_grouping_title").val();
            app.allAssocGroups[newAssocGroupId] = {
                "id": newAssocGroupId,
                "title": title,
                "lsDocId": app.lsDocId
            };

            // and add it to the manage groups modal
            var html = '<tr data-assocgroupid="' + newAssocGroupId + '">';
            html += '<td>' + title + '</td>';
            html += '<td>';
            html += '<button class="assocgroup-edit-btn btn btn-default btn-xs pull-right">Edit</button>';
            html += '<button class="assocgroup-delete-btn btn btn-default btn-xs pull-right" style="margin-right:5px">Delete</button>';
            html += '<span class="assocgroup-description">' + $("#ls_def_association_grouping_description").val() + '</span>';
            html += '</td>';
            html += '</tr>';
            $("#manageAssocGroupsModal").find("tbody").append(html);
            app.initializeManageAssocGroupButtons();

            // add it to the select menu
            app.processAssocGroups("tree1");
            // $("#treeSideLeft .assocGroupSelect").append('<option value="' + newAssocGroupId + '">' + title + '</option>');

            // hide the add modal and show the manage modal
            $addAssocGroupModal.modal('hide');
            $("#manageAssocGroupsModal").modal('show');

        }).fail(function(jqXHR, textStatus, errorThrown){
            app.hideModalSpinner();
            $addAssocGroupModal.find('.modal-body').html(jqXHR.responseText);
        });
    });

    // if you cancel the new assoc group modal, re-open the manage modal
    $addAssocGroupModal.find('.modal-footer .btn-default').on('click', function(e) {
        $("#manageAssocGroupsModal").modal('show');
    });
};

