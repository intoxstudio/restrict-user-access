<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2018 by Joachim Jensen
 */

/**
 * Based on {@link https://github.com/kucrut/wp-menu-item-custom-fields/blob/570f28d3bcf97b0c3d9a5c6e7ddf16ac19bbf805/walker-nav-menu-edit.php}
 */
class RUA_Walker_Nav_Menu_Edit extends Walker_Nav_Menu_Edit {

	/**
	 * Start the element output. Add action to get custom fields
	 *
	 * @since 0.11
	 * @param string $output
	 * @param object $item
	 * @param int    $depth
	 * @param array  $args
	 * @param int    $id
	 */
	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		$item_output = '';
		$item_id = esc_attr( $item->ID );
		parent::start_el( $item_output, $item, $depth, $args, $id );
		$output .= preg_replace(
			'/(?=<(?:p|fieldset) class="field-move)/',
			$this->_get_custom_fields( $item, $depth, $args, $item_id ),
			$item_output
		);
	}
	/**
	 * Get custom fields
	 *
	 * @since 0.11
	 * @param object  $item
	 * @param int     $depth
	 * @param array   $args
	 * @param int     $item_id
	 * @return string
	 */
	protected function _get_custom_fields( $item, $depth, $args, $item_id ) {
		ob_start();

		/**
		 * There seems to be consensus among plugin authors
		 * to use this action
		 *
		 * @see {@link https://github.com/kucrut/wp-menu-item-custom-fields}
		 * @see {@link https://wordpress.org/plugins/nav-menu-roles/}
		 * @see {@link https://wordpress.org/plugins/menu-items-visibility-control/}
		 * @see {@link https://wordpress.org/plugins/menu-icons/} << $id / $item_id is allways 0 (empty) !!
		 */
		do_action( 'wp_nav_menu_item_custom_fields', $item_id, $item, $depth, $args );
		return ob_get_clean();
	}
}

//