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
 * DocumentBuilder implementations take a WordPress post object and turn it
 * into an XML document
 *
 * @since   1.0
 * @author  Christopher Davis <chris@pmg.co>
 */
interface DocumentBuilderInterface
{
    /**
     * Generate the URI for document.
     *
     * @since   1.0
     * @access  public
     * @uses    apply_filters
     * @param   object|WP_Post $post
     * @return  string
     */
    public function uri($post);

    /**
     * Turn a post into an XML string.
     *
     * @since   1.0
     * @access  public
     * @uses    apply_filters
     * @uses    do_action
     * @param   object|WP_Post $post
     * @return  string
     */
    public function build($post);
}
