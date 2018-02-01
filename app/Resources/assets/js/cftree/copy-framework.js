$(document).ready(function(){
    var apx = window.apx||{};
    var utilSalt = require('util-salt');

    apx.copyFramework = {
        init() {
            $("#copyFrameworkForm").submit(function(e){
                e.preventDefault();
                apx.copyFramework.copyFrameworkRequest($(this).serialize());
            });
        },

        copyFrameworkRequest(params) {
            $("#copyFrameworkModal .file-loading .row .col-md-12").html(utilSalt.spinner("Copying Document"));
            $("#copyFrameworkModal .contentModal").addClass("hidden");
            $("#copyFrameworkModal .file-loading").removeClass("hidden");

            $.post("/copy/framework/" + apx.lsDocId, params, function(data){
                apx.copyFramework.copyFrameworkRequestSuccess(data);
            })
            .fail(function(data){
                apx.copyFramework.copyFrameworkRequestFail(data);
            });
        },

        copyFrameworkRequestSuccess(data) {
            $("#copyFrameworkModal .alert-success").find("a.js-docDestination")
                .attr("href", "/cftree/doc/" + data.docDestinationId);
            $("#copyFrameworkModal .alert-success").removeClass("hidden");
            $("#copyFrameworkModal .file-loading").addClass("hidden");
            apx.copyFramework.resetModalAfterRequest();
        },

        copyFrameworkRequestFail(data) {
            $("#copyFrameworkModal .alert-danger").removeClass("hidden");
            $("#copyFrameworkModal .file-loading").addClass("hidden");
            apx.copyFramework.resetModalAfterRequest();
        },

        resetModalAfterRequest(data) {
            setTimeout(apx.copyFramework.resetModal, 3000);
        },

        resetModal(data) {
            $("#copyFrameworkModal .alert-success").addClass("hidden");
            $("#copyFrameworkModal .alert-danger").addClass("hidden");
            $("#copyFrameworkModal .file-loading").addClass("hidden");
            $("#copyFrameworkModal .contentModal").removeClass("hidden");
        }
    };
});
