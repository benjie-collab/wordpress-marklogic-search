<?php
/*
*/

namespace MarkLogic\WordPressSearch;

if ( !defined( 'WP_UNINSTALL_PLUGIN' )) 
    exit ();

delete_option( 'marklogic_search' );

?>
