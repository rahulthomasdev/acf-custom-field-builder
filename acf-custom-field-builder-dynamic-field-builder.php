<?php

$fieldDefinitions = get_option('acf_field_builder_groups') ?? [];

foreach ($fieldDefinitions as $fieldDefinition) {
    $className = preg_replace('/[^A-Za-z0-9_]/', '_', $fieldDefinition['name']);
    $fields = $fieldDefinition['fields'];

    $fieldsExport = var_export($fields, true); // Export fields array to string
    $fieldDefinitions = var_export($fieldDefinitions, true);

    // Build eval code to register fields dynamically
    $evalCode = "
    if (!defined('ABSPATH')) exit;

    class $className extends \\acf_field
    {
        public \$show_in_rest = true;
        private \$env;
        private \$fields = $fieldsExport;
        private \$fieldDefinitions = $fieldDefinitions;

        public function __construct()
        {
            \$this->name = '$className';
            \$this->label = __('$className', 'acf-custom-field-builder');
            \$this->category = 'basic';
            \$this->preview_image = ACF_FIELD_BUILDER_URL . '/assets/images/field-preview-custom.png';
            \$this->description = __('Custom ACF field', 'acf-custom-field-builder');
            \$this->env = array('url' => plugin_dir_url(__FILE__), 'version' => '1.0');
            parent::__construct();
        }

        public function render_field(\$field)
        {
            \$rows = is_array(\$field['value']) ? \$field['value'] : [];
            echo '<div class=\"acf-custom-repeater\" data-name=\"' . esc_attr(\$field['name']) . '\">';

            foreach (\$rows as \$index => \$row) {
                echo '<div class=\"acf-custom-repeater-row existing-row\" style=\"background-color: #eee; padding: 1rem;\">';

                foreach (\$this->fields as \$def) {
                    \$label = esc_html(\$def['label'] ?? '');
                    \$type  = \$def['type'] ?? 'text';
                    \$key   = sanitize_title(\$label);
                    \$value = \$row[\$key] ?? '';

                    echo '<div class=\"acf-field-item\" style=\"margin-bottom:1rem;\">';
                    echo '<label style=\"display:block; margin-bottom:5px;\">' . \$label . '</label>';

                    switch (\$type) {
                        case 'textarea':
                            echo '<textarea name=\"' . esc_attr(\$field['name']) . \"[\$index][\$key]\" . '\" style=\"width:100%; height:80px;\">' . esc_textarea(\$value) . '</textarea>';
                            break;
                        case 'wysiwyg':
                            // Render a WYSIWYG editor
                            \$editor_id   = sanitize_key( \$field['name'] . '[' . \$index . '][' . \$key . ']' );
                            \$editor_name = \$field['name'] . '[' . \$index . '][' . \$key . ']';

                            wp_editor(
                                \$value,                
                                \$editor_id,                  
                                array(
                                    'textarea_name' => \$editor_name, 
                                    'media_buttons' => false,        
                                    'textarea_rows' => 5,           
                                    'teeny'         => true,         
                                    'quicktags'     => true,        
                                )
                            );
                            break;
                        case 'image':
                            \$image_id = intval(\$value ?? 0);
			                \$image_url = \$image_id ? wp_get_attachment_image_url(\$image_id, 'thumbnail') : '';
                            // Hidden image field 1
                            echo '<div class=\"image-container\" style=\"display: flex; flex-direction: column; width: fit-content; gap: 8px; margin-bottom: 1rem;\">';
                            echo '<input type=\"hidden\" class=\"image-id\" name=\"' . esc_attr(\$field['name']) . \"[\$index][\$key]\" . '\" value=\"' . esc_attr(\$value) . '\" />';
                            echo '<div class=\"image-preview-actions\">';
                            echo '<button type=\"button\" class=\"button select-image\" >Select Image</button>';
                            if(!empty(\$value)):
                            echo ' <button type=\"button\" class=\"button remove-image\">Remove</button>';
                            else:
                            echo ' <button type=\"button\" class=\"button remove-image\" style=\"display:none;\">Remove</button>';
                            endif;
                            echo '</div>';

                            if(!empty(\$image_url)):
                            echo '<img class=\"image-preview\" src=\"' . esc_url(\$image_url) . '\" style=\"max-width:100px;display:block;margin-top:10px;margin-bottom:1rem;\" />';
                            else:
                            echo '<img class=\"image-preview\" src=\"\" style=\"max-width:100px;display:none;margin-top:10px;margin-bottom:1rem;\" />';
                            endif;
                            echo '</div>';
                            break;
                        default:
                            echo '<input type=\"text\" name=\"' . esc_attr(\$field['name']) . \"[\$index][\$key]\" . '\" value=\"' . esc_attr(\$value) . '\" style=\"width:100%;\" />';
                            break;
                    }

                    echo '</div>';
                }

                echo '<button type=\"button\" class=\"button remove-row\" style=\"margin-top:1rem;\">Remove</button>';
                echo '</div>';
            }

            echo '<button type=\"button\" class=\"button add-repeater-row\" style=\"margin-top:1rem;\">Add Row</button>';
            echo '</div>';
        }
    }
    ";

    eval($evalCode);
}
