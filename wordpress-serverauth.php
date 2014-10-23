<?php

/*
Plugin Name: WordPress Server Auth
Description: Restricts WP Admin Access to a specific port, to allow Proper apache configuration
Author: Stephen Parente
Version: 1
*/

//We just use redirects!
$GLOBALS['wpsa_bypass'] = false;
define('WPSA_PRIVILEDGED_PORT', 8080);

function wpsa_get_url_on_port( $port=false, $path=true ) {
    if (!$port) $port = WPSA_PRIVILEGED_PORT;
    
    $pageURL = 'http';
    if ($_SERVER['HTTPS'] == 'on') $pageURL .= 's';
    $pageURL .= "://";
    $pageURL .= sprintf("%s:%d", $_SERVER['SERVER_NAME'], $port);
    if ($path) $pageURL .= $_SERVER['REQUEST_URI'];
    return $pageURL;
}

function wpsa_enforce() {

    if (wpsa_should_protect()) {

        if (!wpsa_is_on_privileged_port()) {
            //redirect to port 8080 for the redirection mechanism
            //header(sprintf("Location: %s", wpsa_get_url_on_port(WPSA_PRIVILEDGED_PORT)));
            
            //otherwise, for the 404 route, just throw a 404.
            wpsa_throw_404();
        }
    }
}

function wpsa_is_on_privileged_port($port=false) {
    if (!$port) $port = WPSA_PRIVILEGED_PORT;
    return $_SERVER['SERVER_PORT'] == $port;
}

function wpsa_should_protect() {
    global $pagenow, $wpsa_bypass;
    if ($wpsa_bypass) return false;

    return (is_admin() && !defined('DOING_AJAX')) ||
      in_array( $pagenow, array( 'wp-login.php', 'wp-register.php' ));
}

function wpsa_throw_404() {
    global $wp_query;
    wpsa_404_header();
    if ($wp_query)
        $wp_query->set_404();
    require get_404_template();
    exit;
}

function wpsa_404_header() {
	header("HTTP/1.0 404 Not Found");
}

function wordpress_serverauth_activate() {
    global $wpsa_bypass;
    $wpsa_bypass = true; //dont do it on activation
}
register_activation_hook( __FILE__, 'wordpress_serverauth_activate' );


function wpsa_site_url($url) {
   //check if we are on a weird port
   if ($port = $_SERVER['SERVER_PORT']) {
        if ($port == WPSA_PRIVILEGED_PORT) {
                return wpsa_get_url_on_port($port, false);
                //we just want to get the current url instead
        }
   }
   return $url;
}

add_filter( 'site_url' , 'wpsa_site_url' );

add_action('wp_loaded', 'wpsa_enforce');
