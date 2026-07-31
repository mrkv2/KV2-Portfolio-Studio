<?php
/**
 * Plugin Name: KV2 Portfolio Studio
 * Description: Portfolio de réalisations SEO-first, import WP Portfolio et flux de métadonnées d’images avec ChatGPT.
 * Version: 1.1.1
 * Author: KV2 – Agence Digitale 360°
 * Text Domain: kv2-portfolio-studio
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * License: Proprietary
 */

defined( 'ABSPATH' ) || exit;

define( 'KV2PS_VERSION', '1.1.1' );
define( 'KV2PS_FILE', __FILE__ );
define( 'KV2PS_DIR', plugin_dir_path( __FILE__ ) );
define( 'KV2PS_URL', plugin_dir_url( __FILE__ ) );

require_once KV2PS_DIR . 'includes/class-kv2ps-post-types.php';
require_once KV2PS_DIR . 'includes/class-kv2ps-image-metadata.php';
require_once KV2PS_DIR . 'includes/class-kv2ps-importer.php';
require_once KV2PS_DIR . 'includes/class-kv2ps-schema.php';
require_once KV2PS_DIR . 'includes/class-kv2ps-completeness.php';
require_once KV2PS_DIR . 'includes/class-kv2ps-project-package.php';
require_once KV2PS_DIR . 'includes/class-kv2ps-redirects.php';
require_once KV2PS_DIR . 'includes/class-kv2ps-admin.php';
require_once KV2PS_DIR . 'includes/class-kv2ps-plugin.php';

register_activation_hook( __FILE__, array( 'KV2PS_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'KV2PS_Plugin', 'deactivate' ) );

KV2PS_Plugin::instance();
