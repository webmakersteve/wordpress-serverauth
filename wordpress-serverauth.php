<?php

/*
Plugin Name: WordPress Server Auth
Description: Restricts WP Admin Access to a specific port, to allow Proper apache configuration
Author: Stephen Parente
Version: 1.1
*/

class WPSA_Plugin {

  private $opts;
  private $bypass;
  private $port;

  public function __construct() {
    require('settings.php');
    WPSA_Options::setString( 'wp_serverauthops' );

    if (is_admin()) {
      $this->loadInterface();
    }

    $this->opts = WPSA_Options::getInstance();
    $this->port = $this->opts->getPort();

    register_activation_hook( __FILE__, array($this, 'activate'));
    if ($this->opts->isOn()) {
        add_filter( 'site_url' , array($this, 'filterSiteUrl') );
        add_action('wp_loaded', array($this, 'enforce') );
    }

  }

  private function loadInterface() {
    require('interface.php');
    return new WPSA_SettingsInterface();
  }

  private function getUrlOnPort($port=false,$path=true) {
    if (!$port) {
      $port = $this->port;
    }

    $pageURL = 'http';
    if ($_SERVER['HTTPS'] == 'on') $pageURL .= 's';
    $pageURL .= "://";
    $pageURL .= sprintf("%s:%d", $_SERVER['SERVER_NAME'], $port);
    if ($path) $pageURL .= $_SERVER['REQUEST_URI'];
    return $pageURL;

  }

  public function enforce() {

    if ($this->shouldProtect()) {

      if (!$this->isOnPrivilegedPort()) {
        //redirect to port 8080 for the redirection mechanism
        if ($this->opts->isRedirectMode()) {
          wp_redirect( $this->getUrlOnPort());
          exit;
        } elseif ($this->opts->is404Mode()) {
          //otherwise, for the 404 route, just throw a 404.
          return $this->throw404();
        }

        return false;

      } else {
        //we're on the privileged port so we're good
        return false;
      }

    } else {
      //we should not protect this place
      return false;
    }

  }

  private function isOnPrivilegedPort($port=false) {
    if (!$port) {
      $port = $this->port;
    }

    return $_SERVER['SERVER_PORT'] == $port; //dont need to proto this for LB configs

  }

  private function shouldProtect() {
    global $pagenow;
    if ($this->bypass) return false;

    return (is_admin() && !defined('DOING_AJAX')) ||
      in_array( $pagenow, array( 'wp-login.php', 'wp-register.php' ));

  }

  private function throw404() {
    global $wp_query;
    $this->header404();
    if ($wp_query)
        $wp_query->set_404();
    require get_404_template();
    exit;
  }

  private function header404() {
    header("HTTP/1.0 404 Not Found");
  }

  public function activate() {
    $this->bypass = true;

  }

  public function filterSiteUrl($url) {
    //check if we are on a weird port
    if ($port = $_SERVER['SERVER_PORT']) {
         if ($port == $this->port) {
             return $this->getUrlOnPort($port, false);
             //we just want to get the current url instead
         }
    }
    return $url;
  }

}

$WPSA_Main = new WPSA_Plugin();
