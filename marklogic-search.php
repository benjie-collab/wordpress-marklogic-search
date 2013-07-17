<?php
/*
Plugin Name: MarkLogic Search
Plugin URI: https://github.com/marklogic/wordpress-marklogic-search
Description: Replacement (and improvement over) default WordPress search 
Version: 0.0.0
Author: Eric Bloch
Author URI: https://github.com/eedeebee
Author Email: eric.bloch@gmail.com
License:

  Copyright 2013 Eric Bloch (eric.bloch@gmail.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  
*/

namespace MarkLogic\WordPressSearch;

require(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

register_activation_hook( __FILE__, 'MarkLogic\WordPressSearch\install' );

use MarkLogic\MLPHP;

spl_autoload_register(function($class){
    $path = str_replace('MarkLogic\\WordPressSearch\\', '', $class);
    $path = str_replace(array('_', "\\"), DIRECTORY_SEPARATOR, $path);

    if (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . $path . '.php')) {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . $path . '.php');
    }
});

require('inc/hooks.php');

add_action( 'admin_menu', 'MarkLogic\WordPressSearch\create_menus');
add_action( 'wp_ajax_reload_all', 'MarkLogic\WordPressSearch\reload_all');
add_action( 'wp_ajax_clear', 'MarkLogic\WordPressSearch\clear');

function install() {
    if (version_compare( get_bloginfo('version'), '3.5', '<') ) {
        deactivate_plugins( basename( __FILE__ ) );
    }

    update_option( 'marklogic_search', array(
        'host' => 'localhost',
        'port' => 8123,
        'user' => 'admin',
        'password' => 'admin',
        'auth' => 'digest'
    )) ;
}

/* add_action( 'plugins_loaded', 'wms_setup'); */
/* add_action( 'init',           'wms_setup'); */


function create_menus() {

    add_menu_page('MarkLogic Search Settings', 'MarkLogic Search',
        'manage_options', 'marklogic_search', 'MarkLogic\WordPressSearch\admin_settings_page',
        plugins_url( 'wordpress-marklogic-search/images/marklogic-16x16.jpg' ) );

}

function admin_settings_page() {

    wp_enqueue_script(
        'marklogic_search_connection_test',
        plugins_url( 'wordpress-marklogic-search/js/connection-test.js' ) ,
        array('jquery'), '1.0', true
    );
    wp_enqueue_script(
        'marklogic_reload_all',
        plugins_url( 'wordpress-marklogic-search/js/reload.js' ) ,
        array('jquery'), '1.0', true
    );
	wp_localize_script( 'marklogic_reload_all', 'wms_reload', array(
	    'url' => admin_url( 'admin-ajax.php' )
	));
    wp_enqueue_script(
        'marklogic_clear',
        plugins_url( 'wordpress-marklogic-search/js/clear.js' ) ,
        array('jquery'), '1.0', true
    );
	wp_localize_script( 'marklogic_clear', 'wms_clear', array(
	    'url' => admin_url( 'admin-ajax.php' )
	));

    $o = get_option( 'marklogic_search' );

    $o['host'] = isset($_POST['host']) ? $_POST['host'] : $o['host'];
    $o['user'] = isset($_POST['mluser']) ? $_POST['mluser'] : $o['user'];
    $o['port'] = isset($_POST['port']) ? $_POST['port'] : $o['port'];
    $o['password'] = isset($_POST['mlpassword']) ? $_POST['mlpassword'] : $o['password'];

    ?>
        <div class="wrap">
            <?php screen_icon( 'plugins' ); ?> <!-- TODO: improve this image -->
            <h2>MarkLogic Search</h2>
            <h3>REST API Connection Settings</h3>
            <form class="mws_connection_settings" method="POST" action="">
            <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="host">Host</label></th>
                <td><input maxlength="54" size="25" name="host" value="<?php echo $o['host']; ?>"/> </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="port">Port</label></th>
                <td><input maxlength="54" size="25" name="port" value="<?php echo $o['port']; ?>"/> </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="mluser">User</label></th>
                <td><input maxlength="54" size="25" name="mluser" value="<?php echo $o['user']; ?>"/> </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="mlpassword">Password</label></th>
                <td><input type="password" maxlength="54" size="25" name="mlpassword" value="<?php echo $o['password']; ?>"/> </td>
            </tr>
            <tr valign="top">
                <th scope="row">&#160;</th>
                <td><input type="submit" name="Save" value="Save Options" class="button-primary"/>&#160;&#160;
                <input type="button" name="Test" value="Test Options" class="button mws_connection_test" 
                    data-url="<?php echo plugins_url('wordpress-marklogic-search/inc/connection-test.php'); ?>"/></td>
            </tr>
            <tr valign="top">
                <th scope="row">&#160;</th>
            </tr>
            </table>
            </form>
            <h3>Indexing</h3>
            <form class="mws_connection_settings" method="POST" action="">
            <table class="form-table">
            <tr valign="top">
                <th scope="row">&#160;</th>
                <td><input type="button" name="Reload" value="Reload All Posts" class="button mws_reload_posts"/>&#160;&#160;
                <input type="button" name="Clear" value="Clear All Posts" class="button mws_clear"/>
                </td>
            </tr>
            </table>
            </tr>
        </div>
    <?php

    // TODO: server (and client) validation 
    update_option( 'marklogic_search', $o );
    
}

function reload_all() {
    try {
        exit(Api::reloadAll());
    } catch (\Exception $e) {
        header("HTTP/1.0 500 Internal Server Error");
        exit($e->getMessage());
    }
}

function clear() {
    try {
        exit(Api::clear());
    } catch (\Exception $e) {
        header("HTTP/1.0 500 Internal Server Error");
        exit($e->getMessage());
    }
}
?>
