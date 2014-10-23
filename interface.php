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

class WPSA_SettingsInterface {
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $opts;

    /**
     * Start up
     */
    public function __construct()
    {
		if (!is_admin()) return;
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'Admin Security',
            'manage_options',
            self::_GRP,
            array( $this, 'create_admin_page' )
        );
    }

    const _GRP = 'wp_serverauth';
    const _ID = 'wp_serverauthops';

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $o = WPSA_Options::getInstance();
        $this->options = $o->toArray();
        $this->opts = $o;
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Server Security Settings</h2>
            <form method="post" enctype="multipart/form-data" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( self::_GRP );
                do_settings_sections( self::_GRP );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }


    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            self::_GRP, // Option group
            self::_ID, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'port', // ID
            'Listening Port Configuration', // Title
            array( $this, 'print_section_info' ), // Callback
            self::_GRP // Page
        );

        add_settings_field(
             'id_port', // ID
             'Port Number', // Title
             array( $this, 'port_number_callback' ), // Callback
             self::_GRP,
             'port' // Page
         );


        add_settings_field(
            'activate',
            'Activation',
            array( $this, 'activate_callback' ),
            self::_GRP,
            'port'
        );

        add_settings_field(
            'protection',
            'Protection Mode',
            array( $this, 'protection_mode_callback' ),
            self::_GRP,
            'port'
        );

        add_settings_field(
            'ssl_mode',
            'SSL Mode',
            array( $this, 'ssl_mode_callback' ),
            self::_GRP,
            'port'
        );

        add_settings_section(
            'ip', // ID
            'Individual Servers Configuration', // Title
            array( $this, 'print_section_servers' ), // Callback
            self::_GRP // Page
        );

        add_settings_field(
            'servers',
            'Server Configurations',
            array( $this, 'servers_callback' ),
            self::_GRP,
            'ip'
        );

    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $old = WPSA_Options::getInstance()->toArray();
        $new_input = array();
        if( isset( $input['id_port'] ) )
            $new_input['id_port'] = ( $input['id_port'] );

		    if( isset( $input['activate'] ) && in_array($input['activate'], array(0,1,2)))
            $new_input['activate'] = ( $input['activate'] );

        if( isset( $input['protection'] ) && in_array($input['protection'], array(0,1)))
            $new_input['protection'] = ( $input['protection'] );

        if (isset( $input['ssl_mode']) && in_array( $input['ssl_mode'], array(0,1)))
            $new_input['ssl_mode'] = ( $input['ssl_mode'] );

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print '<p>Please enter your configuration settings for the plugin. Below are some wordpress values that are being filtered.<br>If any of these seem wrong please report them to the plugin author</p><p>';
        print 'Wordpress site_url: ' . site_url() . '<br>';
        print 'Wordpress admin_url: ' . admin_url() . '<br>';
        print 'Current listening port: ' . $_SERVER['SERVER_PORT'] . '<br>';
        print 'Connected server IP: ' . $_SERVER['REMOTE_ADDR'];
        print '</p>';
    }

    public function print_section_servers() {
        print '<p>This section allows you to specify server specific configurations</p>';
        print 'Connected server IP: ' . $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Get the settings option array and print one of its values
     */

    public function port_number_callback() {
        printf(
            '<input type="text" id="id_port" name="'.self::_ID.'[id_port]" value="%s" />',
            isset( $this->options['id_port'] ) ? esc_attr( $this->options['id_port']) : ''
        );
    }

	public function activate_callback()
    {
        printf(
            'Yes <input type="radio" id="activate" name="'.self::_ID.'[activate]" value="1" %s/> / No <input type="radio" id="activate" name="'.self::_ID.'[activate]" value="0" %s/> / Testing Mode <input type="radio" id="activate" name="'.self::_ID.'[activate]" value="2" %s/>',
            (isset( $this->options['activate'])  && $this->options['activate'] == 1) ?'checked' : '',
			(!isset( $this->options['activate']) || $this->options['activate'] == 0) ?'checked' : '',
			(isset( $this->options['activate']) && $this->options['activate'] == 2) ?'checked' : ''
        );
    }

    public function protection_mode_callback() {
        printf(
            '404 <input type="radio" id="protection" name="'.self::_ID.'[protection]" value="1" %s/> / Redirect <input type="radio" id="protection" name="'.self::_ID.'[protection]" value="0" %s/>',
            (isset( $this->options['protection'])  && $this->options['protection'] == 1) ?'checked' : '',
      (!isset( $this->options['protection']) || $this->options['protection'] == 0) ?'checked' : ''
        );
    }
    
    public function ssl_mode_callback() {
        printf(
            'Yes <input type="radio" id="ssl_mode" name="'.self::_ID.'[ssl_mode]" value="1" %s/> / No <input type="radio" id="ssl_mode" name="'.self::_ID.'[ssl_mode]" value="0" %s/>',
            (isset( $this->options['ssl_mode'])  && $this->options['ssl_mode'] == 1) ?'checked' : '',
            (!isset( $this->options['ssl_mode']) || $this->options['ssl_mode'] == 0) ?'checked' : ''
        );
    }

    public function servers_callback() {

        //this is done for as many IPs as there are in addition to the current IP address
        $servers = $this->opts->getServers();
        print_r($servers);

        $def = $this->opts->getDefaultServer();
        $serv = $this->opts->getThisServer();
        ?>
        <h4>Default</h4>
        <?php

        $this->build_server_form( $def, true );
        $this->build_server_form( $serv, true );

        foreach( $servers as $server ) {
            $this->build_server_form( $server );
        }

    }

    private function build_server_form( $server, $override=false ) {

        // If override is false we want to throw out special ones
        if (!$override) {
            if ($server->isDefaultServer() || $server->isThisServer()) return;
        }

        echo $server->getIP();

    }

}
