<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Post_Hit_Counter {

	/**
	 * The single instance of Post_Hit_Counter.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token = 'post_hit_counter';
		$this->_field = '_post_views';

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Increment post view count on single post page
		add_action( 'wp', array( $this, 'count_post_view' ) );

		// Add 'Views' column to posts admin list table
		add_filter( 'manage_post_posts_columns', array( $this, 'add_post_views_column' ) );
		add_action( 'manage_post_posts_custom_column', array( $this, 'display_post_views_column' ), 10, 2 );

		// Add views to post edit screen
		add_action( 'post_submitbox_misc_actions', array( $this, 'display_post_views_meta' ) );

		// Load admin CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	} // End __construct ()

	/**
	 * Count post view on single post page
	 * @return void
	 */
	public function count_post_view () {

		if( is_single() ) {
			global $post;
			if( isset( $post->ID ) && 'post' == $post->post_type ) {
				$this->increment_counter( $post->ID );
			}
		}
	}

	/**
	 * Increment post view counter
	 * @param  integer $post_id Post ID
	 * @return void
	 */
	public function increment_counter ( $post_id = 0 ) {

		if( ! $post_id ) {
			return;
		}

		$views = intval( get_post_meta( $post_id, $this->_field, true ) );

		$views_updated = $views + 1;

		update_post_meta( $post_id, $this->_field, $views_updated, $views );
	}

	/**
	 * Add 'Views' column to post list table
	 * @param  array $columns Default columns
	 * @return array  		  Updated columns
	 */
	public function add_post_views_column ( $columns = array() ) {

		$columns['views'] = __( 'Views', 'post-hit-counter' );

		return $columns;
	}

	/**
	 * Display content for 'Views' column in post list table
	 * @param  string  $column  Current column name
	 * @param  integer $post_id Current post ID
	 * @return void
	 */
	public function display_post_views_column ( $column = '', $post_id = 0 ) {

		if( 'post' != get_post_type( $post_id ) ) {
			return;
		}

		if( 'views' == $column ) {
			$views = intval( get_post_meta( $post_id, $this->_field, true ) );
			echo $views;
		}

	}

	/**
	 * DIsplay post views on post edit screen
	 * @return void
	 */
	public function display_post_views_meta () {
		global $post, $pagenow, $typenow;

		if( 'post.php' == $pagenow && 'post' == $typenow ) {

			$views = intval( get_post_meta( $post->ID, $this->_field, true ) );

			?>
			<div class="misc-pub-section misc-pub-post-views" id="post-views">
				<?php _e( 'Views:', 'post-hit-counter' ); ?>
				<strong><?php echo esc_html( $views ); ?></strong>
			</div>
			<?php
		}
	}

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		global $pagenow, $typenow;

		if( 'post.php' == $pagenow && 'post' == $typenow ) {
			wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
			wp_enqueue_style( $this->_token . '-admin' );
		}
	} // End admin_enqueue_styles ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'post-hit-counter', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'post-view-counter';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main Post_View_Counter Instance
	 *
	 * Ensures only one instance of Post_View_Counter is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Post_View_Counter()
	 * @return Main Post_View_Counter instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

}
