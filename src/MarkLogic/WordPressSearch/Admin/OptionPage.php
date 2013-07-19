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

class OptionPage extends AutoHook
{
    private $generator;

    public function setup()
    {
        $this->generator = new FieldGenerator();
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_menu', array($this, 'registerPage'));
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
                    <input type="submit" class="button-primary" value="<?php esc_attr_e('Save', 'marklogicws'); ?>" />
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
        );
    }
}
