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

/**
 * Default implementation of our document builder.
 *
 * @since   1.0
 * @author  Christopher Davis <chris@pmg.co>
 */
class DocumentBuilder implements DocumentBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function uri($post)
    {
        $uri = null;
        if (is_object($post) && isset($post->ID)) {
            $uri = "/{$post->ID}";
        }

        // XXX maybe a bad idea to allow this to be filterable?
        return apply_filters('mlws_document_uri', $uri, $post);
    }

    /**
     * {@inheritdoc}
     */
    public function build($post)
    {
        if (!is_object($post)) {
            return null;
        }

        $doc = new \SimpleXMLElement('<doc/>');

        // allow users to complex avoid what follows, not generally advisable
        // but dooable in any case.
        if (apply_filters('mlws_pre_build_document', false, $doc, $post)) {
            return apply_filters('mlws_document', $doc->asXML(), $post);
        }

        $this->addPost($doc, $post);
        $this->addAuthor($doc, $post);
        $this->addTerms($doc, $post);

        do_action('mlws_document', $doc, $post);

        return apply_filters('mlws_document', $doc->asXML(), $post);
    }

    /**
     * Add all the post fields to our document.
     *
     * @since   1.0
     * @access  public
     * @uses    do_action
     * @param   SimpleXMLElement $doc
     * @param   object|WP_Post $post
     * @return  void
     */
    protected function addPost(\SimpleXMLElement $doc, $post)
    {
        static $fields = null;
        if (null === $fields) {
            $fields = $this->getSavablePostFields();
        }

        foreach ($fields as $input => $output) {
            $doc->addChild($output, isset($post->$input) ? $this->escape($post->$input) : '');
        }

        do_action('mlws_document_add_post', $doc, $post);
    }

    /**
     * Returns the array of fields from the post that we'll save to MarkLogic
     *
     * Should be an array of $object_attr => output tag pairs
     *
     * @since   1.0
     * @access  protected
     * @uses    apply_filters
     * @return  array
     */
    protected function getSavablePostFields()
    {
        return apply_filters('mlws_savable_post_fields', array(
            'ID'                => 'id',
            'post_content'      => 'content',
            'post_excerpt'      => 'excerpt',
            'post_title'        => 'title',
            'post_type'         => 'type',
            'post_date_gmt'     => 'date',
            'post_modified_gmt' => 'modified',
            'post_status'       => 'status',
            'post_name'         => 'name',
        ));
    }

    /**
     * Add the author object to our post.
     *
     * @since   1.0
     * @access  protected
     * @uses    do_action
     * @param   SimpleXMLElement $doc
     * @param   object|WP_Post $post
     * @return  void
     */
    protected function addAuthor(\SimpleXMLElement $doc, $post)
    {
        static $fields = null;
        if (null === $fields) {
            $fields = $this->getSavableAuthorFields();
        }

        $author = $doc->addChild('author');
        foreach ($fields as $input => $output) {
            $author->addChild($output, $this->escape(get_the_author_meta($input, $post->post_author)));
        }

        do_action('mlws_document_add_author', $author, $post, $doc);
    }

    /**
     * Get the author fields we 'll save to MarkLogic in $input => $output pairs
     *
     * @since   1.0
     * @access  protected
     * @uses    apply_filters
     * @return  array
     */
    protected function getSavableAuthorFields()
    {
        return apply_filters('mlws_savable_author_fields', array(
            'ID'            => 'id',
            'user_login'    => 'login',
            'user_nicename' => 'nicename',
            'display_name'  => 'display_name',
            'nickname'      => 'nickname',
            'first_name'    => 'first_name',
            'last_name'     => 'last_name',
            'description'   => 'description',
        ));
    }

    /**
     * Add all the terms for a given post.
     *
     * @since   1.0
     * @access  protected
     * @param   SimpleXMLElement $doc
     * @param   object|WP_Post $post
     * @return  void
     */
    protected function addTerms(\SimpleXMLElement $doc, $post)
    {
        static $fields = null;
        if (null === $fields) {
            $fields = $this->getSavableTermFields();
        }

        $taxonomies = apply_filters(
            'mlws_document_taxonomies',
            get_object_taxonomies($post->post_type, 'object'),
            $post
        );

        $all_terms = wp_get_object_terms($post->ID, array_keys($taxonomies));

        foreach ($taxonomies as $name => $tax) {
            $tax_doc = $doc->addChild('taxonomy');
            $tax_doc->addChild('name', $name);
            $tax_doc->addChild(
                'singular_name',
                isset($tax->labels->singular_name) ? $this->escape($tax->labels->singular_name) : ''
            );
            $tax_doc->addChild(
                'plural_name',
                isset($tax->labels->name) ? $this->escape($tax->labels->name) : ''
            );

            $terms = $tax_doc->addChild('terms');
            foreach (wp_list_filter($all_terms, array('taxonomy' => $name)) as $term) {
                $_term = $terms->addChild('term');

                foreach ($fields as $input => $output) {
                    $_term->addChild($output, isset($term->$input) ? $this->escape($term->$input) : '');
                }
            }

            do_action('mlws_document_add_taxonomy', $tax_doc, $post, $tax, $terms, $doc);
        }
    }

    /**
     * Get the fields for a term that are allowed to be saved.
     *
     * @since   1.0
     * @access  protected
     * @uses    apply_filters
     * @return  array
     */
    protected function getSavableTermFields()
    {
        return apply_filters('mlws_savable_term_fields', array(
            'term_id'       => 'id',
            'name'          => 'name',
            'slug'          => 'slug', 
            'term_group'    => 'term_group',
            'description'   => 'description',
        ));
    }

    /**
     * Escape content for SimpleXMLElement. For now this just uses esc_attr
     *
     * @since   1.0
     * @access  protected
     * @uses    esc_attr
     * @param   string $to_esc
     * @return  string
     */
    protected function escape($to_esc)
    {
        return esc_attr($to_esc);
    }
}
