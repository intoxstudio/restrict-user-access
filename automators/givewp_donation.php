<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

class RUA_GiveWP_Donation_Member_Automator extends RUA_Member_Automator
{
    protected $type = 'trigger';
    protected $name = 'givewp_donation';

    public function __construct()
    {
        parent::__construct(__('GiveWP Donation', 'restrict-user-access'));
    }

    /**
     * @inheritDoc
     */
    public function get_description()
    {
        return __('Add membership when user donates to', 'restrict-user-access');
    }

    /**
     * @inheritDoc
     */
    public function can_enable()
    {
        return defined('GIVE_VERSION');
    }

    /**
     * @inheritDoc
     */
    public function add_callback()
    {
        add_action('give_update_payment_status', function ($payment_id, $status, $old_status) {
            if ($status !== 'publish') {
                return;
            }

            /** @var Give_Payment $payment */
            $payment = new Give_Payment($payment_id);

            $user_id = $payment->user_id;
            if (empty($user_id)) {
                return;
            }

            $user = rua_get_user($user_id);
            $form_id = $payment->form_id;

            foreach ($this->get_level_data() as $level_id => $level_form_ids) {
                foreach ($level_form_ids as $level_form_id) {
                    if ($level_form_id === $form_id) {
                        if ($user->add_level($level_id)) {
                            $payment->add_note(sprintf('Restrict User Access membership created (Level ID: %s)', $level_id));
                        }
                        break;
                    }
                }
            }
        }, 10, 3);
    }

    /**
     * @inheritDoc
     */
    public function search_content($term, $page, $limit)
    {
        $params = [
            'post_type'              => 'give_forms',
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
