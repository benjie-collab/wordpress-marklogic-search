<?php

namespace MarkLogic\WordPressSearch;

use MarkLogic\MLPHP;

require __DIR__ . "/class.html2text.inc";

class Document {

    const MARKLOGIC_DIR = "/wordpress/";
    const MARKLOGIC_XML_EXT = ".xml";

    static function post_uri($post) {
        return self::MARKLOGIC_DIR . $post->ID . self::MARKLOGIC_XML_EXT;
    }
    
    static function uri_to_id($uri) {
        $len = strlen($uri) - strlen(Document::MARKLOGIC_DIR) - strlen(Document::MARKLOGIC_XML_EXT);
        return substr($uri, strlen(Document::MARKLOGIC_DIR), $len);
    }

    static function esc($s) {
        return htmlentities($s, ENT_COMPAT, "UTF-8");
    }

    static function tidy($s) {
        $t = new \tidy();
        $t->parseString("<html>".$s. "</html>", array(
            'input-encoding'   => 'utf8',
            'output-encoding'  => 'utf8',
            'input-xml'        => true,
            'output-xhtml'     => true,
            'show-body-only'   => true,
            'add-xml-space'    => true,
            'doctype'          => 'omit',
            'numeric-entities' => true
        ), 'utf8');
        $t->cleanRepair();
        return $t;
    }


    static function delete($post) {
        $doc = new MLPHP\Document(Api::client(), self::post_uri($post));
        $doc->delete(self::post_uri($post));
    }

    static function addOrUpdate($post) {

        $uri = self::post_uri($post); 

        Api::logger()->debug("POST: " . serialize($post));
        Api::logger()->debug("URI: " . $uri);

        $doc = new MLPHP\XMLDocument(Api::client(), $uri);

        $dom = new \DOMDocument();
        $root = $dom->createElement("doc");
        $dom->appendChild($root); 
        
        $id = $dom->createElement("id", $post->ID);
        $root->appendChild($id);

        $content = $dom->createElement("content");
        $root->appendChild($content);
        $textContent = $dom->createTextNode(self::esc($post->post_content));
        $content->appendChild($textContent);

        $content = $dom->createElement("content-text");
        $root->appendChild($content);
        $cvt = new \html2text($post->post_content);
        $textContent = $dom->createTextNode($cvt->get_text());
        $content->appendChild($textContent);

        $tidyDoc = new \DOMDocument();
        $tidyXML = self::tidy($post->post_content);
        Api::logger()->debug("tidyXML: " . $tidyXML);
        $tidyDoc->loadXML("<content-tidy>" . $tidyXML . "</content-tidy>");
        $tidyElt = $dom->importNode($tidyDoc->childNodes->item(0), true);
        $root->appendChild($tidyElt);

        $tags = $dom->createElement("tags");
        $root->appendChild($tags);
        
        $post_tags = get_the_tags($post->ID);
        if ($post_tags) {
            foreach ($post_tags as $tag) {
                
                $te = $dom->createElement("tag");
                $tags->appendChild($te);

                foreach (array(
                    'id'            => $tag->term_id,
                    'name'          => $tag->name,
                    'slug'          => $tag->slug, 
                    'term_group'    => $tag->term_group,
                    'taxonomy'      => $tag->taxonomy,
                    'description'   => $tag->description

                ) as $field => $value) {
                    $e = $dom->createElement($field, self::esc($value));
                    $te->appendChild($e);
                }
            }
        }
        
        $author = $dom->createElement("author");
        $root->appendChild($author);
        $dom->createElement("id", $post->post_author);
        foreach (array(
            'login',
            'user_nicename',
            'display_name', 
            'nickname',
            'first_name',
            'last_name',
            'description'
        ) as $field) {
            $e = $dom->createElement($field, self::esc(get_the_author_meta($field, $post->post_author)));
            $author->appendChild($e);
        }

        $root->appendChild($dom->createElement("name", $post->post_name));
        $root->appendChild($dom->createElement("type", $post->post_type));
        $root->appendChild($dom->createElement("title", self::esc($post->post_title)));
        $root->appendChild($dom->createElement("date_gmt", str_replace(" ", "T", $post->post_date_gmt)));
        $root->appendChild($dom->createElement("modified_gmt", str_replace(" ", "T", $post->post_modified_gmt)));
        $root->appendChild($dom->createElement("date", str_replace(" ", "T", $post->post_date)));
        $root->appendChild($dom->createElement("modified", str_replace(" ", "T", $post->post_modified)));
        $root->appendChild($dom->createElement("status", self::esc($post->post_status)));

        $doc->setContent($dom->saveXML());

        // Api::logger()->debug("mime_type: " . get_post_mime_type($post->ID));

        $doc->write($uri);
    }
}


