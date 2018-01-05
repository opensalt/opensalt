var CommentSystem = (function(){
    var commentItem = {
        itemId: null,
        itemType: 'document'
    };

    function exportCSV(){
        var url = "/salt/case/export_comment/"+commentItem.itemType+"/"+commentItem.itemId+"/comment.csv";
        window.location = url;
    }

    function init(nodeRef){
        setItem(nodeRef);
        $('.js-comments-container').comments({
            profilePictureUrl: '',
            enableDeletingCommentWithReplies: true,
            getComments: function(success, error) {
                $.ajax({
                    type: 'get',
                    url: '/comments/'+commentItem.itemType+'/'+commentItem.itemId,
                    success: function(data) {
                        if (typeof data !== 'object') {
                            data = [];
                        }
                        success(data);
                    },
                    error: error
                });
            },
            postComment: function(commentJSON, success, error) {
                $.ajax({
                    type: 'post',
                    url: '/comments/'+commentItem.itemType+'/'+commentItem.itemId,
                    data: appendItemId(commentJSON),
                    success: function(comment) {
                        success(comment);
                    },
                    statusCode: {
                        401: function() {
                            window.location.href = '/login';
                        }
                    },
                    error: error
                });
            },
            putComment: function(commentJSON, success, error) {
                $.ajax({
                    type: 'put',
                    url: '/comments/' + commentJSON.id,
                    data: appendItemId(commentJSON),
                    success: function(comment) {
                        success(comment);
                    },
                    statusCode: {
                        401: function() {
                            window.location.href = '/login';
                        }
                    },
                    error: error
                });
            },
            deleteComment: function(commentJSON, success, error) {
                $.ajax({
                    type: 'delete',
                    url: '/comments/delete/' + commentJSON.id,
                    success: success,
                    statusCode: {
                        401: function() {
                            window.location.href = '/login';
                        }
                    },
                    error: error
                });
            },
            upvoteComment: function(commentJSON, success, error) {
                var commentURL = '/comments/' + commentJSON.id + '/upvote';

                if (commentJSON.user_has_upvoted) {
                    $.ajax({
                        type: 'post',
                        url: commentURL,
                        data: {
                            comment: commentJSON.id
                        },
                        success: function(comment) {
                            success(comment);
                        },
                        statusCode: {
                            401: function() {
                                window.location.href = '/login';
                            }
                        },
                        error: error
                    });
                } else {
                    $.ajax({
                        type: 'delete',
                        url: commentURL,
                        data: {
                            comment: commentJSON.id
                        },
                        success: function(comment) {
                            success(comment);
                        },
                        statusCode: {
                            401: function() {
                                window.location.href = '/login';
                            }
                        },
                        error: error
                    });
                }
            }
        });
    }

    function setItem(nodeRef){
        if( nodeRef ){
            commentItem.itemId = nodeRef.id;
            commentItem.itemType = nodeRef.nodeType;
        } else {
            commentItem.itemId = $('.js-comments-container').data('lsdocid');
        }
    }

    function appendItemId(data){
        return $.extend(data, commentItem);
    }

    return {
        init: init,
        exportCSV: exportCSV
    };
})();

global.CommentSystem = CommentSystem;

$(document).on('ready', function(){
    CommentSystem.init();
});


