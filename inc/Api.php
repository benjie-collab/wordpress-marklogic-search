<?php
namespace MarkLogic\WordPressSearch;

use MarkLogic\MLPHP;

use Monolog\Logger;
use Monolog\Handler\ChromePHPHandler;
use Monolog\Handler\StreamHandler;

class Api {

	static private $client = null;
	static private $options = null;
	static private $logger = null;

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
            self::$logger->pushHandler(new ChromePHPHandler());
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

    static function reloadAll() {
            self::logger()->debug("reloadAll");
		$posts = get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
            self::logger()->debug("Hi");
    
		foreach($posts as $post){
			Document::addOrUpdate($post);
            self::logger()->debug("Hello");
		}

        return count($posts);

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

        return count($posts) + count($attachmets);
        */
    }
}

?>
