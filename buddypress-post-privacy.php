<?php 
/**
 * Plugin Name: Buddypress Post Privacy
 * Plugin URI: 
 * Description: ajax post privacy for buddypress members 
 * Version: 1.0.0
 * Author: Chiming Ho
 * Author URI: 
 * License: GPL2
 */
define("BP_POST_VISIBILITY_COLUMN", "bp_post_privacy");
const PRIVACY_OPTS = array(
    'public' => 'Public',
    'logged-in' => 'Logged-in Users',
    'friends' => 'Friends',
    'only-me' => 'Only Me'
  );


/**** custom post status *******/
function custom_post_status(){
	register_post_status( 'logged-in', array(
		'label'                     => _x( 'Logged-in Users', 'post that only logged-in users can see' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Logged-in Users <span class="count">(%s)</span>', 'Logged-in Users <span class="count">(%s)</span>' ),
	) );
	register_post_status( 'friends', array(
		'label'                     => _x( 'Friends', 'post that only Friends can see' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Friends <span class="count">(%s)</span>', 'Friends <span class="count">(%s)</span>' ),
	) );
	register_post_status( 'only-me', array(
		'label'                     => _x( 'Only-Me', 'post that only I can see' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Only Me <span class="count">(%s)</span>', 'Only Me <span class="count">(%s)</span>' ),
	) );
}
add_action( 'init', 'custom_post_status' );

/* TODO:: not working */
/*
add_action('admin_footer-post.php', 'jc_append_post_status_list');
function jc_append_post_status_list(){
     global $post;
     $complete = '';
     $label = '';
     if($post->post_type == 'post'){
          if($post->post_status == 'archive'){
               $complete = ' selected="selected"';
               $label = '<span id="post-status-display"> Archived</span>';
          }
          echo '
          <script>
          jQuery(document).ready(function($){
               $("select#post_status").append("<option value="archive" '.$complete.'>Archive</option>");
               $(".misc-pub-section label").append("'.$label.'");
          });
          </script>
          ';
     }
}
*/

add_action( 'wp_enqueue_scripts', 'ajax_enqueue_scripts_bp_post_privacy' );
function ajax_enqueue_scripts_bp_post_privacy() {
  if( bp_is_my_profile() ) {
    //do_wp_debug(__FILE__, array('wp_enqueue_scripts', 'bp_is_my_profile'));
    //wp_enqueue_style( 'love', plugins_url( '/love.css', __FILE__ ) );
    wp_enqueue_script( 'jdx', plugins_url( '/jdx.js', __FILE__ ), array('jquery'), '1.0', true );
    wp_localize_script( 'jdx', 'bp_post_privacy', array(
      'ajax_url' => admin_url( 'admin-ajax.php' )
    ));
  }
}

add_action( 'wp_ajax_nopriv_bp_post_privacy_change', 'change_bp_post_privacy' );
add_action( 'wp_ajax_bp_post_privacy_change', 'change_bp_post_privacy' );
function change_bp_post_privacy() {
  if(!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'bp_post_privacy_update'))
    echo 'nonce:'.($_REQUEST['nonce']);
  $post_id = $_REQUEST['post_id'];
  $bp_post_privacy = $_REQUEST[BP_POST_VISIBILITY_COLUMN]; 
  /*
	update_post_meta( $post_id, BP_POST_VISIBILITY_COLUMN, $bp_post_privacy);
  if($bp_post_privacy == 0){
    wp_update_post(array(  
      'ID'           => $post_id,
      'post_status'   => 'publish'));
  }else{
    wp_update_post(array(  
      'ID'           => $post_id,
      'post_status'   => 'private'));
  }
  */
    wp_update_post(array(  
      'ID'           => $post_id,
      'post_status'   => $bp_post_privacy));
  
  die();
}


function get_bp_post_privacy_select($post_id) {
  
  $select_html = '<select id="jdx-bp-post-privacy-'.$post_id.'" name="privacy" class="jdx-form-select privacy jdx-bp-post-privacy-opt">';
    
  $privacy_code = get_post( $post_id)->post_status;
  foreach ((array)PRIVACY_OPTS as $key => $val)
  {
    if ($key==$privacy_code) {
      $select_html .= '<option value="'.$key.'" selected="selected">'.$val.'</option>';                
    }else{
      $select_html .= '<option value="'.$key.'">'.$val.'</option>';  
    } 
  }

  $select_html .= '</select>';
  return $select_html;
}


/**
 * jdx_user_cap_filter()
 *
 * Filter on the current_user_can() function.
 * This function is used to explicitly allow authors to edit contributors and other
 * authors posts if they are published or pending.
 *
 * @param array $allcaps All the capabilities of the user
 * @param array $cap     [0] Required capability
 * @param array $args    [0] Requested capability
 *                       [1] User ID
 *                       [2] Associated object ID
 */
function jdx_user_cap_filter( $allcaps, $cap, $args ) {
//  //do_wp_debug(__FILE__, array('author_cap_filter', $allcaps, $cap, $args));
//	// Bail out if we're not asking about a post:
  if (($args[0]=='read_post') ) {
    //Exit if it is not post related    
    //if($cap[0] == 'read_private_posts'){
    if (!empty($args[2])) {
      $can_view_this_post = jdx_user_can_see_a_post($args[1], $args[2]);	
      if ($can_view_this_post) {			
        $allcaps[$cap[0]] = true;				
      }	
      //do_wp_debug(__FILE__, array('Privacy_read_post', $allcaps, $cap, $args));
    }
	} else 
  if (($args[0]=='read_private_posts')){ 
    if($cap[0] == 'read_private_posts'){
      $allcaps[$cap[0]] = true;
      //do_wp_debug(__FILE__, array('Privacy_read_private_posts', $allcaps, $cap, $args));
    }
  }
  return $allcaps;

}
add_filter( 'user_has_cap', 'jdx_user_cap_filter', 10, 3 );


function jdx_user_can_see_a_post($user_id, $post_id) {	
	$user_can_see_a_post=false;
  $post = get_post($post_id);
	$post_cap='';
  //If a post is public then anybody can see it
	if ($post->post_status == 'publish'
     || ($post->post_status == 'logged-in' && !empty($user_id))
     || ($post->post_status == 'friends' && friends_check_friendship( $user_id, $post->post_author))
     ) { $user_can_see_a_post=true; }
  //wordpress user editor role and above will handle properly on private post reading
  //else if (user_can($user_id,'edit_users')) {$user_can_see_a_post=true; }
  //else if ($user_id == $post->post_author) {$user_can_see_a_post=true; }
//  else if ($post->post_status == 'private'){
//    //Get current user and his/her permissions
//    $post_cap = get_post_meta( $post_id, BP_POST_VISIBILITY_COLUMN, true);
//
//      if(empty($post_cap) || ($post_cap <= 20 ) || ($post_cap <=40 && friends_check_friendship( $user_id, $post->post_author)) )
//      {
//        $user_can_see_a_post=true;
//      }
//  }
	else {

	}
  //do_wp_debug(__FILE__, array('jdx_user_can_see_a_post', $user_id, $post_id, $post_cap, $user_can_see_a_post));
	return $user_can_see_a_post;
}

add_filter( 'pre_get_posts', 'jdx_pre_get_posts' );
function jdx_pre_get_posts( $query ) {

  if ($query->is_archive() || $query->is_single())
  {
    //$query->set( 'post_status', array('any'));    
    $query->set( 'post_status', array('publish','logged-in','friends','only-me'));
    //do_wp_debug(__FILE__, array('jdx_pre_get_posts', $query));
  }
  
	if ( $query->is_tag() || $query->is_search()){
		$query->set( 'post_type', CPTs );
    //$query->set( 'author__in', array(3));    
    //$query->init();
  }

	return $query;
}

/**
 * Modify the main search query of WordPress to search by location
 * 
 */
add_filter('posts_clauses','query_search_custom', 10, 2);
function query_search_custom( $clauses, $query) {
  global $wpdb;
  switch(true){
    case $query->is_tax():
    case $query->is_tag():
    case $query->is_search():
      $members = query_search_get_users_by_current_location();
      if(empty($members)){
        $clauses['where'] = " AND false";
        return $clauses;
      }
      //$clauses['where'] = $wpdb->prepare(" AND $wpdb->posts.post_type = %s ", $query->query_vars['post_type']); 
      $clauses['where'] .= $wpdb->prepare(" AND $wpdb->posts.post_author IN (%s) ", implode( "','", $members )); 
    case $query->is_author():
    case $query->is_single():
    case isset($query->query_vars['action']) && ($query->query_vars['action']) == 'meso_cpt_widget' :
      $clauses = query_search_by_bp_privacy($clauses, $query);
      //do_wp_debug(__FILE__, array('query_search_custom author', $clauses, $query));
      break;
    case 'privacy':
    default: 
      //do_wp_debug(__FILE__, array('query_search_custom default', $clauses, $query));
      break;
  }
  return $clauses;
}

function query_search_get_users_by_location($user_lat, $user_lng, $radius, $units = 3956 /*3959 for miles or 6371 for kilometers */, $return_sql=false) {
  global $wpdb;
  $sql = $wpdb->prepare( "SELECT * FROM
                          ( SELECT *, ROUND( %d * acos( cos( radians( %s ) ) * cos( radians( wfl.lat ) ) * cos( radians( wfl.long ) - radians( %s ) ) + sin( radians( %s ) ) * sin( radians( wfl.lat) ) ),1 ) as distance 
                            FROM wppl_friends_locator as wfl) as fl 
                          where fl.distance < %d ",
                         array( $units, $user_lat, $user_lng, $user_lat, $radius ) );
  if($return_sql) return $sql;
  $results = $wpdb->get_col($sql, 0);
  do_wp_debug(__FILE__, array('query_search_get_users_by_location', $results, $sql));
  return $results;
}

function query_search_get_users_by_current_location() {
  //get the user's current location ( lat/ lng )
  $user_lat = ( isset( $_COOKIE['gmw_lat'] ) ) ? urldecode($_COOKIE['gmw_lat']) : false;
  $user_lng = ( isset( $_COOKIE['gmw_lng'] ) ) ? urldecode($_COOKIE['gmw_lng']) : false;

  //abort if user's current location not exist
  if ( $user_lat == false || $user_lng == false )
  {
//    if(is_user_logged_in()){
//      $sql = "SELECT fl.lat, fl.long FROM wppl_friends_locator as fl where member_id = ".get_current_user_id();
//      $r = $wpdb->get_row($sql);
//      if(!isset($r) || empty($r->lat) ) return array();
//      $user_lat = $r->lat;
//      $user_lng = $r->long;
//      do_wp_debug(__FILE__, array('query_search_get_users_by_current_location_logged_in_user_db_location', $r, $user_lat, $user_lng));
//    }else{
      return array();
//    } 
  }
  
  //set some values
  $radius  = 10; //can be any value
  $units 	 = 3959; //3959 for miles or 6371 for kilometers
  return query_search_get_users_by_location($user_lat, $user_lng, $radius, $units);
}

/**
 * Modify the main search query of WordPress to search by location
 * 
 */
function query_search_by_bp_privacy( $clauses, $query) {
  global $wpdb;
  /*
  $BP_POST_VISIBILITY_COLUMN = 'bp_post_privacy';
  
  $meta_query_args_friends = array(
    'relation' => 'OR', // Optional, defaults to "AND"
    array(
      'key'     => $BP_POST_VISIBILITY_COLUMN,
      'value'   => 'friends',
      'compare' => '='
    )
  );
  $meta_query = new WP_Meta_Query( $meta_query_args_friends );
  $mq_sql = $meta_query->get_sql(
    'post',
    $wpdb->posts,
    'ID',
    null
  );
  */

  if(is_user_logged_in()){
//    if ( class_exists( 'RTMediaFriends' ) ) { // rtMedia plugin 
//      $friendship = new RTMediaFriends();
//      $friends = $friendship->get_friends_cache( get_current_user_id() );
//    }else{ //use buddypress directly. Considering object cache from w3 total cache plugin 
      $friends = friends_get_friend_user_ids( get_current_user_id() );
//    }
    
    $sql= "{$wpdb->prefix}posts.post_status = 'publish' 
      OR {$wpdb->prefix}posts.post_status = 'logged-in' 
      OR {$wpdb->prefix}posts.post_author = ". get_current_user_id();

    if ( !empty( $friends ) ) {
      $sql .= " OR ({$wpdb->prefix}posts.post_status = 'friends' AND {$wpdb->prefix}posts.post_author IN (" . implode( ',', $friends ) . ")) ";
    }
    
    $sql = "(".$sql.")";
    //$clauses['where'] = str_replace("(({$wpdb->prefix}posts.post_status = 'publish'))", $sql,  $clauses['where']);
  }else{
    $sql = "({$wpdb->prefix}posts.post_status = 'publish')";
  }
  
  $clauses['where'] .= ' AND '.$sql;
  $clauses['where'] = str_replace("AND (({$wpdb->prefix}posts.post_status = 'publish' OR {$wpdb->prefix}posts.post_status = 'logged-in' OR {$wpdb->prefix}posts.post_status = 'friends' OR {$wpdb->prefix}posts.post_status = 'only-me'))", 
                                  '',  $clauses['where']);  
  return $clauses;
}


/*
//add_action( 'post_submitbox_misc_actions', 'my_post_submitbox_misc_actions' );
function my_post_submitbox_misc_actions(){
?>
<div class="misc-pub-section my-options">
	<label for="my_custom_post_action">My Option</label><br />
	<select id="my_custom_post_action" name="my_custom_post_action">
		<option value="1">First Option goes here</option>
		<option value="2">Second Option goes here</option>
	</select>
</div>
<?php
}
*/

add_filter( 'gform_post_status_options', 'add_custom_post_status' );
function add_custom_post_status( $post_status_options ) {
  foreach ((array)PRIVACY_OPTS as $key => $val)
  {
    $post_status_options[$key] = $val;
  }
  return $post_status_options;
}