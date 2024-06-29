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

        $this->new_path = $path_info['dirname'] . "/" . $safe_filename . "." . $path_info['extension']; // Build out path to new file

        // Rename the file and update its location in WP
        if (rename($og_path, $this->new_path)) {
            error_log('File renamed successfully: ' . $this->new_path);
        } else {
            error_log('Failed to rename file: ' . $og_path);
        }

        update_attached_file($post_id, $this->new_path);

        // Get the old metadata
        $old_metadata = wp_get_attachment_metadata($post_id);
        error_log('old metadata: ' . print_r($old_metadata, true));

        // Rename the original sizes
        $this->rename_old_sizes($path_info, $old_metadata);

        // Regenerate the attachment metadata, including thumbnails
        $metadata = wp_generate_attachment_metadata($post_id, $this->new_path);
        wp_update_attachment_metadata($post_id, $metadata);
        error_log('new metadata: ' . print_r($metadata, true));

        // Delete the original file after renaming
        $this->delete_original_file($og_path);
    }

    /**
     * Rename old attachment files.
     *
     * @param array $path_info The path info of the original file.
     * @param array $metadata The attachment metadata.
     */
    private function rename_old_sizes($path_info, $metadata)
    {
        // Rename the different sizes of the original file
        if (!empty($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size => $size_info) {
                $old_size_path = $path_info['dirname'] . '/' . $size_info['file'];
                $new_size_path = $path_info['dirname'] . '/' . $this->new_filename . '-' . $size_info['width'] . 'x' . $size_info['height'] . '.' . $path_info['extension'];

                error_log('old size path: ' . $old_size_path);
                error_log('new size path: ' . $new_size_path);

                if (file_exists($old_size_path)) {
                    if (rename($old_size_path, $new_size_path)) {
                        error_log('Size file renamed successfully: ' . $new_size_path);
                    } else {
                        error_log('Failed to rename size file: ' . $old_size_path);
                    }
                } else {
                    error_log('Old size file does not exist: ' . $old_size_path);
                }
            }
        } else {
            error_log('No sizes found in metadata.');
        }
    }

    /**
     * Delete the original file after renaming.
     *
     * @param string $og_path The original file path.
     */
    private function delete_original_file($og_path)
    {
        if (file_exists($og_path)) {
            if (unlink($og_path)) {
                error_log('Original file deleted successfully: ' . $og_path);
            } else {
                error_log('Failed to delete original file: ' . $og_path);
            }
        } else {
            error_log('Original file does not exist: ' . $og_path);
        }
    }
}
