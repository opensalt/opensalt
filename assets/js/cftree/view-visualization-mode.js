const associationTypeColors = {
    IsRelatedTo: "#FF0000",
    ReplacedBy: "#000FFF",
    ExactMatchOf: "#00FFE4",
    Precedes: "#FF00D4",
    IsPartOf: "#2EFF00"
};

apx.viewMode.filterVisualizationByAssociationType = function(associationTypeName){
    $('#graph').html('');

    var dataFiltered = { themes: apx.viewMode.visualizationDocument.themes.filter((theme) => theme.description === associationTypeName ),
                        perspectives: apx.viewMode.visualizationDocument.perspectives};

    var themeNames = dataFiltered.themes.map((theme) => theme.name);

    var itemsFiltered = apx.viewMode.visualizationDocument.ditems.filter(function(item){
        return lArray.intersection(item.links.map((link) => link[0]), themeNames).length > 0;
    });

    var itemFilteredByLinkAssociation = lLang.cloneDeep(apx.viewMode.visualizationDocument).ditems.map(function(item){
        return $.extend(item, {links: item.links.filter((link) => link[1] === associationTypeName)});
    });

    console.info(dataFiltered);

    var graphData = lLang.cloneDeep($.extend(dataFiltered, {ditems: itemFilteredByLinkAssociation}));
    const parsedData = apx.viewMode.parseData(graphData);
    apx.viewMode.loadConceptMap(parsedData);
};

apx.viewMode.initVisualizationFilters = function(){
    lArray.uniqBy(apx.viewMode.visualizationDocument.themes, 'description').map(function(theme){
    $('#visualizationButtonGroup').append('<button type="button"'
                                          + 'style="box-shadow: inset 0 -2px 0 '+associationTypeColors[theme.description.split(" ").join('')]+';"'
                                          + 'class="associationTypeFilter view-btn btn btn-default">'
                                          + theme.description +'</button>')
    });
    $('.btn.associationTypeFilter').on('click', function(){
        apx.viewMode.filterVisualizationByAssociationType($(this).html());
    });
};

apx.viewMode.fillOutVisualizationView = function(){
    let path = apx.path.doctree_retrieve_document_visualization.replace('ID', apx.lsDocId);
    $.get(path, function(data) {
        apx.viewMode.visualizationDocument = data;

        const parsedData = apx.viewMode.parseData(data);
        apx.viewMode.loadConceptMap(parsedData);
        apx.viewMode.initVisualizationFilters();
    });
};

apx.viewMode.themesUniqness = function(graphData) {
    var newThemes = lArray.uniqBy(graphData.themes, 'name');
    return $.extend(graphData, {themes: newThemes});
};

apx.viewMode.parseData = function(data) {
    const parsedData = data.ditems.map((item) => {
        return [item.name, item.links.map((link) => link[0])];
    });
    return parsedData;
}

apx.viewMode.loadConceptMap = function(data) {
    // transform the data into a useful representation
    // 1 is inner, 2, is outer

    // need: inner, outer, links
    //
    // inner:
    // links: { inner: outer: }

    const d3 = require('d3');

    var outer = d3.map();
    var inner = [];
    var links = [];

    var outerId = [0];

    data.forEach(function(d){

        if (d == null)
            return;

        i = { id: 'i' + inner.length, name: d[0], related_links: [] };
        i.related_nodes = [i.id];
        inner.push(i);

        if (!Array.isArray(d[1]))
            d[1] = [d[1]];

        d[1].forEach(function(d1){

            o = outer.get(d1);

            if (o == null)
            {
                o = { name: d1,	id: 'o' + outerId[0], related_links: [] };
                o.related_nodes = [o.id];
                outerId[0] = outerId[0] + 1;

                outer.set(d1, o);
            }

            // create the links
            l = { id: 'l-' + i.id + '-' + o.id, inner: i, outer: o }
            links.push(l);

            // and the relationships
            i.related_nodes.push(o.id);
            i.related_links.push(l.id);
            o.related_nodes.push(i.id);
            o.related_links.push(l.id);
        });
    });

    data = {
        inner: inner,
        outer: outer.values(),
        links: links
    }

    // sort the data -- TODO: have multiple sort options
    outer = data.outer;
    data.outer = Array(outer.length);


    var i1 = 0;
    var i2 = outer.length - 1;

    for (var i = 0; i < data.outer.length; ++i)
    {
        if (i % 2 == 1)
            data.outer[i2--] = outer[i];
        else
            data.outer[i1++] = outer[i];
    }

    // from d3 colorbrewer:
    // This product includes color specifications and designs developed by Cynthia Brewer (http://colorbrewer.org/).
    var colors = ["#a50026","#d73027","#f46d43","#fdae61","#fee090","#ffffbf","#e0f3f8","#abd9e9","#74add1","#4575b4","#313695"]
    var color = d3.scale.linear()
        .domain([60, 220])
        .range([colors.length-1, 0])
        .clamp(true);

    var diameter = 960;
    var rect_width = 140;
    var rect_height = 14;

    var link_width = "1px";

    var il = data.inner.length;
    var ol = data.outer.length;

    var inner_y = d3.scale.linear()
        .domain([0, il])
        .range([-(il * rect_height)/2, (il * rect_height)/2]);

    mid = (data.outer.length/2.0)
    var outer_x = d3.scale.linear()
        .domain([0, mid, mid, data.outer.length])
        .range([15, 170, 190 ,355]);

    var outer_y = d3.scale.linear()
        .domain([0, data.outer.length])
        .range([0, diameter / 2 - 120]);


    // setup positioning
    data.outer = data.outer.map(function(d, i) {
        d.x = outer_x(i);
        d.y = diameter/3;
        return d;
    });

    data.inner = data.inner.map(function(d, i) {
        d.x = -(rect_width / 2);
        d.y = inner_y(i);
        return d;
    });


    function get_color(name)
    {
        var c = Math.round(color(name));
        if (isNaN(c))
            return '#dddddd';	// fallback color

        return colors[c];
    }

    function getColorByRelation(inner, outer)
    {
        var listItem = lCollection.find(apx.viewMode.visualizationDocument.ditems, {name: inner.name});
        var associationType = listItem.links.filter((link, index, self) => link[0] == outer.name)[0][1];
        debugger;
        if (typeof(associationTypeColors[associationType.split(' ').join('')]) === 'undefined')
            return '#dddddd';
        return associationTypeColors[associationType.split(' ').join('')];
    }

    // Can't just use d3.svg.diagonal because one edge is in normal space, the
    // other edge is in radial space. Since we can't just ask d3 to do projection
    // of a single point, do it ourselves the same way d3 would do it.


    function projectX(x)
    {
        return ((x - 90) / 180 * Math.PI) - (Math.PI/2);
    }

    var diagonal = d3.svg.diagonal()
        .source(function(d) { return {"x": d.outer.y * Math.cos(projectX(d.outer.x)),
                                    "y": -d.outer.y * Math.sin(projectX(d.outer.x))}; })
        .target(function(d) { return {"x": d.inner.y + rect_height/2,
                                    "y": d.outer.x > 180 ? d.inner.x : d.inner.x + rect_width}; })
        .projection(function(d) { return [d.y, d.x]; });


    var svg = d3.select("#graph").append("svg")
        .attr("width", diameter)
        .attr("height", diameter)
    .append("g")
        .attr("transform", "translate(" + diameter / 2 + "," + diameter / 2 + ")");


    // links
    var link = svg.append('g').attr('class', 'links').selectAll(".link")
        .data(data.links)
    .enter().append('path')
        .attr('class', 'link')
        .attr('id', function(d) { return d.id })
        .attr("d", diagonal)
        .attr('stroke', function(d) { return getColorByRelation(d.inner, d.outer); })
        .attr('stroke-width', link_width);

    // outer nodes

    var onode = svg.append('g').selectAll(".outer_node")
        .data(data.outer)
    .enter().append("g")
        .attr("class", "outer_node")
        .attr("transform", function(d) { return "rotate(" + (d.x - 90) + ")translate(" + d.y + ")"; })
        .on("mouseover", mouseover)
        .on("mouseout", mouseout);

    onode.append("circle")
        .attr('id', function(d) { return d.id })
        .attr("r", 4.5);

    onode.append("circle")
        .attr('r', 20)
        .attr('visibility', 'hidden');

    onode.append("text")
        .attr('id', function(d) { return d.id + '-txt'; })
        .attr("dy", ".31em")
        .attr("text-anchor", function(d) { return d.x < 180 ? "start" : "end"; })
        .attr("transform", function(d) { return d.x < 180 ? "translate(8)" : "rotate(180)translate(-8)"; })
        .text(function(d) { return d.name; });

    // inner nodes

    var inode = svg.append('g').selectAll(".inner_node")
        .data(data.inner)
        .enter().append("g")
        .attr("class", "inner_node")
        .attr("transform", function(d, i) { return "translate(" + d.x + "," + d.y + ")"})
        .on("mouseover", mouseover)
        .on("mouseout", mouseout);

    inode.append('rect')
        .attr('width', rect_width)
        .attr('height', rect_height)
        .attr('id', function(d) { return d.id; })
        .attr('fill', function(d) { return get_color(d.name); });

    inode.append("text")
        .attr('id', function(d) { return d.id + '-txt'; })
        .attr('text-anchor', 'middle')
        .attr("transform", "translate(" + rect_width/2 + ", " + rect_height * .75 + ")")
        .text(function(d) { return d.name; });

    // need to specify x/y/etc

    d3.select(self.frameElement).style("height", diameter - 150 + "px");

    function mouseover(d)
    {
        // bring to front
        d3.selectAll('.links .link').sort(function(a, b){ return d.related_links.indexOf(a.id); });

        for (var i = 0; i < d.related_nodes.length; i++)
        {
            // d3.select('#' + d.related_nodes[i]).classed('highlight', true);
            d3.select('#' + d.related_nodes[i] + '-txt').attr("font-weight", 'bold');
        }

        for (var i = 0; i < d.related_links.length; i++)
            d3.select('#' + d.related_links[i]).attr('stroke-width', '5px');
    }

    function mouseout(d)
    {
        for (var i = 0; i < d.related_nodes.length; i++)
        {
            // d3.select('#' + d.related_nodes[i]).classed('highlight', false);
            d3.select('#' + d.related_nodes[i] + '-txt').attr("font-weight", 'normal');
        }

        for (var i = 0; i < d.related_links.length; i++)
            d3.select('#' + d.related_links[i]).attr('stroke-width', link_width);
    }
}
