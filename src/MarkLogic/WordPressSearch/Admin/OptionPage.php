<?php
/**
 * MarkLogic WordPress Search
 *
 * @category    WordPress
 * @package     MarkLogicWordPressSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 * @copyright   2013 MarkLogic Corporation
 */

namespace MarkLogic\WordPressSearch\Admin;

use MarkLogic\WordPressSearch\AutoHook;
use MarkLogic\WordPressSearch\Plugin;

use MarkLogic\MLPHP;

class OptionPage extends AutoHook
{
    private $generator;

    public function setup()
    {
        $this->generator = new FieldGenerator();
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_menu', array($this, 'registerPage'));

        add_action( 'wp_ajax_wms_reload_all',      array($this, 'reload_all'));
        add_action( 'wp_ajax_wms_clear',           array($this, 'clear'));
        add_action( 'wp_ajax_wms_connection_test', array($this, 'connection_test'));

    }

    public function registerSettings()
    {
        register_setting(Plugin::OPTION, Plugin::OPTION, array($this, 'settingsValidation'));

        add_settings_section(
            'server',
            __('Server Settings', 'marklogicws'),
            '__return_false',
            Plugin::OPTION
        );

        add_settings_section(
            'enabler',
            __('Enable Search', 'marklogicws'),
            '__return_false',
            Plugin::OPTION
        );

        foreach ($this->getFields() as $key => $field) {
            add_settings_field(
                "mlws_field_{$key}",
                $field['label'],
                array($this->generator, $field['type']),
                Plugin::OPTION,
                $field['section'],
                array(
                    'label_for'     => sprintf('%s[%s]', Plugin::OPTION, $key),
                    'value'         => $this->plugin->option($key),
                )
            );
        }
    }

    public function settingsValidation($dirty)
    {
        $clean = array();
        foreach ($this->getFields() as $key => $field) {
            if ('checkbox' === $field['type']) {
                $clean[$key] = !empty($dirty[$key]) ? 'on' : 'off';
                continue;
            }

            $value = isset($dirty[$key]) ? $dirty[$key] : '';
            $cleaners = isset($field['cleaners']) ? $field['cleaners'] : array();
            foreach((array)$cleaners as $cb) {
                $value = call_user_func($cb, $value);
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    public function registerPage()
    {
        add_menu_page(
            __('MarkLogic Search Settings', 'marklogicws'),
            __('MarkLogic', 'marklogicws'),
            'manage_options',
            'marklogic-search',
            array($this, 'pageCallback'),
            MLWS_URL . 'images/marklogic-16x16.png'
        );
    }

    public function pageCallback()
    {
        wp_enqueue_script(
            'marklogic_connection_test',
            plugins_url( 'wordpress-marklogic-search/js/connection-test.js' ) ,
            array('jquery'), '1.0', true
        );
        wp_enqueue_script(
            'marklogic_reload_all',
            plugins_url( 'wordpress-marklogic-search/js/reload.js' ) ,
            array('jquery'), '1.0', true
        );
        wp_enqueue_script(
            'marklogic_clear',
            plugins_url( 'wordpress-marklogic-search/js/clear.js' ) ,
            array('jquery'), '1.0', true
        );
	    wp_localize_script( 'marklogic_reload_all', 'wms_reload', array(
	        'url' => admin_url( 'admin-ajax.php' )
	    ));
	    wp_localize_script( 'marklogic_clear', 'wms_clear', array(
	        'url' => admin_url( 'admin-ajax.php' )
	    ));
	    wp_localize_script( 'marklogic_connection_test', 'wms_connection_test', array(
	        'url' => admin_url( 'admin-ajax.php' )
	    ));

        ?>
        <div class="wrap">
            <?php screen_icon('plugins'); ?>
            <h2><?php _e('MarkLogic Search Settings', 'marklogicws'); ?></h2>
            <form method="post" action="<?php echo admin_url('options.php'); ?>">
                <?php
                settings_fields(Plugin::OPTION);
                do_settings_sections(Plugin::OPTION);
                ?>
                <p>
                    <input type="submit" class="button-primary" value="<?php esc_attr_e('Save', 'marklogicws'); ?>" />&#160;&#160;
                    <input type="button" name="Test" value="Test Connection" class="button mws_connection_test"/>&#160;&#160;
                    <input type="button" name="Reload" value="Reload All Posts" class="button mws_reload_posts"/>&#160;&#160;
                    <input type="button" name="Clear" value="Clear All Posts" class="button mws_clear"/>
                </p>
            </form>
        </div>
        <?php
    }

    protected function getFields()
    {
        return array(
            'host'      => array(
                'label'     => __('Host', 'marklogicws'),
                'type'      => 'text',
                'section'   => 'server',
                'cleaners'  => array(),
            ),
            'port'      => array(
                'label'     => __('Port', 'marklogicws'),
                'type'      => 'text',
                'section'   => 'server',
                'cleaners'  => array('absint')
            ),
            'user'      => array(
                'label'     => __('Username', 'marklogicws'),
                'type'      => 'text',
                'section'   => 'server',
                'cleaners'  => array(),
            ),
            'password'  => array(
                'label'     => __('Password', 'marklogicws'),
                'type'      => 'password',
                'section'   => 'server',
                'cleaners'  => array(),
            ),
            'enabled'   => array(
                'label'     => __('Search Enabled?', 'marklogicws'),
                'type'      => 'checkbox',
                'section'   => 'enabler',
                'cleaners'  => array(),
            ),
            'debug_log'   => array(
                'label'     => __('Debug Log?', 'marklogicws'),
                'type'      => 'checkbox',
                'section'   => 'server',
                'cleaners'  => array(),
            ),
        );
    }

    function reload_all() {
        try {
            exit(Plugin::client()->reloadAll());
        } catch (\Exception $e) {
            header("HTTP/1.0 500 Internal Server Error");
            exit($e->getMessage());
        }
    }
    
    function clear() {
        try {
            exit(Plugin::client()->clear());
        } catch (\Exception $e) {
            header("HTTP/1.0 500 Internal Server Error");
            exit($e->getMessage());
        }
    }
    
    function connection_test() {
        // Test the current parameters, not the saved ones.
        try {
            $client = new MLPHP\RESTClient(
                
                $_POST['marklogic_search']['host'],
                $_POST['marklogic_search']['port'],
                '',
                'v1',
                $_POST['marklogic_search']['user'],
                $_POST['marklogic_search']['password'],
                "digest"
            );
        
            $search = new MLPHP\Search($client);
            $search->setDirectory('/');
            $results = $search->retrieve("")->getTotal();
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
        exit("Success (". $results . " documents directly under /)");
    }
}
