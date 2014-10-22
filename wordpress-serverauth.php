<?php

/*
Plugin Name: WordPress Server Auth
Description: Restricts WP Admin Access to a specific port, to allow Proper apache configuration
Author: Stephen Parente
Version: 1
*/

//We just use redirects!
$GLOBALS['wpsa_bypass'] = false;

function wpsa_get_url_on_port( $port=8086 ) {
    $pageURL = 'http';
    if ($_SERVER['HTTPS'] == 'on') $pageURL .= 's';
    $pageURL .= "://";
    $pageURL .= sprintf("%s:%d%s", $_SERVER['SERVER_NAME'], $port, $_SERVER['REQUEST_URI']);
    return $pageURL;
}

define('PRIVILEGED_PORT', 8086);

function wpsa_enforce() {

    if (wpsa_should_protect()) {

        if (!wpsa_is_on_privileged_port()) {
            //redirect to port 8080
            //header(sprintf("Location: %s", wpsa_get_url_on_port(PRIVILEGED_PORT)));
            wpsa_throw_404();
        }
    }
}

function wpsa_is_on_privileged_port($port=8086) {
    return $_SERVER['SERVER_PORT'] == PRIVILEGED_PORT;
}

function wpsa_should_protect() {
    global $pagenow, $wpsa_bypass;
    if ($wpsa_bypass) return false;

    return (is_admin() && !defined('DOING_AJAX')) ||
      in_array( $pagenow, array( 'wp-login.php', 'wp-register.php' ));
}

function wpsa_throw_404() {
    global $wp_query;
    header("HTTP/1.0 404");
    if ($wp_query)
        $wp_query->set_404();
    require get_stylesheet_directory() . '/404.php';
    exit;
}



function wordpress_serverauth_activate() {
    global $wpsa_bypass;
    $wpsa_bypass = true; //dont do it on activation
}
register_activation_hook( __FILE__, 'wordpress_serverauth_activate' );

add_action('wp_loaded', 'wpsa_enforce');