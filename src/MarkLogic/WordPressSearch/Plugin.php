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
use MarkLogic\MLPHP\RESTClient;

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
            return new NullLogger();
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

        $this['builder'] = $this->share(function ($p) {
            return new DocumentBuilder();
        });

        $this['client'] = $this->share(function ($p) {
            return new Client($p['restclient'], $p['builder']);
        });
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
}
