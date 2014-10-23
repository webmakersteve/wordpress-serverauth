<?php

/**
 * Ingenuity SSO Plugin
 *
 * Plugin for SSO Wordpress integration for eBay and PayPal sites.
 *
 * Copyright (c) 2014, Ingenuity Design
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This code is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License version 2 only, as
 * published by the Free Software Foundation.
 *
 * This code is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
 * version 2 for more details (a copy is included in the LICENSE file that
 * accompanied this code).
 *
 * You should have received a copy of the GNU General Public License version
 * 2 along with this work; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @package IngenuitySSO
 * @namespace Ingenuity
 *
 */

/**
 * Ingenuity Plugin class just adds some update filtering to every plugin in the suite. Checks itself
 *
 * Plugin for SSO Wordpress integration for eBay and PayPal sites.
 *
 * @package IngenuitySSO
 * @namespace Ingenuity
 * @since 1.1.0
 *
 */

class WPSA_Options {

    /**
     * Holds the singleton instance of the class.
     */

    private static $instance = NULL;

    /**
     * Holds the RAW array data given to us by Wordpress' API
     */

    private $options;

    /**
     * Gets instance. Replaces default constructor
     */

    public static function getInstance() {
        if (self::$instance == NULL) self::$instance = new WPSA_Options();
        return self::$instance;
    }

    /**
     * Made private so it cannot be accessed outside of the class.
     */

    private function __construct() {
        $this->options = get_option( self::$OptionIdentifier );
    }

    /**
     * Identifier to be used to get the data from Wordpress
     */

    private static $OptionIdentifier = NULL;

    /**
     * Passes back the array of options to bypass the abstraction
     *
     * @return Array Array of options
     */

    public function toArray() {
        return $this->options;
    }

    /**
     * Sets error message
     */

    public static function setString( $string ) {
        if (self::$OptionIdentifier === NULL)
            self::$OptionIdentifier = $string;
        else throw new OptionsSetAfterInitializationError();
    }

    /**
     * Checks if an option is set based on the getter
     *
     * Basically just asserts NULL
     *
     * @param string The Option
     * @return bool True if it is not null, false if it is null.
     */

    private function assertNull( $option ) {
        if ($option === NULL) return true;
        else return false;
    }

    const DEFAULT_PORT = 8080;

    /**
     * Getter for port option.
     *
     * @return string|NULL
     */

    public function getPort() {
        if (isset($this->options['id_port'])) {
            if (strlen($this->options['id_port']) < 2) return self::DEFAULT_PORT;
            else return $this->options['id_port'];
        } else return self::DEFAULT_PORT;
    }

    /**
     * Checks if the site should redirect invalid requests.
     * @return bool True if site is in redirect mode. False if not
     */

    public function isRedirectMode() {
        if (isset($this->options['protection'])) {
          return $this->options['protection'] == 0;
        }
        return false;
    }

    /**
     * Checks if the site should obfuscate invalid requests.
     * @return bool True if site is in redirect mode. False if not
     */

    public function is404Mode() {
        if (isset($this->options['protection'])) {
          return $this->options['protection'] == 1;
        }
        return false;
    }

    /**
     * Checks if the site is in testing mode.
     * @return bool True if site is in testing mode. False if not
     */

    public function isTestingMode() {
        if (isset($this->options['activate']) && $this->options['activate'] == 2) return true;
        return false;
    }

    /**
     * Checks if site SSO protection is off
     * @return bool True if site is unprotected, false if it is protected.
     */

    public function isOff() {
        if (!isset($this->options['activate']) || $this->options['activate'] == 0) return true;
        return false;
    }

    /**
     * Checks if SSO protection is fully on.
     * @return bool True if SSO protection is fully on, false if it is either testing mode or off
     */

    public function isOn() {
        if (isset($this->options['activate']) && $this->options['activate'] == 1) return true;
        return false;
    }

    /**
     * Checks if the listening port wants SSL
     * @return bool True if port is HTTPS, false otherwise
     */

    public function isSSLOn() {
        if (isset($this->options['ssl_mode']) && $this->options['ssl_mode'] == 1) return true;
        return false;
    }

    /**
     * Checks if the listening port does not want SSL
     * @return bool True if port is HTTP, false otherwise
     */

    public function isSSLOff() {
        return !$this->isSSLOn();
    }

    public function getServers() {
        if (isset($this->options['servers']) && $this->options['servers']) {
            $servers = $this->options['servers'];
            $ret = array();
            foreach ($servers as $server) {
                $ret[$server['ip']] = new WPSA_Server_Options($server);
            }
            return $ret;
        } else return array();
    }

    public function getThisServer() {
        return $this->getServer($_SERVER['REMOTE_ADDR']);
    }

    public function getDefaultServer() {
        $servers = $this->getServers();
        if ($servers['*']) return $servers['*'];
        else return new WPSA_Server_Options();
    }

    public function getServer( $ip ) {
        $servers = $this->getServers();
        return $servers[$ip] ? new WPSA_Server_Options($servers[$ip]) : new WPSA_Server_Options();
    }

}

class WPSA_Server_Options {

    private $ip;
    private $on;
    private $allow_admin;

    public function __construct($data=null) {
        $defaults = array(
            'ip' => '*',
            'on' => true,
            'allow_admin' => true
        );
        foreach($data as $k=>$v) {
            $defaults[$k] = $v;
        }

        $this->ip = $defaults['ip'];
        $this->on = $defaults['on'];
        $this->allow_admin = $defaults['allow_admin'];

    }

    public function isAdminAllowed() {
        return $this->allow_admin == true;
    }

    public function isOn() {
        return $this->on == true;
    }

    public function getIP() {
        return $this->ip;
    }

    public function isThisServer() {
        return $this->ip == $_SERVER['REMOTE_ADDR'];
    }

    public function isDefaultServer() {
        return $this->ip == '*';
    }
}
