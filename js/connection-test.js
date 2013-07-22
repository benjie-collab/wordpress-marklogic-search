jQuery(document).ready(function($) {

    $('input.mws_connection_test').on('click', function(e) {

        url = window.wms_connection_test.url;
        $.post(url, $('form.mws_connection_settings').serialize() + "&action=wms_connection_test", function(d, ts, jqx) {
            alert(d);
        });

    });

});
