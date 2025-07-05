<?php

/*
Plugin Name: WP Watermark
Plugin URI: https://kevin-benabdelhak.fr/plugins/wp-watermark/
Description: WP Watermark est un plugin qui permet d'ajouter le nom de votre site en tant que filigrane sur vos images directement dans votre bibliothèque WordPress. 
Version: 1.3
Author: Kevin BENABDELHAK
Author URI: https://kevin-benabdelhak.fr/
Contributors: kevinbenabdelhak
*/

if (!defined('ABSPATH')) {
    exit; 
}




if ( !class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
    require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
}
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$monUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/kevinbenabdelhak/WP-Watermark/', 
    __FILE__,
    'wp-watermark' 
);
$monUpdateChecker->setBranch('main');







/* requête en ajax, ajout du water en js*/
require_once plugin_dir_path(__FILE__) . 'script/ajax.php';
/* récupérer l'url */
require_once plugin_dir_path(__FILE__) . 'script/url_image.php';
/* base 64 => img */
require_once plugin_dir_path(__FILE__) . 'script/save.php';

/* page d'option */
require_once plugin_dir_path(__FILE__) . 'options.php';
