jQuery(document).ready(function($) {

    $('input.mws_reload_posts').on('click', function(e) {

        url = $('input.mws_reload_posts').data('url');
        $.post(url, "", function(d, ts, jqx) {
            alert(d);
        });

    });

});
