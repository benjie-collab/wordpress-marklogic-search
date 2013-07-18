<?php

namespace MarkLogic\WordPressSearch;

use MarkLogic\MLPHP;

class Document {

	static function delete($post) {
        $uri = "/" . $post->ID;
        $doc = new MLPHP\Document(Api::client(), $uri);
        $doc->delete($uri);
    }

    static function esc($s) {
        return htmlentities($s, ENT_COMPAT, "UTF-8");
    }

    static function addOrUpdate($post) {

        $uri = "/" . $post->ID;

        Api::logger()->debug("POST: " . serialize($post));
        Api::logger()->debug("URI: " . $uri);

        $doc = new MLPHP\XMLDocument(Api::client(), $uri);

        $root = new \SimpleXMLElement('<doc/>');

        $root->addChild("id", $post->ID);
        $root->addChild("content", self::esc($post->post_content));
        $tags = $root->addChild("tags");
        $post_tags = get_the_tags($post->ID);
        if ($post_tags) {
            foreach ($post_tags as $tag) {
                $t = $tags->addChild("tag");
                $t->addChild("id", self::esc($tag->term_id));
                $t->addChild("name", self::esc($tag->name));
                $t->addChild("slug", self::esc($tag->slug));
                $t->addChild("term_group", self::esc($tag->term_group));
                $t->addChild("taxonomy", self::esc($tag->taxonomy));
                $t->addChild("description", self::esc($tag->description));
            }
        }
        $author = $root->addChild("author");
            $author->addChild("id", $post->post_author);
            $author->addChild("login", self::esc(get_the_author_meta('login', $post->post_author)));
            $author->addChild("nicename", self::esc(get_the_author_meta('user_nicename', $post->post_author)));
            $author->addChild("display_name", self::esc(get_the_author_meta('display_name', $post->post_author)));
            $author->addChild("nickname", self::esc(get_the_author_meta('nickname', $post->post_author)));
            $author->addChild("first_name", self::esc(get_the_author_meta('first_name', $post->post_author)));
            $author->addChild("last_name", self::esc(get_the_author_meta('last_name', $post->post_author)));
            $author->addChild("description", self::esc(get_the_author_meta('description', $post->post_author)));

        $root->addChild("name", $post->post_name);
        $root->addChild("type", $post->post_type);
        $root->addChild("title", self::esc($post->post_title));
        $root->addChild("date", str_replace(" ", "T", $post->post_date_gmt));
        $root->addChild("modified", str_replace(" ", "T", $post->post_modified_gmt));
        $root->addChild("status", self::esc($post->post_status));

        $doc->setContent($root->saveXML());

        // Api::logger()->debug("mime_type: " . get_post_mime_type($post->ID));
        $doc->setContentType("application/xml"); // XXX I think an mlphp bug; this shouldn't be needed

        $doc->write($uri);
    }
}


