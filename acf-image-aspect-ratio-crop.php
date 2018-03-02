<?php

/*
Plugin Name: Advanced Custom Fields: Image Aspect Ratio Crop
Plugin URI: PLUGIN_URL
Description: ACF field that allows user to crop image to a specific aspect ratio
Version: 1.0.0
Author: Johannes Siipola
Author URI: https://siipo.la
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('npx_acf_plugin_image_aspect_ratio_crop') ) :

class npx_acf_plugin_image_aspect_ratio_crop {
	
	// vars
	var $settings;
	
	
	/*
	*  __construct
	*
	*  This function will setup the class functionality
	*
	*  @type	function
	*  @date	17/02/2016
	*  @since	1.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function __construct() {
		
		// settings
		// - these will be passed into the field class.
		$this->settings = array(
			'version'	=> '1.0.0',
			'url'		=> plugin_dir_url( __FILE__ ),
			'path'		=> plugin_dir_path( __FILE__ )
		);
		
		
		// set text domain
		// https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
		load_plugin_textdomain( 'acf-image-aspect-ratio-crop', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' );
		
		
		// include field
		add_action('acf/include_field_types', 	array($this, 'include_field_types')); // v5
		add_action('acf/register_fields', 		array($this, 'include_field_types')); // v4

        add_action( 'wp_ajax_acf_image_aspect_ratio_crop_crop', function() {
        	
            // WTF WordPress
            $post = array_map('stripslashes_deep', $_POST);

            $data = json_decode($post['data'], true);
            print_r($data);
            wp_die();
        } );
	}
	
	
	/*
	*  include_field_types
	*
	*  This function will include the field type class
	*
	*  @type	function
	*  @date	17/02/2016
	*  @since	1.0.0
	*
	*  @param	$version (int) major ACF version. Defaults to false
	*  @return	n/a
	*/
	
	function include_field_types( $version = false ) {
		
		// support empty $version
		if( !$version ) $version = 4;
		
		
		// include
		include_once('fields/class-npx-acf-field-image-aspect-ratio-crop-v' . $version . '.php');
		
	}
	
}


// initialize
new npx_acf_plugin_image_aspect_ratio_crop();


// class_exists check
endif;
	
?>
