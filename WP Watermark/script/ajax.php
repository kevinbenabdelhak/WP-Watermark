<?php 

if (!defined('ABSPATH')) {
    exit; 
}

function watermark_enqueue_scripts($hook) {
    if ($hook !== 'upload.php') {
        return;
    }

    wp_enqueue_script('jquery');

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
            $("#doaction, #doaction2").after("<div id=\'bulk-action-loader\'><span class=\'spinner is-active\' style=\'margin-left: 10px;\'></span> <span id=\'conversion-progress\'>0 / " + attachment_ids.length + " traitées</span></div>");

            var processedCount = 0;
            var failedCount = 0;

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

                $.ajax({
                    url: watermarked_ajax.ajax_url,
                    method: "POST",
                    data: {
                        action: "get_image_url",
                        nonce: watermarked_ajax.nonce,
                        attachment_id: attachment_ids[index]
                    },
                    success: function(response) {
                        if (response.success) {
                            var img = new Image();
                            img.src = response.data.url;
                            img.onload = function() {
                                var canvas = document.createElement("canvas");
                                var ctx = canvas.getContext("2d");
                                canvas.width = img.width;
                                canvas.height = img.height;

                                ctx.drawImage(img, 0, 0);
                                ctx.font = "30px Arial";
                                ctx.fillStyle = "rgba(255, 255, 255, 0.3)";
                                ctx.textAlign = "right";
                                ctx.textBaseline = "bottom";
                                ctx.strokeStyle = "black";
                                ctx.lineWidth = 1;
                                ctx.strokeText("' . get_bloginfo('name') . '", img.width - 10, img.height - 10);
                                ctx.fillText("' . get_bloginfo('name') . '", img.width - 10, img.height - 10);

                                var dataURL = canvas.toDataURL("image/png");
                                saveImage(dataURL, attachment_ids[index]);

                                processedCount++;
                                $("#conversion-progress").text(processedCount + " / " + attachment_ids.length + " traitées");
                                processNext(index + 1);
                            };
                        } else {
                            failedCount++;
                            console.error("Erreur de traitement pour l\'image ID " + attachment_ids[index] + ": " + response.data);
                            $("#conversion-progress").text(processedCount + " / " + attachment_ids.length + " traitées");
                            processNext(index + 1);
                        }
                    },
                    error: function() {
                        failedCount++;
                        console.error("Erreur de traitement pour l\'image ID " + attachment_ids[index]);
                        $("#conversion-progress").text(processedCount + " / " + attachment_ids.length + " traitées");
                        processNext(index + 1);
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
                        image: dataURL
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
    });');

    wp_localize_script('jquery', 'watermarked_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('watermark_nonce')
    ]);
}
add_action('admin_enqueue_scripts', 'watermark_enqueue_scripts');