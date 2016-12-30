$(document).on('ready', function(){
    SaltGithub.init();
    $('input[name="import"]').click(function(){
        loadContent($('input[name="import"]:checked').val());
    });
    $('.import-framework').click(function(){
        ImportFrameworks.fromAsn();
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
                        Importer.csv(content);
                    }else if(name.endsWith('.json')){
                        Importer.json(content);
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

var Importer = (function(){

    function csvImporter(file){
        var lines = file.split("\n");
        var columns = lines[0].split(",");
        columns.forEach(function(val){
            console.log(val);
        });
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

    return {
        csv: csvImporter,
        json: jsonImporter
    };
})();
