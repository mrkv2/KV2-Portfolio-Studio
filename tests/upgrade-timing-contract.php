<?php

$root   = dirname( __DIR__ );
$plugin = file_get_contents( $root . '/includes/class-kv2ps-plugin.php' );
$errors = array();

if ( false === strpos( $plugin, "add_action( 'init', array( \$this, 'maybe_upgrade' ), 1 );" ) ) {
	$errors[] = 'The upgrader must run on init after WordPress creates WP_Rewrite.';
}
if ( false !== strpos( $plugin, "add_action( 'plugins_loaded', array( \$this, 'maybe_upgrade' )" ) ) {
	$errors[] = 'The upgrader must not call permalink helpers during plugins_loaded.';
}
if ( false === strpos( $plugin, 'if ( ! $wp_rewrite instanceof WP_Rewrite )' ) ) {
	$errors[] = 'Published-page detection must guard the WordPress rewrite object.';
}

if ( $errors ) {
	fwrite( STDERR, implode( PHP_EOL, $errors ) . PHP_EOL );
	exit( 1 );
}

echo "Upgrade timing checks passed.\n";
