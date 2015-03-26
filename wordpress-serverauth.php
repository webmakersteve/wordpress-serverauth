<?php

/*
Plugin Name: WordPress Server Auth
Description: Restricts WP Admin Access to a specific port, to allow Proper apache configuration
Author: Stephen Parente
Version: 1.2
*/

class WPSA_Plugin {

  private $opts;
  private $bypass;
  private $port;
    private $listening_port;

  public function __construct() {
    require('settings.php');
    WPSA_Options::setString( 'wp_serverauthops' );

    if (is_admin()) {
      $this->loadInterface();
    }

    $this->opts = WPSA_Options::getInstance();
    $this->port = $this->opts->getPort();
    $this->hostname = $this->opts->getHostname();
    $this->listening_port = $_SERVER['SERVER_PORT'];

    register_activation_hook( __FILE__, array($this, 'activate'));

    if ($this->opts->isOn()) {
        add_filter( 'site_url' , array($this, 'filterSiteUrl') );
        add_action('wp_loaded', array($this, 'enforce') );
        add_filter( 'redirect_canonical', array($this, 'filterRedirect'), 10, 2);
        remove_filter('template_redirect', 'redirect_canonical');
    }

  }

    public function filterRedirect($redirect_url, $requested_url) {
        //return false; //deny it
        return $redirect_url; //allow it
    }

  private function loadInterface() {
    require('interface.php');
    return new WPSA_SettingsInterface();
  }

  private function getUrlOnPort($url, $port=false,$path=true) {
    if (!$port) {
      $port = $this->port;
    }

      $parsed = parse_url($url);
      $parsed['port'] = $port;
      if (is_ssl() && $this->opts->isSSLOn()) {
          $parsed['scheme'] = 'https';
      }

      $pageURL = $this->buildUrl($parsed, true);

      return $pageURL;

  }

  public function enforce() {

    if ($this->shouldProtect()) {

      if ( ( $this->opts->isPrivilegedPortMode() && !$this->isOnPrivilegedPort()) || 
           ( $this->opts->isPrivilegedHostnameMode() && !$this->isOnPrivilegedHostname()) ) {
          //redirect to port 8080 for the redirection mechanism

          if ($this->opts->is404Mode()) {
              //otherwise, for the 404 route, just throw a 404.
              return $this->throw404();
          } else {
            if ($this->opts->isPrivilegedPortMode()) {
              wp_redirect( $this->getUrlOnPort(admin_url(), $this->port));
            } else {
              wp_redirect( admin_url() );
            }
            exit;
          }

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

  private function isOnPrivilegedHostname($hostname=false) {
    if (!$hostname) {
      $hostname = $this->hostname;
    }

    return $_SERVER['SERVER_NAME'] == $hostname;
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
    $parsed = parse_url($url);
    
    if ($_SERVER['SERVER_NAME'] == $this->hostname)
    	$parsed['host'] = $this->hostname;
    $url = $this->buildUrl($parsed);
    if ($port = $_SERVER['SERVER_PORT']) {
         if ($port == $this->port) {
             return $this->getUrlOnPort($url, $port, false);
             //we just want to get the current url instead
         }
    }
    return $url;
  }

    private function buildUrl( $parts, $encode=true ) {
        if ( $encode )
        {
            if ( isset( $parts['user'] ) )
                $parts['user']     = rawurlencode( $parts['user'] );
            if ( isset( $parts['pass'] ) )
                $parts['pass']     = rawurlencode( $parts['pass'] );
            if ( isset( $parts['host'] ) &&
                !preg_match( '!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'] ) )
                $parts['host']     = rawurlencode( $parts['host'] );
            if ( !empty( $parts['path'] ) )
                $parts['path']     = preg_replace( '!%2F!ui', '/',
                    rawurlencode( $parts['path'] ) );
            if ( isset( $parts['query'] ) )
                $parts['query']    = rawurlencode( $parts['query'] );
            if ( isset( $parts['fragment'] ) )
                $parts['fragment'] = rawurlencode( $parts['fragment'] );
        }

        $url = '';
        if ( !empty( $parts['scheme'] ) )
            $url .= $parts['scheme'] . ':';
        if ( isset( $parts['host'] ) )
        {
            $url .= '//';
            if ( isset( $parts['user'] ) )
            {
                $url .= $parts['user'];
                if ( isset( $parts['pass'] ) )
                    $url .= ':' . $parts['pass'];
                $url .= '@';
            }
            if ( preg_match( '!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'] ) )
                $url .= '[' . $parts['host'] . ']'; // IPv6
            else
                $url .= $parts['host'];             // IPv4 or name
            if ( isset( $parts['port'] ) )
                $url .= ':' . $parts['port'];
            if ( !empty( $parts['path'] ) && $parts['path'][0] != '/' )
                $url .= '/';
        }
        if ( !empty( $parts['path'] ) )
            $url .= $parts['path'];
        if ( isset( $parts['query'] ) )
            $url .= '?' . $parts['query'];
        if ( isset( $parts['fragment'] ) )
            $url .= '#' . $parts['fragment'];
        return $url;
    }

}

$WPSA_Main = new WPSA_Plugin();
