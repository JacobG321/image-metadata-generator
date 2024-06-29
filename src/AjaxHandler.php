<?php

namespace JacobGruber\ImageMetadataGenerator;

class AjaxHandler
{
    /**
     * Initialize the AJAX handler.
     */
    public static function init()
    {
        add_action('wp_ajax_generate_metadata', [__CLASS__, 'generate_metadata']);
        add_action('wp_ajax_update_metadata', [__CLASS__, 'update_metadata']);
    }


    /**
     * Encode an image as a base64 string.
     *
     * @param string $image_path
     * @return string
     */
    private static function encode_image($image_path)
    {
        $image_data = file_get_contents($image_path);
        return base64_encode($image_data);
    }

    /**
     * Handle the AJAX request to generate metadata.
     */
    public static function generate_metadata()
    {

        check_ajax_referer('generate_metadata_nonce', 'security');


        $metadata = isset($_POST['metadata']) ? $_POST['metadata'] : []; // Get selected metadata from the request

        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;

        if (empty($metadata) || !$attachment_id) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }

        // API key
        $options = get_option('image_metadata_generator_settings');
        $api_key = isset($options['api_key']) ? esc_attr($options['api_key']) : '';

        if (!$api_key) {
            wp_send_json_error(['message' => 'API key is not set.']);
        }


        $image_path = get_attached_file($attachment_id); // Get attachment file path
        $base64_image = self::encode_image($image_path);
        $file_extension = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
        $mime_type = $file_extension === 'jpg' ? 'image/jpeg' : 'image/' . $file_extension;
        $original_file_name = basename($image_path);

        if (!empty($metadata)) {

            $metadata_count = count($metadata);

            if ($metadata_count > 4){
                $metadata = array_splice($metadata, 1);
            }

            $verb_plurality = $metadata_count > 1 ? 'are' : 'is';

            $metadata_comma_separated = implode(", ", $metadata);

            // add 'and' before the last metadata
            $last_comma_position = strrpos($metadata_comma_separated, ',');
            if ($last_comma_position !== false) {
                $metadata_comma_separated = substr_replace($metadata_comma_separated, ' and', $last_comma_position, 1);
            }

            $metadata_comma_separated_dash_removed = str_replace("-", " ", $metadata_comma_separated);

            $text = "Please create a {$metadata_comma_separated_dash_removed} for this image file currently called '{$original_file_name}'.";

            if (in_array("file-name", $metadata)) {
                $text .= " The new file name should be the same file type as the current file.";
            }

            $text .= " Please make sure the {$metadata_comma_separated_dash_removed} {$verb_plurality} unique, concise, accurate, and descriptive. Our goal is to provide helpful and descriptive information to our website's users and search engine crawlers about the content in these images.";

            if (in_array("alt text", $metadata)) {
                $text .= " Please make sure the alt text is at least 80 characters, but LESS than 100 characters total.";
            }

            $text .= " Please provide the {$metadata_comma_separated_dash_removed} in a JSON format with the following keys: {$metadata_comma_separated}. Here is the image:";

            error_log($text);
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ];

        $payload = [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $text,
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mime_type};base64,{$base64_image}",
                                'detail' => 'high',
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 300,
        ];

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => $headers,
            'body' => json_encode($payload),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Request failed: ' . $response->get_error_message()]);
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code != 200) {
            wp_send_json_error(['message' => 'API response error: ' . $response_body]);
        }

        $response_data = json_decode($response_body, true);
        $generated_content = $response_data['choices'][0]['message']['content'];

        // Strip JSON code block markers if present
        $generated_content = trim($generated_content);
        if (strpos($generated_content, '```json') === 0) {
            $generated_content = trim($generated_content, "```json");
            $generated_content = trim($generated_content, "```");
        }

        $generated_metadata = json_decode($generated_content, true);
        error_log(print_r($generated_metadata, true));

        // $generated_metadata = [
        //     'title' => 'Generated Title',
        //     'description' => 'Generated Description',
        //     'alt' => 'Generated Alt Text',
        //     'caption' => 'Generated Caption',
        //     'file-name' => 'Generated File Name',
        // ];

        wp_send_json_success(['message' => 'Metadata generated successfully.', 'metadata' => $generated_metadata]);
    }



    // handle update metadata request
    public static function update_metadata()
    {
        check_ajax_referer('update_metadata_nonce', 'security');

        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;

        if (!$attachment_id) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }


        // retrieve new metadata from request
        $metadata = isset($_POST['metadata']) ? $_POST['metadata'] : [];
        error_log(print_r($metadata, true));

        if (isset($metadata['title']) && !empty($metadata['title'])) {
            $title = $metadata['title'];
            $title = sanitize_text_field($title);

            
            $post_data = array(
                'ID'         => $attachment_id,
                'post_title' => $title,
            );

            wp_update_post($post_data);
            error_log('new title: ' . $title);
        }

        if (isset($metadata['alt']) && !empty($metadata['alt'])) {
            $alt = $metadata['alt'];
            $alt = sanitize_text_field($alt);
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
            error_log('new alt text: ' . $alt);
        }

        if (isset($metadata['caption']) && !empty($metadata['caption'])) {
            $caption = $metadata['caption'];
            $caption = sanitize_text_field($caption);
            $post_data = array(
                'ID'         => $attachment_id,
                'post_excerpt' => $caption,
            );
            wp_update_post($post_data);
            error_log('new caption: ' . $caption);
        }
        

        if (isset($metadata['file_name']) && !empty($metadata['file_name'])) {
            $file_name = $metadata['file_name'];
            $file_name = sanitize_text_field($file_name);
            
            $file_name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file_name); // strip file extension
            
            $file_renamer = new FileRenamer();
            $file_renamer->new_filename = $file_name;
            $file_renamer->rename_attachment($attachment_id);
            
            error_log('file name: ' . $file_name);
        }


        wp_send_json_success(['message' => 'Metadata updated successfully.']);
    }
}
