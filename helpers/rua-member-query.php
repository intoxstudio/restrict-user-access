<?php

defined('ABSPATH') || exit;

if (!class_exists('WP_Comment_Query')) {
    require_once ABSPATH . 'wp-includes/class-wp-comment-query.php';
}

#[AllowDynamicProperties]
class RUA_Member_Query extends WP_Comment_Query {

	/**
	 * Parse arguments passed to the comment query with default query parameters.
	 *
	 * @since 4.2.0 Extracted from WP_Comment_Query::query().
	 *
	 * @param string|array $query WP_Comment_Query arguments. See WP_Comment_Query::__construct() for accepted arguments.
	 */
	public function parse_query( $query = '' ) {
		if ( empty( $query ) ) {
			$query = $this->query_vars;
		}

		$this->query_vars = wp_parse_args( $query, $this->query_var_defaults );

		/**
		 * Fires after the comment query vars have been parsed.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Comment_Query $query The WP_Comment_Query instance (passed by reference).
		 */
		//do_action_ref_array( 'parse_comment_query', array( &$this ) );
	}

	/**
	 * Get a list of comments matching the query vars.
	 *
	 * @since 4.2.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return int|int[]|WP_Comment[] List of comments or number of found comments if `$count` argument is true.
	 */
	public function get_comments() {
		global $wpdb;

		$this->parse_query();

		// Parse meta query.
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars( $this->query_vars );

		/**
		 * Fires before comments are retrieved.
		 *
		 * @since 3.1.0
		 *
		 * @param WP_Comment_Query $query Current instance of WP_Comment_Query (passed by reference).
		 */
		//do_action_ref_array( 'pre_get_comments', array( &$this ) );

		// Reparse query vars, in case they were modified in a 'pre_get_comments' callback.
		$this->meta_query->parse_query_vars( $this->query_vars );
		if ( ! empty( $this->meta_query->queries ) ) {
			$this->meta_query_clauses = $this->meta_query->get_sql( 'comment', $wpdb->comments, 'comment_ID', $this );
		}

		$comment_data = null;

		/**
		 * Filters the comments data before the query takes place.
		 *
		 * Return a non-null value to bypass WordPress' default comment queries.
		 *
		 * The expected return type from this filter depends on the value passed
		 * in the request query vars:
		 * - When `$this->query_vars['count']` is set, the filter should return
		 *   the comment count as an integer.
		 * - When `'ids' === $this->query_vars['fields']`, the filter should return
		 *   an array of comment IDs.
		 * - Otherwise the filter should return an array of WP_Comment objects.
		 *
		 * Note that if the filter returns an array of comment data, it will be assigned
		 * to the `comments` property of the current WP_Comment_Query instance.
		 *
		 * Filtering functions that require pagination information are encouraged to set
		 * the `found_comments` and `max_num_pages` properties of the WP_Comment_Query object,
		 * passed to the filter by reference. If WP_Comment_Query does not perform a database
		 * query, it will not have enough information to generate these values itself.
		 *
		 * @since 5.3.0
		 * @since 5.6.0 The returned array of comment data is assigned to the `comments` property
		 *              of the current WP_Comment_Query instance.
		 *
		 * @param array|int|null   $comment_data Return an array of comment data to short-circuit WP's comment query,
		 *                                       the comment count as an integer if `$this->query_vars['count']` is set,
		 *                                       or null to allow WP to run its normal queries.
		 * @param WP_Comment_Query $query        The WP_Comment_Query instance, passed by reference.
		 */
		//$comment_data = apply_filters_ref_array( 'comments_pre_query', array( $comment_data, &$this ) );

		if ( null !== $comment_data ) {
			if ( is_array( $comment_data ) && ! $this->query_vars['count'] ) {
				$this->comments = $comment_data;
			}

			return $comment_data;
		}

		/*
		 * Only use the args defined in the query_var_defaults to compute the key,
		 * but ignore 'fields', 'update_comment_meta_cache', 'update_comment_post_cache' which does not affect query results.
		 */
		$_args = wp_array_slice_assoc( $this->query_vars, array_keys( $this->query_var_defaults ) );
		unset( $_args['fields'], $_args['update_comment_meta_cache'], $_args['update_comment_post_cache'] );

		$key          = md5( serialize( $_args ) );
		$last_changed = wp_cache_get_last_changed( 'comment' );

		$cache_key   = "get_comments:$key:$last_changed";
		$cache_value = wp_cache_get( $cache_key, 'comment-queries' );
		if ( false === $cache_value ) {
			$comment_ids = $this->get_comment_ids();
			if ( $comment_ids ) {
				$this->set_found_comments();
			}

			$cache_value = array(
				'comment_ids'    => $comment_ids,
				'found_comments' => $this->found_comments,
			);
			wp_cache_add( $cache_key, $cache_value, 'comment-queries' );
		} else {
			$comment_ids          = $cache_value['comment_ids'];
			$this->found_comments = $cache_value['found_comments'];
		}

		if ( $this->found_comments && $this->query_vars['number'] ) {
			$this->max_num_pages = (int) ceil( $this->found_comments / $this->query_vars['number'] );
		}

		// If querying for a count only, there's nothing more to do.
		if ( $this->query_vars['count'] ) {
			// $comment_ids is actually a count in this case.
			return (int) $comment_ids;
		}

		$comment_ids = array_map( 'intval', $comment_ids );

		if ( $this->query_vars['update_comment_meta_cache'] ) {
			wp_lazyload_comment_meta( $comment_ids );
		}

		if ( 'ids' === $this->query_vars['fields'] ) {
			$this->comments = $comment_ids;
			return $this->comments;
		}

		_prime_comment_caches( $comment_ids, false );

		// Fetch full comment objects from the primed cache.
		$_comments = array();
		foreach ( $comment_ids as $comment_id ) {
			$_comment = get_comment( $comment_id );
			if ( $_comment ) {
				$_comments[] = $_comment;
			}
		}

		// Prime comment post caches.
		if ( $this->query_vars['update_comment_post_cache'] ) {
			$comment_post_ids = array();
			foreach ( $_comments as $_comment ) {
				$comment_post_ids[] = $_comment->comment_post_ID;
			}

			_prime_post_caches( $comment_post_ids, false, false );
		}

		/**
		 * Filters the comment query results.
		 *
		 * @since 3.1.0
		 *
		 * @param WP_Comment[]     $_comments An array of comments.
		 * @param WP_Comment_Query $query     Current instance of WP_Comment_Query (passed by reference).
		 */
		//$_comments = apply_filters_ref_array( 'the_comments', array( $_comments, &$this ) );

		// Convert to WP_Comment instances.
		$comments = array_map( 'get_comment', $_comments );

		if ( $this->query_vars['hierarchical'] ) {
			$comments = $this->fill_descendants( $comments );
		}

		$this->comments = $comments;
		return $this->comments;
	}

	/**
	 * Used internally to get a list of comment IDs matching the query vars.
	 *
	 * @since 4.4.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return int|array A single count of comment IDs if a count query. An array of comment IDs if a full query.
	 */
	protected function get_comment_ids() {
		global $wpdb;

		// Assemble clauses related to 'comment_approved'.
		$approved_clauses = array();

		// 'status' accepts an array or a comma-separated string.
		$status_clauses = array();
		$statuses       = wp_parse_list( $this->query_vars['status'] );

		// Empty 'status' should be interpreted as 'all'.
		if ( empty( $statuses ) ) {
			$statuses = array( 'all' );
		}

		// 'any' overrides other statuses.
		if ( ! in_array( 'any', $statuses, true ) ) {
			foreach ( $statuses as $status ) {
				switch ( $status ) {
					case 'hold':
						$status_clauses[] = "comment_approved = '0'";
						break;

					case 'approve':
						$status_clauses[] = "comment_approved = '1'";
						break;

					case 'all':
					case '':
						$status_clauses[] = "( comment_approved = '0' OR comment_approved = '1' )";
						break;

					default:
						$status_clauses[] = $wpdb->prepare( 'comment_approved = %s', $status );
						break;
				}
			}

			if ( ! empty( $status_clauses ) ) {
				$approved_clauses[] = '( ' . implode( ' OR ', $status_clauses ) . ' )';
			}
		}

		// User IDs or emails whose unapproved comments are included, regardless of $status.
		if ( ! empty( $this->query_vars['include_unapproved'] ) ) {
			$include_unapproved = wp_parse_list( $this->query_vars['include_unapproved'] );

			foreach ( $include_unapproved as $unapproved_identifier ) {
				// Numeric values are assumed to be user IDs.
				if ( is_numeric( $unapproved_identifier ) ) {
					$approved_clauses[] = $wpdb->prepare( "( user_id = %d AND comment_approved = '0' )", $unapproved_identifier );
				} else {
					// Otherwise we match against email addresses.
					if ( ! empty( $_GET['unapproved'] ) && ! empty( $_GET['moderation-hash'] ) ) {
						// Only include requested comment.
						$approved_clauses[] = $wpdb->prepare( "( comment_author_email = %s AND comment_approved = '0' AND {$wpdb->comments}.comment_ID = %d )", $unapproved_identifier, (int) $_GET['unapproved'] );
					} else {
						// Include all of the author's unapproved comments.
						$approved_clauses[] = $wpdb->prepare( "( comment_author_email = %s AND comment_approved = '0' )", $unapproved_identifier );
					}
				}
			}
		}

		// Collapse comment_approved clauses into a single OR-separated clause.
		if ( ! empty( $approved_clauses ) ) {
			if ( 1 === count( $approved_clauses ) ) {
				$this->sql_clauses['where']['approved'] = $approved_clauses[0];
			} else {
				$this->sql_clauses['where']['approved'] = '( ' . implode( ' OR ', $approved_clauses ) . ' )';
			}
		}

		$order = ( 'ASC' === strtoupper( $this->query_vars['order'] ) ) ? 'ASC' : 'DESC';

		// Disable ORDER BY with 'none', an empty array, or boolean false.
		if ( in_array( $this->query_vars['orderby'], array( 'none', array(), false ), true ) ) {
			$orderby = '';
		} elseif ( ! empty( $this->query_vars['orderby'] ) ) {
			$ordersby = is_array( $this->query_vars['orderby'] ) ?
				$this->query_vars['orderby'] :
				preg_split( '/[,\s]/', $this->query_vars['orderby'] );

			$orderby_array            = array();
			$found_orderby_comment_id = false;
			foreach ( $ordersby as $_key => $_value ) {
				if ( ! $_value ) {
					continue;
				}

				if ( is_int( $_key ) ) {
					$_orderby = $_value;
					$_order   = $order;
				} else {
					$_orderby = $_key;
					$_order   = $_value;
				}

				if ( ! $found_orderby_comment_id && in_array( $_orderby, array( 'comment_ID', 'comment__in' ), true ) ) {
					$found_orderby_comment_id = true;
				}

				$parsed = $this->parse_orderby( $_orderby );

				if ( ! $parsed ) {
					continue;
				}

				if ( 'comment__in' === $_orderby ) {
					$orderby_array[] = $parsed;
					continue;
				}

				$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
			}

			// If no valid clauses were found, order by comment_date_gmt.
			if ( empty( $orderby_array ) ) {
				$orderby_array[] = "$wpdb->comments.comment_date_gmt $order";
			}

			// To ensure determinate sorting, always include a comment_ID clause.
			if ( ! $found_orderby_comment_id ) {
				$comment_id_order = '';

				// Inherit order from comment_date or comment_date_gmt, if available.
				foreach ( $orderby_array as $orderby_clause ) {
					if ( preg_match( '/comment_date(?:_gmt)*\ (ASC|DESC)/', $orderby_clause, $match ) ) {
						$comment_id_order = $match[1];
						break;
					}
				}

				// If no date-related order is available, use the date from the first available clause.
				if ( ! $comment_id_order ) {
					foreach ( $orderby_array as $orderby_clause ) {
						if ( str_contains( 'ASC', $orderby_clause ) ) {
							$comment_id_order = 'ASC';
						} else {
							$comment_id_order = 'DESC';
						}

						break;
					}
				}

				// Default to DESC.
				if ( ! $comment_id_order ) {
					$comment_id_order = 'DESC';
				}

				$orderby_array[] = "$wpdb->comments.comment_ID $comment_id_order";
			}

			$orderby = implode( ', ', $orderby_array );
		} else {
			$orderby = "$wpdb->comments.comment_date_gmt $order";
		}

		$number = absint( $this->query_vars['number'] );
		$offset = absint( $this->query_vars['offset'] );
		$paged  = absint( $this->query_vars['paged'] );
		$limits = '';

		if ( ! empty( $number ) ) {
			if ( $offset ) {
				$limits = 'LIMIT ' . $offset . ',' . $number;
			} else {
				$limits = 'LIMIT ' . ( $number * ( $paged - 1 ) ) . ',' . $number;
			}
		}

		if ( $this->query_vars['count'] ) {
			$fields = 'COUNT(*)';
		} else {
			$fields = "$wpdb->comments.comment_ID";
		}

		$post_id = absint( $this->query_vars['post_id'] );
		if ( ! empty( $post_id ) ) {
			$this->sql_clauses['where']['post_id'] = $wpdb->prepare( 'comment_post_ID = %d', $post_id );
		}

		// Parse comment IDs for an IN clause.
		if ( ! empty( $this->query_vars['comment__in'] ) ) {
			$this->sql_clauses['where']['comment__in'] = "$wpdb->comments.comment_ID IN ( " . implode( ',', wp_parse_id_list( $this->query_vars['comment__in'] ) ) . ' )';
		}

		// Parse comment IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['comment__not_in'] ) ) {
			$this->sql_clauses['where']['comment__not_in'] = "$wpdb->comments.comment_ID NOT IN ( " . implode( ',', wp_parse_id_list( $this->query_vars['comment__not_in'] ) ) . ' )';
		}

		// Parse comment parent IDs for an IN clause.
		if ( ! empty( $this->query_vars['parent__in'] ) ) {
			$this->sql_clauses['where']['parent__in'] = 'comment_parent IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['parent__in'] ) ) . ' )';
		}

		// Parse comment parent IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['parent__not_in'] ) ) {
			$this->sql_clauses['where']['parent__not_in'] = 'comment_parent NOT IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['parent__not_in'] ) ) . ' )';
		}

		// Parse comment post IDs for an IN clause.
		if ( ! empty( $this->query_vars['post__in'] ) ) {
			$this->sql_clauses['where']['post__in'] = 'comment_post_ID IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['post__in'] ) ) . ' )';
		}

		// Parse comment post IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['post__not_in'] ) ) {
			$this->sql_clauses['where']['post__not_in'] = 'comment_post_ID NOT IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['post__not_in'] ) ) . ' )';
		}

		if ( '' !== $this->query_vars['author_email'] ) {
			$this->sql_clauses['where']['author_email'] = $wpdb->prepare( 'comment_author_email = %s', $this->query_vars['author_email'] );
		}

		if ( '' !== $this->query_vars['author_url'] ) {
			$this->sql_clauses['where']['author_url'] = $wpdb->prepare( 'comment_author_url = %s', $this->query_vars['author_url'] );
		}

		if ( '' !== $this->query_vars['karma'] ) {
			$this->sql_clauses['where']['karma'] = $wpdb->prepare( 'comment_karma = %d', $this->query_vars['karma'] );
		}

		// Filtering by comment_type: 'type', 'type__in', 'type__not_in'.
		$raw_types = array(
			'IN'     => array_merge( (array) $this->query_vars['type'], (array) $this->query_vars['type__in'] ),
			'NOT IN' => (array) $this->query_vars['type__not_in'],
		);

		$comment_types = array();
		foreach ( $raw_types as $operator => $_raw_types ) {
			$_raw_types = array_unique( $_raw_types );

			foreach ( $_raw_types as $type ) {
				switch ( $type ) {
					// An empty translates to 'all', for backward compatibility.
					case '':
					case 'all':
						break;

					case 'comment':
					case 'comments':
						$comment_types[ $operator ][] = "''";
						$comment_types[ $operator ][] = "'comment'";
						break;

					case 'pings':
						$comment_types[ $operator ][] = "'pingback'";
						$comment_types[ $operator ][] = "'trackback'";
						break;

					default:
						$comment_types[ $operator ][] = $wpdb->prepare( '%s', $type );
						break;
				}
			}

			if ( ! empty( $comment_types[ $operator ] ) ) {
				$types_sql = implode( ', ', $comment_types[ $operator ] );
				$this->sql_clauses['where'][ 'comment_type__' . strtolower( str_replace( ' ', '_', $operator ) ) ] = "comment_type $operator ($types_sql)";
			}
		}

		$parent = $this->query_vars['parent'];
		if ( $this->query_vars['hierarchical'] && ! $parent ) {
			$parent = 0;
		}

		if ( '' !== $parent ) {
			$this->sql_clauses['where']['parent'] = $wpdb->prepare( 'comment_parent = %d', $parent );
		}

		if ( is_array( $this->query_vars['user_id'] ) ) {
			$this->sql_clauses['where']['user_id'] = 'user_id IN (' . implode( ',', array_map( 'absint', $this->query_vars['user_id'] ) ) . ')';
		} elseif ( '' !== $this->query_vars['user_id'] ) {
			$this->sql_clauses['where']['user_id'] = $wpdb->prepare( 'user_id = %d', $this->query_vars['user_id'] );
		}

		// Falsey search strings are ignored.
		if ( isset( $this->query_vars['search'] ) && strlen( $this->query_vars['search'] ) ) {
			$search_sql = $this->get_search_sql(
				$this->query_vars['search'],
				array( 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_author_IP', 'comment_content' )
			);

			// Strip leading 'AND'.
			$this->sql_clauses['where']['search'] = preg_replace( '/^\s*AND\s*/', '', $search_sql );
		}

		// If any post-related query vars are passed, join the posts table.
		$join_posts_table = false;
		$plucked          = wp_array_slice_assoc( $this->query_vars, array( 'post_author', 'post_name', 'post_parent' ) );
		$post_fields      = array_filter( $plucked );

		if ( ! empty( $post_fields ) ) {
			$join_posts_table = true;
			foreach ( $post_fields as $field_name => $field_value ) {
				// $field_value may be an array.
				$esses = array_fill( 0, count( (array) $field_value ), '%s' );

				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$this->sql_clauses['where'][ $field_name ] = $wpdb->prepare( " {$wpdb->posts}.{$field_name} IN (" . implode( ',', $esses ) . ')', $field_value );
			}
		}

		// 'post_status' and 'post_type' are handled separately, due to the specialized behavior of 'any'.
		foreach ( array( 'post_status', 'post_type' ) as $field_name ) {
			$q_values = array();
			if ( ! empty( $this->query_vars[ $field_name ] ) ) {
				$q_values = $this->query_vars[ $field_name ];
				if ( ! is_array( $q_values ) ) {
					$q_values = explode( ',', $q_values );
				}

				// 'any' will cause the query var to be ignored.
				if ( in_array( 'any', $q_values, true ) || empty( $q_values ) ) {
					continue;
				}

				$join_posts_table = true;

				$esses = array_fill( 0, count( $q_values ), '%s' );

				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$this->sql_clauses['where'][ $field_name ] = $wpdb->prepare( " {$wpdb->posts}.{$field_name} IN (" . implode( ',', $esses ) . ')', $q_values );
			}
		}

		// Comment author IDs for an IN clause.
		if ( ! empty( $this->query_vars['author__in'] ) ) {
			$this->sql_clauses['where']['author__in'] = 'user_id IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['author__in'] ) ) . ' )';
		}

		// Comment author IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['author__not_in'] ) ) {
			$this->sql_clauses['where']['author__not_in'] = 'user_id NOT IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['author__not_in'] ) ) . ' )';
		}

		// Post author IDs for an IN clause.
		if ( ! empty( $this->query_vars['post_author__in'] ) ) {
			$join_posts_table                              = true;
			$this->sql_clauses['where']['post_author__in'] = 'post_author IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['post_author__in'] ) ) . ' )';
		}

		// Post author IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['post_author__not_in'] ) ) {
			$join_posts_table                                  = true;
			$this->sql_clauses['where']['post_author__not_in'] = 'post_author NOT IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['post_author__not_in'] ) ) . ' )';
		}

		$join    = '';
		$groupby = '';

		if ( $join_posts_table ) {
			$join .= "JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->comments.comment_post_ID";
		}

		if ( ! empty( $this->meta_query_clauses ) ) {
			$join .= $this->meta_query_clauses['join'];

			// Strip leading 'AND'.
			$this->sql_clauses['where']['meta_query'] = preg_replace( '/^\s*AND\s*/', '', $this->meta_query_clauses['where'] );

			if ( ! $this->query_vars['count'] ) {
				$groupby = "{$wpdb->comments}.comment_ID";
			}
		}

		if ( ! empty( $this->query_vars['date_query'] ) && is_array( $this->query_vars['date_query'] ) ) {
			$this->date_query = new WP_Date_Query( $this->query_vars['date_query'], 'comment_date' );

			// Strip leading 'AND'.
			$this->sql_clauses['where']['date_query'] = preg_replace( '/^\s*AND\s*/', '', $this->date_query->get_sql() );
		}

		$where = implode( ' AND ', $this->sql_clauses['where'] );

		$pieces = array( 'fields', 'join', 'where', 'orderby', 'limits', 'groupby' );

		/**
		 * Filters the comment query clauses.
		 *
		 * @since 3.1.0
		 *
		 * @param string[]         $clauses {
		 *     Associative array of the clauses for the query.
		 *
		 *     @type string $fields   The SELECT clause of the query.
		 *     @type string $join     The JOIN clause of the query.
		 *     @type string $where    The WHERE clause of the query.
		 *     @type string $orderby  The ORDER BY clause of the query.
		 *     @type string $limits   The LIMIT clause of the query.
		 *     @type string $groupby  The GROUP BY clause of the query.
		 * }
		 * @param WP_Comment_Query $query   Current instance of WP_Comment_Query (passed by reference).
		 */
		//$clauses = apply_filters_ref_array( 'comments_clauses', array( compact( $pieces ), &$this ) );
        $clauses = compact($pieces);

		$fields  = isset( $clauses['fields'] ) ? $clauses['fields'] : '';
		$join    = isset( $clauses['join'] ) ? $clauses['join'] : '';
		$where   = isset( $clauses['where'] ) ? $clauses['where'] : '';
		$orderby = isset( $clauses['orderby'] ) ? $clauses['orderby'] : '';
		$limits  = isset( $clauses['limits'] ) ? $clauses['limits'] : '';
		$groupby = isset( $clauses['groupby'] ) ? $clauses['groupby'] : '';

		$this->filtered_where_clause = $where;

		if ( $where ) {
			$where = 'WHERE ' . $where;
		}

		if ( $groupby ) {
			$groupby = 'GROUP BY ' . $groupby;
		}

		if ( $orderby ) {
			$orderby = "ORDER BY $orderby";
		}

		$found_rows = '';
		if ( ! $this->query_vars['no_found_rows'] ) {
			$found_rows = 'SQL_CALC_FOUND_ROWS';
		}

		$this->sql_clauses['select']  = "SELECT $found_rows $fields";
		$this->sql_clauses['from']    = "FROM $wpdb->comments $join";
		$this->sql_clauses['groupby'] = $groupby;
		$this->sql_clauses['orderby'] = $orderby;
		$this->sql_clauses['limits']  = $limits;

		// Beginning of the string is on a new line to prevent leading whitespace. See https://core.trac.wordpress.org/ticket/56841.
		$this->request =
			"{$this->sql_clauses['select']}
			 {$this->sql_clauses['from']}
			 {$where}
			 {$this->sql_clauses['groupby']}
			 {$this->sql_clauses['orderby']}
			 {$this->sql_clauses['limits']}";

		if ( $this->query_vars['count'] ) {
			return (int) $wpdb->get_var( $this->request );
		} else {
			$comment_ids = $wpdb->get_col( $this->request );
			return array_map( 'intval', $comment_ids );
		}
	}
}
