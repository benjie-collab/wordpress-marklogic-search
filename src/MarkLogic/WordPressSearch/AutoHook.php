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
 * Abstract base class that provides a nice instance init scheme
 *
 * @since   1.0
 * @author  Christopher Davis <chris@pmg.co>
 */
abstract class AutoHook
{
    protected $plugin;

    private static $reg = array();

    public static function instance()
    {
        $cls = get_called_class();

        if (!isset(self::$reg[$cls])) {
            self::$reg[$cls] = new $cls(Plugin::instance());
        }

        return self::$reg[$cls];
    }

    public static function init()
    {
        static::instance()->setup();
    }

    abstract public function setup();

    /**
     * Constructor. Set the plugin.
     *
     * @since   1.0
     * @access  public
     * @param   Pimple $plugin
     * @return  void
     * @final
     */
    final public function __construct(\Pimple $plugin)
    {
        $this->plugin = $plugin;
    }
}
