<?php

namespace MarkLogic\WordPressSearch;

use MarkLogic\MLPHP;

class Document {

	static function delete($post) {
        $uri = "/" . $post->ID;
        $doc = new MLPHP\Document(Api::client(), $uri);
        $doc->delete($uri);
    }

    static function addOrUpdate($post) {

        $uri = "/" . $post->ID;

        Api::logger()->debug("POST: " . serialize($post));
        Api::logger()->debug("URI: " . $uri);

        $doc = new MLPHP\Document(Api::client(), $uri);
        $doc->setContent($post->post_content);
        // $doc->setContentType(get_post_mime_type($post->ID));

        Api::logger()->debug("mime_type: " . get_post_mime_type($post->ID));
        $doc->setContentType("text/plain");

        $doc->write($uri);
    }
}

?>
