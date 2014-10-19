<?php
/*
 * Plugin Name: Post View Counter
 * Version: 1.0
 * Plugin URI: http://www.hughlashbrooke.com/
 * Description: A counter to track how many times your posts are viewed.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: post-view-counter
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-post-view-counter.php' );

/**
 * Returns the main instance of Post_View_Counter to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Post_View_Counter
 */
function Post_View_Counter () {
	$instance = Post_View_Counter::instance( __FILE__, '1.0.0' );
	return $instance;
}

Post_View_Counter();
