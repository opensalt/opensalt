$(document).on('ready', function(){
    if ($('input[name="import"]').length > 0) {
        $('input[name="import"]').click(function(){
            Util.loadContent();
        });
        Util.loadContent();
    }

    $('.import-framework').click(function(){
        Import.fromAsn();
    });
});

var SaltGithub = (function(){

    function getRepoList(page, perPage){
        if ($('.js-github-list').length > 0) {
            $.get('/user/github/repos', { page: page, perPage: perPage }, function(data){

                $('#repos').html('');
                $.each(data.data, function(i, e){
                    $(".js-github-list .js-github-message-loading").hide();
                    $(".js-github-list #repos").append('<li class="list-group-item item" data-owner="'+e.owner.login+'" data-repo="'+e.name+'" data-sha="">'+e.name+'</li>');
                    $('#repos').removeClass('hidden');
                });

                paginate(data.totalPages);
                itemListener(false);
            })
            .fail(function(){
                $(".js-github-list .js-github-message-loading").hide();
                $(".js-github-list .js-github-message-error").show();
            });
        }
    }

    function getFiles(evt, sha){
        var name = $(evt.target).attr('data-fname');

        $.ajax({
            url: '/user/github/files',
            data: {
                owner: $(evt.target).attr('data-owner'),
                repo: $(evt.target).attr('data-repo'),
                sha: $(evt.target).attr('data-sha')
            },
            type: 'get',
            dataType: 'json',
            success: function(response){
                if (sha) {
                    var content = window.atob(response.data.content);
                    if (name.endsWith('.csv')) {
                        Import.csv(content);
                    } else if (name.endsWith('.json')) {
                        Import.json(content);
                    }
                    // $.ajax({
                    //     url: '/user/github/import',
                    //     data: {doc: response},
                    //     type: 'post',
                    //     dataType: 'text',
                    //     success: function(){
                    //         console.log('done');
                    //     }
                    // });
                } else {
                    $(".js-github-list #files").html('<ul></ul>');
                    response.data.forEach(function(file){
                        if (file.name.endsWith('.json') || file.name.endsWith('.csv') || file.name.endsWith('.md')){

                            $(".js-github-list #files")
                            .append('<li class="list-group-item file-item" data-owner="'+$(evt.target)
                                    .attr('data-owner')+'" data-repo="'+$(evt.target).attr('data-repo')+'" data-sha="'+file.sha+'" data-fname="'+file.name+'">'+file.name+'</li>');

                                    $('#repos').addClass('hidden');
                                    $('#files').removeClass('hidden');
                                    $('#pagination').addClass('hidden');
                                    $('.repositories-list').removeClass('hidden');
                                    $('.panel-title').html($(evt.target).attr('data-repo'));
                        }
                    });
                    itemListener(true);
                }
            }
        });
    }

    function paginate(pages){
        $('#pagination').twbsPagination({
            totalPages: pages,
            visiblePages: 5,
            onPageClick: function(event, page){
                getRepoList(page, 30);
            }
        });
    }

    function itemListener(file){
        var $element = $('.item');

        if (file) {
            $element = $('.file-item');
        }

        $element.click(function(evt){
            getFiles(evt, file);
        });
    }

    return {
        getRepoList: getRepoList
    };
})();

var Import = (function() {

    var file = "";
    var cfItemKeys = {};

    function csvImporter(content) {
        file = content;

        var fields = CfItem.fields;
        var lines = file.split("\n");
        var columns = lines[0].split(",");
        var index = null, field = null, column = null;

        for (var i = 0; i < fields.length; i++) {
            field = fields[i];
            for (var j = 0; j < columns.length; j++) {
                column = columns[j];
                if (column.length > 0) {
                    if (Util.simplify(field) === Util.simplify(column)) {
                        cfItemKeys[field] = column;

                        index = fields.indexOf(field);
                        if (index >= 0) {
                            fields.splice(index, 1);
                        }

                        index = columns.indexOf(column);
                        if (index >= 0) {
                            columns.splice(index, 1);
                        }

                        i--;
                        break;
                    }
                }
            }
        }

        if (fields.length > 0) {
            fields.forEach(function(field) {
                CfItem.missingField(field);
            });

            $('#import-div').addClass('hidden');
            $('#errors').removeClass('hidden');
        } else {
            $('#import-div').addClass('hidden');
            $('.file-loading .row .col-md-12').html(Util.spinner('Loading file'));
            $('.file-loading').removeClass('hidden');
        }

        index = fields.indexOf('humanCodingScheme');

        if (index < 0) {
            $('.file-loading .row .col-md-12').html(Util.spinner('Loading file'));
            $('.file-loading').removeClass('hidden');
            sendData();
        }
    }

    function jsonImporter(file){
        var json = JSON.parse(file);
        var keys = Object.keys(json);
        var subKey = [];

        keys.forEach(function(subJson){
            subKey = Object.keys(subJson);
        });
    }

    function sendData(){
        var columns = {};
        var dataRequest = {
            content: window.btoa(unescape(encodeURIComponent(file))),
            cfItemKeys: cfItemKeys,
            lsDocId: $('#lsDocId').val()
        };

        $.ajax({
            url: '/cf/github/import',
            type: 'post',
            data: dataRequest,
            success: function(response){
                location.reload();
            }
        });
    }

    function asnStructure(content){
        $.ajax({
            url: '/cf/asn/import',
            type: 'post',
            data: {
                fileUrl: $('#asn-url').val()
            },
            success: function(response){
                location.reload();
            },
            error: function(){
                $('.asn-error-msg').html('<strong>Error!</strong> Something went wrong.').removeClass('hidden');
            }
        });
    }

    return {
        csv: csvImporter,
        json: jsonImporter,
        send: sendData,
        fromAsn: asnStructure
    };
})();

var SaltLocal = (function(){

    function handleFileSelect(){
        // var files = evt.target.files; // FileList Object
        var files = document.getElementById('file-url').files;
        var json = '', f;

        if (window.File && window.FileReader && window.FileList && window.Blob) {
            for (var i=0; f = files[i]; i++) {
                console.log('name:', escape(f.name), '- type:', f.type || 'n/a', '- size:', f.size,
                            'bytes', '- lastModified:', f.lastModified ? f.lastModifiedDate.toLocaleDateString() : 'n/a');

                var reader = new FileReader();
                if (f.type === 'text/csv') {
                    reader.onload = (function(theFile) {
                        return function(e) {
                            file = e.target.result;
                            Import.csv(file, lsDocId);
                        };
                    })(f);

                    reader.readAsText(f);
                } else {
                    console.error('file type not allowed - ' + f.type);
                }
            }
        } else {
            console.error('The FILE APIs are not fully supported in this broswer');
        }
    }

    return {
        handleFile: handleFileSelect
    };
})();

var CfItem = (function(){

    var fields = [
        'identifier',
        'fullStatement',
        'humanCodingScheme',
        'abbreviatedStatement',
        'conceptKeywords',
        'notes',
        'language',
        'educationLevel',
        'cfItemType',
        'license',
        'cfAssociationGroupIdentifier',
        'isChildOf',
        'isPartOf',
        'replacedBy',
        'exemplar',
        'hasSkillLevel'
    ];

    function generateDropdowns(arrData, type){
        var mandatoryClass = "";
        var panelType = "default";
        arrData.chunk(2).forEach(function(dropdownGrouped){
        $('.dropdowns.'+type).append('<div class="row"></div>');
        dropdownGrouped.forEach(function(dropdown){
                if( dropdown[1] === 'M' ){ mandatoryClass = "mandatory-class"; panelType = "primary" }
                $('.dropdowns.'+type+' .row').last().append('<div class="col-xs-6"><div class="panel panel-'+ panelType +'"><div class="panel-body '+ mandatoryClass +'"></div></div></div>');
                $('.dropdowns.'+type+' .row .panel-body').last().append('<div class="col-xs-6"><div class="form-group"><label>'+dropdown[0].titleize()+'</label><select name="'+dropdown[0]+'" class="form-control select"><option>Choose one option</option></select></div></div>');
                $('.dropdowns.'+type+' .row .panel-body').last().append('<div class="col-xs-6"><div class="form-group"><label>Enter default value if needed</label><input name="'+dropdown[0]+'_default_value" type="text" class="form-control"/></div></div>');
                mandatoryClass = "";panelType = "default";
            });
        });
    }

    function validDropdowns(formMatchedSelector) {
        var missingRequiredFiles = false;

        $(formMatchedSelector).find("select").each(function(i,e) {
            if ($(e).val().length < 1) {
                 missingRequiredFiles = true;
            }
        });

        if (missingRequiredFiles) {
            $(".js-alert-missing-fields").removeClass("hidden");
        } else {
            $(".js-alert-missing-fields").addClass("hidden");
        }

        return !missingRequiredFiles;
    }

    function missingField(field) {
        var alert = '<div class="alert alert-warning js-alert-missing-fields" role="alert">';
        alert += '<a href="#" class="close" data-dismiss="alert" aria-label="close">x</a>';
        alert += '<strong>Missing field "'+Util.titleize(field)+'"</strong>, if you did not list a column '+field+' in your CSV ignore this message! ';
        alert += 'if you meant to, please take a look at the import template and try again!';
        alert += '<div>';

        $('.missing-fields').append(alert);
    }

    return {
        fields: fields,
        validDropdowns: validDropdowns,
        missingField: missingField
    };
})();

var SanitizeData = (function(){

    function matchedFields(formSelector){
        var sanitizedData = {},
            tempData = {},
            formData = $(formSelector).serializeArray();

        formData.forEach(function(e){ tempData[e.name] = e.value; });

        return tempData;
    }

    return {
        matchedFields: matchedFields
    };
})();

var Util = (function(){

    function simplify(string){
        return string.match(/[a-zA-Z]*/g).join("").toLowerCase();
    }

    function capitalize(string){
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function titleize(string){
        return capitalize(string.replace(/([A-Z]+)/g, " $1").replace(/([A-Z][a-z])/g, " $1"));
    }

    function chunk(array, n){
        if ( !this.length ) {
            return [];
        }
        return [ this.slice( 0, n ) ].concat( this.slice(n).chunk(n) );
    }

    function loadContent(){
        var value = $('input[name="import"]:checked').val();

        if (value ===  'github') {
            SaltGithub.getRepoList(1, 30);
            $("#asn").addClass('hidden');
            $("#local").addClass('hidden');
            $("#github").removeClass('hidden');
        } else if (value === 'local') {
            $("#github").addClass('hidden');
            $("#asn").addClass('hidden');
            $('#local').removeClass('hidden');
        }
    }

    function spinnerHtml(msg) {
        return '<div class="spinnerOuter"><span class="glyphicon glyphicon-cog spinning spinnerCog"></span><span class="spinnerText">' + msg + '</span></div>';
    }

    return {
        simplify: simplify,
        loadContent: loadContent,
        titleize: titleize,
        spinner: spinnerHtml
    };
})();

function listRepositories(){
    $('#files').addClass('hidden');
    $('#repos').removeClass('hidden');
    $('#pagination').removeClass('hidden');
    $('.repositories-list').addClass('hidden');
    $('.panel-title').html('Repositories list');
}
