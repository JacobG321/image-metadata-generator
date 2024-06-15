<?php

namespace JacobGruber\ImageMetadataGenerator;

/**
 * Main class for the Image Metadata Generator plugin.
 */
class ImageMetadataGenerator {
    /**
     * Initialize the plugin.
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'settings_init']);
    }

    /**
     * Add settings page to the admin menu.
     */
    public static function add_admin_menu() {
        add_options_page(
            'Image Metadata Generator',
            'Image Metadata Generator',
            'manage_options',
            'image_metadata_generator',
            [__CLASS__, 'options_page']
        );
    }

    /**
     * Initialize plugin settings.
     */
    public static function settings_init() {
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
     * Render the API key input field.
     */
    public static function api_key_render() {
        $options = get_option('image_metadata_generator_settings');
        $api_key = isset($options['api_key']) ? esc_attr($options['api_key']) : '';
        ?>
        <input type='text' name='image_metadata_generator_settings[api_key]' value='<?php echo $api_key; ?>'>
        <?php
    }

    /**
     * Render the settings section description.
     */
    public static function settings_section_callback() {
        echo __('Enter your OpenAI API key here.', 'image-metadata-generator');
    }

    /**
     * Render the options page.
     */
    public static function options_page() {
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
}
