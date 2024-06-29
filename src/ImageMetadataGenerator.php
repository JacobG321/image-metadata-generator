<?php

namespace JacobGruber\ImageMetadataGenerator;

/**
 * Main class
 */
class ImageMetadataGenerator
{
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'settings_init']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('add_meta_boxes', [__CLASS__, 'generate_metadata_meta_box']);
        add_action('admin_footer', [__CLASS__, 'add_dialog_box_html']);

        AjaxHandler::init();
    }


    /**
     * Add settings page to the admin menu.
     */
    public static function add_admin_menu()
    {
        add_options_page(
            'Image Metadata Generator',
            'Image Metadata Generator',
            'manage_options',
            'image_metadata_generator',
            [__CLASS__, 'options_page']
        );
    }

    /**
     *  Plugin settings.
     */
    public static function settings_init()
    {
        register_setting('imageMetadataGenerator', 'image_metadata_generator_settings');

        add_settings_section(
            'image_metadata_generator_section',
            __('API Settings', 'image-metadata-generator'),
            [__CLASS__, 'settings_section_callback'],
            'imageMetadataGenerator'
        );

        add_settings_field(
            'api_key',
            __('OpenAI API Key', 'image-metadata-generator'),
            [__CLASS__, 'api_key_render'],
            'imageMetadataGenerator',
            'image_metadata_generator_section'
        );
    }

    /**
     * API key field.
     */
    public static function api_key_render()
    {
        $options = get_option('image_metadata_generator_settings');
        $api_key = isset($options['api_key']) ? esc_attr($options['api_key']) : '';
?>
        <input type='text' name='image_metadata_generator_settings[api_key]' value='<?php echo $api_key; ?>'>
    <?php
    }

    /**
     * Settings section description.
     */
    public static function settings_section_callback()
    {
        echo __('Enter your OpenAI API key here.', 'image-metadata-generator');
    }

    public static function options_page()
    {
    ?>
        <form action='options.php' method='post'>
            <h2>Image Metadata Generator</h2>
            <?php
            settings_fields('imageMetadataGenerator');
            do_settings_sections('imageMetadataGenerator');
            submit_button();
            ?>
        </form>
        <?php
    }


    /**
     * Enqueue scripts and styles.
     */
    public static function enqueue_scripts($hook)
    {
        // Enqueue only on the media editing page
        $post_type = self::get_current_post_type();
        if ('post.php' !== $hook || $post_type !== 'attachment') {
            return;
        }

        global $post;

        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');

        wp_enqueue_script('image-metadata-generator-script', plugin_dir_url(__DIR__) . 'assets/js/image-metadata-generator.js', ['jquery', 'jquery-ui-dialog'], '1.0', true);

        wp_enqueue_style('image-metadata-generator-style', plugin_dir_url(__DIR__) . 'assets/css/image-metadata-generator.css');


        wp_localize_script('image-metadata-generator-script', 'imageMetadataGenerator', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('generate_metadata_nonce'),
            'attachment_id' => $post->ID,
        ]);
    }

    public static function generate_metadata_meta_box()
    {
        add_meta_box(
            'generate_metadata_meta_box',
            'Generate Metadata',
            [__CLASS__, 'render_meta_box_content'],
            'attachment',
            'side',
            'high'
        );
    }

    /**
     * Render metabox.
     */
    public static function render_meta_box_content($post)
    {
        echo '<button id="generate-metadata-button" class="button button-primary">Generate New Metadata</button>';
    }


    public static function get_current_post_type() {
	
        global $post, $typenow, $current_screen;
        
        if ($post && $post->post_type) return $post->post_type;
        
        elseif($typenow) return $typenow;
        
        elseif($current_screen && $current_screen->post_type) return $current_screen->post_type;
        
        elseif(isset($_REQUEST['post_type'])) return sanitize_key($_REQUEST['post_type']);
        
        return null;
        
    }


    /**
     * Add dialog box HTML to the footer.
     */
    public static function add_dialog_box_html()
    {
        $post_type = self::get_current_post_type();
        if ($post_type == 'attachment') {
        ?>
            <div id="generate-metadata-dialog" title="Generate New Metadata" style="display:none;">
                <p>Select the image metadata you want to generate new data for:</p>
                <form id="generate-metadata-form">
                    <!-- select all -->
                    <label>
                        <input type="checkbox" id="all-metadata" name="all-metadata"> Select All
                    </label><br>
                    <label>
                        <input type="checkbox" name="metadata[]" value="title"> Title
                    </label><br>
                    <label>
                        <input type="checkbox" name="metadata[]" value="alt"> Alt Text
                    </label><br>
                    <label>
                        <input type="checkbox" name="metadata[]" value="file-name"> File Name
                    </label><br>
                    <label>
                        <input type="checkbox" name="metadata[]" value="caption"> Caption
                    </label>
                </form>
                <div id="generate-metadata-results" style="display:none;"></div>
            </div>
        <?php
        }
    }
}