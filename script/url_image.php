<?php 


if (!defined('ABSPATH')) {
    exit; 
}

function get_image_url() {
    check_ajax_referer('watermark_nonce', 'nonce');

    $attachment_id = intval($_POST['attachment_id']);
    $image_url = wp_get_attachment_url($attachment_id);

    if ($image_url) {
        return wp_send_json_success(['url' => $image_url]);
    }

    return wp_send_json_error('Erreur lors de la récupération de l\'URL de l\'image.');
}
add_action('wp_ajax_get_image_url', 'get_image_url');