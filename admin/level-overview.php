<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

final class RUA_Level_Overview {

	/**
	 * List table columns
	 * @var array
	 */
	protected $columns = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Add callbacks to actions queue
	 *
	 * @since 0.5
	 */
	protected function add_actions() {
		add_action('load-edit.php',
			array($this,'init_columns'));
		add_action('manage_'.RUA_App::TYPE_RESTRICT.'_posts_custom_column',
			array($this,'admin_column_rows'),10,2);
	}

	/**
	 * Add callbacks to filters queue
	 *
	 * @since 0.5
	 */
	protected function add_filters() {
		add_filter('request',
			array($this,'admin_column_orderby'));
		add_filter('manage_'.RUA_App::TYPE_RESTRICT.'_posts_columns',
			array($this,'admin_column_headers'),99);
		add_filter('manage_edit-'.RUA_App::TYPE_RESTRICT.'_sortable_columns',
			array($this,'admin_column_sortable_headers'));
	}

	/**
	 * Add admin column headers
	 *
	 * @since  0.5
	 * @param  array $columns 
	 * @return array          
	 */
	public function admin_column_headers($columns) {
		$new_columns = array();
		foreach ($this->columns as $id => $column) {
			$new_columns[$id] = isset($column['title']) ? $column['title'] : $columns[$id];
		}
		return $new_columns;
	}
		
	/**
	 * Make some columns sortable
	 *
	 * @since  0.5
	 * @param  array $columns 
	 * @return array
	 */
	public function admin_column_sortable_headers($columns) {
		foreach ($this->columns as $id => $column) {
			if($column['sortable']) {
				$columns[$id] = $id;
			}
		}
		return $columns;
	}
	
	/**
	 * Manage custom column sorting
	 *
	 * @since  0.5
	 * @param  array $vars 
	 * @return array 
	 */
	public function admin_column_orderby($vars) {
		$orderby = isset($vars['orderby']) ? $vars['orderby'] : '';
		if (isset($this->columns[$orderby]) && $this->columns[$orderby]['sortable']) {
			$vars = array_merge($vars, array(
				'meta_key' => RUA_App::META_PREFIX . $orderby,
				'orderby'  => 'meta_value'
			));
		}
		return $vars;
	}
	
	/**
	 * Render columns rows
	 *
	 * @since  0.5
	 * @param  string $column_name 
	 * @param  int $post_id
	 * @return void
	 */
	public function admin_column_rows($column_name, $post_id) {
		$method_name = 'column_'.$column_name;
		if(method_exists($this, $method_name)) {
			echo $this->$method_name($column_name, $post_id);
		}
	}

	/**
	 * Initiate column definitions
	 *
	 * @since  0.5
	 * @return void
	 */
	public function init_columns() {
		$screen = get_current_screen();
		if($screen->post_type != RUA_App::TYPE_RESTRICT) {
			return;
		}
		RUA_App::instance()->level_manager->populate_metadata();
		$this->columns = array(
			'cb'        => array(
				'sortable' => false
			),
			'title'     => array(
				'sortable' => false
			),
			'name'     => array(
				'title' => __('Name',RUA_App::DOMAIN),
				'sortable' => false
			),
			'role'    => array(
				"title" => __("Members",RUA_App::DOMAIN),
				"sortable" => true
			),
			'duration'   => array(
				"title" => RUA_App::instance()->level_manager->metadata()->get("duration")->get_title(),
				"sortable" => false
			),
			'caps'   => array(
				"title" => RUA_App::instance()->level_manager->metadata()->get("caps")->get_title(),
				"sortable" => false
			),
			'handle' => array(
				"title" => RUA_App::instance()->level_manager->metadata()->get('handle')->get_title(),
				"sortable" => true
			)
		);
	}

	/**
	 * Display slug column
	 *
	 * @since  0.6
	 * @param  string  $column_name
	 * @param  int     $post_id
	 * @return string
	 */
	protected function column_name($column_name,$post_id) {
		$post = get_post($post_id);
		return "<code>".$post->post_name."</code>";
	}

	/**
	 * Display role column
	 *
	 * @since  0.5
	 * @param  string  $column_name
	 * @param  int     $post_id
	 * @return string
	 */
	protected function column_role($column_name,$post_id) {
		$metadata = RUA_App::instance()->level_manager->metadata()->get($column_name);
		$retval = '';
		if($metadata) {
			$data = $metadata->get_data($post_id);
			if($data == '-1') {
				$users = get_users(array(
					'meta_key' => RUA_App::META_PREFIX.'level',
					'meta_value' => $post_id,
					'fields' => 'ID'
				));
				$retval = '<a href="post.php?post='.$post_id.'&action=edit#top#rua-members">'.count($users).'</a>';
			} else {
				$retval = $metadata->get_list_data($post_id,false);
			}
		}
		return $retval;
	}

	/**
	 * Display handle column
	 *
	 * @since  0.5
	 * @param  string  $column_name
	 * @param  int     $post_id
	 * @return string
	 */
	protected function column_handle($column_name,$post_id) {
		$metadata = RUA_App::instance()->level_manager->metadata()->get($column_name);
		$retval = '';
		if($metadata) {
			$data = $metadata->get_data($post_id);
			$retval = $metadata->get_list_data($post_id);
			if ($data != 2) {
				//TODO: with autocomplete, only fetch needed pages
				$page = RUA_App::instance()->level_manager->metadata()->get('page')->get_list_data($post_id);
				$retval .= ": " . ($page ? $page : '<span style="color:red;">' . __('Please update Page', RUA_App::DOMAIN) . '</span>');
			}
		}
		echo $retval;
	}

	/**
	 * Display duration column
	 *
	 * @since  0.5
	 * @param  string  $column_name
	 * @param  int     $post_id
	 * @return string
	 */
	protected function column_duration($column_name,$post_id) {
		$metadata = RUA_App::instance()->level_manager->metadata()->get($column_name);
		$retval = '';
		
		if($metadata) {
			$data = $metadata->get_data($post_id);
			if(isset($data["count"],$data["unit"]) && $data["count"]) {
				$retval = $this->_get_duration_text($data["count"],$data["unit"]);
			} else {
				$retval = __('Unlimited',RUA_App::DOMAIN);
			}
		}
		return esc_html($retval);
	}

	/**
	 * Display capabilities column
	 *
	 * @since  0.11
	 * @param  string  $column_name
	 * @param  int     $post_id
	 * @return string
	 */
	protected function column_caps($column_name,$post_id) {
		$counts = array(
			0 => 0,
			1 => 0
		);
		$metadata = RUA_App::instance()->level_manager->metadata()->get($column_name);
		$caps = $metadata->get_data($post_id);
		if($caps) {
			foreach ($caps as $cap) {
				$counts[$cap]++;
			}
		}
		return sprintf(__('%d permitted / %d denied',RUA_App::DOMAIN),$counts[1],$counts[0]);
	}

	/**
	 * Get localized duration
	 *
	 * @since  0.11
	 * @param  int     $duration
	 * @param  string  $unit
	 * @return string
	 */
	protected function _get_duration_text($duration,$unit) {
		$units = array(
			'day'   => _n_noop('%d day', '%d days'),
			'week'  => _n_noop('%d week', '%d weeks'),
			'month' => _n_noop('%d month', '%d months'),
			'year'  => _n_noop('%d year', '%d years')
		);
		return sprintf(translate_nooped_plural( $units[$unit], $duration, RUA_App::DOMAIN),$duration);
	}
}

//eol