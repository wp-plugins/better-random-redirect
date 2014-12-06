<?php
/*
Plugin Name: Better Random Redirect
Plugin URI: https://wordpress.org/plugins/better-random-redirect/
Description: Based on the original Random Redirect, this plugin enables random redirection to a post in a manner that won't overload your MySQL database for a large website under a high volume of clicks. Also perfectly suitable for smaller sites as well. Supports picking a random post from a specific category, and setting your own redirector URL.
Author: Robert Peake
Version: 1.0
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
    add_option('brr_default_slug', 'random');
    add_option('brr_default_timeout', 3600);
    register_setting( 'better_random_redirect', 'brr_default_slug', '\better_random_redirect\slug_check' ); 
    register_setting( 'better_random_redirect', 'brr_default_timeout', '\better_random_redirect\integer_check' );  
}

function slug_check( $string ) {
    return filter_var($string, FILTER_SANITIZE_URL);
}

function integer_check( $int ) {
    return filter_var($int, FILTER_SANITIZE_NUMBER_INT);
}

function do_redirect() {
    global $wpdb;
    $url_slug = get_option('brr_default_slug'); //slug to use in URL
    $expiration = get_option('brr_default_timeout');; //how long to cache the list of valid posts (in seconds)
    $transient_id = 'better_random_redirect_post_ids';
    if (isset($_GET['cat']) && term_exists($_GET['cat'],'category')) {
        $category = $_GET['cat'];
        $transient_id = $transient_id . '_category_'.$category;
    }
    
    $url_base = parse_url(network_site_url(),PHP_URL_PATH);
    $url_current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (substr($url_base,-1) != '/') {
        $url_base = $url_base . '/';
    }
    if (substr($url_base,0,1) != '/') {
        $url_base = '/'.$url_base;
    }
    if (  $url_base.$url_slug == $url_current || $url_base.$url_slug.'/' == $url_current ) {
        // check the cache first
        if ( false === ( $post_ids = get_transient( $transient_id ) ) ) {
            if (strlen($category) > 0) {
                $query = 'SELECT ID FROM '.$wpdb->posts.' p '.
                         'LEFT OUTER JOIN '.$wpdb->term_relationships.' r ON r.object_id = p.ID '.
                         'LEFT OUTER JOIN '.$wpdb->term_taxonomy.' x ON x.term_taxonomy_id = r.term_taxonomy_id '.
                         'LEFT OUTER JOIN '.$wpdb->terms.' t ON t.term_id = x.term_id '.
                         ' where post_type=\'post\' and post_status=\'publish\' and post_password = \'\' and t.slug=\''.$category.'\'';
            } else {
                $query = 'SELECT ID FROM '.$wpdb->posts.' where post_type=\'post\' and post_status=\'publish\' and post_password = \'\'';
            }
            // query for valid posts: type=post and status=published
            $post_ids = $wpdb->get_col( $query );
            // set the cache
            set_transient($transient_id, $post_ids, $expiration);
        }
        if (sizeof($post_ids) == 0) {
            \better_random_redirect\force_redirect_no_cache();
            wp_redirect( get_home_url(), 404); // no valid posts, signal not found error and redirect to homepage
        } else {
            $max = (sizeof($post_ids) - 1);
            $index = rand(0,$max); // get a random index in PHP
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
        // if we get here, something has gone seriously wrong between the cached version of posts and current site
        \better_random_redirect\force_redirect_no_cache();
        wp_redirect( get_home_url(), 500); // signal error, redirect to homepage of site
    }
}

function force_redirect_no_cache() {
    header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
    header('Pragma: no-cache'); // HTTP 1.0.
    header('Expires: 0'); // Proxies.
}

add_action( 'plugins_loaded', '\better_random_redirect\load_textdomain' );
add_action( 'admin_menu', '\better_random_redirect\register_menu_page' );
add_action( 'admin_init', '\better_random_redirect\register_settings' );
add_action( 'template_redirect', '\better_random_redirect\do_redirect' );
