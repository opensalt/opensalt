$.fn.select2.defaults.set('theme', 'bootstrap');
//$.fn.modal.Constructor.prototype.enforceFocus = function() {};
// OR try when adding the select2....
//  $("#select2insidemodal").select2({
//    dropdownParent: $("#myModal")
//  });
$('body').tooltip({
    container: 'body',
    selector: '[data-toggle="tooltip"]'
});

jQuery.fn.extend({
    getURLParameter: function(sParam) {
        let sPageURL      = window.location.search.substring(1);
        let sURLVariables = sPageURL.split('&');
        let value         = '';
        for (let i = 0; i < sURLVariables.length; i++)  {
            let sParameterName = sURLVariables[i].split('=');
            if (sParameterName[0] === sParam)  {
                value = sParameterName[1];
            }
        }
        return value;
    }
});

$(document).ready(function() {
    let editFramework = $().getURLParameter('edit');
    if( editFramework > 0 ) {
        $("button[data-target='#editDocModal']").trigger('click');
    }
});