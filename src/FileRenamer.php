<?php

namespace JacobGruber\ImageMetadataGenerator;

class FileRenamer
{
    public $new_filename;

    private $new_path;

    function __construct()
    {
        add_action("add_attachment", array($this, "rename_attachment"), 10, 1);
    }

    /**
     * Rename the attachment file.
     *
     * @param int $post_id The WordPress post ID for the attachment.
     */
    function rename_attachment($post_id)
    {
        
        $og_path = get_attached_file($post_id);
        error_log('og path: ' . $og_path);
        $path_info = pathinfo($og_path);
        error_log('path info: ' . print_r($path_info, true));
        
        $safe_filename = wp_unique_filename($path_info['dirname'], $this->new_filename);
        error_log('safe filename: ' . $safe_filename);
        
        $this->new_path = $path_info['dirname'] . "/" . $safe_filename . "." . $path_info['extension'];// Build out path to new file

        // Rename the file and update its location in WP
        rename($og_path, $this->new_path);
        error_log('new path: ' . $this->new_path);
        update_attached_file($post_id, $this->new_path);

        // Register filter to update metadata.
        add_filter('wp_update_attachment_metadata', array($this, 'custom_update_attachment_metadata'), 10, 2);
    }

    /**
     * Update attachment metadata with new file name.
     *
     * @param array $data The attachment metadata.
     * @param int $post_id The WordPress post ID for the attachment.
     * @return array Updated attachment metadata.
     */
    function custom_update_attachment_metadata($data, $post_id)
    {
        return wp_generate_attachment_metadata($post_id, $this->new_path);
    }
}
