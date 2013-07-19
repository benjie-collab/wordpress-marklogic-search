<?php

namespace MarkLogic\WordPressSearch;

use MarkLogic\MLPHP;

class Hooks {
	function __construct(){
		add_action( 'save_post', array( &$this, 'save_post' ) );
		// add_action( 'delete_post', array( &$this, 'delete_post' ) );
		// add_action( 'trash_post', array( &$this, 'delete_post' ) );
	}
	
	function save_post( $post_id ) {
		if (is_object( $post_id )) {
			$post = $post_id;
		} else {
			$post = get_post( $post_id );
		}

		if ($post->post_status == 'trash'){
            /*
            // Api::delete($post);

            $o = get_option( 'wms_server_options' );
            $clientdoc = new Document($o['client'], $uri);
            $doc->setContent(
            $doc->setContentType(
            $doc->write(
			Document::delete($post);
            */
		}

		if ($post->post_status == 'publish'){
			Document::addOrUpdate($post);
		}
	}

	function delete_post( $post_id ) {
		if(is_object( $post_id )){
			$post = $post_id;
		} else {
			$post = get_post( $post_id );
		}

        /*
		if(!in_array($post->post_type, Api::types())){
			return;
		}
		$index = Api::index(true);

		Document::delete($post);
        */
	}
}

new Hooks();

