$(document).on('ready', function(){
    SaltGithub.init();
    Dropdowns.init();

    $('input[name="import"]').click(function(){
        loadContent($('input[name="import"]:checked').val());
    });
    $('.import-framework').click(function(){
        ImportFrameworks.fromAsn();
    });
    $('.send-info').click(function(){
        if( Dropdowns.validDropdown("form.matched-fields-cfdof") &&
            Dropdowns.validDropdown("form.matched-fields-cfitem") &&
              Dropdowns.validDropdown("form.matched-fields-cfassociation")){
            Import.send();
        }
    });
});

var SaltGithub = (function(){

    function init(){
        getRepoList(1, 30);
        $('#js-wizard-btn').click(function(){
            loadModal();
        });
    }

    function getRepoList(page, perPage){
        if( $('.js-github-list').length > 0 ){
            $.get('/app_dev.php/user/github/repos', { page: page, perPage: perPage }, function(data){

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
            url: '/app_dev.php/user/github/files',
            data: {
                owner: $(evt.target).attr('data-owner'),
                repo: $(evt.target).attr('data-repo'),
                sha: $(evt.target).attr('data-sha')
            },
            type: 'get',
            dataType: 'json',
            success: function(response){
                if(sha){
                    var content = window.atob(response.data.content);
                    if(name.endsWith('.csv')){
                        Import.csv(content);
                    }else if(name.endsWith('.json')){
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
                }else{
                    $(".js-github-list #files").html('<ul></ul>')
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

        if(file){
            $element = $('.file-item');
        }

        $element.click(function(evt){
            getFiles(evt, file);
        });
    }

    function loadModal(){
        $('#wizard').modal('show');
    }

    return {
        init: init
    };
})();

function loadContent(value){
    if (value ===  'github'){
        $("#asn").addClass('hidden');
        $("#github").removeClass('hidden');
    }else if (value === 'asn'){
        $("#github").addClass('hidden');
        $("#asn").removeClass('hidden');
    }
}

function listRepositories(){
    $('#files').addClass('hidden');
    $('#repos').removeClass('hidden');
    $('#pagination').removeClass('hidden');
    $('.repositories-list').addClass('hidden');
    $('.panel-title').html('Repositories list');
}

var ImportFrameworks = (function(){

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
        fromAsn: asnStructure
    };
})();

var Import = (function(){

    var file = "";

    function csvImporter(content){
        file = content;

        var lines = file.split("\n");
        var columns = lines[0].split(",");
        var selects = $('.select');

        $.each(selects, function(){
            var $select = $(this);
            $select.find('option').remove().end();
            $select.append($('<option>').val('').text(''));

            columns.forEach(function(column){
                if(column.length > 0){
                    $select.append($('<option />').val(column).text(column));
                }
            });
        });

        $('#wizard').modal('hide');
        $('#fields').modal('show');
    }

    function jsonImporter(file){
        var json = JSON.parse(file);
        var keys = Object.keys(json);
        var subKey = [];
        console.log(keys);

        keys.forEach(function(subJson){
            subKey = Object.keys(subJson);
            console.log(subKey);
        });
    }

    function sendData(){
        var columns = {};
        var selects = $('.select');

        var dataRequest = {
            content: window.btoa(file),
            cfDocKeys: SanitizeData.matchedFields("form.matched-fields-cfdoc"),
            cfItemKeys: SanitizeData.matchedFields("form.matched-fields-cfitem"),
            cfAssociationKeys: SanitizeData.matchedFields("form.matched-fields-cfassociation")
        };

        console.info(dataRequest);

        $.each(selects, function(){
            var $select = $(this);
            columns[$select.attr('name')] = $select.val();
        });

        $.ajax({
            url: '/app_dev.php/cf/github/import',
            type: 'post',
            data: dataRequest,
            success: function(response){
                console.log(response);
                location.reload();
            }
        });
    }

    return {
        csv: csvImporter,
        json: jsonImporter,
        send: sendData
    };
})();

var Dropdowns = (function(){

    var docDropdowns = [
        ['creator', 'M'],
        ['title', 'M'],
        ['lastChangeDateTime', 'O'],
        ['officialSourceURL', 'O'],
        ['publisher', 'O'],
        ['description', 'O'],
        ['subject', 'O'],
        ['subjectURL', 'O'],
        ['language', 'O'],
        ['version', 'O'],
        ['adoptionStatus', 'O'],
        ['statusStartDate', 'O'],
        ['statusEndDate', 'O'],
        ['license', 'O'],
        ['licenseURI', 'O'],
        ['notes', 'O'],
        ['CFPackageURI', 'O']
    ];

    var itemDropdowns = [
        ['fullStatement', 'M'],
        ['humanCodingScheme', 'O'],
        ['listEnumeration', 'O'],
        ['abbreviatedStatement', 'O'],
        ['conceptKeywords', 'O'],
        ['conceptKeywordsUri', 'O'],
        ['notes', 'O'],
        ['language', 'O'],
        ['educationLevel', 'O'],
        ['type', 'O'],
        ['typeUri', 'O'],
        ['license', 'O'],
        ['lastChangeDateTime', 'O'],
        ['lsItemAssociationUri', 'O']
    ];
    var associationDropdowns = [
        ['origin', 'O'],
        ['originNodeUri', 'O'],
        ['destination', 'O'],
        ['destinationNodeUri', 'O'],
        ['identifier', 'O'],
        ['originNodeIdentifier', 'O'],
        ['originNodeUri', 'O'],
        ['destinationNodeIdentifier', 'O'],
        ['groupUri', 'O'],
        // Association Types
        ['isChildOf', 'O'],
        ['isPartOf', 'O'],
        ['exactMatchOf', 'O'],
        ['precedes', 'O'],
        ['isRelatedTo', 'O'],
        ['replacedBy', 'O'],
        ['exemplar', 'O'],
        ['hasSkillLevel', 'O']
    ];

    function generateDropdowns(arrData, type){
        var mandatoryClass = "";
        arrData.chunk(2).forEach(function(dropdownGrouped){
	    $('.dropdowns.'+type).append('<div class="row"></div>');
	    dropdownGrouped.forEach(function(dropdown){
                if( dropdown[1] === 'M' ){ mandatoryClass = "mandatory-class"; }
                $('.dropdowns.'+type+' .row').last().append('<div class="col-xs-6"><div class="panel panel-default"><div class="panel-body '+ mandatoryClass +'"></div></div></div>');
                $('.dropdowns.'+type+' .row .panel-body').last().append('<div class="col-xs-6"><div class="form-group"><label>'+dropdown[0].titleize()+'</label><select name="'+dropdown[0]+'" class="form-control select"><option>Choose one option</option></select></div></div>');
                $('.dropdowns.'+type+' .row .panel-body').last().append('<div class="col-xs-6"><div class="form-group"><label>Enter default value if needed</label><input name="'+dropdown[0]+'_default_value" type="text" class="form-control"/></div></div>');
                mandatoryClass = "";
            });
        });
    }

    function validDropdown(formMatchedSelector){
        var missingRequiredFiles = false;
        $(formMatchedSelector).find("div.mandatory-class select").each(function(i,e){
            if ( $(e).val().length < 1 && $(e).parents(".panel-body").first().find("input").first().val().length < 1 ){
                 missingRequiredFiles = true;
            }
        });
        if(missingRequiredFiles){
            $(".js-alert-missing-fields").removeClass("hidden");
        }else{
            $(".js-alert-missing-fields").addClass("hidden");
        }
        return !missingRequiredFiles;
    }

    function init(){
        generateDropdowns(docDropdowns, 'cfdoc');
        generateDropdowns(itemDropdowns, 'cfitem');
        generateDropdowns(associationDropdowns, 'cfassociation');
    }

    return {
        init: init,
        validDropdown: validDropdown
    };
})();

var SanitizeData = (function(){
    function matchedFields(formSelector){
        var sanitizedData = {},
            tempData = {},
            formData = $(formSelector).serializeArray();

        formData.forEach(function(e){ tempData[e.name] = e.value; });
	for (i=0; i < formData.length;i+=2){
            if( tempData[formData[i].name+'_default_value'] !== "" ){
                sanitizedData[formData[i].name] = tempData[formData[i].name+'_default_value'] + ', true';
                continue;
            }
            sanitizedData[formData[i].name] = formData[i].value;
	}
        return sanitizedData;
    }

    return {
        matchedFields: matchedFields
    }
})();

String.prototype.capitalize = function(){
    return this.charAt(0).toUpperCase() + this.slice(1);
}

String.prototype.titleize = function(){
    return this.replace(/([A-Z]+)/g, " $1").replace(/([A-Z][a-z])/g, " $1").capitalize();
}

Array.prototype.chunk = function ( n ) {
    if ( !this.length ) {
        return [];
    }
    return [ this.slice( 0, n ) ].concat( this.slice(n).chunk(n) );
};
