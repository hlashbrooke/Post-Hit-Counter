<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Post_Hit_Counter_Widget_Most_Viewed_Posts extends WP_Widget {

	public function __construct () {

		$widget_ops = array( 'classname' => 'widget_most_viewed widget_recent_entries', 'description' => __( "Your site&#8217;s most viewed Posts.", 'post-hit-counter' ) );

		parent::__construct('most-viewed-posts', __( 'Most Viewed Posts', 'post-hit-counter' ), $widget_ops);

		$this->alt_option_name = 'widget_most_viewed';

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
	}

	public function widget ( $args, $instance ) {

		$cache = array();

		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get( 'widget_most_viewed', 'widget' );
		}

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		ob_start();

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Most Viewed Posts', 'post-hit-counter' );

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number ) {
			$number = 5;
		}

		$show_hits = isset( $instance['show_hits'] ) ? $instance['show_hits'] : false;
		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;

		$r = new WP_Query( apply_filters( 'widget_most_viewed_posts_args', array(
			'posts_per_page'      => $number,
			'no_found_rows'       => true,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'meta_key'			  => Post_Hit_Counter()->_field,
			'orderby'			  => 'meta_value_num'
		) ) );

		if ($r->have_posts()) :
?>
		<?php echo $args['before_widget']; ?>
		<?php if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		} ?>
		<ul>
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>
			<li>
				<a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
			<?php if ( $show_hits ) : ?>
				<?php $hits = intval( get_post_meta( get_the_ID(), Post_Hit_Counter()->_field, true ) ); ?>
				<span class="post-views"><?php printf( __( 'Views: %d', 'post-hit-counter' ), $hits ); ?></span>
			<?php endif; ?>
			<?php if ( $show_date ) : ?>
				<span class="post-date"><?php echo get_the_date(); ?></span>
			<?php endif; ?>
			</li>
		<?php endwhile; ?>
		</ul>
		<?php echo $args['after_widget']; ?>
<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		endif;

		if ( ! $this->is_preview() ) {
			$cache[ $args['widget_id'] ] = ob_get_flush();
			wp_cache_set( 'widget_most_viewed', $cache, 'widget' );
		} else {
			ob_end_flush();
		}
	}

	public function update ( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$instance['show_hits'] = isset( $new_instance['show_hits'] ) ? (bool) $new_instance['show_hits'] : false;
		$instance['show_date'] = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		$this->flush_widget_cache();

		return $instance;
	}

	public function flush_widget_cache () {
		wp_cache_delete( 'widget_most_viewed', 'widget' );
	}

	public function form ( $instance ) {
		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$show_hits = isset( $instance['show_hits'] ) ? (bool) $instance['show_hits'] : false;
		$show_date = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'post-hit-counter' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:', 'post-hit-counter' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_hits ); ?> id="<?php echo $this->get_field_id( 'show_hits' ); ?>" name="<?php echo $this->get_field_name( 'show_hits' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_hits' ); ?>"><?php _e( 'Display post hit count?', 'post-hit-counter' ); ?></label></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display post date?', 'post-hit-counter' ); ?></label></p>
<?php
	}

	public function enqueue_styles () {
		wp_register_style( Post_Hit_Counter()->_token . '-widget', esc_url( Post_Hit_Counter()->assets_url ) . 'css/widget.css', array(), Post_Hit_Counter()->_version );
		wp_enqueue_style( Post_Hit_Counter()->_token . '-widget' );
	}
}