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

use MarkLogic\MLPHP\RESTClient;
use MarkLogic\MLPHP\XMLDocument as MarkLogicDocument;

/**
 * Some thin wrappers arround the MLPHP rest client.
 *
 * @since   1.0
 * @author  Christopher Davis <chris@pmg.co>
 */
class Client
{
    /**
     * The MarkLogic rest client.
     *
     * @since   1.0
     * @access  protected
     * @var     MarkLogic\MLPHP\RESTClient
     */
    protected $client = null;

    /**
     * Document builder.
     *
     * @since   1.0
     * @access  protected
     * @var     MarkLogic\WordPressSearch\DocumentBuilderInterface
     */
    protected $builder = null;

    /**
     * Constructor. Set the client and builder.
     *
     * @since   1.0
     * @access  public
     * @param   MarkLogic\MLPHP\RESTClient $client
     * @param   MarkLogic\WordPressSearch\DocumentBuilderInterface $builder
     * @return  void
     */
    public function __construct(RESTClient $client, DocumentBuilderInterface $builder)
    {
        $this->setRestClient($client);
        $this->setDocumentBuilder($builder);
    }

    /** Setters/Getters **********/

    /**
     * Set the client.
     *
     * @since   1.0
     * @access  public
     * @param   MarkLogic\MLPHP\RESTClient $marklogic
     * @return  MarkLogic\WordPressSearch\Client
     * @chainable
     */
    public function setRestClient(RESTClient $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Get the rest client
     *
     * @since   1.0
     * @access  public
     * @return  MarkLogic\MLPHP\RESTClient
     */
    public function getRestClient()
    {
        return $this->client;
    }

    /**
     * Set the document builder.
     *
     * @since   1.0
     * @access  public
     * @param   MarkLogic\WordPressSearch\DocumentBuilderInterface $builder
     * @return  MarkLogic\WordPressSearch\Client
     * @chainable
     */
    public function setDocumentBuilder(DocumentBuilderInterface $builder)
    {
        $this->builder = $builder;
        return $this;
    }

    /**
     * Get the document builder.
     *
     * @since   1.0
     * @access  public
     * @return  MarkLogic\WordPressSearch\DocumentBuilderInterface
     */
    public function getDocumentBuilder()
    {
        return $this->builder;
    }

    /** API **********/

    /**
     * Delete a post from the MarkLogic backend
     *
     * @since   1.0
     * @access  public
     * @param   object|WP_Post $post
     * @return  boolean true on success
     */
    public function deletePost($post)
    {
        if ($uri = $this->getDocumentBuilder()->uri($post)) {
            $this->generateDocument($uri)->delete();
            return true;
        }

        return false;
    }

    /**
     * Add or update a post on the MarkLogic server
     *
     * @since   1.0
     * @access  public
     * @param   object|WP_Post $post
     * @return  boolean true on success
     */
    public function savePost($post)
    {
        $builder = $this->getDocumentBuilder();

        $uri = $builder->uri($post);
        if (!$uri) {
            return false;
        }

        $xml = $builder->build($post);
        if (!$xml) {
            return false;
        }

        $doc = $this->generateDocument($uri);
        $doc->setContent($xml);
        $doc->setContentType("application/xml");
        $doc->write();

        return true;
    }

    protected function generateDocument($uri)
    {
        return new MarkLogicDocument($this->getRestClient(), $uri);
    }
}
