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

use Psr\Log\NullLogger;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use MarkLogic\MLPHP\RESTClient;
use MarkLogic\MLPHP\Database;

/**
 * A service locator.
 *
 * @since   1.0
 * @author  Christopher Davis <chris@pmg.co>
 */
class Plugin extends \Pimple
{
    const OPTION = 'marklogic_search';

    private static $instance = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $services=array())
    {
        parent::__construct($services);

        $this['logger'] = $this->share(function ($p) {
            if ($p->option('debug_log')) {
                $logger = new \Monolog\Logger('marklogic_search');
                $logger->pushHandler(new \Monolog\Handler\StreamHandler('/tmp/debug.log', Logger::DEBUG));
                return $logger;
            } else {
                return new NullLogger();
            }
        });

        $this['builder'] = $this->share(function ($p) {
            return new DocumentBuilder();
        });

        $this['restclient'] = $this->share(function ($p) {
            return new RESTClient(
                $p->option('host'),
                $p->option('port'),
                '',
                'v1',
                $p->option('user'),
                $p->option('password'),
                'digest',
                $p['logger']
            );
        });

        $this['client'] = $this->share(function ($p) {
            return new Client($p['restclient'], $p['builder']);
        });

        $this['searcher'] = $this->share(function ($p) {
            return new Searcher();
        });
    }

    /**
     * 
     */
    public function init() 
    {
        $i = self::instance();
        $client = $i['client'];

	    add_action( 'save_post',   array( $client, 'savePost' ) );
	    add_action( 'delete_post', array( $client, 'deletePost' ) ); // XXX do I need both hooks?
	    add_action( 'trash_post',  array( $client, 'deletePost' ) );

        $searcher = $i['searcher'];
        $searcher->init();
    }
       

    /**
     * A helper to get options.
     *
     * @since   1.0
     * @access  public
     * @param   string $key
     * @param   mixed $default
     * @return  mixed
     */
    public function option($key, $default=null)
    {
        $opts = get_option(static::OPTION, array());
        return array_key_exists($key, $opts) ? $opts[$key] : $default;
    }

    /**
     * We need to share the same instance of this throughout the plugin --
     * specifically for subclasses of AutoHook. This is a helper to do that.
     *
     * XXX global state, sad.
     *
     * @since   1.0
     * @access  public
     * @return  MarkLogic\WordPressSearch\Plugin
     */
    public static function instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @return Client
     */
    public static function client() {
        $i = self::instance();
        return $i['client'];
    }

    /**
     * @param $s string
     */
    public static function debug($s) {
        $i = self::instance();
        $i['logger']->debug($s);
    }

    /**
     * @param $s string
     */
    public static function error($s) {
        $i = self::instance();
        $i['logger']->error($s);
    }
}
