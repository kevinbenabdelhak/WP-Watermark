<?php

if (!defined('ABSPATH')) {
    exit;
}

function watermark_enqueue_scripts($hook) {
    if ($hook !== 'upload.php') {
        return;
    }

    wp_enqueue_script('jquery');

    $text          = get_option('wp_watermark_text', get_bloginfo('name'));
    $font_size     = get_option('wp_watermark_font_size', '30');
    $color         = get_option('wp_watermark_color', '#ffffff');
    $opacity       = get_option('wp_watermark_opacity', '0.3');
    $position      = get_option('wp_watermark_position', 'bottom-right');
    $stroke_color  = get_option('wp_watermark_stroke_color', '#000000');
    $stroke_width  = get_option('wp_watermark_stroke_width', '1');
    $logo_id       = get_option('wp_watermark_logo_id');
    $watermark_type= get_option('wp_watermark_type', 'text');
    $logo_size     = get_option('wp_watermark_logo_size', 25);
    $logo_opacity  = get_option('wp_watermark_logo_opacity', 0.5);

    function hex_to_rgba($hex, $alpha = 0.3) {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return "rgba($r, $g, $b, $alpha)";
    }

    $color_rgba = hex_to_rgba($color, floatval($opacity));
    $stroke_color_hex = $stroke_color;
    $stroke_width_px = intval($stroke_width);

    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            if ($("select[name=\'action\'] option[value=\'add_text_watermark\']").length === 0) {
                $("select[name=\'action\'], select[name=\'action2\']").append(\'<option value="add_text_watermark">Ajouter un watermark</option>\');
            }
            $(document).on("click", "#doaction, #doaction2", function(e) {
                var action = $("select[name=\'action\']").val() !== "-1" ? $("select[name=\'action\']").val() : $("select[name=\'action2\']").val();
                if (action !== "add_text_watermark") return;
                e.preventDefault();

                var attachment_ids = [];
                $("tbody th.check-column input[type=\'checkbox\']:checked").each(function() {
                    attachment_ids.push($(this).val());
                });

                if (attachment_ids.length === 0) {
                    alert("Aucune image sélectionnée");
                    return;
                }

                $("#bulk-action-loader").remove();
                $("#doaction, #doaction2").after(\'<div id="bulk-action-loader"><span class="spinner is-active" style="margin-left: 10px;"></span> <span id="conversion-progress">0 / \' + attachment_ids.length + \' traitées</span></div>\');

                var processedCount = 0;
                var failedCount = 0;
                var pos = watermarked_ajax.position;
                var padding = 10;

                function processNext(index) {
                    if (index >= attachment_ids.length) {
                        $("#bulk-action-loader").remove();
                        var message = processedCount + " image(s) traitées avec succès.";
                        if (failedCount > 0) {
                            message += " " + failedCount + " échec(s).";
                        }
                        $("<div class=\'notice notice-success is-dismissible\'><p>" + message + "</p></div>").insertAfter(".wp-header-end");
                        location.reload();
                        return;
                    }
                    var attachmentId = attachment_ids[index];
                    $.ajax({
                        url: watermarked_ajax.ajax_url,
                        method: "POST",
                        data: {
                            action: "get_image_url",
                            nonce: watermarked_ajax.nonce,
                            attachment_id: attachmentId
                        },
                        success: function(response) {
                            if (response.success) {
                                var img = new Image();
                                img.crossOrigin = "anonymous";
                                img.src = response.data.url;
                                img.onload = function() {
                                    var canvas = document.createElement("canvas");
                                    var ctx = canvas.getContext("2d");
                                    canvas.width = img.width;
                                    canvas.height = img.height;

                                    ctx.drawImage(img, 0, 0);

                                    if (watermarked_ajax.type === "text") {
                                        ctx.font = watermarked_ajax.font_size + "px Arial";
                                        ctx.fillStyle = watermarked_ajax.color;
                                        ctx.strokeStyle = watermarked_ajax.stroke_color;
                                        ctx.lineWidth = watermarked_ajax.stroke_width;

                                        var x, y;
                                        switch(pos) {
                                            case "top-left":
                                                ctx.textAlign = "left";
                                                ctx.textBaseline = "top";
                                                x = padding;
                                                y = padding;
                                                break;
                                            case "top-right":
                                                ctx.textAlign = "right";
                                                ctx.textBaseline = "top";
                                                x = canvas.width - padding;
                                                y = padding;
                                                break;
                                            case "bottom-left":
                                                ctx.textAlign = "left";
                                                ctx.textBaseline = "bottom";
                                                x = padding;
                                                y = canvas.height - padding;
                                                break;
                                            case "bottom-right":
                                            default:
                                                ctx.textAlign = "right";
                                                ctx.textBaseline = "bottom";
                                                x = canvas.width - padding;
                                                y = canvas.height - padding;
                                        }

                                        if (watermarked_ajax.stroke_width > 0) {
                                            ctx.strokeText(watermarked_ajax.text, x, y);
                                        }
                                        ctx.fillText(watermarked_ajax.text, x, y);
                                        processAndSave();
                                    } else if (watermarked_ajax.type === "logo") {
                                        var logo = new Image();
                                        logo.crossOrigin = "anonymous";
                                        logo.src = watermarked_ajax.logo_url;

                                        logo.onload = function() {
                                            var logoWidth = img.width * (watermarked_ajax.logo_size / 100);
                                            var logoHeight = logo.height * (logoWidth / logo.width);

                                            var logoX, logoY;
                                            switch(pos) {
                                                case "top-left":
                                                    logoX = padding;
                                                    logoY = padding;
                                                    break;
                                                case "top-right":
                                                    logoX = canvas.width - logoWidth - padding;
                                                    logoY = padding;
                                                    break;
                                                case "bottom-left":
                                                    logoX = padding;
                                                    logoY = canvas.height - logoHeight - padding;
                                                    break;
                                                case "bottom-right":
                                                default:
                                                    logoX = canvas.width - logoWidth - padding;
                                                    logoY = canvas.height - logoHeight - padding;
                                            }

                                            ctx.globalAlpha = watermarked_ajax.logo_opacity;
                                            ctx.drawImage(logo, logoX, logoY, logoWidth, logoHeight);
                                            ctx.globalAlpha = 1.0;

                                            processAndSave();
                                        };

                                        logo.onerror = function() {
                                            console.error("Erreur de chargement du logo");
                                            processAndSave();
                                        };
                                    } else {
                                        processAndSave();
                                    }

                                    function processAndSave() {
                                        var dataURL = canvas.toDataURL("image/png");
                                        saveImage(dataURL, attachmentId);
                                        processedCount++;
                                        $("#conversion-progress").text(processedCount + " / " + attachment_ids.length + " traitées");
                                        processNext(index+1);
                                    }
                                };

                                img.onerror = function() {
                                    failedCount++;
                                    console.error("Erreur de chargement de l\'image ID " + attachmentId);
                                    $("#conversion-progress").text(processedCount + " / " + attachment_ids.length + " traitées");
                                    processNext(index+1);
                                };
                            } else {
                                failedCount++;
                                console.error("Erreur lors de la récupération de l\'URL pour l\'image ID " + attachmentId);
                                $("#conversion-progress").text(processedCount + " / " + attachment_ids.length + " traitées");
                                processNext(index+1);
                            }
                        },
                        error: function() {
                            failedCount++;
                            console.error("Erreur AJAX pour l\'image ID " + attachmentId);
                            $("#conversion-progress").text(processedCount + " / " + attachment_ids.length + " traitées");
                            processNext(index+1);
                        }
                    });
                }

                function saveImage(dataURL, attachmentId) {
                    $.ajax({
                        url: watermarked_ajax.ajax_url,
                        method: "POST",
                        data: {
                            action: "save_watermarked_image",
                            nonce: watermarked_ajax.nonce,
                            attachment_id: attachmentId,
                            image: dataURL,
                            is_logo: (watermarked_ajax.type === "logo"),
                            logo_id: watermarked_ajax.logo_id
                        },
                        success: function(response) {
                            if (!response.success) {
                                console.error("Erreur lors de l\'enregistrement de l\'image ID " + attachmentId + ": " + response.data);
                            }
                        }
                    });
                }

                processNext(0);
            });
        });
    ');

    wp_localize_script('jquery', 'watermarked_ajax', [
        'ajax_url'      => admin_url('admin-ajax.php'),
        'nonce'         => wp_create_nonce('watermark_nonce'),
        'text'          => $text,
        'font_size'     => $font_size,
        'color'         => $color_rgba,
        'position'      => $position,
        'stroke_color'  => $stroke_color_hex,
        'stroke_width'  => $stroke_width_px,
        'type'          => $watermark_type,
        'logo_id'       => $logo_id,
        'logo_url'      => $logo_id ? wp_get_attachment_url($logo_id) : '',
        'logo_size'     => $logo_size,
        'logo_opacity'  => floatval($logo_opacity),
    ]);
}
add_action('admin_enqueue_scripts', 'watermark_enqueue_scripts');