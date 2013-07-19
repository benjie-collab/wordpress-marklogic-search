<?php
/**
 * MarkLogic WordPress Search
 *
 * Some Un-namespaced global functions.
 *
 * @category    WordPress
 * @package     MarkLogicWordPressSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 * @copyright   2013 MarkLogic Corporation
 */

use MarkLogic\WordPressSearch;

function mlws_load()
{
    if (is_admin()) {
        WordPressSearch\Admin\OptionPage::init();
    }

    do_action('mlws_loaded');
}

function mlws_install()
{
    if (version_compare(get_bloginfo('version'), '3.5', '<')) {
        deactivate_plugins(basename(__FILE__));
    }

    add_option(WordPressSearch\Plugin::OPTION, array(
        'host'      => 'localhost',
        'port'      => 8123,
        'user'      => 'admin',
        'password'  => 'admin',
        'auth'      => 'digest',
        'enabled'   => 'off',
    ));
}
