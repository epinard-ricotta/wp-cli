<?php

// Utilities that depend on WordPress code.

namespace WP_CLI\Utils;

function wp_not_installed() {
	if ( !is_blog_installed() && !defined( 'WP_INSTALLING' ) ) {
		\WP_CLI::error(
			"The site you have requested is not installed.\n" .
			'Run `wp core install`.' );
	}
}

function wp_debug_mode() {
	if ( \WP_CLI::get_config( 'debug' ) ) {
		error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );
		ini_set( 'display_errors', true );
	} else {
		\wp_debug_mode();
	}
}

function replace_wp_die_handler() {
	\remove_filter( 'wp_die_handler', '_default_wp_die_handler' );
	\add_filter( 'wp_die_handler', function() { return __NAMESPACE__ . '\\' . 'wp_die_handler'; } );
}

function wp_die_handler( $message ) {
	if ( is_wp_error( $message ) ) {
		$message = $message->get_error_message();
	}

	if ( preg_match( '|^\<h1>(.+?)</h1>|', $message, $matches ) ) {
		$message = $matches[1];
	}

	$message = html_entity_decode( $message );

	\WP_CLI::error( $message );
}

function maybe_require( $since, $path ) {
	global $wp_version;

	if ( version_compare( $wp_version, $since, '>=' ) )
		require $path;
}

// Handle --user parameter
function set_user( $assoc_args ) {
	if ( !isset( $assoc_args['user'] ) )
		return;

	$user = $assoc_args['user'];

	if ( is_numeric( $user ) ) {
		$user_id = (int) $user;
	} else {
		$user_id = (int) username_exists( $user );
	}

	if ( !$user_id || !wp_set_current_user( $user_id ) ) {
		\WP_CLI::error( sprintf( 'Could not get a user_id for this user: %s', var_export( $user, true ) ) );
	}
}

function set_wp_query() {
	if ( isset( $GLOBALS['wp_query'] ) && isset( $GLOBALS['wp'] ) ) {
		$GLOBALS['wp']->parse_request();
		$GLOBALS['wp_query']->query($GLOBALS['wp']->query_vars);
	}
}

function get_upgrader( $class ) {
	if ( !class_exists( '\WP_Upgrader' ) )
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	return new $class( new \WP_CLI\UpgraderSkin );
}

