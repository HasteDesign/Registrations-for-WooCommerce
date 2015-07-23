<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Bookings_Meta_Boxes {
	private $meta_boxes = array();

	public function __construct() {
		// Include the meta box classes
		$this->meta_boxes[] = include( 'class-wc-bookings-details-meta-box.php' );
		$this->meta_boxes[] = include( 'class-wc-bookings-save-meta-box.php' );
		$this->meta_boxes[] = include( 'class-wc-bookable-resource-details-meta-box.php' );

		// Set up required actions
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 1 );
	}

	/**
	 * Add meta boxes to edit product page
	 */
	public function add_meta_boxes() {
		foreach ( $this->meta_boxes as $meta_box ) {
			foreach ( $meta_box->post_types as $post_type ) {
				add_meta_box(
		            $meta_box->id,
		            $meta_box->title,
		            array( $meta_box, 'meta_box_inner' ),
		            $post_type,
		            $meta_box->context,
		            $meta_box->priority
		        );
			}
		}
	}
}

return new WC_Bookings_Meta_Boxes();