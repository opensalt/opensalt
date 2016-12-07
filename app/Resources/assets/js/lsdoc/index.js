$(document).on('ready', function(){
    SaltGithub.getRepoList();
});

var SaltGithub = (function(){

    function getRepoList(){
        if( $('.js-github-list').length > 0 ){
            $.get('/app_dev.php/user/github/files', function(data){
                $.each(data.data, function(i, e){
                    $(".js-github-list .js-github-message-loading").hide();
                    $(".js-github-list").append("<li class=\"list-group-item\">"+e.name+"</li>");
                });
            })
            .fail(function(){
                $(".js-github-list .js-github-message-loading").hide();
                $(".js-github-list .js-github-message-error").show();
            });
        }
    }

    return {
        getRepoList: getRepoList
    }
})();
