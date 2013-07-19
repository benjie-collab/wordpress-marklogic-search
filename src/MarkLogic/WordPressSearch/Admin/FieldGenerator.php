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

class FieldGenerator
{
    public function text(array $args)
    {
        $this->input('text', $args['label_for'], $args['value']);
    }

    public function password(array $args)
    {
        $this->input('password', $args['label_for'], $args['value']);
    }

    public function checkbox(array $args)
    {
        printf(
            '<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s />',
            esc_attr($args['label_for']),
            checked('on', $args['value'], false)
        );
    }

    protected function input($type, $id, $value)
    {
        printf(
            '<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" class="regular-text" />',
            esc_attr($type),
            esc_attr($id),
            esc_attr($value)
        );
    }
}
