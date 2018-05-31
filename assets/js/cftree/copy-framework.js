$(document).ready(function(){
    var apx = window.apx||{};
    var utilSalt = require("util-salt");
    var sourceCustomValue = null;
    var customParams = null;

    apx.copyFramework = {
        init() {
            $("#copyFrameworkModal_copyRightBtn, #copyFrameworkModal_copyLeftBtn").click(function(e){
                e.preventDefault();
            });
            $("#copyFrameworkModal #copyType, #copyFrameworkModal #copyAndAssociateType").click(function(e){
                apx.copyFramework.setCustomParams();
            });
            $("#copyFrameworkModal_copyLeftBtn").click(function(e){
                apx.copyFramework.copyFrameworkToLeft(this);
            });
            $("#copyFrameworkModal_copyRightBtn").click(function(e){
                apx.copyFramework.copyFrameworkToRight(this);
            });
            $("#copyFrameworkForm").submit(function(e){
                e.preventDefault();
                apx.copyFramework.copyFrameworkRequest($(this).serialize());
            });
        },

        copyFrameworkRequest(params) {
            var sourceDocRequest = null;
            var paramsDocRequest = null;
            $("#copyFrameworkModal .file-loading .row .col-md-12").html(utilSalt.spinner("Copying Document"));
            $("#copyFrameworkModal .contentModal").addClass("hidden");
            $("#copyFrameworkModal .file-loading").removeClass("hidden");

            sourceDocRequest = (sourceCustomValue == null ? apx.lsDocId : sourceCustomValue);
            paramsDocRequest = (customParams == null ? params : jQuery.param(customParams));

            $.post("/copy/framework/" + sourceDocRequest, paramsDocRequest, function(data){
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

        copyFrameworkToLeft(element) {
            apx.copyFramework.setCustomParams();
            $(element).addClass('active');
            $("#copyFrameworkModal_copyRightBtn").removeClass('active');
        },

        copyFrameworkToRight(element) {
            sourceCustomValue = null;
            customParams = null;
            $(element).addClass('active');
            $("#copyFrameworkModal_copyLeftBtn").removeClass('active');
        },

        setCustomParams() {
            sourceCustomValue = $('#js-framework-to-copy').val();
            customParams = {
                frameworkToCopy: apx.lsDocId,
                type: ($("#copyType").is(":checked") ? 'copy' : 'copyAndAssociate')
            };
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
