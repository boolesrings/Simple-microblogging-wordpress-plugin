<?php
/*
 * Plugin Name: Simple microblogging
 * Description: Use your wordpress site as a microblog; display the microposts in a widget or using a shortcode.
 * Version: 0.0
 * Author: Samuel Coskey, Victoria Gitman
 * Author URI: http://boolesrings.org
*/

define('SCRIPT_DEBUG', true);

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
		$idObj = get_category_by_slug('tweets'); 
		$catid = $idObj->term_id;
		$title = $instance['title'];
		$numberposts = $instance['numberposts'];
		$rss = $instance['rss'];

		// retrieve posts information from database
		global $post;
		$query = "category_name=tweets&posts_per_page=" . $numberposts;
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
			      . get_the_excerpt()
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
			echo ' <a href="' . get_category_link($catid) . 'feed/" class="rss">';
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
	$query .= "category_name=tweets&posts_per_page=" . $num;
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
		      . get_the_excerpt()
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
 * Category exclusion code
*/
add_filter('pre_get_posts','microblog_exclude_categories');
function microblog_exclude_categories($query) {
	$idObj = get_category_by_slug('tweets'); 
	$id = $idObj->term_id;
  	$cat[0]=$id;
  	if ($query->is_home || ($query->is_feed && !$query->is_category) ) {
		$query->set('category__not_in', $cat); }

	return $query;
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