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

define('MLWS_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/functions.php';

register_activation_hook(__FILE__, 'mlws_install');
add_action('plugins_loaded', 'mlws_load');
