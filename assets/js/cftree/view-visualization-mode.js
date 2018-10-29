/* global empty */

/* global render */
/* global apx */
window.apx = window.apx||{};
/* global lLang */
window.lLang = window.lLang||{};
/* global lArray */
window.lArray = window.lArray||{};
/* global lCollection */
window.lCollection = window.lCollection||{};

// Colors by association type
const ASSOC_COLORS = {
    IsRelatedTo: "#FF0000",
    ReplacedBy: "#000FFF",
    ExactMatchOf: "#00FFE4",
    Precedes: "#FF00D4",
    IsPartOf: "#2EFF00",
};

// filterByType will remove the concept map tags
apx.viewMode.removeConceptMap = () => {
    $("#graph").html("");
};

// filterByType will load the concept map
// with the association selected
apx.viewMode.filterByType = (assocTypeName) => {
    const vDocument = apx.viewMode.visualizationDocument;
    // Use lodash to remove item assocs types that are diff than assocTypeName
    const ditems = lLang.cloneDeep(vDocument).ditems.map(function(item){
        return $.extend(item, { 
            links: item.links.filter((link) => link.type === assocTypeName) 
        });
    });
    const graphData = { ditems };
    const parsedData = apx.viewMode.parseData(graphData);

    apx.viewMode.removeConceptMap();
    apx.viewMode.loadConceptMap(parsedData);
};

// loadAllVisualizations will load the concept map 
// with all the assocs
apx.viewMode.loadAllVisualizations = () => {
    const data = apx.viewMode.visualizationDocument;
    const parsedData = apx.viewMode.parseData(data);

    apx.viewMode.removeConceptMap();    
    apx.viewMode.loadConceptMap(parsedData);
};

// initFilters will initilize the assocs types buttons with filter event
apx.viewMode.initFilters = () => {
    const vDocument = apx.viewMode.visualizationDocument;
    
    // Iterate over all assocs types
    lArray.uniqBy(vDocument.themes, "description").map(function(theme){
        const color = ASSOC_COLORS[theme.description.split(" ").join("")];  

        $("#visualizationButtonGroup").append('<button type="button"'
            + 'style="box-shadow: inset 0 -2px 0 '+color+';"'
            + 'class="associationTypeFilter view-btn btn btn-default">'
            + theme.description +"</button>");
    });

    $("#visualizationButtonGroup").append('<button type="button"'
        + 'class="associationTypeFilter view-btn btn btn-default">'
        + "Show All" + "</button>");

    
    $(".btn.associationTypeFilter").on("click", function(){
        const type = $(this).html();

        if (type === "Show All") {
            apx.viewMode.loadAllVisualizations();
        } else {
            apx.viewMode.filterByType(type);
        } 
    });
};

// fillOutVisualizationView will request for document tree info for concept map
apx.viewMode.fillOutVisualizationView = () => {
    const url = apx.path.doctree_retrieve_document_visualization;
    const path = url.replace("ID", apx.lsDocId);

    $.get(path, function(data) {
        apx.viewMode.visualizationDocument = data;

        const parsedData = apx.viewMode.parseData(data);
        apx.viewMode.loadConceptMap(parsedData);
        apx.viewMode.initFilters();
    });
};

// parseData will take ditems from data and use the right format for d3
apx.viewMode.parseData = function(data) {
    const parsedData = data.ditems.map((item) => {
        return [item.name, item.links, item.id];
    });
    return parsedData;
};

// Use ApxDocument.showDocument logic to render document details and assocs
apx.viewMode.renderDocument = (item) => {
    const $jq = $("#itemVisualizationInfo");
    const itemTitle = apx.mainDoc.getItemTitle(item);

    $jq.find(".itemTitleSpan").html(itemTitle);
    $jq.find(".itemTitleIcon").attr("src", "/assets/img/item.png");
    // show item details
    let html = "";
    let key, attributes, val;
    for (key in attributes = { 
        "fstmt": "Full Statement",
        "ck": "Concept Keywords",
        "el": "Education Level",
        "itp": "Type",
        "notes": "Notes"
    }) {
        if (!empty(item[key])) {
            val = item[key];
            if (key === "fstmt" || key === "notes") {
                html += '<li class="list-group-item markdown-body">'
                    + "<strong>" + attributes[key] + ":</strong> "
                    + render.block(val)
                    + "</li>"
                ;
            } else {
                // TODO: deal with ck, el, itp
                html += '<li class="list-group-item">'
                    + "<strong>" + attributes[key] + ":</strong> "
                    + render.escaped(val)
                    + "</li>"
                ;
            }
        }
    }

    for (key in attributes = {
        "uri": "CASE Item URI",
        "le": "List Enumeration in Source",
        "cku": "Concept Keywords URI",
        "lang": "Language",
        "licenceUri": "Licence URI",
        "mod": "Last Changed"
    }) {
        if (!empty(item[key]) || key === "uri") {
            val = item[key];

            // TODO: deal with cku, licenceUri
            // for uri, get it from the ApxDocument
            if (key === "uri") {
                val = apx.mainDoc.getItemUri(item);
                html += '<li class="list-group-item lsItemDetailsExtras">'
                    + "<strong>" + attributes[key] + ":</strong> "
                    + render.inlineLinked(val)
                    + "</li>"
                ;
            } else {
                html += '<li class="list-group-item lsItemDetailsExtras">'
                    + "<strong>" + attributes[key] + ":</strong> "
                    + render.escaped(val)
                    + "</li>"
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

            if (a.type === "isChildOf") {
                continue;
            }

            if (a.type !== lastType || a.inverse !== lastInverse) {
                // close previous type section if we already opened one
                if (lastType !== "") {
                    html += "</div></div></div></section>";
                }

                // open type section
                let title = apx.mainDoc.getAssociationTypePretty(a);
                let icon = "";
                if (a.type !== "isChildOf") {
                    icon = '<img class="association-panel-icon" src="/assets/img/association-icon.png">';
                }
                html += '<section class="panel panel-default panel-component item-component">'
                    + '<div class="panel-heading">' + icon + render.escaped(title) + "</div>"
                    + '<div class="panel-body"><div><div class="list-group">'
                ;

                lastType = a.type;
                lastInverse = a.inverse;
            }

            // now the associated item

            // determine if the origin item is a member of the edited doc or an other doc
            let originDoc = "edited";
            if (a.assocDoc !== apx.mainDoc.doc.identifier) {
                originDoc = "other";
                // if it's another doc, no remove btn
            }

            // assocGroup if assigned -- either in self or mainDoc
            if (!empty(a.groupId)) {
                let groupName = "Group " + a.groupId;
                if (originDoc === "edited") {
                    if (!empty(apx.mainDoc.assocGroupIdHash[a.groupId])) {
                        groupName = apx.mainDoc.assocGroupIdHash[a.groupId].title;
                    }

                } else {
                    if (!empty(apx.mainDoc.assocGroupIdHash[a.groupId])) {
                        groupName = apx.mainDoc.assocGroupIdHash[a.groupId].title;
                    }
                }
                html += '<span class="label label-default">' + render.escaped(groupName) + "</span>";
            }

            html += '<a data-association-id="' + a.id + '" data-association-identifier="' + a.identifier + '" data-association-item="dest" class="list-group-item lsassociation lsitem clearfix lsassociation-' + originDoc + '-doc">'
                + '<span class="itemDetailsAssociationTitle">'
                + apx.mainDoc.associationDestItemTitle(a)
                + "</span>"
                + "</a>"
            ;
        }
        // close final type section
        html += "</div></div></div></section>";
    }
    // End of code composing associations
    $(".lsItemAssociationsView").html(html);
};


apx.viewMode.loadConceptMap = (data) => {
    // transform the data into a useful representation
    // 1 is inner, 2, is outer

    // need: inner, outer, links
    //
    // inner:
    // links: { inner: outer: }

    const d3 = require("d3");

    let outer = d3.map();
    let inner = [];
    let links = [];

    let outerId = [0];

    data.forEach((d) => {

        if (d == null) {
            return;
        }

        let i = { id: "i" + inner.length, name: d[0], relatedLinks: [], identifier: d[2] };
        i.relatedNodes = [i.id];
        inner.push(i);

        if (!Array.isArray(d[1])) {
            d[1] = [d[1]];
        }

        d[1].forEach(function(d1){
            
            let o = outer.get(d1.name);

            if (o == null) {
                o = { name: d1.name,	id: "o" + outerId[0], relatedLinks: [], identifier: [d1.id] };
                o.relatedNodes = [o.id];
                outerId[0] = outerId[0] + 1;

                outer.set(d1.name, o);
            } else {
                o.identifier.push(d1.id);
            }

            // create the links
            let l = { id: "l-" + i.id + "-" + o.id, inner: i, outer: o };
            links.push(l);

            // and the relationships
            i.relatedNodes.push(o.id);
            i.relatedLinks.push(l.id);
            o.relatedNodes.push(i.id);
            o.relatedLinks.push(l.id);
        });
    });

    data = {
        inner,
        outer: outer.values(),
        links
    };

    outer = data.outer;
    data.outer = Array(outer.length);


    var i1 = 0;
    var i2 = outer.length - 1;

    for (var i = 0; i < data.outer.length; ++i)
    {
        if (i % 2 === 1) {
            data.outer[i2--] = outer[i];
        } else {
            data.outer[i1++] = outer[i];
        }
    }

    // from d3 colorbrewer:
    // This product includes color specifications and designs developed by Cynthia Brewer (http://colorbrewer.org/).
    var colors = ["#a50026","#d73027","#f46d43","#fdae61","#fee090","#ffffbf","#e0f3f8","#abd9e9","#74add1","#4575b4","#313695"];
    var color = d3.scale.linear()
        .domain([60, 220])
        .range([colors.length-1, 0])
        .clamp(true);

    var diameter = 1020;
    var rectWidth = 140;
    var rectHeight = 32;

    var linkWidth = "1px";

    var il = data.inner.length;
    var ol = data.outer.length;

    var height = il*rectHeight + 250;

    var innerY = d3.scale.linear()
        .domain([0, il])
        .range([-(il * rectHeight)/2, (il * rectHeight)/2]);

    var mid = (data.outer.length/2.0);
    var outerX = d3.scale.linear()
        .domain([0, mid, mid, data.outer.length])
        .range([15, 170, 190 ,355]);

    var outerY = d3.scale.linear()
        .domain([0, data.outer.length])
        .range([0, diameter / 2 - 120]);


    // setup positioning
    data.outer = data.outer.map(function(d, i) {
        d.x = outerX(i);
        d.y = diameter/3;
        return d;
    });

    data.inner = data.inner.map(function(d, i) {
        d.x = -(rectWidth / 2);
        d.y = innerY(i);
        return d;
    });


    function getColor(d)
    {
        return "#b3d4fc";
    }

    function getColorByRelation(inner, outer)
    {
        var listItem = lCollection.find(apx.viewMode.visualizationDocument.ditems, {id: inner.identifier});
        var id = lArray.intersection(lCollection.map(listItem.links, "id"), outer.identifier);
        if (id[0]) {
            let item = lCollection.find(listItem.links, { id: id[0] });
            var associationType = item.type;
            if (typeof(ASSOC_COLORS[associationType.split(" ").join("")]) === "undefined") {
                return "#dddddd";                
            }
            return ASSOC_COLORS[associationType.split(" ").join("")];
        }
        return "#dddddd";
    }

    // Can't just use d3.svg.diagonal because one edge is in normal space, the
    // other edge is in radial space. Since we can't just ask d3 to do projection
    // of a single point, do it ourselves the same way d3 would do it.


    function projectX(x)
    {
        return ((x - 90) / 180 * Math.PI) - (Math.PI/2);
    }


    function clickInner(d) {
        const item = lCollection.find(apx.mainDoc.items, { identifier: d.identifier });

        // Remove inverse associations before render item
        lArray.remove(item.assocs, { inverse: true });
        apx.viewMode.renderDocument(item);
    }

    function clickOuter(d) {
        const item = lCollection.find(apx.mainDoc.assocs, { identifier: d.identifier[0] });
        const itemDestination = lCollection.find(apx.mainDoc.items, { identifier: item.dest.item});
        lArray.remove(itemDestination.assocs, { inverse: true });
        apx.viewMode.renderDocument(itemDestination);
    }

    function mouseover(d) {
        // bring to front
        d3.selectAll(".links .link").sort(function(a, b){ return d.relatedLinks.indexOf(a.id); });

        var i;
        for (i = 0; i < d.relatedNodes.length; i++) {
            d3.select("#" + d.relatedNodes[i] + "-txt").attr("font-weight", "bold");
        }

        for (i = 0; i < d.relatedLinks.length; i++) {
            d3.select("#" + d.relatedLinks[i]).attr("stroke-width", "5px");
        }
    }

    function mouseout(d) {
        var i;
        for (i = 0; i < d.relatedNodes.length; i++) {
            d3.select("#" + d.relatedNodes[i] + "-txt").attr("font-weight", "normal");
        }

        for (i = 0; i < d.relatedLinks.length; i++) {
            d3.select("#" + d.relatedLinks[i]).attr("stroke-width", linkWidth);
        }
    }

    var diagonal = d3.svg.diagonal()
        .source(function(d) { return {"x": d.outer.y * Math.cos(projectX(d.outer.x)),
                                    "y": -d.outer.y * Math.sin(projectX(d.outer.x))}; })
        .target(function(d) { return {"x": d.inner.y + rectHeight/2,
                                    "y": d.outer.x > 180 ? d.inner.x : d.inner.x + rectWidth}; })
        .projection(function(d) { return [d.y, d.x]; });


    var svg = d3.select("#graph").append("svg")
        .attr("width", diameter)
        .attr("height", height)
        .append("g")
        .attr("transform", "translate(" + diameter / 2 + "," + height / 2 + ")");


    // links
    var link = svg.append("g").attr("class", "links").selectAll(".link")
        .data(data.links)
        .enter().append("path")
        .attr("class", "link")
        .attr("id", function(d) { return d.id; })
        .attr("d", diagonal)
        .attr("stroke", function(d) { return getColorByRelation(d.inner, d.outer); })
        .attr("stroke-width", linkWidth);

    // outer nodes

    var onode = svg.append("g").selectAll(".outer_node")
        .data(data.outer)
        .enter().append("g")
        .attr("class", "outer_node")
        .attr("transform", function(d) { return "rotate(" + (d.x - 90) + ")translate(" + d.y + ")"; })
        .on("click", clickOuter)
        .on("mouseover", mouseover)
        .on("mouseout", mouseout);

    onode.append("circle")
        .attr("id", function(d) { return d.id; })
        .attr("r", 4.5);

    onode.append("circle")
        .attr("r", 20)
        .attr("visibility", "hidden");

    onode.append("text")
        .attr("id", function(d) { return d.id + "-txt"; })
        .attr("dy", ".31em")
        .attr("text-anchor", function(d) { return d.x < 180 ? "start" : "end"; })
        .attr("transform", function(d) { return d.x < 180 ? "translate(8)" : "rotate(180)translate(-8)"; })
        .on("click", clickOuter)
        .text(function(d) { return d.name; });

    // inner nodes

    var inode = svg.append("g").selectAll(".inner_node")
        .data(data.inner)
        .enter().append("g")
        .attr("class", "inner_node")
        .attr("transform", function(d, i) { return "translate(" + d.x + "," + d.y + ")"; })
        .on("click", clickInner)
        .on("mouseover", mouseover)
        .on("mouseout", mouseout);

    inode.append("rect")
        .attr("width", rectWidth)
        .attr("height", rectHeight)
        .attr("style", "stroke:white;stroke-width:1")
        .attr("id", function(d) { return d.id; })
        .attr("fill", function(d) { return getColor(d); });

    inode.append("text")
        .attr("id", function(d) { return d.id + "-txt"; })
        .attr("text-anchor", "middle")
        .attr("transform", "translate(" + rectWidth/2 + ", " + rectHeight * .75 + ")")
        .text(function(d) { return d.name; });

    // need to specify x/y/etc

    d3.select(self.frameElement).style("height", diameter - 150 + "px");

};
