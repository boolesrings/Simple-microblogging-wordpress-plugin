<?php
/*
 * Plugin Name: Simple microblogging
 * Description: Use your wordpress site as a microblog; display the microposts in a widget or using a shortcode.
 * Version: 0.0
 * Author: Samuel Coskey, Victoria Gitman
 * Author URI: http://boolesrings.org
*/


/*
 * Create the new post type
*/
add_action( 'init', 'create_micropost_type' );
function create_micropost_type() {
	register_post_type( 'micropost',
		array(
			'labels' => array(
				'name' => __( 'Microposts' ),
				'singular_name' => __( 'Micropost' ),
			),
			'has_archive' => true,
			'menu_icon' => plugins_url( 'microblogging-icon.png', __FILE__ ),
			'menu_position' => 5,
			'public' => true,
			'rewrite' => array( 'slug' => 'microposts' ),
			'supports' => array( 'title', 'editor', 'comments' ),
// uncomment to support categories and tags:
//			'taxonomies' => array ( 'category', 'post_tag' ),
		)
	);
}

/*
 * Tells wordpress to reset its permalink structure, to accommodate the new post type
*/
register_activation_hook( __FILE__, 'my_rewrite_flush' );
function my_rewrite_flush() 
{
	create_micropost_type();
	flush_rewrite_rules();
}


/*
 * Microblog widget code
*/
add_action('widgets_init', 'ahspfc_load_widgets');
function ahspfc_load_widgets() {
	register_widget('microblog_widget');
}
class microblog_widget extends WP_Widget {

	function microblog_widget() {
		$widget_ops = array(
			'classname' => 'microblog_widget',
			'description' => 'Allows you to display a list of microblog entries while excluding them from posts.',
		);
		$control_ops = array(
			'id_base' => 'microblog-widget',
		);
		$this->WP_Widget('microblog-widget', 'Microblog', $widget_ops, $control_ops );
	}

	function form ($instance) {
		$defaults = array(
			'numberposts' => '5',
			'title'       => '',
			'rss'         => '',
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
?>
  <p>
    <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
    <input type="text" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?> " value="<?php echo $instance['title'] ?>" size="20">
  </p>
  <p>
   <label for="<?php echo $this->get_field_id('numberposts'); ?>">Number of posts:</label>
   <input type="text" name="<?php echo $this->get_field_name('numberposts'); ?>" id="<?php echo $this->get_field_id('numberposts'); ?>" value="<?php echo $instance['numberposts']; ?>">
  </p>
  <p>
   <input type="checkbox" id="<?php echo $this->get_field_id('rss'); ?>" name="<?php echo $this->get_field_name('rss'); ?>" <?php if ($instance['rss']) echo 'checked="checked"' ?> />
   <label for="<?php echo $this->get_field_id('rss'); ?>">Show RSS feed link?</label>
  </p>
<?php
	}

	function update ($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['numberposts'] = $new_instance['numberposts'];
		$instance['title'] = $new_instance['title'];
		$instance['rss'] = $new_instance['rss'];

		return $instance;
	}

	function widget ($args,$instance) {
		extract($args);
		$title = $instance['title'];
		$numberposts = $instance['numberposts'];
		$rss = $instance['rss'];

		// retrieve posts information from database
		global $post;
		$query = "post_type=micropost&posts_per_page=" . $numberposts;
		$query_results = new WP_Query($query);

		// build the widget contents!
		$out = "<ul>";
		while ( $query_results->have_posts() ) {
			$query_results->the_post();
			$out .= "<li>";
			$post_title = the_title( '', '', false );
			if ( $post_title ) {
				$out .= "<span class='microblog-widget-post-title'>"
				      . $post_title
				      . " </span>";
			}
			$out .= "<span class='microblog-widget-post-content'>"
			      . wp_kses($post->post_content,
					array('a'      => array('href'=>array()),
					      'em'     => array(),
					      'strong' => array(),
					      'b'      => array(),
					      'i'      => array(),
					      )
					)
			      . "</span>";
			$out .= "<span lass='microblog-widget-commentlink'>";
			$out .= " <a href='" . get_permalink() . "'>";
			$out .= "<img width='14px' src='"
			      . site_url() . "/wp-includes/images/wlw/wp-comments.png'>";
			$out .= "&times;" . get_comments_number();
			$out .= '</a>';
			$out .= "</span>\n";
			$out .= "</li>\n";
		}
		$out .= "</ul>";

		//print the widget for the sidebar
		echo $before_widget;
		echo $before_title;
		echo $title;
		if ($rss) {
			echo ' <a href="' . get_site_url() . '/feed/?post_type=micropost" class="rss">';
			echo '<img src="' . site_url() . '/wp-includes/images/rss.png"/>';
			echo '</a>';
		}
		echo $after_title;
		echo $out;
		echo $after_widget;

		// clean up
		wp_reset_postdata();
	}
}

/*
 * Microblog shortcode code
*/
add_shortcode( 'microblog', 'microblog_shortcode' );
function microblog_shortcode($atts) {
	global $post;

	// Arguments to the shortcode
	extract( shortcode_atts(  array(
		'num'         => '5',
		'null_text'   => '(none)',
		'show_date'   => '',
		'date_format' => get_option('date_format'), // I recommend 'F j'
	), $atts ) );

	/*
	* query the database for tweets!
	* query syntax:
	* http://codex.wordpress.org/Class_Reference/WP_Query#Parameters
	*/
	$query .= "post_type=micropost&posts_per_page=" . $num;
	$query_results = new WP_Query($query);
	
	if ( $query_results->post_count == 0 ) {
		return "<p>" . wp_kses($null_text,array()) . "</p>\n";
	}
	
	$out = "<ul class='microblog-shortcode'>\n";
	while ( $query_results->have_posts() ) {
		$query_results->the_post();
		$out .= "<li>"; 
		if ( $show_date) {
			$out .= "<span  class='microblog-shortcode-date'>"
			      . get_the_date($date_format)
			      . "</span>";
			$out .= "<span class='microblog-shortcode-date-sep'>: </span>\n";
		}
		$post_title = the_title( '', '', false );
		if ( $post_title ) {
			$out .= "<span class='microblog-shortcode-post-title'>"
			      . $post_title
			      . " </span>";
		}
		$out .= "<span class='microblog-shortcode-post-content'>"
		      . $post->post_content
		      . "</span>";
		$out .= "<span class='microblog-shortcode-commentlink'>";
		$out .= " <a href='" . get_permalink() . "'>";
		$out .= "<img width='14px' src='"
		      . site_url() . "/wp-includes/images/wlw/wp-comments.png'>";
		$out .= "&times;" . get_comments_number();
		$out .= "</a>";
		$out .= "</span>\n";
		$out .= "</li>\n";
	}
	$out .= "</ul>";

	// clean up	
	wp_reset_postdata();

	return $out;

}


/*
 * Load our default style sheet
*/
add_action( 'wp_print_styles', 'microblog_enqueue_styles' );
function microblog_enqueue_styles() {
	wp_register_style( 'simple-microblogging',
		   plugins_url('simple-microblogging.css', __FILE__) );
	wp_enqueue_style( 'simple-microblogging' );
}

?>