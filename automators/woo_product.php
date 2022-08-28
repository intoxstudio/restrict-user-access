<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

class RUA_WooProduct_Member_Automator extends RUA_Member_Automator
{
    protected $type = 'trigger';
    protected $name = 'woo_product';

    public function __construct()
    {
        parent::__construct(__('WooCommerce Purchase'));
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
    public function can_enable()
    {
        return defined('WC_VERSION');
    }

    /**
     * @inheritDoc
     */
    public function add_callback()
    {
        add_action('woocommerce_order_status_completed', function ($order_id, $order) {
            if (empty($order->get_user_id())) {
                return;
            }

            $user = rua_get_user($order->get_user_id());

            $product_ids = [];
            foreach ($order->get_items() as $item) {
                $product_ids[$item->get_product_id()] = 1;
            }

            foreach ($this->get_level_data() as $level_id => $level_product_ids) {
                foreach ($level_product_ids as $level_product_id) {
                    if (isset($product_ids[$level_product_id])) {
                        if ($user->add_level($level_id)) {
                            $order->add_order_note(sprintf('Restrict User Access membership created (Level ID: %s)', $level_id));
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
    public function search_content($term, $page, $limit)
    {
        $params = [
            'post_type'              => 'product',
            'post_status'            => ['publish','private','future','draft'],
            'orderby'                => 'title',
            'order'                  => 'ASC',
            'paged'                  => $page,
            'posts_per_page'         => $limit,
            'ignore_sticky_posts'    => true,
            'update_post_term_cache' => false,
            'suppress_filters'       => true,
            'no_found_rows'          => false
        ];
        if (!empty($term)) {
            $params['s'] = $term;
        }

        $products = [];
        $query = new WP_Query($params);
        foreach ($query->posts as $product) {
            $products[$product->ID] = $product->post_title;
        }
        return $products;
    }

    /**
     * @inheritDoc
     */
    public function get_content_title($selected_value)
    {
        $post = get_post($selected_value);
        if ($post instanceof WP_Post) {
            return $post->post_title;
        }
        return null;
    }
}
