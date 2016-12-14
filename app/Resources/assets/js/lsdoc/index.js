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
        getRepoList();
        $('#js-wizard-btn').click(function(){
            loadModal();
        });
    }

    function getRepoList(){
        if( $('.js-github-list').length > 0 ){
            $.get('/app_dev.php/user/github/repos', function(data){

                $.each(data.data, function(i, e){
                    $(".js-github-list .js-github-message-loading").hide();
                    $(".js-github-list #repos").append('<li class="list-group-item item" data-owner="'+e.owner.login+'" data-repo="'+e.name+'" data-path="">'+e.name+'</li>');
                    $('#repos').removeClass('hidden');
                });

                itemListener(false);
            })
            .fail(function(){
                $(".js-github-list .js-github-message-loading").hide();
                $(".js-github-list .js-github-message-error").show();
            });
        }
    }

    function getFiles(evt, path){
        $.ajax({
            url: '/app_dev.php/user/github/files',
            data: {owner: $(evt.target).attr('data-owner'), repo: $(evt.target).attr('data-repo'), path: $(evt.target).attr('data-path')},
            type: 'get',
            dataType: 'json',
            success: function(response){
                if(path){
                    // this will be the logic when importing from github. -- not yet implemented.
                    // $.ajax({
                    //     url: '/app_dev.php/user/github/import',
                    //     data: {doc: response},
                    //     type: 'post',
                    //     dataType: 'text',
                    //     success: function(){
                    //         console.log('done');
                    //     }
                    // });
                }else{
                    response.data.forEach(function(file){
                        if (file.name.endsWith('.json') || file.name.endsWith('.csv') || file.name.endsWith('.md')){

                            $(".js-github-list #files").html('<ul></ul>')
                            .append('<li class="list-group-item file-item" data-owner="'+$(evt.target).attr('data-owner')+'" data-repo="'+$(evt.target).attr('data-repo')+'" data-path="'+file.name+'">'+file.name+'</li>');

                            $('#repos').addClass('hidden');
                            $('#files').removeClass('hidden');
                            $('.repositories-list').removeClass('hidden');
                            $('.panel-title').html($(evt.target).attr('data-repo'));
                        }
                    });
                    itemListener(true);
                }
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
    }
})();

function loadContent(value){
    if (value ===  'github'){
        $("#asn").addClass('hidden');
        $("#github").removeClass('hidden');
    }else if (value = 'asn'){
        $("#github").addClass('hidden');
        $("#asn").removeClass('hidden');
    }
}

function listRepositories(){
    $('#files').addClass('hidden');
    $('#repos').removeClass('hidden');
    $('.repositories-list').addClass('hidden');
    $('.panel-title').html('Repositories list');
}

var ImportFrameworks = (function(){

    function asnStructure(content){
        $.ajax({
            url: '/app_dev.php/cf/asn/import',
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
    }
})();
