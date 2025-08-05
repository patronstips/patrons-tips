<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Define Patrons Tips' tables names
 * @global wpdb $wpdb
 */
global $wpdb;
$db_prefix = $wpdb->prefix;

if( ! defined( 'PATIPS_TABLE_TIERS' ) )            { define( 'PATIPS_TABLE_TIERS', $db_prefix . 'patips_tiers' ); }
if( ! defined( 'PATIPS_TABLE_RESTRICTED_TERMS' ) ) { define( 'PATIPS_TABLE_RESTRICTED_TERMS', $db_prefix . 'patips_restricted_terms' ); }
if( ! defined( 'PATIPS_TABLE_TIERS_PRODUCTS' ) )   { define( 'PATIPS_TABLE_TIERS_PRODUCTS', $db_prefix . 'patips_tiers_products' ); }
if( ! defined( 'PATIPS_TABLE_PATRONS' ) )          { define( 'PATIPS_TABLE_PATRONS', $db_prefix . 'patips_patrons' ); }
if( ! defined( 'PATIPS_TABLE_PATRONS_HISTORY' ) )  { define( 'PATIPS_TABLE_PATRONS_HISTORY', $db_prefix . 'patips_patrons_history' ); }
if( ! defined( 'PATIPS_TABLE_META' ) )             { define( 'PATIPS_TABLE_META', $db_prefix . 'patips_meta' ); }
if( ! defined( 'PATIPS_TABLE_EXPORTS' ) )          { define( 'PATIPS_TABLE_EXPORTS', $db_prefix . 'patips_exports' ); }




// USER

/**
 * Get the user id corresponding to a secret key
 * @since 0.8.0
 * @version 0.25.5
 * @param string $secret_key
 * @return int User ID or 0 if not found
 */
function patips_get_user_id_by_secret_key( $secret_key ) {
	global $wpdb;
	$query = 'SELECT user_id FROM ' . $wpdb->usermeta . ' WHERE meta_key = "patips_secret_key" AND meta_value = %s;';
	$query = $wpdb->prepare( $query, $secret_key ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$user_id = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	return $user_id ? intval( $user_id ) : 0;
}




// METADATA

/**
 * Get metadata
 * @since 0.5.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param string $object_type
 * @param int|array $object_id
 * @param string $meta_key
 * @param boolean $single
 * @return array|mixed|false
 */
function patips_get_metadata( $object_type, $object_id, $meta_key = '', $single = false ) {
	global $wpdb;

	if( ! $object_type || empty( $object_id ) || ( ! is_numeric( $object_id ) && ! is_array( $object_id ) ) ) { return false; }
	
	if( is_numeric( $object_id ) ) {
		$object_id = absint( $object_id );
	} else if( is_array( $object_id ) ) {
		$object_id = array_filter( array_map( 'absint', array_unique( $object_id ) ) );
	}
	
	if( ! $object_id ) { return false; }
	
	$query	= 'SELECT object_id, meta_key, meta_value FROM ' . PATIPS_TABLE_META
			. ' WHERE object_type = %s';

	$variables = array( $object_type );
	
	if( is_numeric( $object_id ) ) {
		$query .= ' AND object_id = %d';
		$variables[] = $object_id;
		
	} else if( is_array( $object_id ) ) {
		$query .= ' AND object_id IN ( %d ';
		$array_count = count( $object_id );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $object_id );
	}
	
	if( $meta_key !== '' ) {
		$query .= ' AND meta_key = %s';
		$variables[] = $meta_key;
	}

	$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if( $single ) {
		$metadata = $wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return isset( $metadata->meta_value ) ? maybe_unserialize( $metadata->meta_value ) : false;
	}

	$metadata = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if( is_null( $metadata ) ) { return false; }

	$metadata_array = array();
	foreach( $metadata as $metadata_pair ) {
		if( is_array( $object_id ) ) {
			if( ! isset( $metadata_array[ $metadata_pair->object_id ] ) ) { $metadata_array[ $metadata_pair->object_id ] = array(); }
			$metadata_array[ $metadata_pair->object_id ][ $metadata_pair->meta_key ] = maybe_unserialize( $metadata_pair->meta_value );
		} else {
			$metadata_array[ $metadata_pair->meta_key ] = maybe_unserialize( $metadata_pair->meta_value );
		}
	}

	return $metadata_array;
}


/**
 * Update metadata
 * @since 0.5.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param string $object_type
 * @param int|array $object_id
 * @param array $metadata_array
 * @return int|false
 */
function patips_update_metadata( $object_type, $object_id, $metadata_array ) {
	global $wpdb;

	if( ! $object_type || empty( $object_id ) || ( ! is_numeric( $object_id ) && ! is_array( $object_id ) ) || ! is_array( $metadata_array ) ) { return false; }
	
	if( is_array( $metadata_array ) && empty( $metadata_array ) ) { return 0; }

	if( is_numeric( $object_id ) ) {
		$object_id = absint( $object_id );
	} else if( is_array( $object_id ) ) {
		$object_id = array_filter( array_map( 'absint', array_unique( $object_id ) ) );
	}
	if( ! $object_id ) { return false; }

	$object_ids                   = is_array( $object_id ) ? $object_id : array( $object_id );
	$current_meta_per_object_id   = patips_get_metadata( $object_type, $object_ids );
	$meta_to_update_per_object_id = array();
	foreach( $object_ids as $_object_id ) {
		if( ! isset( $current_meta_per_object_id[ $_object_id ] ) ) {
			$current_meta_per_object_id[ $_object_id ] = array();
		}
	}
	
	// Insert new meta and find existing meta
	$inserted = 0;
	foreach( $current_meta_per_object_id as $_object_id => $current_meta ) {
		$meta_to_insert = array_diff_key( $metadata_array, $current_meta );
		if( $meta_to_insert ) {
			$inserted_n = patips_insert_metadata( $object_type, $_object_id, $meta_to_insert );
			
			if( is_int( $inserted_n ) && is_int( $inserted ) ) {
				$inserted += $inserted_n;
			} else if( $inserted_n === false ) {
				$inserted = false;
			}
		}
		
		$meta_to_update = array_intersect_key( $metadata_array, $current_meta );
		if( $meta_to_update ) {
			$meta_to_update_per_object_id[ $_object_id ] = $meta_to_update;
		}
	}

	// Update existing meta
	$query_start = 'UPDATE ' . PATIPS_TABLE_META . ' SET meta_value = ';
	$query_end   = ' WHERE object_type = %s AND object_id = %d AND meta_key = %s;';
	
	$updated = 0;
	foreach( $meta_to_update_per_object_id as $_object_id => $meta_to_update ) {
		foreach( $meta_to_update as $meta_key => $meta_value ) {
			$query_n = $query_start;

			if( is_int( $meta_value ) )        { $query_n .= '%d'; }
			else if( is_float( $meta_value ) ) { $query_n .= '%f'; }
			else                               { $query_n .= '%s'; }

			$query_n .= $query_end;

			$variables = array(
				is_array( $meta_value ) || is_object( $meta_value ) ? maybe_serialize( $meta_value ) : $meta_value,
				$object_type,
				$_object_id,
				$meta_key
			);

			$query_n   = $wpdb->prepare( $query_n, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$updated_n = $wpdb->query( $query_n ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if( is_int( $updated_n ) && is_int( $updated ) ) {
				$updated += $updated_n;
			} else if( $updated_n === false ) {
				$updated = false;
			}
		}
	}

	if( is_int( $inserted ) && is_int( $updated ) ) {
		return $inserted + $updated;
	}

	return false;
}


/**
 * Insert metadata
 * @since 0.5.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param string $object_type
 * @param int|array $object_id
 * @param array $metadata_array
 * @return int|boolean
 */
function patips_insert_metadata( $object_type, $object_id, $metadata_array ) {
	global $wpdb;

	if( ! $object_type || empty( $object_id ) || ( ! is_numeric( $object_id ) && ! is_array( $object_id ) ) || ! is_array( $metadata_array ) || empty( $metadata_array ) ) { return false; }
	
	if( is_numeric( $object_id ) ) {
		$object_id = absint( $object_id );
	} else if( is_array( $object_id ) ) {
		$object_id = array_filter( array_map( 'absint', array_unique( $object_id ) ) );
	}
	if( ! $object_id ) { return false; }
	
	$object_ids = is_array( $object_id ) ? $object_id : array( $object_id );

	$query = 'INSERT INTO ' . PATIPS_TABLE_META . ' ( object_type, object_id, meta_key, meta_value ) VALUES ';
	$variables = array();
	$i = 0;
	foreach( $object_ids as $_object_id ) {
		foreach( $metadata_array as $meta_key => $meta_value ) {
			if( $i !== 0 ) { $query .= ', '; }
			++$i;

			$query .= '( %s, %d, %s, ';

			if( is_int( $meta_value ) )        { $query .= '%d'; }
			else if( is_float( $meta_value ) ) { $query .= '%f'; }
			else                               { $query .= '%s'; }
			
			$query .= ' )';
			
			$variables[] = $object_type;
			$variables[] = $_object_id;
			$variables[] = $meta_key;
			$variables[] = is_array( $meta_value ) || is_object( $meta_value ) ? maybe_serialize( $meta_value ) : $meta_value;
		}
	}
	
	$query .= ';';
	
	$query    = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$inserted = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return $inserted;
}


/**
 * Duplicate metadata
 * @since 0.5.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param string $object_type
 * @param int $source_id
 * @param int $recipient_id
 * @return int|boolean
 */
function patips_duplicate_metadata( $object_type, $source_id, $recipient_id ) {
	global $wpdb;
	
	if( ! $object_type || ! is_numeric( $source_id ) || ! is_numeric( $recipient_id ) ) { return false; }
	
	$source_id		= absint( $source_id );
	$recipient_id	= absint( $recipient_id );
	if( ! $source_id || ! $recipient_id ) { return false; }
	
	$query		= 'INSERT INTO ' . PATIPS_TABLE_META . ' ( object_type, object_id, meta_key, meta_value ) '
				. ' SELECT object_type, %d, meta_key, meta_value '
				. ' FROM ' . PATIPS_TABLE_META
				. ' WHERE object_type = %s ' 
				. ' AND object_id = %d';
	$query_prep	= $wpdb->prepare( $query, $recipient_id, $object_type, $source_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$inserted	= $wpdb->query( $query_prep ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	return $inserted;
}


/**
 * Delete metadata
 * @since 0.5.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param string $object_type
 * @param int|array $object_id
 * @param array $metadata_key_array Array of metadata keys to delete. Leave it empty to delete all metadata of the desired object.
 * @return int|boolean
 */
function patips_delete_metadata( $object_type, $object_id, $metadata_key_array = array() ) {
	global $wpdb;
	
	if( ! $object_type || empty( $object_id ) || ( ! is_numeric( $object_id ) && ! is_array( $object_id ) ) ) { return false; }
	
	if( is_numeric( $object_id ) ) {
		$object_id = absint( $object_id );
	} else if( is_array( $object_id ) ) {
		$object_id = array_filter( array_map( 'absint', array_unique( $object_id ) ) );
	}
	if( ! $object_id ) { return false; }
	
	$query = 'DELETE FROM ' . PATIPS_TABLE_META . ' WHERE object_type = %s ';

	$variables = array( $object_type );
	
	if( is_numeric( $object_id ) ) {
		$query .= ' AND object_id = %d';
		$variables[] = $object_id;
		
	} else if( is_array( $object_id ) ) {
		$query .= ' AND object_id IN ( %d ';
		$array_count = count( $object_id );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $object_id );
	}
	
	if( $metadata_key_array ) {
		$query .= ' AND meta_key IN( %s';
		for( $i=1,$len=count($metadata_key_array); $i < $len; ++$i ) {
			$query .= ', %s';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, array_values( $metadata_key_array ) );
	}
	$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$deleted = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	return $deleted;
}