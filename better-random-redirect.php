<?php
/*
Plugin Name: Better Random Redirect
Plugin URI: https://wordpress.org/plugins/better-random-redirect/
Description: Based on the original Random Redirect, this plugin enables efficent, easy random redirection to a post.
Author: Robert Peake
Version: 1.1
Author URI: http://www.robertpeake.com/
Text Domain: better_random_redirect
Domain Path: /languages/
*/
namespace better_random_redirect;

if ( !function_exists( 'add_action' ) ) {
    die();
}

function load_textdomain() {
    load_plugin_textdomain( 'better_random_redirect', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function register_menu_page(){
    add_options_page( __('Better Random Redirect Options','better_random_redirect'), __('Better Random Redirect','better_random_redirect'), 'manage_options', plugin_dir_path(  __FILE__ ).'admin.php');
}

function register_settings() {
    global $wpdb;
    add_option('brr_default_slug', __('random','better_random_redirect'));
    add_option('brr_default_timeout', 3600);
    add_option('brr_default_category', '');
    add_option('brr_transient_id','better_random_redirect_post_ids');
    add_option('brr_query_pattern','SELECT %s FROM '.$wpdb->posts.' where post_type=\'post\' and post_status=\'publish\' and post_password = \'\'');
    add_option('brr_query_category_pattern', 'SELECT %s FROM '.$wpdb->posts.' p '.
                 'LEFT OUTER JOIN '.$wpdb->term_relationships.' r ON r.object_id = p.ID '.
                 'LEFT OUTER JOIN '.$wpdb->term_taxonomy.' x ON x.term_taxonomy_id = r.term_taxonomy_id '.
                 'LEFT OUTER JOIN '.$wpdb->terms.' t ON t.term_id = x.term_id '.
                 ' where post_type=\'post\' and post_status=\'publish\' and post_password = \'\' and t.slug=\'%s\'');
    register_setting( 'better_random_redirect', 'brr_default_slug', '\better_random_redirect\slug_check' );
    register_setting( 'better_random_redirect', 'brr_default_category', '\better_random_redirect\cat_check' ); 
    register_setting( 'better_random_redirect', 'brr_default_timeout', '\better_random_redirect\integer_check' );  
}

function slug_check( $string ) {
    return filter_var($string, FILTER_SANITIZE_URL);
}

function cat_check( $string ) {
    if ($string == '') {
        return $string;
    }
    $string = filter_var($string, FILTER_SANITIZE_STRING);
    if (term_exists($string,'category')) {
        return $string;
    } else {
        return '';
    }
}

function integer_check( $int ) {
    return filter_var($int, FILTER_SANITIZE_NUMBER_INT);
}

function random_url_shortcode( $atts ) {
    global $wpdb;
    $url_slug = get_option('brr_default_slug'); //slug to use in URL
    $expiration = get_option('brr_default_timeout'); //how long to cache the list of valid posts (in seconds)
    $transient_id = get_option('brr_transient_id');
    extract( shortcode_atts( array(
                'cat' => '',
            ), $atts, 'better_random_redirect' ) );
    if ($cat && strlen($cat) > 0 && term_exists($cat,'category')) {
        $category = $cat;
        $transient_id = $transient_id . '_category_'.$category;
    }
    if (false === ($max = get_transient( $transient_id . '_count'))) {
        if ($category && strlen($category) > 0) {
            $query = sprintf( get_option('brr_query_category_pattern'), 'count(*)',$category);
        } else {
            $query = sprintf(get_option('brr_query_pattern'),'count(*)');
        }
        $total = $wpdb->get_var($query);
        $max = $total - 1;
        set_transient( $transient_id . '_count', $max, $expiration);
    }
    $url_base = site_url().'/'.$url_slug.'/';
    $query_data = array();
    if (strlen($category) > 0) {
        $query_data['cat'] = $category;
    }
    $query_data['r'] = rand(0,$max);
    $query_part = http_build_query($query_data);
    if ($query_part && strlen($query_part) > 0) {
        $url = $url_base . '?' . $query_part;
    } else {
        $url = $url_base;
    }
    return $url;
}

function do_redirect() {
    global $wpdb;
    $url_slug = get_option('brr_default_slug'); //slug to use in URL
    $expiration = get_option('brr_default_timeout');; //how long to cache the list of valid posts (in seconds)
    $transient_id = get_option('brr_transient_id');
    if (isset($_GET['cat']) && term_exists($_GET['cat'],'category')) {
        $category = $_GET['cat'];
    } else {
        $category = get_option('brr_default_category');
    }
    if ($category && strlen($category) > 0) {
        $transient_id = $transient_id . '_category_'.$category;
    }
    $url_base = parse_url(site_url(),PHP_URL_PATH);
    $url_current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (substr($url_base,-1) != '/') {
        $url_base = $url_base . '/';
    }
    if (substr($url_base,0,1) != '/') {
        $url_base = '/'.$url_base;
    }
    if (  $url_base.$url_slug == $url_current || $url_base.$url_slug.'/' == $url_current ) {
        // check the transient cache first, if either the post id list or count is not found or expired, regenerate both
        if ( false === ( $post_ids = get_transient( $transient_id ) ) || false === ($max = get_transient( $transient_id . '_count')) ) {
            if (strlen($category) > 0) {
                $query = sprintf( get_option('brr_query_category_pattern'), 'ID',$category);
            } else {
                $query = sprintf(get_option('brr_query_pattern'),'ID');
            }
            // query for valid posts: type=post and status=published
            $post_ids = $wpdb->get_col( $query );
            // set the cache
            set_transient($transient_id, $post_ids, $expiration);
            $max = (sizeof($post_ids) - 1);
            set_transient( $transient_id . '_count', $max, $expiration);
        }
        if ($max >= 0) { //max is indexed from zero to count minus one
            if (isset($_GET['r']) && is_numeric($_GET['r']) &&  ( ctype_digit($_GET['r']) || is_int($_GET['r']) ) && $_GET['r'] >= 0 && $_GET['r'] <= $max ) {
                $index = filter_var($_GET['r'], FILTER_SANITIZE_NUMBER_INT);
            } else {
                $index = rand(0,$max); // get a random index in PHP
            }
            if (isset($post_ids[$index])) {
                $id = $post_ids[$index];
                $max_count = 10; //how many "lucky dip" requests to make before giving up, in case the cache and actual posts are considerably out of sync
                do {
                    $post = get_post( $id );
                    if ($post) {
                        // found a valid random post, redirect to it
                        \better_random_redirect\force_redirect_no_cache();
                        wp_redirect ( get_permalink ( $post->ID ) , 302 );
                        exit;
                    } else {
                        // not a valid post, try again up to $max_count times
                        $index = rand(0,$max);
                        $id = $post_ids[$index];
                    }
                    $count++;
                } while (!$post && $count < $max_count); //continue as long as we haven't exceeded $max_count
            }
        }
    }
}

function force_redirect_no_cache() {
    header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
    header('Pragma: no-cache'); // HTTP 1.0.
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT'); // Proxies.
}

add_action( 'plugins_loaded', '\better_random_redirect\load_textdomain' );
add_action( 'admin_menu', '\better_random_redirect\register_menu_page' );
add_action( 'admin_init', '\better_random_redirect\register_settings' );
add_action( 'template_redirect', '\better_random_redirect\do_redirect' );
add_shortcode('random-url','\better_random_redirect\random_url_shortcode');