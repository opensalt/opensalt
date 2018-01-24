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
            if (a.type === "exemplar") {
                title = a[key].uri;

            // else see if the "item" is actually a document
            } else if (!empty(apx.allDocs[a[key].item]) && typeof(apx.allDocs[a[key].item]) !== "string") {
                title = "Document: " + apx.allDocs[a[key].item].doc.title;

            // else if we know about this item via allItemsHash...
            } else if (!empty(apx.allItemsHash[a[key].item])) {
                let destItem = apx.allItemsHash[a[key].item];
                title = apx.mainDoc.getItemTitle(destItem, true);
                doc = destItem.doc;

            // else we don't (currently at least) know about this item...
            } else {
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
        $(".assocViewTitle").each(function() {
            let content = $(this).html();
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
    setTimeout(function() { $(".tooltip").remove(); }, 100);
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
