$(document).on('ready', function(){
    SaltGithub.init();
    $('input[name="import"]').click(function(){
        loadContent($('input[name="import"]:checked').val());
    });
});

var SaltGithub = (function(){

    var githubData = null;

    function init(){
        getRepoList();
    }

    function getRepoList(){
        if( $('.js-github-list').length > 0 ){
            $.get('/app_dev.php/user/github/repos', function(data){
                githubData = data.data;

                $.each(githubData, function(i, e){
                    $(".js-github-list .js-github-message-loading").hide();
                    $(".js-github-list").append("<li class=\"list-group-item\">"+e.name+"</li>");
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
        if(path){
            console.log('owner', $(evt.target).attr('data-owner'));
            console.log('repo', $(evt.target).attr('data-repo'));
            console.log('file', evt.target.textContent);
            $.ajax({
                url: '/app_dev.php/user/github/files',
                data: {owner: $(evt.target).attr('data-owner'), repo: $(evt.target).attr('data-repo'), path: evt.target.textContent},
                type: 'get',
                dataType: 'json',
                success: function(content){
                    console.log(content);
                }
            });

        }else{

            githubData.forEach(function(repo){
                if(repo.name == evt.target.textContent){
                    $.ajax({
                        url: '/app_dev.php/user/github/files',
                        data: {owner: repo.owner.login, repo: repo.name, path: ''},
                        type: 'get',
                        dataType: 'json',
                        success: function(project){
                            $('.js-github-list').html('');
                            project.data.forEach(function(file){
                                if (file.name.endsWith('.json') || file.name.endsWith('.html')){
                                    $(".js-github-list").append("<li class=\"list-group-item\" data-owner=\""+repo.owner.login+"\" data-repo=\""+repo.name+"\">"+file.name+"</li>");
                                }
                                itemListener(true);
                            });
                        }
                    });
                }
            });
        }
    }

    function itemListener(file){
        $(".list-group-item").click(function(evt){
            getFiles(evt, file);
        });
    }

    function getGithubData(){
        return githubData;
    }

    return {
        init: init,
        data: getGithubData
    }
})();

function loadModal(){
    $('#wizard').modal('show');
}

function loadContent(value){
    if (value ===  'github'){
        $("#asn").hide();
        $("#github").show();
    }else if (value = 'asn'){
        $("#github").hide();
        $("#asn").show();
    }
}
