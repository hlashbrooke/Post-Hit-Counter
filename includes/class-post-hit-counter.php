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
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.1.0
	 */
	public $settings = null;

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
	 * The active post types for this plugin.
	 * @var     string
	 * @access  public
	 * @since   1.1.0
	 */
	public $active_types = false;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * The blocked user roles for this plugin.
	 * @var     string
	 * @access  public
	 * @since   1.1.0
	 */
	public $blocked_roles = array();

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
		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$this->active_types = apply_filters( $this->_token . '_active_posttypes', get_option( 'phc_active_posttypes', false ) );
		$this->blocked_roles = apply_filters( $this->_token . '_blocked_roles', get_option( 'phc_blocked_roles', array() ) );

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Increment post view count on single post page
		add_action( 'wp', array( $this, 'count_post_view' ) );

		// Add 'Views' column to posts admin list table
		add_filter( 'manage_posts_columns', array( $this, 'add_post_views_column' ) );
		add_filter( 'manage_pages_columns', array( $this, 'add_post_views_column' ) );

		// Display data in 'Views' column
		add_action( 'manage_posts_custom_column', array( $this, 'display_post_views_column' ), 10, 2 );
		add_action( 'manage_pages_custom_column', array( $this, 'display_post_views_column' ), 10, 2 );

		// Add views to post edit screen
		add_action( 'post_submitbox_misc_actions', array( $this, 'display_post_views_meta' ) );

		// Add views to the admin bar on the frontend
		add_action( 'admin_bar_menu', array( $this, 'display_post_views_admin_bar' ), 999 );

		// Reste hit count for single post in admin
		add_action( 'wp_ajax_reset_hit_count', array( $this, 'reset_hit_count' ) );

		// Load frontend CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );

		// Load admin CSS & JS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );

		// Load widgets
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widgets' ) );

		// Add shortcodes
		add_shortcode( 'hit_count', array( $this, 'hit_count_shortcode' ) );

		if( ! $this->active_types ) {
			$post_types = get_post_types();
		} else {
			$post_types = $this->active_types;
		}

		foreach( $post_types as $type ) {
			add_filter( 'manage_edit-' . $type . '_sortable_columns', array( $this, 'sortable_columns' ) );
		}
		add_action( 'pre_get_posts', array( $this, 'sort_column' ) );

		// Load API for generic admin functions
		if( is_admin() ) {
			$this->admin = new Post_Hit_Counter_Admin_API();
		}

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

	} // End __construct ()

	/**
	 * Count post view on single post page
	 * @return void
	 */
	public function count_post_view () {

		if( is_single() || is_page() ) {
			global $post;
			if( isset( $post->ID ) ) {
				if( $this->count_post_type( $post->post_type ) && ! $this->block_user_role() ) {
					$this->increment_counter( $post->ID );
				}
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
		global $typenow;

		if( $this->count_post_type( $typenow ) ) {
			$columns['hits'] = __( 'Hits', 'post-hit-counter' );
		}

		return $columns;
	}

	/**
	 * Display content for 'Views' column in post list table
	 * @param  string  $column  Current column name
	 * @param  integer $post_id Current post ID
	 * @return void
	 */
	public function display_post_views_column ( $column = '', $post_id = 0 ) {
		global $typenow;

		$screen = get_current_screen();

		if( $this->count_post_type( $typenow ) ) {
			if( 'hits' == $column ) {
				$views = intval( get_post_meta( $post_id, $this->_field, true ) );
				echo $views;
			}
		}

	}

	/**
	 * Add 'Hits' section to admin bar on frontend for single posts
	 * @param  object $wp_admin_bar WordPress admin bar object
	 * @return void
	 */
	public function display_post_views_admin_bar ( $wp_admin_bar ) {

		if( is_single() || is_page() ) {
			global $post;
			if( isset( $post->ID ) ) {
				if( $this->count_post_type( $post->post_type ) ) {

					$views = intval( get_post_meta( $post->ID, $this->_field, true ) );

					$args = array(
						'id'    => 'hit_counter',
						'title' => sprintf( _n( '1 Hit', '%s Hits', $views, 'post-hit-counter' ), $views ),
						'href'  => admin_url( 'options-general.php?page=post_hit_counter_settings' ),
						'meta'  => array( 'class' => 'hit-counter', 'title' => __( 'Post Hit Counter settings', 'post-hit-counter' ) )
					);

					$wp_admin_bar->add_node( $args );
				}
			}
		}
	}

	/**
	 * Add 'Hits' columns to array of sortable columns
	 * @param  array  $sortable_columns Default array
	 * @return array                    Modified array
	 */
	public function sortable_columns ( $sortable_columns = array() ) {
		$sortable_columns['hits'] = 'hits';
		return $sortable_columns;
	}

	/**
	 * Handle sorting of list table by hits
	 * @param  object $query Default query array
	 * @return void
	 */
	public function sort_column ( $query ) {
		if ( is_admin() && $query->is_main_query() && 'hits' == $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', $this->_field );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * DIsplay post views on post edit screen
	 * @return void
	 */
	public function display_post_views_meta () {
		global $post, $pagenow, $typenow;

		if( 'post.php' == $pagenow && $this->count_post_type( $typenow ) ) {

			$views = intval( get_post_meta( $post->ID, $this->_field, true ) );

			?>
			<div class="misc-pub-section misc-pub-post-views" id="post-views">
				<?php _e( 'Hits:', 'post-hit-counter' ); ?>
				<strong><?php echo esc_html( $views ); ?></strong>
				<span class="dashicons dashicons-update hit-count-reset" title="<?php _e( 'Reset hit count', 'post-hit-counter' ); ?>"></span>
			</div>
			<?php
		}
	}

	/**
	 * Check whether a specified post type must be counted
	 * @param  string  $post_type Post type to check
	 * @return boolean            True if post type must be counted
	 */
	public function count_post_type ( $post_type = 'post' ) {

		if( ! $post_type ) {
			return false;
		}

		if( ! $this->active_types || ( $this->active_types && is_array( $this->active_types ) && in_array( $post_type, $this->active_types ) ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Check whether a specified (or the current) user role should not be counted
	 * @param  string  $role User role to check
	 * @return boolean       True is user role must be blocked from counting hits
	 */
	public function block_user_role ( $role = '' ) {

		if( ! is_user_logged_in() ) {
			return false;
		}

		if( ! $role ) {
			if( is_user_logged_in() ) {
				foreach( (array) $this->blocked_roles as $role ) {
					if( current_user_can( $role ) ) {
						return true;
					}
				}
			}
		} else {
			if( current_user_can( $role ) ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Register frontend widgets
	 * @return void
	 */
	public function register_widgets () {
		register_widget( 'Post_Hit_Counter_Widget_Most_Viewed_Posts' );
	}

	/**
	 * Register dashboard widgets
	 * @return void
	 */
	public function register_dashboard_widgets () {
		wp_add_dashboard_widget( 'most-viewed-posts', apply_filters( $this->_token . '_dashboard_widget_title', __( 'Most Viewed Posts', 'post-hit-counter' ) ), array( $this, 'dashboard_widget' ) );
	}

	/**
	 * Add content to dashboard widget
	 * @return void
	 */
	public function dashboard_widget () {
		$args = apply_filters( 'dashboard_widget_most_viewed_posts_args', array(
			'post_type'			  => 'post',
			'posts_per_page'      => 5,
			'no_found_rows'       => true,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'meta_key'			  => $this->_field,
			'orderby'			  => 'meta_value_num'
		) );

		$posts = new WP_Query( $args );

		$html = '';

		if ( $posts->have_posts() ) {

			$html .= '<ul>';

			while ( $posts->have_posts() ) {
				$posts->the_post();

				$views = intval( get_post_meta( get_the_ID(), $this->_field, true ) );

				$format = __( '<span>%1$s hits</span> <a href="%2$s">%3$s</a>', 'post-hit-counter' );
				$html .= sprintf( '<li>' . $format . '</li>', $views, get_edit_post_link(), _draft_or_post_title() );
			}

			$html .= '</ul>';
		} else {
			$html .= '<p><em>' . __( 'No viewed posts.', 'post-hit-counter' ) . '</em></p>';
		}

		echo $html;
	}

	/**
	 * Shortcode for displaying hit count for single post
	 * @param  array  $atts Shortcode attributes
	 * @return string       HTML output of shortcode
	 */
	public function hit_count_shortcode ( $atts = array() ) {

		// Parse parameters
		$atts = shortcode_atts( array(
			'post' => 0,
		), $atts, 'hit_count' );

		$html = '';
		$post_id = 0;

		// Get post ID to use
		if( $atts['post'] ) {
			$post_id = $atts['post'];
		} else {
			global $post;
			if( isset( $post->ID ) ) {
				$post_id = $post->ID;
			}
		}

		// Get shortcode output
		if( $post_id ) {
			$views = intval( get_post_meta( $post_id, $this->_field, true ) );
			$html = '<span class="hit-count">' . sprintf( __( 'Views: %d', 'post-hit-counter' ), $views ) . '</span>';
		}

		// Return output
		return apply_filters( $this->_token . '_post_views_shortcode_html', $html );
	}

	/**
	 * Reset the post hit count via AJAX
	 * @return void
	 */
	public function reset_hit_count() {

		if( isset( $_POST['post_id'] ) ) {

			$post_id = intval( $_POST['post_id'] );

			if( $post_id ) {
				update_post_meta( $post_id, $this->_field, 0 );
				echo 0;
			}
		}

		exit;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.1.0
	 * @return void
	 */
	public function enqueue_styles () {

		if( is_single() || is_page() ) {
			global $post;
			if( isset( $post->ID ) ) {
				if( $this->count_post_type( $post->post_type ) ) {
					wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
					wp_enqueue_style( $this->_token . '-frontend' );
				}
			}
		}

	} // End enqueue_styles ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		global $pagenow, $typenow;

		if( 'index.php' == $pagenow || ( in_array( $pagenow, array( 'post.php', 'edit.php' ) ) && $this->count_post_type( $typenow ) ) || ( isset( $_GET['page'] ) && 'post_hit_counter_settings' == $_GET['page'] ) ) {
			wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
			wp_enqueue_style( $this->_token . '-admin' );
		}
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.3.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		global $pagenow, $typenow;

		if( 'post.php' == $pagenow && $this->count_post_type( $typenow ) ) {
			wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
			wp_enqueue_script( $this->_token . '-admin' );
		}
	} // End admin_enqueue_scripts ()

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
