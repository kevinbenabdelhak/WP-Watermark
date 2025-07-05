<?php 

if (!defined('ABSPATH')) {
    exit; 
}

function wp_watermark_sanitize_text($input) {
    return sanitize_text_field($input);
}

function wp_watermark_sanitize_font_size($input) {
    $size = intval($input);
    if ($size < 8) {
        $size = 8;
    } elseif ($size > 100) {
        $size = 100;
    }
    return $size;
}

function wp_watermark_sanitize_color($input) {
    if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $input)) {
        return $input;
    }
    return '#ffffff';
}

function wp_watermark_sanitize_opacity($input) {
    $opacity = floatval($input);
    if ($opacity < 0) {
        $opacity = 0;
    } elseif ($opacity > 1) {
        $opacity = 1;
    }
    return $opacity;
}

function wp_watermark_sanitize_position($input) {
    $valid = ['bottom-right', 'bottom-left', 'top-right', 'top-left'];
    if (in_array($input, $valid, true)) {
        return $input;
    }
    return 'bottom-right';
}

function wp_watermark_sanitize_logo($input) {
    return absint($input);
}

function wp_watermark_sanitize_type($input) {
    $valid = ['text', 'logo'];
    return in_array($input, $valid, true) ? $input : 'text';
}

function wp_watermark_sanitize_logo_size($input) {
    $size = intval($input);
    if ($size < 10) {
        $size = 10;
    } elseif ($size > 100) {
        $size = 100;
    }
    return $size;
}

function wp_watermark_sanitize_logo_opacity($input) {
    $opacity = floatval($input);
    if ($opacity < 0) {
        $opacity = 0;
    } elseif ($opacity > 1) {
        $opacity = 1;
    }
    return $opacity;
}

function wp_watermark_register_settings() {
    register_setting('wp_watermark_settings_group', 'wp_watermark_text', ['sanitize_callback' => 'wp_watermark_sanitize_text']);
    register_setting('wp_watermark_settings_group', 'wp_watermark_font_size', ['sanitize_callback' => 'wp_watermark_sanitize_font_size']);
    register_setting('wp_watermark_settings_group', 'wp_watermark_color', ['sanitize_callback' => 'wp_watermark_sanitize_color']);
    register_setting('wp_watermark_settings_group', 'wp_watermark_opacity', ['sanitize_callback' => 'wp_watermark_sanitize_opacity']);
    register_setting('wp_watermark_settings_group', 'wp_watermark_position', ['sanitize_callback' => 'wp_watermark_sanitize_position']);
    register_setting('wp_watermark_settings_group', 'wp_watermark_stroke_color', ['sanitize_callback' => 'wp_watermark_sanitize_color']);
    register_setting('wp_watermark_settings_group', 'wp_watermark_stroke_width', ['sanitize_callback' => 'wp_watermark_sanitize_font_size']);
    register_setting('wp_watermark_settings_group', 'wp_watermark_logo_id', ['sanitize_callback' => 'wp_watermark_sanitize_logo']);
    register_setting('wp_watermark_settings_group', 'wp_watermark_type', ['sanitize_callback' => 'wp_watermark_sanitize_type']);
    register_setting('wp_watermark_settings_group', 'wp_watermark_logo_size', ['sanitize_callback' => 'wp_watermark_sanitize_logo_size']);
    register_setting('wp_watermark_settings_group', 'wp_watermark_logo_opacity', ['sanitize_callback' => 'wp_watermark_sanitize_logo_opacity']);
}
add_action('admin_init', 'wp_watermark_register_settings');

function wp_watermark_add_options_page() {
    add_options_page('WP Watermark Options', 'WP Watermark', 'manage_options', 'wp-watermark-options', 'wp_watermark_options_page_html');
}
add_action('admin_menu', 'wp_watermark_add_options_page');

function wp_watermark_options_page_html() {
    if (!current_user_can('manage_options')) {
        wp_die('Accès refusé');
    }

    $text           = get_option('wp_watermark_text', get_bloginfo('name'));
    $font_size      = get_option('wp_watermark_font_size', '30');
    $color          = get_option('wp_watermark_color', '#ffffff');
    $opacity        = get_option('wp_watermark_opacity', '0.3');
    $position       = get_option('wp_watermark_position', 'bottom-right');
    $stroke_color   = get_option('wp_watermark_stroke_color', '#000000');
    $stroke_width   = get_option('wp_watermark_stroke_width', '1');
    $logo_id        = get_option('wp_watermark_logo_id');
    $watermark_type = get_option('wp_watermark_type', 'text');
    $logo_size      = get_option('wp_watermark_logo_size', 25);
    $logo_opacity   = get_option('wp_watermark_logo_opacity', 0.5);

    $positions = [
        'bottom-right' => 'Bas à droite',
        'bottom-left'  => 'Bas à gauche',
        'top-right'    => 'Haut à droite',
        'top-left'     => 'Haut à gauche',
    ];

    ?>
    <div class="wrap">
        <h1>WP Watermark - Options</h1>
        <form method="post" action="options.php">
            <?php 
            settings_fields('wp_watermark_settings_group'); 
            do_settings_sections('wp_watermark_settings_group'); 
            ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="wp_watermark_type">Type de filigrane</label></th>
                        <td>
                            <select name="wp_watermark_type" id="wp_watermark_type">
                                <option value="text" <?php selected($watermark_type, 'text'); ?>>Texte</option>
                                <option value="logo" <?php selected($watermark_type, 'logo'); ?>>Logo</option>
                            </select>
                        </td>
                    </tr>

                    <!-- Options texte -->
                    <tr id="text-options" <?php if ($watermark_type === 'logo') echo 'style="display:none;"'; ?>>
                        <th scope="row"><label for="wp_watermark_text">Texte du filigrane</label></th>
                        <td><input name="wp_watermark_text" type="text" id="wp_watermark_text" value="<?php echo esc_attr($text); ?>" class="regular-text"></td>
                    </tr>

                    <tr id="font-size-option" <?php if ($watermark_type === 'logo') echo 'style="display:none;"'; ?>>
                        <th scope="row"><label for="wp_watermark_font_size">Taille de la police (px)</label></th>
                        <td><input name="wp_watermark_font_size" type="number" id="wp_watermark_font_size" value="<?php echo esc_attr($font_size); ?>" min="8" max="100"></td>
                    </tr>

                    <tr id="opacity-option" <?php if ($watermark_type === 'logo') echo 'style="display:none;"'; ?>>
                        <th scope="row"><label for="wp_watermark_opacity">Opacité du texte (0 à 1)</label></th>
                        <td><input name="wp_watermark_opacity" type="number" step="0.05" min="0" max="1" id="wp_watermark_opacity" value="<?php echo esc_attr($opacity); ?>"></td>
                    </tr>

                    <tr id="color-option" <?php if ($watermark_type === 'logo') echo 'style="display:none;"'; ?>>
                        <th scope="row"><label for="wp_watermark_color">Couleur du texte</label></th>
                        <td><input name="wp_watermark_color" type="color" id="wp_watermark_color" value="<?php echo esc_attr($color); ?>" style="width:80px; height:40px;"></td>
                    </tr>

                    <tr id="stroke-color-option" <?php if ($watermark_type === 'logo') echo 'style="display:none;"'; ?>>
                        <th scope="row"><label for="wp_watermark_stroke_color">Couleur du contour</label></th>
                        <td><input name="wp_watermark_stroke_color" type="color" id="wp_watermark_stroke_color" value="<?php echo esc_attr($stroke_color); ?>" style="width:80px; height:40px;"></td>
                    </tr>

                    <tr id="stroke-width-option" <?php if ($watermark_type === 'logo') echo 'style="display:none;"'; ?>>
                        <th scope="row"><label for="wp_watermark_stroke_width">Taille du contour (px)</label></th>
                        <td><input name="wp_watermark_stroke_width" type="number" id="wp_watermark_stroke_width" value="<?php echo esc_attr($stroke_width); ?>" min="0" max="10"></td>
                    </tr>

                    <!-- Options logo -->
                    <tr id="logo-option" <?php if ($watermark_type === 'text') echo 'style="display:none;"'; ?>>
                        <th scope="row"><label for="wp_watermark_logo_id">Logo (image)</label></th>
                        <td>
                            <input name="wp_watermark_logo_id" type="hidden" id="wp_watermark_logo_id" value="<?php echo esc_attr($logo_id); ?>" />
                            <input type="button" class="button" value="Sélectionner un logo" id="select-logo" />
                            <div id="logo-preview" style="margin-top:10px;">
                                <?php
                                if ($logo_id) {
                                    echo wp_get_attachment_image($logo_id, 'thumbnail');
                                }
                                ?>
                            </div>
                        </td>
                    </tr>

                    <tr id="logo-size-option" <?php if ($watermark_type === 'text') echo 'style="display:none;"'; ?>>
                        <th scope="row"><label for="wp_watermark_logo_size">Taille du logo (%)</label></th>
                        <td>
                            <input name="wp_watermark_logo_size" type="number" id="wp_watermark_logo_size" value="<?php echo esc_attr($logo_size); ?>" min="10" max="100">%
                            <p class="description">Taille du logo en pourcentage de la largeur de l’image originale.</p>
                        </td>
                    </tr>

                    <tr id="logo-opacity-option" <?php if ($watermark_type === 'text') echo 'style="display:none;"'; ?>>
                        <th scope="row"><label for="wp_watermark_logo_opacity">Opacité du logo (0 à 1)</label></th>
                        <td>
                            <input name="wp_watermark_logo_opacity" type="number" step="0.05" min="0" max="1" id="wp_watermark_logo_opacity" value="<?php echo esc_attr($logo_opacity); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="wp_watermark_position">Position du filigrane</label></th>
                        <td>
                            <select name="wp_watermark_position" id="wp_watermark_position">
                                <?php foreach ($positions as $val => $label): ?>
                                    <option value="<?php echo esc_attr($val); ?>" <?php selected($position, $val); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('wp_watermark_type');
            const textOptions = document.querySelectorAll('#text-options, #font-size-option, #opacity-option, #color-option, #stroke-color-option, #stroke-width-option');
            const logoOptions = document.querySelectorAll('#logo-option, #logo-size-option, #logo-opacity-option');

            function toggleOptions() {
                if (typeSelect.value === 'text') {
                    textOptions.forEach(el => el.style.display = 'table-row');
                    logoOptions.forEach(el => el.style.display = 'none');
                } else {
                    textOptions.forEach(el => el.style.display = 'none');
                    logoOptions.forEach(el => el.style.display = 'table-row');
                }
            }
            toggleOptions();
            typeSelect.addEventListener('change', toggleOptions);

            let mediaUploader;
            document.getElementById('select-logo').addEventListener('click', function(e) {
                e.preventDefault();
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                mediaUploader = wp.media({
                    title: 'Choisir un logo',
                    button: {
                        text: 'Utiliser ce logo'
                    },
                    multiple: false
                });
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    document.getElementById('wp_watermark_logo_id').value = attachment.id;
                    document.getElementById('logo-preview').innerHTML = '<img src="' + attachment.sizes.thumbnail.url + '" style="max-width:100%; height:auto;">';
                });
                mediaUploader.open();
            });
        });
    </script>
    <?php
}