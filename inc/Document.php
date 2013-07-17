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

        $doc = new MLPHP\XMLDocument(Api::client(), $uri);

        $root = new \SimpleXMLElement('<doc/>');

        $root->addChild("content", htmlentities($post->post_content, ENT_COMPAT , "UTF-8"));
        $root->addChild("author", $post->post_author); // TODO: consider looking up name and denormalizing other deets about author
        $root->addChild("name", $post->post_name);
        $root->addChild("type", $post->post_type);
        $root->addChild("title", htmlentities($post->post_title, ENT_COMPAT , "UTF-8"));
        $root->addChild("date", str_replace(" ", "T", $post->post_date_gmt));
        $root->addChild("modified", str_replace(" ", "T", $post->post_modified_gmt));
        $root->addChild("status", htmlentities($post->post_status, ENT_COMPAT , "UTF-8"));

        // $doc->setContentType(get_post_mime_type($post->ID));

        $doc->setContent($root->saveXML());

        // Api::logger()->debug("mime_type: " . get_post_mime_type($post->ID));
        $doc->setContentType("application/xml"); // XXX mlphp bug

        $doc->write($uri);
    }
}


