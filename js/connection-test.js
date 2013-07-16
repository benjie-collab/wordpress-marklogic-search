jQuery(document).ready(function($) {

    $('input.mws_connection_test').on('click', function(e) {

        url = $('input.mws_connection_test').data('url');
        $.post(url, $('form.mws_connection_settings').serialize(), function(d, ts, jqx) {
            alert(d);
        });

    });

});
