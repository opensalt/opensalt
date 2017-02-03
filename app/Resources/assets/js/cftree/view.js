//////////////////////////////////////////////////////
// SET UP APP OBJECT
window.app = window.app||{};
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
    var key = event.state;
    // set popStateActivate so we don't re-push this history state
    app.popStateActivate = true;
    app.ft.fancytree("getTree").activateKey(key);
};

// Function to update the history state; called when a tree node is activated
app.pushHistoryState = function(key, path) {
    // if we just called this after the user clicked back or forward, though, don't push a new state
    if (app.popStateActivate != true) {
        window.history.pushState(key, "Competency Framework", path);
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
app.loadItemDetails = function(lsItemId) {
    // clone the itemInfoTemplate
    $jq = $("#itemInfoTemplate").clone();

    // add lsItemId
    $jq.attr("data-item-lsItemId", lsItemId);

    // fill in the title, which we can get from the item's tree node
    var n = app.getNodeFromLsItemId(lsItemId);
    $jq.find(".itemTitle").html(app.titleFromNode(n));
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
            $jq.find("[data-target=deleteItem]").on('click', app.deleteItem);

            // enable addAssociation button
            $jq.find("[data-target=addAssociation]").on('click', app.addAssociation);

            // enable copyItem button
            $jq.find("[data-target=copyItem]").on('click', app.copyItemInitiate);

            // enable remove association button(s)
            $jq.find(".btn-remove-association").on('click', function(e) { app.deleteAssociation(e); });

            // new item button doesn't need to be enabled because it shows a dialog
        }
    )
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

app.processTree = function(tree) {
    // first make sure the node's title attribute is filled in and matches what will appear in the window
    tree.title = app.titleFromNode(tree);

    // if tree has any children
    if (tree.children != null && tree.children.length > 0) {
        // sort children by listEnum
        tree.children.sort(function(a,b) {
            var leA = a.listEnum * 1;
            var leB = b.listEnum * 1;
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
    app.processTree(app.tree1[0]);

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
            smartRevert: true,
            // focusOnClick: true,
            // this function seems to need to be defined for the dnd functionality to work
            dragStart: function(node, data) {
                // don't allow the document to be dragged
                var lsItemId = app.lsItemIdFromNode(node);
                if (lsItemId == null) {
                    return false;
                } else {
                    return true;
                }
            },
            //dragStop: function(node, data){ console.log('dragStop'); },
            //initHelper: function(){ console.log('initHelper'); },
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
                        return ["before", "after"];
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
                        if (droppedNode.folder == true) {
                            return true;
                        } else {
                            return ["before", "after"];
                        }
                    }
                }
            },

            dragDrop: function(droppedNode, data){
                var draggedNode = data.otherNode;

                // determine if this is inter- or intra-tree drag
                var treeDraggedFrom = "tree1";
                if (droppedNode.tree != draggedNode.tree) {
                    treeDraggedFrom = "tree2";
                }

                var draggedItemId = app.lsItemIdFromNode(draggedNode);
                var droppedItemId = app.lsItemIdFromNode(droppedNode);
                var hitMode = data.hitMode;

                console.log('tree1 dragDrop from ' + treeDraggedFrom + ' (tree2Mode: ' + app.tree2Mode + '): ' + draggedItemId + ' to ' + hitMode + ' ' + droppedItemId);

                // intra-tree drag
                if (treeDraggedFrom == "tree1") {
                    // move the item in the tree
                    app.reorderItems(draggedNode, droppedNode, data.hitMode);

                    // inter-tree drag
                } else {
                    // if we're in associate mode, show choice for what type of association to add
                    if (app.tree2Mode == "addAssociation") {
                        app.createAssociation(draggedNode, droppedNode);

                        // else if we're in copy mode; copy node to new tree
                    } else if (app.tree2Mode == "copyItem") {
                        app.copyItem(draggedNode, droppedNode, data.hitMode);
                    }
                }
            }
        },

        // we don't currently need the below functions
        // beforeSelect: function(event, data){console.log(event, data);},
        // select: function(event, data){console.log(event, data);},

        // debugLevel:2
    });
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

    }).fail(function(jqXHR, textStatus, errorThrown){
        $('#viewmode_tree2').html("ERROR:" + jqXHR.responseText);
        $('#ls_doc_list_lsDoc').val("");
    });
}

// Render tree2 to copy items or create associations
app.renderTree2 = function() {
    // first process the tree
    app.processTree(app.tree2[0]);

    $('#viewmode_tree2').html("");

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
                if (lsItemId == null) {
                    return false;
                } else {
                    return true;
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
                // we should never get here
                console.log('tree2 dragDrop (' + app.tree2Mode + '): ' + draggedItemId + ' to ' + hitMode + ' ' + droppedItemId);
            }
        },
    });
}

// Toggle visibility of tree2 / the item details section
app.tree2Showing = false;
app.tree2Toggle = function(showTree2) {
    if (showTree2 === true || showTree2 === false) {
        app.tree2Showing = showTree2;
    } else {
        app.tree2Showing = !app.tree2Showing;
    }

    if (app.tree2Showing) {
        $("#tree2Section").show();
        $("#itemSection").hide();
        // caller should also set app.tree2Mode

    } else {
        $("#tree2Section").hide();
        $("#itemSection").show();

        // if we're hiding tree2, set app.tree2Mode to none
        app.tree2Mode = "none";
    }
}

// Determine if a node is the main document node
app.isDocNode = function(n) {
    return (n.parent == null || n.parent.parent == null);
}

// Given an lsItemId, return the corresponding ft node
app.getNodeFromLsItemId = function(lsItemId, tree) {
    if (tree == "tree2") app.ft2;
    else tree = app.ft;

    if (lsItemId == null) {
        return tree.fancytree("getTree").getNodeByKey("doc-" + app.lsDocId);
    } else {
        return tree.fancytree("getTree").getNodeByKey(lsItemId+"");
    }
}

// Given a node, return the lsItemId as derived from the key -- or null if it's the doc node
app.lsItemIdFromNode = function(n) {
    if (typeof(n) != "object" || app.isDocNode(n)) {
        return null;
    } else {
        return n.key;
    }
};

// Given a node, return the title html we want to show for the node
app.titleFromNode = function(node, format) {
    var data;
    if (node.data != null) data = node.data;
    else data = node;

    var title;
    // document -- for some reason the title is in node and other data is in node.data
    if (node.title != null) {
        title = node.title;
    } else {
        if (data.abbrStmt != null && data.abbrStmt != "") {
            title = data.abbrStmt;
        } else {
            title = data.fullStmt;
        }
        // if we have a humanCoding for the node, show it first in bold
        if (data.humanCoding != null && data.humanCoding != "") {
            title = '<span class="item-humanCodingScheme">' + data.humanCoding + '</span> ' + title;
        }
    }
    // if format is "ftTitleSpan", return wrapped in the fancytree-title span
    if (format == "ftTitleSpan") {
        return '<span class="fancytree-title">' + title + '</span>';
        // if format is "textOnly", extract a text only version
    } else if (format == "textOnly") {
        return $('<div>' + title + '</div>').text();

        // otherwise return as is
    } else {
        return title;
    }
};

// Initialize a tooltip for a tree item
app.treeItemTooltip = function(node) {
    var $jq = $(node.span);

    var content = node.title;
    if (content == null) {
        content = node.data.fullStmt;
        if (node.data.humanCoding != null) {
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
    if (lsItemId == app.lsItemId) return;

    // replace app.lsItemId
    app.lsItemId = lsItemId;

    // if this is the lsDoc node
    if (app.lsItemId == null) {
        // replace url
        app.pushHistoryState(app.lsItemId, app.path.lsDoc.replace('ID', app.lsDocId));

        // show documentInfo and hide all itemInfos
        $(".itemInfo").hide();
        $("#documentInfo").show();

        // set appropriate class on itemSection
        $("#itemSection").removeClass("lsItemItemSection").addClass("docStatus-{{ lsDoc.adoptionStatus|default('Draft') }}");

        // else it's an lsItem
    } else {
        // replace url
        app.pushHistoryState(app.lsItemId, app.path.lsItem.replace('ID', app.lsItemId));

        // hide documentInfo and all itemInfos
        $(".itemInfo").hide();
        $("#documentInfo").hide();

        // set appropriate class on itemSection
        $("#itemSection").removeClass("docStatus-{{ lsDoc.adoptionStatus|default('Draft') }}").addClass("lsItemItemSection");

        // if we already have an item div loaded for this item, just show it
        if (app.getLsItemDetailsJq(app.lsItemId).length > 0) {
            app.getLsItemDetailsJq(app.lsItemId).show();

            // else...
        } else {
            // construct and show it
            app.loadItemDetails(app.lsItemId);
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

    // now we need to update the listEnum fields for the draggedNode's (possibly new) parent's children
    // (the former parent's children will still be in order, though we might want to "clean up" those listEnums too)
    var siblings = draggedNode.parent.children;
    var lsItems = {};
    for (var i = 0; i < siblings.length; ++i) {
        var key = siblings[i].key;

        // update listEnum if changed
        if (siblings[i].data == null || siblings[i].data.listEnum != (i+1)) {
            lsItems[key] = {
                "listEnumInSource": (i+1)
            };
            // update field value in display
            app.getLsItemDetailsJq(key).find("[data-field-name=listEnumInSource]").text(i+1);
        }

        // if we got to the draggedNode...
        if (key == draggedNode.key) {
            // ...then if the parent changed...
            if (draggedNode.parent != originalParent) {
                // we have to update the parent of the dragged node.

                if (lsItems[key] == null) lsItems[key] = {};

                // if parent is the document...
                if (draggedNode.parent.key.search(/^doc-(.+)/) > -1) {
                    // note the docId, and the fact that it's a document
                    lsItems[key].parentId = RegExp.$1;
                    lsItems[key].parentType = "doc";
                    // otherwise the parent is an item
                } else {
                    lsItems[key].parentId = draggedNode.parent.key;
                    lsItems[key].parentType = "item";
                }

                // also, in this case we should update listEnum's for the original parent, since we took the draggedNode out
                if (originalParent.children != null && originalParent.children.length > 0) {
                    for (var j = 0; j < originalParent.children.length; ++j) {
                        if (originalParent.children[j].data == null || originalParent.children[j].data.listEnum != (j+1)) {
                            var key = originalParent.children[j].key;
                            lsItems[key] = {
                                "listEnumInSource": (j+1)
                            };
                            // update field value in display
                            app.getLsItemDetailsJq(key).find("[data-field-name=listEnumInSource]").text(j+1);
                        }
                    }
                }
            }
        }
    }

    // ajax call to submit changes
    app.showModalSpinner("Reordering Items");
    $.ajax({
        url: app.path.doctree_update_items.replace('ID', app.lsDocId),
        method: 'POST',
        data: {"lsItems": lsItems}
    }).done(function(data, textStatus, jqXHR){
        app.hideModalSpinner();
    }).fail(function(jqXHR, textStatus, errorThrown){
        app.hideModalSpinner();
        alert("An error occurred.");
    });

};


//////////////////////////////////////////////////////
// COPY AN ITEM FROM TREE2 TO TREE1

// Initiate copying items from tree 2 to tree 1
app.copyItemInitiate = function() {
    app.tree2Toggle(true);
    app.tree2Mode = "copyItem";

    // if an lsItem is active, make sure it's a folder, and open it
    if (app.lsItemId != null) {
        var node = app.getNodeFromLsItemId(app.lsItemId);
        node.folder = true;
        node.setExpanded(true);
        node.render();
    }

    $("#tree2Section .itemTitle").text("Add Copy of Existing Item");
    $("#tree2SectionCopyInstructions").show();
    $("#tree2SectionRelationshipInstructions").hide();
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
        var newNode = app.getNodeFromLsItemId("copiedItem");
        var siblings = newNode.parent.children;
        var lsItems = {};
        for (var i = 0; i < siblings.length; ++i) {
            var key = siblings[i].key;

            // update listEnum if changed
            if (siblings[i].data == null || siblings[i].data.listEnum != (i+1)) {
                lsItems[key] = {
                    "listEnumInSource": (i+1)
                };
                // update field value in display
                app.getLsItemDetailsJq(key).find("[data-field-name=listEnumInSource]").text(i+1);
            }

            // if we got to the new node...
            if (key == newNode.key) {
                if (lsItems[key] == null) lsItems[key] = {};

                // set copyFromId flag so that updateItemAction will copy the item
                lsItems[key].copyFromId = copiedLsItemId;

                // set parentId and parentType
                // if parent is the document...
                if (newNode.parent.key.search(/^doc-(.+)/) > -1) {
                    // note the docId, and the fact that it's a document
                    lsItems[key].parentId = RegExp.$1;
                    lsItems[key].parentType = "doc";
                    // otherwise the parent is an item
                } else {
                    lsItems[key].parentId = newNode.parent.key;
                    lsItems[key].parentType = "item";
                }
            }
        }

        // ajax call to submit changes
        app.showModalSpinner("Copying Item");
        $.ajax({
            url: app.path.doctree_update_items.replace('ID', app.lsDocId),
            method: 'POST',
            data: {"lsItems": lsItems}
        }).done(function(data, textStatus, jqXHR){
            // hide spinner
            app.hideModalSpinner();

            // returned data will be the path for the new item, which gives us the id
            var newItemId = data.replace(/.*\/(.*)$/, "$1");

            // update key of newNode and re-render
            newNode.key = newItemId;
            newNode.render();

        }).fail(function(jqXHR, textStatus, errorThrown){
            app.hideModalSpinner();
            alert("An error occurred.");
        });
    }, 50);    // end of anonymous setTimeout function
}

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
app.getAddNewChildPath = function() {
    // if we don't have an lsItemId, we're showing/editing the doc
    if (app.lsItemId == null) {
        return app.path.lsitem_new.replace('DOC', app.lsDocId);

        // else we're showing/editing an item
    } else {
        return app.path.lsitem_new.replace('DOC', app.lsDocId).replace('PARENT', app.lsItemId);
    }
};

app.prepareAddNewChildModal = function() {
    $('#addNewChildModal').find('.modal-body').html(app.spinnerHtml("Loading Form"));
    var $addNewChildModal = $('#addNewChildModal');
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
                "key": data.replace(/.*\/(.*)$/, "$1"),
                "fullStmt": $("#ls_item_fullStatement").val(),
                "humanCoding": $("#ls_item_humanCodingScheme").val(),
                "abbrStmt": $("#ls_item_abbreviatedStatement").val(),
            };

            $addNewChildModal.modal('hide');

            app.addNewChild(newChildData);

            // window.location.reload(true);
        }).fail(function(jqXHR, textStatus, errorThrown){
            app.hideModalSpinner();
            $('#addNewChildModal').find('.modal-body').html(jqXHR.responseText);
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

    // get the parentNode (current item) and add the child to the parent
    var parentNode = app.getNodeFromLsItemId(app.lsItemId);
    parentNode.addChildren([data]);

    // enable the tooltip on the new child
    app.treeItemTooltip(app.getNodeFromLsItemId(data.key));
};

//////////////////////////////////////////////////////
// EDIT THE DOCUMENT OR AN ITEM

app.prepareEditDocModal = function() {
    $('#editDocModal').find('.modal-body').html(app.spinnerHtml("Loading Form"));
    var $editDocModal = $('#editDocModal');
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
            $('#editDocModal').find('.modal-body').html(jqXHR.responseText);
            $('#ls_doc_subjects').select2entity({dropdownParent: $('#editDocModal')});
        });
    });
};

app.prepareEditItemModal = function() {
    $('#editItemModal').find('.modal-body').html(app.spinnerHtml("Loading Form"));
    var $editItemModal = $('#editItemModal');
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
            // on successful add, update the item to the tree
            var updatedData = {
                "fullStmt": $("#ls_item_fullStatement").val(),
                "humanCoding": $("#ls_item_humanCodingScheme").val(),
                "abbrStmt": $("#ls_item_abbreviatedStatement").val(),
            };
            $editItemModal.modal('hide');
            app.updateEditedItem(updatedData);

        }).fail(function(jqXHR, textStatus, errorThrown){
            app.hideModalSpinner();
            $('#editItemModal').find('.modal-body').html(jqXHR.responseText);
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
    $('#associateModal').find('.modal-body').html(app.spinnerHtml("Loading Form"));
    var $associateModal = $('#associateModal');
    $associateModal.on('shown.bs.modal', function(e){
        $('#associateModal').find('.modal-body').load(
            app.addAssociationCreatePath(),
            null,
            // Call app.createAssociationModalLoaded when modal is loaded
            function(responseText, textStatus, jqXHR){ app.createAssociationModalLoaded() }
        )
    }).on('hidden.bs.modal', function(e){
        $('#associateModal').find('.modal-body').html(app.spinnerHtml("Loading Form"));
    });
    $associateModal.find('.btn-save').on('click', function(e){
        app.showModalSpinner("Saving Association");
        $.ajax({
            url: app.addAssociationCreatePath(),
            method: 'POST',
            data: $associateModal.find('form[name=ls_association_tree]').serialize()
        }).done(function(data, textStatus, jqXHR){
            app.hideModalSpinner();
            $associateModal.modal('hide');

            // call createAssociationSaved when save is done
            app.createAssociationSaved();

        }).fail(function(jqXHR, textStatus, errorThrown){
            app.hideModalSpinner();
            $('#associateModal').find('.modal-body').html(jqXHR.responseText);
        });
    });
}

// initiate adding an association from tree2 to tree1
app.addAssociation = function() {
    app.tree2Toggle(true);
    app.tree2Mode = "addAssociation";

    $("#tree2Section .itemTitle").text("Add Association");
    $("#tree2SectionCopyInstructions").hide();
    $("#tree2SectionRelationshipInstructions").show();
};

// called when user drags and drops an item from tree2 to tree1 to create an association
app.createAssociation = function(draggedNode, droppedNode) {
    // remember dragged and dropped nodes while we make the call to open the form
    app.createAssociationNodes = {
        "draggedNode": draggedNode,
        "droppedNode": droppedNode
    };

    // then open the modal form
    $('#associateModal').modal();
};

app.addAssociationCreatePath = function() {
    var path = app.path.lsassociation_tree_new;
    // the "origin" refers to the node that's 'receiving' the association -- so this is the droppedNode
    // the "destination" refers to the node that's being associated with the origin node -- so this is the draggedNode
    path = path.replace('ORIGIN_ID', app.lsItemIdFromNode(app.createAssociationNodes.droppedNode));
    path = path.replace('DESTINATION_ID', app.lsItemIdFromNode(app.createAssociationNodes.draggedNode));
    console.log(path);
    return path;
}

// callback after ajax to load associate form
app.createAssociationModalLoaded = function() {
    console.log("createAssociationModalLoaded");
    // show origin and destination
    var destination = app.titleFromNode(app.createAssociationNodes.draggedNode);
    var origin = app.titleFromNode(app.createAssociationNodes.droppedNode);
    $("#lsAssociationDestinationDisplay").html(destination);
    $("#lsAssociationOriginDisplay").html(origin);
};

app.createAssociationSaved = function() {
    // clear and reload item details for droppedNode
    var lsItemId = app.lsItemIdFromNode(app.createAssociationNodes.droppedNode);
    app.clearItemDetails(lsItemId);
    app.loadItemDetails(lsItemId);

    // clear createAssociationNodes
    app.createAssociationNodes = null;
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
// DELETE AN ITEM

app.deleteItem = function() {
    // get node
    var node = app.getNodeFromLsItemId(app.lsItemId);
    var children = 0;
    var deleteItem = function() {
        // replace item with "Deleting..." message
        app.getLsItemDetailsJq(app.lsItemId).find(".lsItemDetails").html(app.spinnerHtml("Deleting"));
        app.getLsItemDetailsJq(app.lsItemId).find(".lsItemAssociations").text("");

        $.ajax({
            url: app.path.lsitem_tree_delete.replace('ID', app.lsItemId).replace('CHILDREN', children),
            method: 'POST'
        }).done(function (data, textStatus, jqXHR) {
            // find parent
            var parentNode = node.parent;

            // delete node
            node.remove();

            // activate parent
            parentNode.setActive();

        }).fail(function (jqXHR, textStatus, errorThrown) {
            alert("An error occurred.");
            // console.log(jqXHR.responseText);
        });
    };

    // make user confirm
    if ($.isArray(node.children) && node.children.length > 0) {
        $('#deleteItemAndChildrenModal').modal()
        .one('click', '.btn-delete', function() {
            $(this).closest('.modal').modal('hide');
            children = 1;
            deleteItem();
        });
    } else {
        $('#deleteOneItemModal').modal()
        .one('click', '.btn-delete', function() {
            $(this).closest('.modal').modal('hide');
            deleteItem();
        });
    }

};

/////////////////////////////////////////////////////
// FILTER ON TREE

app.filterOnTree = function() {
    var inputSelector = "#filterOnTree";
    var tree1 = app.ft.fancytree("getTree");
    var tree2 = null;

    if( app.ft2 != null ){
        tree2 = app.ft2.fancytree("getTree");
    }

    [tree1, tree2].forEach(function(tree){
        if(tree === null ){ return; }
        $(inputSelector).on('keyup', function(){
            if( $(this).val().trim().length > 0 ){
                tree.filterNodes($(this).val(), {autoExpand: true, leavesOnly: false});
            }else{
                tree.clearFilter();
            }
        });
    });
}
