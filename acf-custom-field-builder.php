<?php
/*
Plugin Name: ACF Custom Field Builder
Plugin URI: https://github.com/rahulthomasdev/acf-custom-field-builder
Description: A plugin to build custom fields for Advanced Custom Fields.
License: GPL2
Version: 1.0
Author: Rahul Thomas
Text Domain: acf-custom-field-builder
Author URI: 
*/

if (! defined('ABSPATH')) exit;

define('ACF_FIELD_BUILDER_URL', plugin_dir_url(__FILE__));
define('ACF_FIELD_BUILDER_PATH', plugin_dir_path(__FILE__));
define('ACF_FIELD_BUILDER_VERSION', '1.0');

class ACF_Field_Builder
{

    private $option_name = 'acf_field_builder_groups';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_page']);
    }

    public function register_admin_page()
    {
        add_menu_page(
            'ACF Field Builder',
            'ACF Field Builder',
            'manage_options',
            'acf-field-builder',
            [$this, 'render_admin_page'],
            'dashicons-feedback',
            30
        );
    }

    public function get_groups()
    {
        return get_option($this->option_name, []);
    }

    public function save_groups($groups)
    {
        update_option($this->option_name, $groups);
    }

    public function render_admin_page()
    {
        $groups = $this->get_groups();

        // Handle actions (save, delete)
        if (isset($_POST['action']) && $_POST['action'] === 'save_group') {
            check_admin_referer('acf_field_builder_save');
            
            $groupName = sanitize_text_field($_POST['group_name']);
            $currentGrps = $this->get_groups();
            
            if (!empty($groupName) && !count(array_filter($currentGrps, function ($grp) use ($groupName) {
                return $grp['name'] === $groupName;
            }))) {
                $groups[$_POST['group_key']] = [
                    'name'   => $groupName,
                    'fields' => $_POST['fields'] ?? [],
                ];
                $this->save_groups($groups);
                echo '<div class="updated"><p>Group saved.</p></div>';
            } else {

                if(isset($_GET['edit']) && array_find($currentGrps, function ($grp) use ($groupName) {
                    return $grp['name'] === $groupName;
                })){
                    $groups[$_GET['edit']] = [
                        'name'   => $groupName,
                        'fields' => $_POST['fields'] ?? [],
                    ];
                    $this->save_groups($groups);
                    echo '<div class="updated"><p>Group updated.</p></div>';
                    
                }else{
                    echo '<div class="error"><p>Group name already exists. Please try again with a different name.</p></div>';
                }
            }
        }

        if (isset($_GET['delete'])) {
            unset($groups[$_GET['delete']]);
            $this->save_groups($groups);
            echo '<div class="updated"><p>Group deleted.</p></div>';
        }

?>
        <div class="wrap">
            <h1>ACF Field Builder</h1>

            <h2>Custom Field Groups</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Group Key</th>
                        <th>Group Name</th>
                        <th>Fields Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($groups)): ?>
                        <?php foreach ($groups as $key => $group): ?>
                            <tr>
                                <td><?php echo esc_html($key); ?></td>
                                <td><?php echo esc_html($group['name']); ?></td>
                                <td><?php echo count($group['fields']); ?></td>
                                <td>
                                    <a href="?page=acf-field-builder&edit=<?php echo esc_attr($key); ?>">Edit</a> |
                                    <a href="?page=acf-field-builder&delete=<?php echo esc_attr($key); ?>" onclick="return confirm('Delete this group?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No groups created yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <p><a class="button button-primary" href="?page=acf-field-builder&new=1">+ Create New Group</a></p>

            <?php
            // Edit or New Form
            if (isset($_GET['edit']) || isset($_GET['new'])) {
                $group_key = $_GET['edit'] ?? uniqid('group_');
                $group     = $groups[$group_key] ?? ['name' => '', 'fields' => []];
            ?>
                <h2><?php echo isset($_GET['edit']) ? 'Edit Group' : 'New Group'; ?></h2>
                <form method="post" class="acf-field-builder-form">
                    <?php wp_nonce_field('acf_field_builder_save'); ?>
                    <input type="hidden" name="action" value="save_group">
                    <input type="hidden" name="group_key" value="<?php echo esc_attr($group_key); ?>">

                    <table class="form-table">
                        <tr>
                            <th><label for="group_name">Group Name</label></th>
                            <td><input type="text" name="group_name" id="group_name" value="<?php echo esc_attr($group['name']); ?>" required></td>
                        </tr>
                    </table>

                    <h3>Fields</h3>
                    <div id="fields-wrapper">
                        <?php
                        if (!empty($group['fields'])) {
                            foreach ($group['fields'] as $i => $field) {
                                $this->render_field_row($i, $field);
                            }
                        }
                        ?>
                    </div>

                    <div class="acf-field-builder-actions">
                        <button type="button" class="button" id="add-field">+ Add Field</button>
                        <input type="submit" class="button button-primary" value="Save Group">
                    </div>
                </form>

                <script>
                    (function($) {
                        let i = <?php echo count($group['fields']); ?>;
                        $('#add-field').on('click', function() {
                            let html = `
                        <div class="field-row">
                            <label>Label: <input type="text" name="fields[` + i + `][label]" required></label>
                            <label>Type: 
                                <select name="fields[` + i + `][type]">
                                    <option value="text">Text</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="image">Image</option>
                                    <option value="wysiwyg">WYSIWYG</option>
                                </select>
                            </label>
                            <button type="button" class="remove-field button">Remove</button>
                        </div>`;
                            $('#fields-wrapper').append(html);
                            i++;
                        });

                        $(document).on('click', '.remove-field', function() {
                            $(this).parent().remove();
                        });
                    })(jQuery);
                </script>
            <?php
            }
            ?>
        </div>
    <?php
    }

    private function render_field_row($i, $field)
    {
    ?>
        <div class="field-row">
            <label>Label:
                <input type="text" name="fields[<?php echo $i; ?>][label]" value="<?php echo esc_attr($field['label']); ?>" required>
            </label>
            <label>Type:
                <select name="fields[<?php echo $i; ?>][type]">
                    <option value="text" <?php selected($field['type'], 'text'); ?>>Text</option>
                    <option value="textarea" <?php selected($field['type'], 'textarea'); ?>>Textarea</option>
                    <option value="image" <?php selected($field['type'], 'image'); ?>>Image</option>
                    <option value="wysiwyg" <?php selected($field['type'], 'wysiwyg'); ?>>WYSIWYG</option>
                </select>
            </label>
            <button type="button" class="remove-field button">Remove</button>
        </div>
<?php
    }
}

new ACF_Field_Builder();

include_once __DIR__ . '/init-fields.php';
