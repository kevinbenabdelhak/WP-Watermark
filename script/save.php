<?php

if (!defined('ABSPATH')) {
    exit; 
}

function save_watermarked_image() {
    check_ajax_referer('watermark_nonce', 'nonce');

    $attachment_id = intval($_POST['attachment_id']);
    $image_data = $_POST['image'];

    $image_data = str_replace('data:image/png;base64,', '', $image_data);
    $image_data = str_replace(' ', '+', $image_data);

    $file_path = get_attached_file($attachment_id);

    if (file_put_contents($file_path, base64_decode($image_data))) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attach_data);

        return wp_send_json_success();
    }

    return wp_send_json_error('Erreur lors de l\'enregistrement de l\'image watermarkée.');
}
add_action('wp_ajax_save_watermarked_image', 'save_watermarked_image');