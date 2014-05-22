<?php
/************************************************************************
Plugin Name: Reco
Plugin URI: https://github.com/mbijon/wp_reco
Description: Ultra-simple, fast WordPress recommendations plugin
Version: 0.2.0
Author: Mike Bijon
Author URI: http://www.mbijon.com/
License: GPLv2


Copyright 2014, Mike Bijon, mikebijon@gmail.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License version 2, 
as published by the Free Software Foundation. 

You may NOT assume that you can use any other version of the GPL.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

The license for this software can likely be found here: 
http://www.gnu.org/licenses/gpl-2.0.html

************************************************************************/

if ( ! defined( 'DB_NAME' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}


if ( ! class_exists( 'WP_Reco' ) ) :

/**
 * @package Main
 */
class WP_Reco {
	/**
	 * Plugin settings/options values. Will store an array()
	 */
	public $reco_options;
	
	/**
	 * Post types allowed
	 */
	public $reco_post_types;
	
	/**
	 * Storage for in-class Filter
	 */
	public $reco_current_post_id;
	
	/**
	 * WP Error holder
	 */
	public $error = '';
	
	/**
	 * Version # of Network Pruner
	 */
	const RECO_PLUGIN_VERSION = '0.2.0';
	
	/**
	 * Transient expiration time: 24-hours
	 */
	const RECO_TRANSIENT_TIME = '86400';
	
	/**
	 * Posts per-page to show
	 *
	 * ### TO-DO: Add plugin setting and allow to be lower
	 */
	const RECO_MAX_ITEMS = 6;
	
	
	
	/**
	 * Constructor: Actions setup
	 */
	public function __construct() {
		
		add_action( 'init', array( $this, 'wp_init' ) );
		
		// Admin Settings page
		add_action( 'admin_menu', array( $this, 'admin_page_menu' ) );
		//add_action( 'admin_enqueue_scripts', array( $this, 'admin_page_statics' ) );
		
		// Show recommendations after post/page/cpt content
		add_action( 'wp_head', array( $this, 'show_recommendations' ) );
		
	}

	/**
	 * Plugin setup, post-construct. Fires on 'init' hook
	 */
	public function wp_init() {
		
		// Setup data/array once
		if ( ! is_array( $this->reco_options ) && false === ( get_transient( 'reco_options' ) ) )
			add_option( 'reco_options', array(), false, false );
			
		
		load_plugin_textdomain( 'wp-reco', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		if ( '' != WPLANG )
			setlocale( LC_ALL, WPLANG . '.UTF-8' );
		
		// Generate list of supported post types
		$args = array(
			'public' => true,
			'_builtin' => false
		);
		
		$post_names = get_post_types( $args );
		$post_names[] = 'post';
		$post_names[] = 'page';
		
		$this->reco_post_types = $post_names;
		
	/*
		// Custom scripts & styles
		wp_register_script(
			'reco-admin',
			plugins_url( 'js/assets/app.js', __FILE__ ),
			array( 'jquery' ),
			'reco_' . self::RECO_PLUGIN_VERSION,
			true
		);
		wp_register_style(
			'reco-css',
			plugins_url( 'css/assets/app.css', __FILE__ ),
			false,
			'reco_' . self::RECO_PLUGIN_VERSION,
			'all'
		);
	*/
		
	}
	
	
	private function is_reco_post( $post ) {
		
		if ( in_array( get_post_type( $post ), $this->reco_post_types ) ) {
			return true;
			
		} else {
			return false;
			
		}
	}
	
	
	private function get_current_post_id() {
		return $this->reco_current_post_id;
	}
	
	
	public function show_recommendations() {
		
		// Check for post type(s)
		if ( ! is_admin() && ! is_feed() ) {
			
			$post = get_queried_object();
			
			if ( self::is_reco_post( $post ) ) {
				
				$post_id = $post->ID;
				$this->reco_current_post_id = $post_id;
				
				add_filter(
					'the_content',
					function( $content ) use ( $post_id ) {
						return $this->output_recommendations( $content, $post_id );
					}
				);
			}
			
		}
		
	}
	
	
	public function output_recommendations( $content, $post_id ) {
		
		
		// ###
		// ### TO-DO ... Actual recommended-post algorithm
		// ###
		
		
		$recos = $this->_rateByCategory( $post_id );
		
		// Include admin page/HTML output from separate file
		require_once( dirname( __file__ ) . '/views/reco-list-template.php' );
		if ( ! empty( $after_content ) ) {
			$content .= $after_content;
		}
		
		return $content;
		
	}
	
	
	private function _rateByCategory( $post_id ) {
		
		$cat = get_the_category( $post_id );
		
		if ( ! $cat ) { return; }
		
		$args = array(
			'posts_per_page' => self::RECO_MAX_ITEMS,
			'orderby'=> 'rand',
			'post_status' => 'publish',
			'post__not_in' => array( $post_id ),
			'cat' => $cat[0]->term_id
		);
		
		// Cleanup the custom query
		wp_reset_postdata();
		
		// Generate Recommendations
		return get_posts( $args );
		
	}
	
	
	private function _rateRandom( $post_id = null ) {
		
		$args = array(
			'posts_per_page' => self::RECO_MAX_ITEMS,
			'orderby'=> 'rand',
			'post_status' => 'publish',
			'post__not_in' => array( $post_id )
		);
		
		// Cleanup the custom query
		wp_reset_postdata();
		
		// Generate Recommendations
		return get_posts( $args );
		
	}
	
	
	// Add submenu under Settings in WP-Admin		
	public function admin_page_menu() {
		
		$options_page = add_options_page(
			'WP Reco',
			'WP Reco',
			'manage_options',
			'reco-admin',
			array( $this, 'render_options_page' )
		);
		
		// Add contextual help menu in WP-admin
		//add_action("load-$admin_page", 'add_help_menu');
		
	}
	
	
	// For admin-only scripts
	public function admin_page_statics( $hook ) {
		
		if ( $hook == 'settings_page_reco-admin' ) {
			
			// JS
			wp_enqueue_script( 'reco-admin' );
			
			// CSS
			wp_enqueue_style( 'reco-css' );
			
		}
		
	}
	
	
	// HTML output for WP-Admin: Settings > WP Reco
	public function render_options_page() {
		
		// Security: Check that the user has the required capability 
		if ( ! current_user_can( 'manage_options' ) )
			wp_die( __( 'You do not have sufficient permissions to access this page. Please check your login and try again.', 'network-pruner' ) );
		
		// Security: Check WP nonce to prevent external use
		if ( isset( $_POST['reco'] ) && check_admin_referer( 'reco_update_admin_options', 'reco_admin_nonce' ) ) {
			
			// Save/Edit stuff here
			
		} elseif ( isset( $_POST['reco'] ) ) {
			
			wp_die( __( 'Invalid: Update without permissions. Please check your login and try again.', 'network-pruner' ) );
			
		}
		
		
		// Include admin page/HTML output from separate file
		require_once( dirname( __file__ ) . '/views/admin/reco-options-page.php' );
		
	}
	
	
	// Sanitization helper function for 'render_reco_media_option()'
	public function sanitize_reco_media_option( $reco_options ) {
		
		if ( isset( $_POST['reco'] ) ) {
			
			$valid = array();
			$valid['reco'] = intval( $_POST['reco'] );
			
			// !!! Must delete transient, because WP's built-in save routine won't
			delete_transient( 'reco_options' );
			
			return $valid;
			
		} else {
			
			add_settings_error(
				'Reco',
				'reco_settings_error',
				'Invalid Setting &#039;###&#039;: The setting must be ###',
				//print_r($reco_options), // Debugging
				'error'
			);
		
		}
		
	}
	
	
	// Save plugin option array & update transient
	public function update_plugin_settings() {
		
		$this->reco_options['reco'] = intval( $reco );
		update_option( 'reco_options', $this->reco_options );
		
		$this->foo = intval( $reco );
		set_transient( 'reco_options', intval( $reco ), self::RECO_TRANSIENT_TIME );
		
		return true;
		
	}
	
	
	// Contextual help menu
	public function add_help_menu() {
		
	}
	
	
	/**
	 * Deletes all plugin options & transient
	 */
	public function plugin_deactivation( $network_wide ) {
		
		delete_option( 'reco_options' );
		delete_transient( 'reco_options' );
		
	}
	
}
$wp_reco = new WP_Reco();


register_deactivation_hook( __FILE__, array( $wp_reco, 'plugin_deactivation' ) );


endif;