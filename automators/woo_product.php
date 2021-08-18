<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

class RUA_WooProduct_Member_Automator extends RUA_Member_Automator
{
    protected $type = 'trigger';

    public function __construct()
    {
        parent::__construct('woo_product', __('WooCommerce Purchase'));
    }

    /**
     * @inheritDoc
     */
    public function get_description()
    {
        return __('Add membership when user purchases');
    }

    /**
     * @inheritDoc
     */
    public function add_callback()
    {
        add_action( 'woocommerce_order_status_completed', function( $order_id, $order ) {
            if(empty($this->get_level_data()) || empty($order->get_user_id())) {
                return;
            }

            $user = rua_get_user($order->get_user_id());

            $product_ids = [];
            foreach ( $order->get_items() as $item ) {
                $product_ids[$item->get_product_id()] = 1;
            }

            foreach ($this->get_level_data() as $level_id => $level_product_ids) {
                foreach($level_product_ids as $level_product_id) {
                    if(isset($product_ids[$level_product_id])) {
                        if($user->add_level($level_id)) {
                            $order->add_order_note( sprintf('Restrict User Access membership created (Level ID: %s)', $level_id) );
                        }
                        break;
                    }
                }
            }

        }, 10, 2);
    }

    /**
     * @inheritDoc
     */
    public function get_content($selected_value = null)
    {
        $products = [];
        $query = new WP_Query([
            'post_type'              => 'product',
            'post_status'            => ['publish','private','future','draft'],
            'orderby'                => 'title',
            'order'                  => 'ASC',
            'paged'                  => 0,
            'posts_per_page'         => -1,
            'ignore_sticky_posts'    => true,
            'update_post_term_cache' => false,
            'suppress_filters'       => true,
            'no_found_rows'          => false,
        ]);
        foreach($query->posts as $product) {
            if($selected_value !== null && $selected_value !== $product->ID) {
                continue;
            }
            $products[$product->ID] = $product->post_title;
        }
        return $products;
    }
}
