<?php
namespace MarkLogic\WordPressSearch;

use MarkLogic\MLPHP;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Api {

	static private $client = null;
	static private $options = null;
	static private $logger = null;

    static private $types = array('post', 'page', 'attachment');
    static private $tax = array();

    static function types() {
        return self::$types;
    }

    static function taxonomies() {
        return self::$tax;
    }

	static function option($name) {
		if(self::$options == null) {
			self::$options = get_option('marklogic_search');
		}

		return isset(self::$options[$name]) ? self::$options[$name] : null;
	}

	static function logger() {
		if (self::$logger == null) {
            self::$logger = new \Monolog\Logger('marklogic_search');
            self::$logger->pushHandler(new StreamHandler('/tmp/debug.log', Logger::DEBUG));
        }
        return self::$logger;
    }

	static function client() {

		if (self::$client == null) {

            self::$client = new MLPHP\RESTClient(
                self::option('host'),
                self::option('port'),
                '',
                'v1',
                self::option('user'),
                self::option('password'),
                "digest",
                self::logger()
            );
        }

		return self::$client;
	}

    static function clear() {
        self::logger()->debug("clear");
		$posts = get_posts(array(
            'post_type' => array('post','page'),
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

		foreach($posts as $post){
			Document::delete($post);
		}

        return "Cleared " . count($posts) . " posts/pages";
    }

    static function reloadAll() {
        self::logger()->debug("reloadAll");
		$posts = get_posts(array(
            'post_type' => array('post','page'),
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
    
		foreach($posts as $post){
			Document::addOrUpdate($post);
		}

        $ret =  "Reloaded " . count($posts) . " posts/pages";

        /*
        $attachments = get_posts(array( 
            'post_type' => 'attachment', 
            'posts_per_page' => -1, 
            'post_status' => 'any', 
            'post_parent' => null 
        ));

		foreach($attachments as $post){
			Document::addOrUpdate($post);
		}

        $ret .=  " and " . count($attachments) " attachments";
        */
        return  $ret;
    }
}


