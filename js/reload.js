jQuery(document).ready(function($) {

    $('input.mws_reload_posts').on('click', function(e) {
        url = window.wms_reload.url;
        $.post(url, {
            action : 'wms_reload_all'
        }, function(d, ts, jqx) {
            alert(d);
        });
    });

});
