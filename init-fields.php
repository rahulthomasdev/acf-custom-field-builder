<?php

/**
 * Get ACF Field Builder groups (cached).
 */
function get_acf_field_builder_groups()
{
    static $groups = null;

    if ($groups === null) {
        $groups = get_option('acf_field_builder_groups', []);
    }

    return $groups;
}


/**
 * Registration logic for the new ACF field type.
 */

if (! defined('ABSPATH')) {
    exit;
}
add_action('init', 'init_acf_custom_field_builder_dynamic');

/**
 * Registers the ACF field type.
 */
function init_acf_custom_field_builder_dynamic()
{
    if (! function_exists('acf_register_field_type')) {
        return;
    }

    require_once __DIR__ . '/acf-custom-field-builder-dynamic-field-builder.php';

    $fieldDefinitions = get_acf_field_builder_groups();

    foreach ($fieldDefinitions as $key => $fieldDefinition) {
        $className = $fieldDefinition['name'];
        $className = preg_replace('/[^A-Za-z0-9_]/', '_', $className);
        acf_register_field_type($className);
    }
}

add_action('admin_enqueue_scripts', function () {
    $fieldDefinitions = get_acf_field_builder_groups();
    $url = defined('ACF_FIELD_BUILDER_URL') ? ACF_FIELD_BUILDER_URL : plugin_dir_url(__FILE__);
    $version = defined('ACF_FIELD_BUILDER_VERSION') ? ACF_FIELD_BUILDER_VERSION : '1.0';
    wp_enqueue_media();
    wp_register_script('acf-custom-field-builder', "{$url}assets/js/acf-custom-field-builder.js", array('acf-input', 'jquery'), $version, true);
    wp_register_style('acf-custom-field-builder', "{$url}assets/css/acf-custom-field-builder.css", array('acf-input'), $version);
    wp_enqueue_script('acf-custom-field-builder');
    wp_enqueue_style('acf-custom-field-builder');
    wp_add_inline_script('acf-custom-field-builder', 'var acfCustomFieldBuilder = ' . json_encode($fieldDefinitions) . ';', 'acf-custom-field-builder');
}, 10);
