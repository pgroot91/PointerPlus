<?php

/**
 * Class PointerPlus based on QL_Pointer to facilitate creation of WP Pointers
 * @author QueryLoop
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class PointerPlus {

	/**
	 * Prefix strings like styles, scripts and pointers IDs
	 * @var string
	 */
	var $prefix = 'pointerplus';
	var $pointers = array();

	function __construct( $args = array() ) {
		if ( isset( $args[ 'prefix' ] ) ) {
			$this->prefix = $args[ 'prefix' ];
		}
		add_action( 'current_screen', array( $this, 'maybe_add_pointers' ) );
	}

	/**
	 * Set pointers and its options
	 *
	 * @since 1.0.0
	 */
	function initial_pointers() {
		$defaults = array(
			'class' => 'pointerplus',
			'width' => 300, //only fixed value
			'align' => 'middle',
			'edge' => 'left',
			'post_type' => array(),
			'pages' => 'all'
		);
		$screen = get_current_screen();
		$current_post_type = isset( $screen->post_type ) ? $screen->post_type : false;
		$search_pt = false;

		$pointers = apply_filters( 'pointerplus_list', array(
				// Pointers are added through the 'initial_pointerplus' filter
				), $this->prefix );

		foreach ( $pointers as $key => $pointer ) {
			$pointers[ $key ] = wp_parse_args( $pointer, $defaults );
			$search_pt = false;
			
			$pointers[ $key ][ 'post_type' ] = array_filter($pointers[ $key ][ 'post_type' ]);
			if ( !empty( $pointers[ $key ][ 'post_type' ] ) ) {
				if ( !empty( $current_post_type ) ) {
					if ( is_array( $pointers[ $key ][ 'post_type' ] ) ) {
						// Search the post_type
						foreach ( $pointers[ $key ][ 'post_type' ] as $value ) {
							if ( $value === $current_post_type ) {
								$search_pt = true;
							}
						}
						if ( $search_pt === false ) {
							unset( $pointers[ $key ] );
						}
					} else {
						new WP_Error( 'broke', __( 'PointerPlus Error: post_type is not an array!' ) );
					}
				//If not in CPT view remove all the pointers with post_type
				} else {
					unset( $pointers[ $key ] );
				}
			}

			if ( $pointers[ $key ][ 'pages' ] ) {
				
			}
		}
		
			print_r($pointers[ $key ]);
		return $pointers;
	}

	/**
	 * Check that pointers haven't been dismissed already. If there are pointers to show, enqueue assets.
	 */
	function maybe_add_pointers() {

		// Get default pointers that we want to create
		$default_keys = $this->initial_pointers();

		// Get pointers dismissed by user
		$dismissed = explode( ',', get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

		// Check that our pointers haven't been dismissed already
		$diff = array_diff_key( $default_keys, array_combine( $dismissed, $dismissed ) );

		// If we have some pointers to show, save them and start enqueuing assets to display them
		if ( !empty( $diff ) ) {
			$this->pointers = $diff;
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ) );
		}
	}

	/**
	 * Enqueue pointer styles and scripts to display them.
	 *
	 * @since 1.0.0
	 */
	function admin_enqueue_assets() {

		$base_url = plugins_url( '', __FILE__ );

		wp_enqueue_style( $this->prefix, $base_url . '/pointerplus.css', array( 'wp-pointer' ) );

		wp_enqueue_script( $this->prefix, $base_url . '/pointerplus.js', array( 'wp-pointer' ) );

		wp_localize_script( $this->prefix, 'pointerplus', apply_filters( 'pointerplus_js_vars', $this->pointers ) );
	}

}
