window.apx = window.apx||{};

apx.copyFramework = { };

apx.copyFramework.consts = {
    copyModalId: '',
    copyFormId: '#copyFrameworkForm',
    copyUrl: '/app_dev.php/copy/framework/',
    docDestination: '/app_dev.php/cftree/doc/',
    contentModal: '#copyFrameworkModal .contentModal',
    spinnerSelector: '#copyFrameworkModal .file-loading .row .col-md-12',
    spinnerContainerSelector: '#copyFrameworkModal .file-loading',
    alertSuccess: '#copyFrameworkModal .alert-success',
    dangerSuccess: '#copyFrameworkModal .alert-danger'
}

apx.copyFramework.init = function(){
    $(apx.copyFramework.consts.copyFormId).submit(function(e){
        e.preventDefault();
        apx.copyFramework.copyFrameworkRequest($(this).serialize());
    });
};

apx.copyFramework.copyFrameworkRequest = function(params){
    $(apx.copyFramework.consts.spinnerSelector).html(Util.spinner('Copying Document'));
    $(apx.copyFramework.consts.contentModal).addClass('hidden');
    $(apx.copyFramework.consts.spinnerContainerSelector).removeClass('hidden');

    $.post(apx.copyFramework.consts.copyUrl + apx.lsDocId, params, function(data){
        apx.copyFramework.copyFrameworkRequestSuccess(data);
    })
    .fail(function(data){
        apx.copyFramework.copyFrameworkRequestFail(data);
    });
};

apx.copyFramework.copyFrameworkRequestSuccess = function(data){
    $(apx.copyFramework.consts.alertSuccess).find('a.js-docDestination')
        .attr('href', apx.copyFramework.consts.docDestination + data.docDestinationId);
    $(apx.copyFramework.consts.alertSuccess).removeClass('hidden');
    $(apx.copyFramework.consts.spinnerContainerSelector).addClass('hidden');
    apx.copyFramework.resetModalAfterRequest();
};

apx.copyFramework.copyFrameworkRequestFail = function(data){
    $(apx.copyFramework.consts.dangerSuccess).removeClass('hidden');
    $(apx.copyFramework.consts.spinnerContainerSelector).addClass('hidden');
    apx.copyFramework.resetModalAfterRequest();
};

apx.copyFramework.resetModalAfterRequest = function(data){
    setTimeout(apx.copyFramework.resetModal, 3000);
}

apx.copyFramework.resetModal = function(data){
    $(apx.copyFramework.consts.alertSuccess).addClass('hidden');
    $(apx.copyFramework.consts.dangerSuccess).addClass('hidden');
    $(apx.copyFramework.consts.spinnerContainerSelector).addClass('hidden');
    $(apx.copyFramework.consts.contentModal).removeClass('hidden');
};
