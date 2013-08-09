<?php
/**
 * MarkLogic WordPress Search
 *
 * @category    WordPress
 * @package     MarkLogicWordPressSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 * @copyright   2013 MarkLogic Corporation
 */

namespace MarkLogic\WordPressSearch;

require __DIR__ . "/class.html2text.inc";

/**
 * Default implementation of our document builder.
 *
 * @since   1.0
 * @author  Christopher Davis <chris@pmg.co>
 * @author  Eric Bloch <eric.bloch@gmail.com>
 */
class DocumentBuilder implements DocumentBuilderInterface
{
    const MARKLOGIC_DIR = "/wordpress/";
    const MARKLOGIC_XML_EXT = ".xml";

    static function uri_to_id($uri) {
        $len = strlen($uri) - strlen(self::MARKLOGIC_DIR) - strlen(self::MARKLOGIC_XML_EXT);
        return substr($uri, strlen(self::MARKLOGIC_DIR), $len);
    }

    static protected function esc($s) {
        return htmlentities($s, ENT_COMPAT, "UTF-8");
    }

    static protected function tidy($s) {
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

    /**
     * {@inheritdoc}
     */
    static public function uri($post) {
        return self::MARKLOGIC_DIR . $post->ID . self::MARKLOGIC_XML_EXT;
    }
    
    /**
     * {@inheritdoc}
     */
    public function build($post)
    {
        if (!is_object($post)) {
            return null;
        }

        $dom = new \DOMDocument();
        $root = $dom->createElement("doc");
        $dom->appendChild($root); 
        
        $xml = $dom->saveXML();

        // allow users to complex avoid what follows, not generally advisable
        // but dooable in any case.
        if (apply_filters('mlws_pre_build_document', false, $xml, $post)) {
            return apply_filters('mlws_document', $xml, $post);
        }

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
        Plugin::debug("tidyXML: " . $tidyXML);
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

        $xml = $dom->saveXML();

        do_action('mlws_document', $xml, $post);

        return apply_filters('mlws_document', $xml, $post);
    }
}
