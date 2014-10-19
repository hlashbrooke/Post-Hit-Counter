<?php
/*
 * Plugin Name: Post Hit Counter
 * Version: 1.0
 * Plugin URI: https://wordpress.org/plugins/post-hit-counter/
 * Description: A counter to track how many times your posts are viewed.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: post-hit-counter
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-post-hit-counter.php' );

/**
 * Returns the main instance of Post_Hit_Counter to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Post_Hit_Counter
 */
function Post_Hit_Counter () {
	$instance = Post_Hit_Counter::instance( __FILE__, '1.0.0' );
	return $instance;
}

Post_Hit_Counter();
