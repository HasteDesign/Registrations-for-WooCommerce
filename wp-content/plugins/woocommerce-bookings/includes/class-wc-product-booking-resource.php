<?php

/**
 * Class for a booking product's resource type
 */
class WC_Product_Booking_Resource {

	private $resource;
	private $product_id;

	/**
	 * Constructor
	 */
	public function __construct( $post, $product_id = 0 ) {
		$this->resource   = $post;
		$this->product_id = $product_id;
	}

	/**
	 * __isset function.
	 *
	 * @access public
	 * @param string $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->resource->$key );
	}

	/**
	 * __get function.
	 *
	 * @access public
	 * @param string $key
	 * @return string
	 */
	public function __get( $key ) {
		return $this->resource->$key;
	}

	/**
	 * Return the ID
	 * @return int
	 */
	public function get_id() {
		return $this->resource->ID;
	}

	/**
	 * Get the title of the resource
	 * @return string
	 */
	public function get_title() {
		return $this->resource->post_title;
	}

	/**
	 * Return if we have qty at resource level
	 * @return boolean
	 */
	public function has_qty() {
		return $this->get_qty() !== '';
	}

	/**
	 * Return the quantity set at resource level
	 * @return int
	 */
	public function get_qty() {
		return get_post_meta( $this->get_id(), 'qty', true );
	}

	/**
	 * Return the base cost
	 * @return int|float
	 */
	public function get_base_cost() {
		$costs = get_post_meta( $this->product_id, '_resource_base_costs', true );
		$cost  = isset( $costs[ $this->get_id() ] ) ? $costs[ $this->get_id() ] : '';

		return $cost;
	}

	/**
	 * Return the block cost
	 * @return int|float
	 */
	public function get_block_cost() {
		$costs = get_post_meta( $this->product_id, '_resource_block_costs', true );
		$cost  = isset( $costs[ $this->get_id() ] ) ? $costs[ $this->get_id() ] : '';

		return $cost;
	}
}
