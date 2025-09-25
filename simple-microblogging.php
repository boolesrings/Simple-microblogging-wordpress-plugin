<?php
/*
 * Plugin Name: Simple microblogging 【增强版】
 * Description: Use your wordpress site as a microblog; display the microposts in a widget or using a shortcode. 增强版优化页面显示，增加分页功能。技术支持：http://oba.by
 * Version: 1.10
 * Author: Samuel Coskey, Victoria Gitman, obaby
 * Author URI: http://boolesrings.org
*/
/*
https://www.qiniu.com/qfans/qnso-21193676#comments
*/

// 处理分页
function remove_page_from_query_string($query_string)
{ 
    if (isset($query_string['name']) && $query_string['name'] == 'page' && isset($query_string['page'])) {
        unset($query_string['name']);
        // 'page' in the query_string looks like '/2', so i'm spliting it out
        @list($delim, $page_index) = explode('/', $query_string['page']);
        $query_string['paged'] = $page_index;
    }      
    return $query_string;
}
// I will kill you if you remove this. I died two days for this line 
add_filter('request', 'remove_page_from_query_string');

// following are code adapted from Custom Post Type Category Pagination Fix by jdantzer
function fix_category_pagination($qs){
    if(isset($qs['category_name']) && isset($qs['paged'])){
        $qs['post_type'] = get_post_types($args = array(
            'public' => true,
            '_builtin' => false
        ));
        array_push($qs['post_type'],'post');
    }
    return $qs;
}
add_filter('request', 'fix_category_pagination');

/*
 * Create the new post type
*/
add_action( 'init', 'create_micropost_type' );
function create_micropost_type() {
 register_post_type( 'micropost',
  array(
   'labels' => array(
    'name' => __( 'Microposts' ),
	 'menu_name' => __('MicroPost'),
    'singular_name' => __( 'Micropost' ),
   ),
   'has_archive' => true,
   'menu_icon' => plugins_url( 'bubble2-icon.png', __FILE__ ),
   'menu_position' => 5,
   'public' => true,
   'rewrite' => array( 'slug' => 'microposts' ),
   'supports' => array( 'title', 'editor', 'author', 'comments' ),
// uncomment to support categories and tags:
 'taxonomies' => array ( 'category', 'post_tag' ),
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
  $this->__construct('microblog-widget', 'Microblog', $widget_ops, $control_ops );

//$this->WP_Widget('microblog-widget', 'Microblog', $widget_ops, $control_ops );


 }
 function form ($instance) {
  $defaults = array(
   'numberposts' => '5',
   'title' => '',
   'rss' => '',
    'use_excerpt' => false,
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
   <input type="checkbox" id="<?php echo $this->get_field_id('use_excerpt'); ?>" name="<?php echo $this->get_field_name('use_excerpt'); ?>" <?php if ($instance['use_excerpt']) echo 'checked="checked"' ?> />
   <label for="<?php echo $this->get_field_id('use_excerpt'); ?>">Show excerpts only?</label>
  </p>
  <p>
   <input type="checkbox" id="<?php echo $this->get_field_id('rss'); ?>" name="<?php echo $this->get_field_name('rss'); ?>" <?php if ($instance['rss']) echo 'checked="checked"' ?> />
   <label for="<?php echo $this->get_field_id('rss'); ?>">Show RSS feed link?</label>
  </p>
<?php
 }
 function update ($new_instance, $old_instance) {
  $instance = $old_instance;
  $instance['title'] = $new_instance['title'];
  $instance['numberposts'] = $new_instance['numberposts'];
  $instance['use_excerpt'] = $new_instance['use_excerpt'];
  $instance['rss'] = $new_instance['rss'];
  return $instance;
 }
 function widget ($args,$instance) {
  extract($args);
  $title = $instance['title'];
  $numberposts = $instance['numberposts'];
  $use_excerpt = $instance['use_excerpt'];
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
    $out .= "<div class='microblog-widget-post-title'>"
          ." <a href='" . get_permalink() . "'>"
         . $post_title. "</a>"
	 . " </div>";
    $out .= "<div class='microblog-widget-commentlink'> <a href='" . get_permalink() . "'> 评论:";
   $out .= "<img width='14px' src='"
        . site_url() . "/wp-content/plugins/simple-microblogging/bubble-icon.png'>";
   $out .= "&times;" . get_comments_number();
   $out .= '</a></div>';
   }
   $out .= "<span class='microblog-widget-post-content'>";
   if ( $use_excerpt ) {
    add_filter('excerpt_more', 'micropost_excerpt_more');
    $out .= get_the_excerpt();
    remove_filter('excerpt_more', 'micropost_excerpt_more');
   } else {
    // 使用 apply_filters 来确保所有内容过滤器都被应用，包括视频处理
    $parsed_content = apply_filters('the_content', $post->post_content);
    $out .= $parsed_content;
   }
   $out .= "</span>";
   //$out .= "<span lass='microblog-widget-commentlink'>";
   //$out .= " <a href='" . get_permalink() . "'>";
   //$out .= "<img width='14px' src='"
   //     . site_url() . "/wp-content/plugins/simple-microblogging/bubble-icon.png'>";
   //$out .= "&times;" . get_comments_number();
   //$out .= '</a>';
  // $out .= "</span>\n";
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
/**
     * Defines the visisble user name
     * @param object $user_info
     * @param string $wpam_user_name
     * @since 3.1
     */
 function get_username ($user_info, $wpam_user_name) {
        // Nick name option
        if ($wpam_user_name == 'nickname') {
            return $user_info->nickname;
        }
        // User login option
        if ($wpam_user_name == 'user_login') {
            return $user_info->user_login;
        }
        // Full name option (First name and last name)
        if ($wpam_user_name == 'full_name') {
            return $user_info->first_name . ' ' . $user_info->last_name;
        }
        // Default
        return $user_info->display_name;
    }
/*
 * Microblog shortcode code
*/
add_shortcode( 'microblog', 'microblog_shortcode' );
function microblog_shortcode($atts) {
 global $post;
 // 分页处理
 $paged = get_query_var('paged') ? get_query_var('paged') : 1;
 // Arguments to the shortcode
 extract( shortcode_atts( array(
  'num' => '5',
  'null_text' => '(none)',
  'show_date' => '',
  'date_format' => get_option('date_format'), // I recommend 'F j'
  'use_excerpt' => '',
  'q' => '',
 ), $atts ) );
 if ( $q ) {
  $q = str_replace ( "&#038;", "&", $q );
 }
 /*
 * query the database for tweets!
 * query syntax:
 * http://codex.wordpress.org/Class_Reference/WP_Query#Parameters
 */
 $offset = ($paged-1)*$num;
 $query .= "post_type=micropost&posts_per_page=" . $num."&offset=".$offset;
 if ( $q ) {
  $query .= "&" . $q;
 }
 //echo $query;
 $query_results = new WP_Query($query);
 if ( $query_results->post_count == 0 ) {
  return "<p>" . wp_kses($null_text,array()) . "</p>\n";
 }
 $out = "<ul class='microblog-shortcode'>\n";
 while ( $query_results->have_posts() ) {
  $query_results->the_post();
  $avatar = get_avatar( get_the_author_email(), '24','', '', array('style' => 'border-radius: 50%; overflow: hidden;') );;
  $out .= "<li> ".$avatar;
  if ( $show_date) {
   $out .= "<span style='margin-left: 8px;'></span><span class='microblog-shortcode-date'>"
         . get_the_date($date_format)
         . "</span>";
   $out .= "<span class='microblog-shortcode-date-sep'>: </span><span style='margin-right: 8px;'></span>\n";
  }
  $post_title = the_title( '', '', false );
  if ( $post_title ) {
  $out .= "<span class='microblog-shortcode-post-title'>"." <a href='" . get_permalink() . "'>"
         . $post_title. "</a>"
         . " </span></br>";
  }
  $out .= "<span class='microblog-shortcode-post-content'></br>";
  if ( $use_excerpt ) {
   add_filter('excerpt_more', 'micropost_excerpt_more');
   $out .= get_the_excerpt();
   remove_filter('excerpt_more', 'micropost_excerpt_more');
  } else {
   // 使用 apply_filters 来确保所有内容过滤器都被应用，包括视频处理
   $parsed_content = apply_filters('the_content', $post->post_content);
   $out .= $parsed_content;
  }
  $out .= "</span></br>";
  $out .= "</br><span class='microblog-shortcode-commentlink'>  评论：";
  $out .= " <a href='" . get_permalink() . "'>";
  $out .= "<img width='18' height='18' src='"
        . site_url() . "/wp-content/plugins/simple-microblogging/bubble-icon.png'>";
  $out .= "&times;" . get_comments_number();
  $out .= "</a>";
  $out .= "</span>\n";
  $out .= "</li>\n";
  $out .= "</br><hr></br>\n";
 }
 $out .= "</ul>";
$args = array(
        'post_type' => 'micropost',
        'orderby' => 'date',
        'order' => 'DESC',
        'posts_per_page' => $num,
        'post_status' => array('publish'),
        'paged' => $paged
    );
$the_query = new WP_Query($args);
 $total_pages = $the_query->max_num_pages;
 //echo $total_pages;
if ($total_pages > 1){
    $current_page = max(1, get_query_var('paged'));
 //echo $current_page;
	$out .= "<div id='paging' class='group'>";
    $out .= paginate_links(array(
        'base' => get_pagenum_link(1) . '%_%',
        'format' => '/page/%#%',
        'current' => $current_page,
        'total' => $total_pages,
        'prev_text' => __('« prev'),
        'next_text' => __('next »'),
    ));
    $out .= "</div>";
}
 // clean up
 wp_reset_postdata();
 return $out;
}
function micropost_excerpt_more($more) {
 return ' ...';
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
