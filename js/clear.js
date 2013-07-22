jQuery(document).ready(function($) {

    $('input.mws_clear').on('click', function(e) {
        url = window.wms_reload.url;
        $.post(url, {
            action : 'wms_clear'
        }, function(d, ts, jqx) {
            alert(d);
        });
    });

});
