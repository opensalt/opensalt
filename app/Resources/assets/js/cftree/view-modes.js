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
    if (context == "button" || context == "avTable") {
        // if the user clicked the button and the last view button pushed wasn't tree...
        if (context == "button" && apx.viewMode.lastViewButtonPushed != "tree") {
            // then the user must have been in the assoc view, then clicked the button to go to the tree view, so push a history state
            apx.pushHistoryState();
        }
        // set viewMode.lastViewButtonPushed to "tree" (so if we got back to the tree view via clicking on an item from the assoc table, we "simulate" clicking the tree view button)
        apx.viewMode.lastViewButtonPushed = "tree";
    }
    
    // set buttons appropriately
    $("#displayAssocBtn").removeClass("btn-primary").addClass("btn-default").blur();
    $("#displayTreeBtn").addClass("btn-primary").removeClass("btn-default").blur();
    
    // hide the assocView and show the treeView
    $("#assocView").hide();
    $("#treeView").show();
};

apx.viewMode.avFilters = {
    "avShowChild": false,
    "avShowExact": true,
    "avShowExemplar": true,
    "avShowOtherTypes": true,
    "groups": []
};
apx.viewMode.assocViewStatus = "not_written";
apx.viewMode.showAssocView = function(context) {
    // can't show the assocView until all docs have been loaded
    for (var identifier in apx.allDocs) {
        if (apx.allDocs[identifier] == "loading") {
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
    if (context == "refresh") {
        // set viewMode.assocViewStatus to "stale" so we make sure to reload it
        apx.viewMode.assocViewStatus = "stale";
    
    // else if the user clicked the button to load this view
    } else if (context == "button") {
        // unless the user has now clicked the Associations button twice in a row, push a history state
        if (apx.viewMode.lastViewButtonPushed != "assoc") {
            apx.pushHistoryState();
        }
        
        // note that this was the last button pushed
        apx.viewMode.lastViewButtonPushed = "assoc";
    }
    
    // if viewMode.assocViewStatus isn't "current", re-write the table
    if (apx.viewMode.assocViewStatus != "current") {
        // destroy previous table if we already created it
        if (apx.viewMode.assocViewStatus != "not_written") {
            $("#assocViewTable").DataTable().destroy();
        }
    
        // make sure viewMode.avFilters.groups is set up to use included groups
        var gft = [];
        for (var i = 0; i < apx.mainDoc.assocGroups.length; ++i) {
            var group = apx.mainDoc.assocGroups[i];
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
            var title;
            var doc = apx.allDocs[a[key].doc];

            // for the dest of an exemplar, we just use .item
            if (key === "dest" && a.type === "exemplar") {
                title = a[key].item;

                // else look for a title in the dest part of the association
            } else if (!empty(a.dest.title)) {
                // we should get this for documents loaded from other servers
                title = a.dest.title;

                // if we found a loaded document
            } else if (typeof(doc) === "object") {
                var item = doc.itemHash[a[key].item];
                if (!empty(item)) {
                    title = doc.getItemTitle(item, true);
                    if (doc !== apx.mainDoc) {
                        title += " <span style='color:red'>[" + doc.doc.title + "]</span>";
                    }
                } else {
                    title = "Document: " + doc.doc.title;
                }

                // hopefully this won't happen...
            } else {
                title = "Document: " + a[key].doc + "; Item: " + a[key].item;
            }

            var html = '<div data-association-id="' + a.id + '" data-association-identifier="' + a.identifier + '" data-association-item="' + key + '" class="assocViewTitle">'
                + title
                + '</div>'
            ;

            return html;
        }

        // compose datatables data array
        var dataSet = [];
        for (var i = 0; i < apx.mainDoc.assocs.length; ++i) {
            var assoc = apx.mainDoc.assocs[i];
            
            // skip associations (probably inverse associations) from other docs
            if (assoc.assocDoc != apx.mainDoc.doc.identifier) {
                continue;
            }
        
            // skip types if filters dictate
            if (assoc.type == "isChildOf") {
                if (!apx.viewMode.avFilters.avShowChild) {
                    continue;
                }
            } else if (assoc.type == "exactMatchOf") {
                if (!apx.viewMode.avFilters.avShowExact) {
                    continue;
                }
            } else if (assoc.type == "exemplar") {
                if (!apx.viewMode.avFilters.avShowExemplar) {
                    continue;
                }
            } else {
                if (!apx.viewMode.avFilters.avShowOtherTypes) {
                    continue;
                }
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
            var groupForLinks = "default";
            if ("groupId" in assoc) {
                groupForLinks = assoc.groupId;
            }
            
            // get text to show in origin and destination column
            var origin = avGetItemCell(assoc, "origin");
            var dest = avGetItemCell(assoc, "dest");
        
            // get type cell, with remove association button (only for editors)
            var type = apx.mainDoc.getAssociationTypePretty(assoc) + $("#associationRemoveBtn").html();
        
            // construct array for row
            var arr = [origin, type, dest];

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
        var columns = [
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
        $("#assocViewTable_wrapper .dataTables_length").prepend($("#assocViewTableFilters").html());

        // enable type filters
        for (var filter in apx.viewMode.avFilters) {
            $("#assocViewTable_wrapper input[data-filter=" + filter + "]").prop("checked", apx.viewMode.avFilters[filter])
                .on('change', function() {
                    apx.viewMode.avFilters[$(this).attr("data-filter")] = $(this).is(":checked");
                    apx.viewMode.showAssocView("refresh");
                    // TODO: save this value in localStorage?
                });
        }
    
        // enable group filters if we have any groups
        if (apx.mainDoc.assocGroups.length > 0) {
            $gf = $("#assocViewTable_wrapper .assocViewTableGroupFilters");
            for (var groupId in apx.viewMode.avFilters.groups) {
                if (groupId != 0) {
                    $gf.append('<label class="avGroupFilter"><input type="checkbox" data-group-id="' + groupId + '"> ' + apx.mainDoc.assocGroupIdHash[groupId].title + '</label><br>');
                }
                $("#assocViewTable_wrapper .avGroupFilter input[data-group-id=" + groupId + "]").prop("checked", apx.viewMode.avFilters.groups[groupId])
                    .on('change', function() {
                        apx.viewMode.avFilters.groups[$(this).attr("data-group-id")] = $(this).is(":checked");
                        apx.viewMode.showAssocView("refresh");
                        // TODO: save this value in localStorage?
                    });
            }
            $gf.css("display", "inline-block");
        }
    
        // enable remove buttons
        $("#assocViewTable_wrapper .btn-remove-association").on('click', function(e) {
            e.preventDefault();
            var assocId = $(this).closest("tr").find("[data-association-id]").attr("data-association-id");
            console.log("delete " + assocId);
            
            apx.edit.deleteAssociation(assocId, function() {
                // refresh the table after deleting the association
                apx.viewMode.showAssocView("refresh");
            });
            return false;
        });
    
        // tooltips for items with titles
        $(".assocViewTitle").each(function() {
            var content = $(this).html();
            $(this).tooltip({
                "title": content,
                "delay": { "show": 200, "hide": 100 },
                "placement": "bottom",
                "html": true,
                "container": "body"
            });
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
    $("#displayTreeBtn").removeClass("btn-primary").addClass("btn-default").blur();
    $("#displayAssocBtn").addClass("btn-primary").removeClass("btn-default").blur();

    // hide the treeView and show the assocView
    $("#treeView").hide();
    $("#assocView").show();
};
