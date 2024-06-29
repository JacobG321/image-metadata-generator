<?php

namespace JacobGruber\ImageMetadataGenerator;

class AjaxHandler {
    /**
     * Initialize the AJAX handler.
     */
    public static function init() {
        add_action('wp_ajax_generate_metadata', [__CLASS__, 'generate_metadata']);
    }

    /**
     * Encode an image as a base64 string.
     *
     * @param string $image_path
     * @return string
     */
    private static function encode_image($image_path) {
        $image_data = file_get_contents($image_path);
        return base64_encode($image_data);
    }

    /**
     * Handle the AJAX request to generate metadata.
     */
    public static function generate_metadata() {
        
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

        if  (!empty($metadata)){
            

            $metadata = array_splice($metadata, 1);
            $metadata_comma_separated = implode(", ", $metadata);
            $metadata_comma_separated_dash_removed = str_replace("-", " ", $metadata_comma_separated);

            $text = "Please create a ";
            $text .= $metadata_comma_separated_dash_removed;
            $text .= " for this image file currently called '{$original_file_name}'.";
            $text .= " The new image file name should contain the original file type.";
            $text .= " Please make sure the ";
            $text .= $metadata_comma_separated;
            $text .= " are unique, concise, accurate, and descriptive.";
            $text .= " Our goal is to provide helpful and descriptive information to our website's users and search engine crawlers about the content in these images.";
            if (in_array("alt text", $metadata)){
                $text .= " Please make sure the alt text is at least 80 characters, but LESS than 100 characters total.";
            }
            $text .= " Please provide the ";
            $text .= $metadata_comma_separated_dash_removed;
            $text .= " in a JSON format with the following keys: ";
            $text .= $metadata_comma_separated;
            $text .= ". Here is the image:";

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
                            'text' => "Please create alt text, title, caption, and a file name for this image file currently called '{$original_file_name}'. The new image file name should contain the original file type. Please make sure the alt text, file name, caption, and title are unique, concise, accurate, and descriptive. Our goal is to provide helpful and descriptive information to our website's users and search engine crawlers about the content in these images. Please make sure the alt text is at least 80 characters, but LESS than 100 characters total. Please provide the alt text, title, and new image file name in a JSON format with the following keys: alt_text, title, file_name, and caption. Here is the image:",
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

        // $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        //     'headers' => $headers,
        //     'body' => json_encode($payload),
        //     'timeout' => 60,
        // ]);

        // if (is_wp_error($response)) {
        //     wp_send_json_error(['message' => 'Request failed: ' . $response->get_error_message()]);
        // }

        // $response_body = wp_remote_retrieve_body($response);
        // $response_code = wp_remote_retrieve_response_code($response);

        // if ($response_code != 200) {
        //     wp_send_json_error(['message' => 'API response error: ' . $response_body]);
        // }

        // $response_data = json_decode($response_body, true);
        // $generated_content = $response_data['choices'][0]['message']['content'];

        // // Strip JSON code block markers if present
        // $generated_content = trim($generated_content);
        // if (strpos($generated_content, '```json') === 0) {
        //     $generated_content = trim($generated_content, "```json");
        //     $generated_content = trim($generated_content, "```");
        // }

        // $generated_metadata = json_decode($generated_content, true);
        // error_log(print_r($generated_metadata, true));

        // wp_send_json_success(['message' => 'Metadata generated successfully.', 'metadata' => $generated_metadata]);
    }
}
