<?php

/*
Plugin Name: WordPress Server Auth
Description: Restricts WP Admin Access to a specific port, to allow Proper apache configuration
Author: Ingenuity Design
Version: 1
*/

//We just use redirects!

function wpsa_get_url_on_port( $port=8086 ) {
    $pageURL = 'http';
    if ($_SERVER['HTTPS'] == 'on') $pageURL .= 's';
    $pageURL .= "://";
    $pageURL .= sprintf("%s:%d%s", $_SERVER['SERVER_NAME'], $port, $_SERVER['REQUEST_URI']);
    return $pageURL;
}

if (is_admin() && !defined('DOING_AJAX')) {
    if ($_SERVER['SERVER_PORT'] != 8080) {
        //redirect to port 8080
        header(sprintf("Location: %s", wpsa_get_url_on_port()));
        exit;
    }
}

//ta da