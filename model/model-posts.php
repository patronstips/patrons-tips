<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Get posts filtered by categories, tags, and patronage history
 * @since 0.11.0 (was patips_get_posts)
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $filters (see patips_format_patron_post_filters)
 * @param int $patron_id
 * @return WP_Post[]
 */
function patips_get_patron_posts( $filters = array(), $patron_id = 0 ) {
	global $wpdb;
	
	// If both categories and tags are specified, get posts that have both one of the categories and one the tags
	$has_cat_and_tag = $filters[ 'categories' ] && $filters[ 'tags' ] && $filters[ 'cat_and_tag' ];
	
	$variables = array();
	
	$query = 'SELECT DISTINCT P.*, SUM( IF( RT.term_id IS NULL, 0, 1 ) ) as is_restricted,'
	       . ' SUM( IF( H.id IS NULL, 0, 1 ) ) as is_unlocked ';
	
	if( $has_cat_and_tag ) {
		$query .= ', T.term_id ';
	}
	
	$query .= ' FROM ' . $wpdb->posts . ' as P'
	       . ' LEFT JOIN ' . $wpdb->term_relationships . ' as TR ON TR.object_id = P.ID '
	       . ' LEFT JOIN ' . $wpdb->term_taxonomy . ' as TT ON TT.term_taxonomy_id = TR.term_taxonomy_id '
	       . ' LEFT JOIN ' . $wpdb->terms . ' as T ON T.term_id = TT.term_id '
	       . ' LEFT JOIN ' . PATIPS_TABLE_RESTRICTED_TERMS . ' as RT ON RT.term_id = T.term_id AND RT.scope = "active" '
	       . ' LEFT JOIN ' . PATIPS_TABLE_PATRONS_HISTORY . ' as H ON H.tier_id = RT.tier_id AND H.active = 1 AND H.patron_id = %d AND H.date_start <= %s AND H.date_end >= %s '
	       . ' WHERE TRUE ';
	
	$timezone    = patips_get_wp_timezone();
	$now_dt      = new DateTime( 'now', $timezone );
	$variables[] = $patron_id;
	$variables[] = $now_dt->format( 'Y-m-d' );
	$variables[] = $now_dt->format( 'Y-m-d' );
	
	if( $filters[ 'types' ] ) {
		$query .= ' AND P.post_type IN ( %s ';
		$array_count = count( $filters[ 'types' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'types' ] );
	}
	
	if( $filters[ 'statuses' ] ) {
		// Attachments do not have post_status, so retrieve them all (check post_date for the future ones)
		$query .= ' AND ('
		        . ' ( P.post_type = "attachment"';
		if( ! in_array( 'future', $filters[ 'statuses' ], true ) ) {
			$query .= ' AND P.post_date_gmt <= %s';
			$now_utc_dt  = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
			$variables[] = $now_utc_dt->format( 'Y-m-d H:i:s' );
		}
		
		$query .= ' ) OR ( P.post_status IN ( %s';
		$array_count = count( $filters[ 'statuses' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s';
			}
		}
		$query .= ' ) ) ) ';
		$variables = array_merge( $variables, $filters[ 'statuses' ] );
	}
	
	if( $filters[ 'categories' ] ) {
		$query .= $filters[ 'tags' ] ? ' AND ( ( ' : ' AND ( ';
		
		$compulsory_cat_ids   = array_values( array_filter( $filters[ 'categories' ], 'is_numeric' ) );
		$compulsory_cat_slugs = array_values( array_diff( $filters[ 'categories' ], $compulsory_cat_ids ) );
		
		if( $compulsory_cat_ids ) {
			$query .= 'T.term_id IN ( %d ';
			$array_count = count( $compulsory_cat_ids );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query .= ', %d ';
				}
			}
			$query .= ') ';
		}
		
		if( $compulsory_cat_slugs ) {
			if( $compulsory_cat_ids ) {
				$query .= ' OR ';
			}
			$query .= 'T.slug IN ( %s ';
			$array_count = count( $compulsory_cat_slugs );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query .= ', %s ';
				}
			}
			$query .= ') ';
		}
		
		$variables = array_merge( $variables, $compulsory_cat_ids, $compulsory_cat_slugs );
		
		$query .= ') ';
	}
	
	if( $filters[ 'tags' ] ) {
		$query .= $filters[ 'categories' ] ? ' OR ( ' : ' AND ( ';
		$compulsory_tag_ids   = array_values( array_filter( $filters[ 'tags' ], 'is_numeric' ) );
		$compulsory_tag_slugs = array_values( array_diff( $filters[ 'tags' ], $compulsory_tag_ids ) );
		
		if( $compulsory_tag_ids ) {
			$query .= 'T.term_id IN ( %d ';
			$array_count = count( $compulsory_tag_ids );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query .= ', %d ';
				}
			}
			$query .= ') ';
		}
		
		if( $compulsory_tag_slugs ) {
			if( $compulsory_tag_ids ) {
				$query .= ' OR ';
			}
			$query .= 'T.slug IN ( %s ';
			$array_count = count( $compulsory_tag_slugs );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query .= ', %s ';
				}
			}
			$query .= ') ';
		}
		
		$variables = array_merge( $variables, $compulsory_tag_ids, $compulsory_tag_slugs );
		
		$query .= $filters[ 'categories' ] ? ') ) ' : ') ';
	}
	
	if( $filters[ 'from' ] ) {
		$query .= ' AND P.post_date >= %s ';
		$variables[] = $filters[ 'from' ];
	}
	
	if( $filters[ 'to' ] ) {
		$query .= ' AND P.post_date <= %s ';
		$variables[] = $filters[ 'to' ];
	}
	
	$query .= ' GROUP BY P.ID ';
	
	if( $has_cat_and_tag ) {
		$query .= ', T.term_id ';
	}
	
	$query .= ' HAVING TRUE ';
	
	if( $filters[ 'restricted' ] !== false ) {
		// Retrieve only restricted posts
		if( $filters[ 'restricted' ] ) {
			$query .= ' AND is_restricted > 0 ';
		
		// Retrieve only unrestricted posts
		} else {
			$query .= ' AND is_restricted <= 0 ';
		}
	}
	
	if( $filters[ 'unlocked' ] !== false ) {
		// Retrieve only unlocked posts
		if( $filters[ 'unlocked' ] ) {
			$query .= ' AND is_unlocked > 0 ';
		
		// Retrieve only locked posts
		} else {
			$query .= ' AND is_unlocked <= 0 ';
		}
	}
	
	if( $filters[ 'order_by' ] ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($filters[ 'order_by' ]); $i<$len; ++$i ) {
			$query .= $filters[ 'order_by' ][ $i ];
			if( $filters[ 'order' ] ) { $query .= ' ' . $filters[ 'order' ]; }
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}
	
	if( $has_cat_and_tag ) {
		$query = 'SELECT * FROM ( ' . $query . ' ) as PT GROUP BY PT.ID HAVING COUNT( PT.ID ) > 1';
	}
	
	if( $filters[ 'offset' ] || $filters[ 'per_page' ] ) {
		$query .= ' LIMIT ';
		if( $filters[ 'offset' ] ) {
			$query .= '%d';
			if( $filters[ 'per_page' ] ) { $query .= ', '; }
			$variables[] = $filters[ 'offset' ];
		}
		if( $filters[ 'per_page' ] ) { 
			$query .= '%d ';
			$variables[] = $filters[ 'per_page' ];
		}
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$query = apply_filters( 'patips_get_patron_posts_query', $query, $filters, $patron_id );
	
	$posts = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	// Tranform results into an array of WP_Post
	$wp_posts = $posts ? array_map( 'get_post', $posts ) : array();
	
	return $wp_posts;
}
