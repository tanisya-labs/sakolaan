<?php
/** One-time migration from the legacy School packages to Sakolaan. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.SlowDBQuery -- One-time migration requires transactional, ID-preserving access to exact database rows.

/** Map a legacy option name to its Sakolaan equivalent. */
function sakolaan_migration_option_name( $name ) {
	$name = (string) $name;
	if ( 'theme_mods_school-theme' === $name ) {
		return 'theme_mods_pijar-sakolaan';
	}
	return str_replace(
		array( 'school_core_', 'school_theme_', 'school-core', 'school-theme' ),
		array( 'sakolaan_', 'pijar_sakolaan_', 'sakolaan', 'pijar-sakolaan' ),
		$name
	);
}

/** Map legacy content metadata to the new schema. */
function sakolaan_migration_meta_key( $key ) {
	$key = (string) $key;
	if ( 0 === strpos( $key, '_school_core_' ) ) {
		return '_sakolaan_' . substr( $key, strlen( '_school_core_' ) );
	}
	if ( 0 === strpos( $key, '_school_' ) ) {
		return '_sakolaan_' . substr( $key, strlen( '_school_' ) );
	}
	return $key;
}

/** Update product namespaces and stored presentation identifiers without rewriting media filenames. */
function sakolaan_migration_transform_string( $value ) {
	$value = str_replace(
		array( 'school-theme/', 'school-core/', 'school-theme', 'school-core', 'School Theme', 'School Core', 'school_core_', 'school_theme_', 'schoolCore', 'schoolTheme', 'schoolLayoutSection', '--sc-' ),
		array( 'pijar-sakolaan/', 'sakolaan/', 'pijar-sakolaan', 'sakolaan', 'Pijar Sakolaan', 'Sakolaan', 'sakolaan_', 'pijar_sakolaan_', 'sakolaan', 'pijarSakolaan', 'sakolaanLayoutSection', '--sakolaan-' ),
		(string) $value
	);
	$value = str_replace(
		array( 'class="school-', "class='school-", 'className":"school-', ' school-', '.school-', '#school-', 'id="school-', "id='school-", 'data-school-', 'is-style-school-' ),
		array( 'class="sakolaan-', "class='sakolaan-", 'className":"sakolaan-', ' sakolaan-', '.sakolaan-', '#sakolaan-', 'id="sakolaan-', "id='sakolaan-", 'data-sakolaan-', 'is-style-sakolaan-' ),
		$value
	);
	return $value;
}

/** Recursively migrate identifiers inside serialized option values. */
function sakolaan_migration_transform_value( $value ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $key => $item ) {
			$value[ $key ] = sakolaan_migration_transform_value( $item );
		}
		return $value;
	}
	if ( is_object( $value ) ) {
		foreach ( get_object_vars( $value ) as $key => $item ) {
			$value->$key = sakolaan_migration_transform_value( $item );
		}
		return $value;
	}
	return is_string( $value ) ? sakolaan_migration_transform_string( $value ) : $value;
}

/** Normalize an uploaded legacy backup only at the import boundary. */
function sakolaan_migration_normalize_backup_payload( $payload, $kind ) {
	if ( ! is_array( $payload ) ) {
		return $payload;
	}
	$legacy_format = 'settings' === $kind ? 'school-core-settings' : 'school-core-data';
	$new_format = 'settings' === $kind ? 'sakolaan-settings' : 'sakolaan-data';
	if ( $legacy_format !== ( $payload['format'] ?? '' ) ) {
		return $payload;
	}
	$payload = sakolaan_migration_transform_value( $payload );
	$payload['format'] = $new_format;
	return $payload;
}

/** Collect every database row that may be changed by the migration. */
function sakolaan_migration_snapshot_data() {
	global $wpdb;
	$option_like_core  = '%' . $wpdb->esc_like( 'school_core_' ) . '%';
	$option_like_theme = '%' . $wpdb->esc_like( 'school_theme_' ) . '%';
	$option_like_core_slug  = '%' . $wpdb->esc_like( 'school-core' ) . '%';
	$option_like_theme_slug = '%' . $wpdb->esc_like( 'school-theme' ) . '%';
	$options = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT option_id, option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name IN ('theme_mods_school-theme','template','stylesheet','active_plugins','widget_block') ORDER BY option_id",
			$option_like_core,
			$option_like_theme,
			$option_like_core_slug,
			$option_like_theme_slug
		),
		ARRAY_A
	);
	$options = array_values(
		array_filter(
			$options,
			static function ( $row ) {
				if ( in_array( $row['option_name'], array( 'template', 'stylesheet' ), true ) ) {
					return 'school-theme' === $row['option_value'];
				}
				if ( in_array( $row['option_name'], array( 'active_plugins', 'widget_block' ), true ) ) {
					return false !== strpos( $row['option_value'], 'school-' ) || false !== strpos( $row['option_value'], 'school_' );
				}
				return true;
			}
		)
	);
	$destination_options = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT option_id, option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s ORDER BY option_id",
			'%' . $wpdb->esc_like( 'sakolaan' ) . '%',
			$wpdb->esc_like( 'sakolaan_migration_' ) . '%'
		),
		ARRAY_A
	);

	$meta_tables = array(
		'postmeta'    => array( $wpdb->postmeta, 'meta_id', 'post_id' ),
		'termmeta'    => array( $wpdb->termmeta, 'meta_id', 'term_id' ),
		'usermeta'    => array( $wpdb->usermeta, 'umeta_id', 'user_id' ),
		'commentmeta' => array( $wpdb->commentmeta, 'meta_id', 'comment_id' ),
	);
	$meta = array();
	$destination_meta = array();
	foreach ( $meta_tables as $name => $spec ) {
		$like = $wpdb->esc_like( '_school_' ) . '%';
		$meta[ $name ] = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE meta_key LIKE %s ORDER BY %i', $spec[0], $like, $spec[1] ), ARRAY_A );
		$destination_like = $wpdb->esc_like( '_sakolaan_' ) . '%';
		$destination_meta[ $name ] = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE meta_key LIKE %s ORDER BY %i', $spec[0], $destination_like, $spec[1] ), ARRAY_A );
	}

	$content_markers = array( '%school-theme/%', '%school-core/%', '%class="school-%', "%class='school-%", '%className":"school-%', '% school-%', '%.school-%', '%#school-%', '%data-school-%', '%--sc-%', '%school_core_%', '%school_theme_%', '%schoolLayoutSection%' );
	$conditions = array();
	foreach ( $content_markers as $marker ) {
		$conditions[] = $wpdb->prepare( 'post_content LIKE %s', $marker );
	}
	$conditions[] = $wpdb->prepare( '(post_type = %s AND post_name = %s)', 'custom_css', 'school-theme' );
	$posts = $wpdb->get_results( "SELECT ID, post_type, post_name, post_content FROM {$wpdb->posts} WHERE " . implode( ' OR ', $conditions ) . ' ORDER BY ID', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Every dynamic condition is prepared above.

	$theme_terms = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT t.term_id, t.name, t.slug, tt.term_taxonomy_id, tt.description, tt.parent, tt.count FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id WHERE tt.taxonomy=%s AND t.slug IN (%s,%s) ORDER BY t.term_id",
			'wp_theme',
			'school-theme',
			'pijar-sakolaan'
		),
		ARRAY_A
	);
	$term_taxonomy_ids = array_map( 'intval', array_column( $theme_terms, 'term_taxonomy_id' ) );
	$relationships = array();
	if ( $term_taxonomy_ids ) {
		$placeholders = implode( ',', array_fill( 0, count( $term_taxonomy_ids ), '%d' ) );
		$relationships = $wpdb->get_results( $wpdb->prepare( "SELECT object_id, term_taxonomy_id, term_order FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ($placeholders) ORDER BY object_id, term_taxonomy_id", ...$term_taxonomy_ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN placeholders match the normalized integer IDs.
	}

	return array(
		'options'             => $options,
		'destination_options' => $destination_options,
		'meta'                => $meta,
		'destination_meta'    => $destination_meta,
		'posts'               => $posts,
		'theme_terms'         => $theme_terms,
		'relationships'       => $relationships,
	);
}

/** Produce stable counts recorded alongside the checksummed snapshot. */
function sakolaan_migration_snapshot_counts( $data ) {
	if ( ! is_array( $data ) || ! isset( $data['options'], $data['destination_options'], $data['meta'], $data['destination_meta'], $data['posts'], $data['theme_terms'], $data['relationships'] ) ) {
		return array();
	}
	$counts = array(
		'options'             => count( $data['options'] ),
		'destination_options' => count( $data['destination_options'] ),
		'posts'               => count( $data['posts'] ),
		'theme_terms'         => count( $data['theme_terms'] ),
		'relationships'       => count( $data['relationships'] ),
	);
	foreach ( $data['meta'] as $name => $rows ) {
		$counts[ 'meta_' . $name ] = count( $rows );
		$counts[ 'destination_meta_' . $name ] = count( $data['destination_meta'][ $name ] ?? array() );
	}
	return $counts;
}

/** Persist a protected, checksummed migration snapshot. */
function sakolaan_migration_write_snapshot( $data ) {
	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) ) {
		return new WP_Error( 'sakolaan_snapshot_uploads', $uploads['error'] );
	}
	$directory = trailingslashit( $uploads['basedir'] ) . 'sakolaan-migrations';
	if ( ! wp_mkdir_p( $directory ) ) {
		return new WP_Error( 'sakolaan_snapshot_directory', __( 'Folder backup migrasi tidak dapat dibuat.', 'sakolaan' ) );
	}
	if ( false === file_put_contents( $directory . '/index.php', "<?php\n// Silence is golden.\n" ) ) {
		return new WP_Error( 'sakolaan_snapshot_protection', __( 'Folder backup migrasi tidak dapat diamankan.', 'sakolaan' ) );
	}
	file_put_contents( $directory . '/.htaccess', "Require all denied\nDeny from all\n" );
	file_put_contents( $directory . '/web.config', "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><security><authorization><remove users=\"*\" roles=\"\" verbs=\"\"/><add accessType=\"Deny\" users=\"*\"/></authorization></security></system.webServer></configuration>" );
	$payload = array(
		'format'     => 'sakolaan-migration',
		'version'    => 1,
		'created_at' => gmdate( 'c' ),
		'site'       => home_url( '/' ),
		'counts'     => sakolaan_migration_snapshot_counts( $data ),
		'data'       => $data,
	);
	$checksum = hash( 'sha256', wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	$wrapper = array( 'checksum' => $checksum, 'snapshot' => $payload );
	$json = wp_json_encode( $wrapper, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	if ( false === $json ) {
		return new WP_Error( 'sakolaan_snapshot_json', __( 'Snapshot migrasi tidak dapat dikodekan.', 'sakolaan' ) );
	}
	$path = trailingslashit( $directory ) . 'snapshot-' . gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 24, false, false ) . '.json';
	if ( strlen( $json ) !== file_put_contents( $path, $json, LOCK_EX ) ) {
		return new WP_Error( 'sakolaan_snapshot_write', __( 'Snapshot migrasi tidak dapat disimpan.', 'sakolaan' ) );
	}
	$stored = json_decode( (string) file_get_contents( $path ), true );
	$stored_snapshot = is_array( $stored ) ? ( $stored['snapshot'] ?? null ) : null;
	$stored_checksum = is_array( $stored_snapshot ) ? hash( 'sha256', wp_json_encode( $stored_snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) : '';
	$counts_match = is_array( $stored_snapshot ) && ( $stored_snapshot['counts'] ?? null ) === sakolaan_migration_snapshot_counts( $stored_snapshot['data'] ?? array() );
	if ( ! is_array( $stored_snapshot ) || ! $counts_match || ! hash_equals( $checksum, $stored_checksum ) || ! hash_equals( $checksum, (string) ( $stored['checksum'] ?? '' ) ) ) {
		wp_delete_file( $path );
		return new WP_Error( 'sakolaan_snapshot_roundtrip', __( 'Snapshot migrasi gagal melewati validasi round-trip.', 'sakolaan' ) );
	}
	update_option( 'sakolaan_migration_snapshot', array( 'path' => $path, 'checksum' => $checksum, 'created_at' => $payload['created_at'] ), false );
	return $path;
}

/** Compare legacy and destination rows before changing anything. */
function sakolaan_migration_preflight( $snapshot ) {
	global $wpdb;
	$conflicts = array();
	foreach ( $snapshot['options'] as $row ) {
		$old_name = $row['option_name'];
		$new_name = sakolaan_migration_option_name( $old_name );
		if ( $old_name === $new_name ) {
			continue;
		}
		$current = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name=%s", $new_name ) );
		$expected = maybe_serialize( sakolaan_migration_transform_value( maybe_unserialize( $row['option_value'] ) ) );
		if ( null !== $current && $current !== $expected ) {
			$conflicts[] = sprintf( 'option:%s', $new_name );
		}
	}

	$meta_specs = array(
		'postmeta'    => array( $wpdb->postmeta, 'post_id' ),
		'termmeta'    => array( $wpdb->termmeta, 'term_id' ),
		'usermeta'    => array( $wpdb->usermeta, 'user_id' ),
		'commentmeta' => array( $wpdb->commentmeta, 'comment_id' ),
	);
	foreach ( $meta_specs as $name => $spec ) {
		$grouped = array();
		foreach ( $snapshot['meta'][ $name ] as $row ) {
			$new_key = sakolaan_migration_meta_key( $row['meta_key'] );
			$grouped[ $row[ $spec[1] ] . '|' . $new_key ][] = maybe_serialize( sakolaan_migration_transform_value( maybe_unserialize( $row['meta_value'] ) ) );
		}
		foreach ( $grouped as $identity => $old_values ) {
			list( $object_id, $new_key ) = explode( '|', $identity, 2 );
			$new_values = $wpdb->get_col( $wpdb->prepare( 'SELECT meta_value FROM %i WHERE %i=%d AND meta_key=%s ORDER BY meta_value', $spec[0], $spec[1], $object_id, $new_key ) );
			sort( $old_values );
			sort( $new_values );
			if ( $new_values && $new_values !== $old_values ) {
				$conflicts[] = sprintf( '%s:%s:%s', $name, $object_id, $new_key );
			}
		}
	}

	$old_theme = array_values( array_filter( $snapshot['theme_terms'], static function ( $row ) { return 'school-theme' === $row['slug']; } ) );
	$new_theme = array_values( array_filter( $snapshot['theme_terms'], static function ( $row ) { return 'pijar-sakolaan' === $row['slug']; } ) );
	if ( $old_theme && $new_theme && (int) $old_theme[0]['term_id'] !== (int) $new_theme[0]['term_id'] ) {
		$conflicts[] = 'wp_theme:pijar-sakolaan';
	}

	return $conflicts ? new WP_Error( 'sakolaan_migration_conflict', sprintf( /* translators: %s: comma-separated conflict identifiers. */ __( 'Migrasi dibatalkan karena data tujuan berbeda: %s', 'sakolaan' ), implode( ', ', $conflicts ) ) ) : true;
}

/** Validate every changed row before committing the transaction. */
function sakolaan_migration_verify_applied( $snapshot ) {
	global $wpdb;
	foreach ( $snapshot['options'] as $row ) {
		$old_name = $row['option_name'];
		$new_name = sakolaan_migration_option_name( $old_name );
		$value = maybe_unserialize( $row['option_value'] );
		if ( 'template' === $old_name || 'stylesheet' === $old_name ) {
			$value = 'pijar-sakolaan';
		} elseif ( 'active_plugins' === $old_name && is_array( $value ) ) {
			$value = array_values( array_map( static function ( $plugin ) { return 'school-core/school-core.php' === $plugin ? 'sakolaan/sakolaan.php' : $plugin; }, $value ) );
		} else {
			$value = sakolaan_migration_transform_value( $value );
		}
		$current = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name=%s", $new_name ) );
		if ( maybe_serialize( $value ) !== $current ) {
			return new WP_Error( 'sakolaan_migration_option_verify', sprintf( /* translators: %s: option name. */ __( 'Validasi option migrasi gagal: %s', 'sakolaan' ), $new_name ) );
		}
		if ( $old_name !== $new_name && null !== $wpdb->get_var( $wpdb->prepare( "SELECT option_id FROM {$wpdb->options} WHERE option_name=%s", $old_name ) ) ) {
			return new WP_Error( 'sakolaan_migration_option_cleanup', sprintf( /* translators: %s: option name. */ __( 'Option lama belum terhapus: %s', 'sakolaan' ), $old_name ) );
		}
	}

	$meta_specs = array(
		'postmeta'    => array( $wpdb->postmeta, 'meta_id', 'post_id' ),
		'termmeta'    => array( $wpdb->termmeta, 'meta_id', 'term_id' ),
		'usermeta'    => array( $wpdb->usermeta, 'umeta_id', 'user_id' ),
		'commentmeta' => array( $wpdb->commentmeta, 'meta_id', 'comment_id' ),
	);
	foreach ( $meta_specs as $name => $spec ) {
		foreach ( $snapshot['meta'][ $name ] as $row ) {
			$new_key = sakolaan_migration_meta_key( $row['meta_key'] );
			$new_value = maybe_serialize( sakolaan_migration_transform_value( maybe_unserialize( $row['meta_value'] ) ) );
			$found = $wpdb->get_var( $wpdb->prepare( 'SELECT %i FROM %i WHERE %i=%d AND meta_key=%s AND meta_value=%s LIMIT 1', $spec[1], $spec[0], $spec[2], $row[ $spec[2] ], $new_key, $new_value ) );
			if ( null === $found ) {
				return new WP_Error( 'sakolaan_migration_meta_verify', sprintf( /* translators: 1: table, 2: metadata key. */ __( 'Validasi metadata migrasi gagal pada %1$s: %2$s', 'sakolaan' ), $name, $new_key ) );
			}
		}
	}

	foreach ( $snapshot['posts'] as $row ) {
		$current = $wpdb->get_row( $wpdb->prepare( "SELECT post_name, post_content FROM {$wpdb->posts} WHERE ID=%d", $row['ID'] ), ARRAY_A );
		$expected_name = ( 'custom_css' === $row['post_type'] && 'school-theme' === $row['post_name'] ) ? 'pijar-sakolaan' : $row['post_name'];
		if ( ! $current || $expected_name !== $current['post_name'] || sakolaan_migration_transform_string( $row['post_content'] ) !== $current['post_content'] ) {
			return new WP_Error( 'sakolaan_migration_post_verify', sprintf( /* translators: %d: post ID. */ __( 'Validasi konten migrasi gagal pada ID %d.', 'sakolaan' ), $row['ID'] ) );
		}
	}
	return true;
}

/** Execute the database rename after snapshot and conflict checks pass. */
function sakolaan_migration_apply( $snapshot ) {
	global $wpdb;
	$wpdb->last_error = '';
	if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
		return new WP_Error( 'sakolaan_migration_transaction', __( 'Database tidak mendukung transaksi migrasi yang aman.', 'sakolaan' ) );
	}
	try {
		foreach ( $snapshot['options'] as $row ) {
			$old_name = $row['option_name'];
			$new_name = sakolaan_migration_option_name( $old_name );
			$value = maybe_unserialize( $row['option_value'] );
			if ( 'template' === $old_name || 'stylesheet' === $old_name ) {
				$value = 'pijar-sakolaan';
			} elseif ( 'active_plugins' === $old_name && is_array( $value ) ) {
				$value = array_values( array_map( static function ( $plugin ) { return 'school-core/school-core.php' === $plugin ? 'sakolaan/sakolaan.php' : $plugin; }, $value ) );
			} else {
				$value = sakolaan_migration_transform_value( $value );
			}
			$encoded = maybe_serialize( $value );
			if ( $old_name !== $new_name ) {
				$existing = $wpdb->get_var( $wpdb->prepare( "SELECT option_id FROM {$wpdb->options} WHERE option_name=%s", $new_name ) );
				if ( $existing ) {
					$wpdb->update( $wpdb->options, array( 'option_value' => $encoded ), array( 'option_name' => $new_name ), array( '%s' ), array( '%s' ) );
				} else {
					$wpdb->insert( $wpdb->options, array( 'option_name' => $new_name, 'option_value' => $encoded, 'autoload' => $row['autoload'] ), array( '%s', '%s', '%s' ) );
				}
				$wpdb->delete( $wpdb->options, array( 'option_name' => $old_name ), array( '%s' ) );
			} else {
				$wpdb->update( $wpdb->options, array( 'option_value' => $encoded ), array( 'option_name' => $old_name ), array( '%s' ), array( '%s' ) );
			}
		}

		$meta_specs = array(
			'postmeta'    => array( $wpdb->postmeta, 'meta_id', 'post_id' ),
			'termmeta'    => array( $wpdb->termmeta, 'meta_id', 'term_id' ),
			'usermeta'    => array( $wpdb->usermeta, 'umeta_id', 'user_id' ),
			'commentmeta' => array( $wpdb->commentmeta, 'meta_id', 'comment_id' ),
		);
		foreach ( $meta_specs as $name => $spec ) {
			foreach ( $snapshot['meta'][ $name ] as $row ) {
				$new_key = sakolaan_migration_meta_key( $row['meta_key'] );
				$new_value = maybe_serialize( sakolaan_migration_transform_value( maybe_unserialize( $row['meta_value'] ) ) );
				$duplicate = $wpdb->get_var( $wpdb->prepare( 'SELECT %i FROM %i WHERE %i=%d AND meta_key=%s AND meta_value=%s LIMIT 1', $spec[1], $spec[0], $spec[2], $row[ $spec[2] ], $new_key, $new_value ) );
				if ( $duplicate ) {
					$wpdb->delete( $spec[0], array( $spec[1] => $row[ $spec[1] ] ), array( '%d' ) );
				} else {
					$wpdb->update( $spec[0], array( 'meta_key' => $new_key, 'meta_value' => $new_value ), array( $spec[1] => $row[ $spec[1] ] ), array( '%s', '%s' ), array( '%d' ) );
				}
			}
		}

		foreach ( $snapshot['posts'] as $row ) {
			$data = array( 'post_content' => sakolaan_migration_transform_string( $row['post_content'] ) );
			if ( 'custom_css' === $row['post_type'] && 'school-theme' === $row['post_name'] ) {
				$data['post_name'] = 'pijar-sakolaan';
			}
			$wpdb->update( $wpdb->posts, $data, array( 'ID' => $row['ID'] ), array_fill( 0, count( $data ), '%s' ), array( '%d' ) );
		}

		foreach ( $snapshot['theme_terms'] as $row ) {
			if ( 'school-theme' === $row['slug'] ) {
				$wpdb->update( $wpdb->terms, array( 'name' => 'pijar-sakolaan', 'slug' => 'pijar-sakolaan' ), array( 'term_id' => $row['term_id'] ), array( '%s', '%s' ), array( '%d' ) );
			}
		}
		if ( $wpdb->last_error ) {
			throw new RuntimeException( $wpdb->last_error );
		}
		$verified = sakolaan_migration_verify_applied( $snapshot );
		if ( is_wp_error( $verified ) ) {
			throw new RuntimeException( $verified->get_error_message() );
		}

		$wpdb->query( 'COMMIT' );
		wp_cache_flush();
		return true;
	} catch ( Throwable $error ) {
		$wpdb->query( 'ROLLBACK' );
		wp_cache_flush();
		return new WP_Error( 'sakolaan_migration_failed', $error->getMessage() );
	}
}

/** Remove an obsolete activation path after WordPress finishes activating the renamed plugin. */
function sakolaan_cleanup_legacy_active_plugin() {
	$plugins = get_option( 'active_plugins', array() );
	if ( ! is_array( $plugins ) || ! in_array( 'school-core/school-core.php', $plugins, true ) ) {
		return;
	}
	$plugins = array_values( array_diff( $plugins, array( 'school-core/school-core.php' ) ) );
	if ( ! in_array( 'sakolaan/sakolaan.php', $plugins, true ) ) {
		$plugins[] = 'sakolaan/sakolaan.php';
	}
	update_option( 'active_plugins', $plugins );
}
add_action( 'admin_init', 'sakolaan_cleanup_legacy_active_plugin', 1 );

/** Run or retry the complete legacy migration. */
function sakolaan_run_legacy_migration() {
	if ( (int) get_option( 'sakolaan_migration_version', 0 ) >= SAKOLAAN_MIGRATION_VERSION ) {
		return true;
	}
	$snapshot = sakolaan_migration_snapshot_data();
	$has_data = ! empty( $snapshot['options'] ) || ! empty( $snapshot['posts'] ) || ! empty( $snapshot['theme_terms'] );
	foreach ( $snapshot['meta'] as $rows ) {
		$has_data = $has_data || ! empty( $rows );
	}
	if ( ! $has_data ) {
		update_option( 'sakolaan_migration_version', SAKOLAAN_MIGRATION_VERSION, false );
		update_option( 'sakolaan_migration_status', 'complete', false );
		delete_option( 'sakolaan_migration_error' );
		return true;
	}
	$path = sakolaan_migration_write_snapshot( $snapshot );
	if ( is_wp_error( $path ) ) {
		update_option( 'sakolaan_migration_status', 'failed', false );
		update_option( 'sakolaan_migration_error', $path->get_error_message(), false );
		return $path;
	}
	$preflight = sakolaan_migration_preflight( $snapshot );
	if ( is_wp_error( $preflight ) ) {
		update_option( 'sakolaan_migration_status', 'failed', false );
		update_option( 'sakolaan_migration_error', $preflight->get_error_message(), false );
		return $preflight;
	}
	$result = sakolaan_migration_apply( $snapshot );
	if ( is_wp_error( $result ) ) {
		update_option( 'sakolaan_migration_status', 'failed', false );
		update_option( 'sakolaan_migration_error', $result->get_error_message(), false );
		return $result;
	}
	update_option( 'sakolaan_migration_version', SAKOLAAN_MIGRATION_VERSION, false );
	update_option( 'sakolaan_migration_status', 'complete', false );
	delete_option( 'sakolaan_migration_error' );
	return true;
}

/** Read and validate the latest migration snapshot. */
function sakolaan_migration_read_snapshot() {
	$record = get_option( 'sakolaan_migration_snapshot', array() );
	$path = is_array( $record ) ? ( $record['path'] ?? '' ) : '';
	if ( ! $path || ! is_readable( $path ) ) {
		return new WP_Error( 'sakolaan_snapshot_missing', __( 'Snapshot migrasi tidak ditemukan.', 'sakolaan' ) );
	}
	$wrapper = json_decode( (string) file_get_contents( $path ), true );
	$snapshot = $wrapper['snapshot'] ?? null;
	$checksum = is_array( $snapshot ) ? hash( 'sha256', wp_json_encode( $snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) : '';
	$counts_match = is_array( $snapshot ) && ( $snapshot['counts'] ?? null ) === sakolaan_migration_snapshot_counts( $snapshot['data'] ?? array() );
	if ( ! is_array( $snapshot ) || ! $counts_match || 'sakolaan-migration' !== ( $snapshot['format'] ?? '' ) || ! hash_equals( (string) ( $wrapper['checksum'] ?? '' ), $checksum ) ) {
		return new WP_Error( 'sakolaan_snapshot_invalid', __( 'Checksum snapshot migrasi tidak cocok.', 'sakolaan' ) );
	}
	return $snapshot;
}

/** Restore the exact legacy rows captured before migration. */
function sakolaan_migration_restore_snapshot() {
	global $wpdb;
	$snapshot = sakolaan_migration_read_snapshot();
	if ( is_wp_error( $snapshot ) ) {
		return $snapshot;
	}
	if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
		return new WP_Error( 'sakolaan_restore_transaction', __( 'Database tidak mendukung transaksi pemulihan yang aman.', 'sakolaan' ) );
	}
	try {
		foreach ( $snapshot['data']['options'] as $row ) {
			$new_name = sakolaan_migration_option_name( $row['option_name'] );
			if ( $new_name !== $row['option_name'] ) {
				$wpdb->delete( $wpdb->options, array( 'option_name' => $new_name ), array( '%s' ) );
			}
			$wpdb->replace( $wpdb->options, $row, array( '%d', '%s', '%s', '%s' ) );
		}
		foreach ( $snapshot['data']['destination_options'] as $row ) {
			$wpdb->replace( $wpdb->options, $row, array( '%d', '%s', '%s', '%s' ) );
		}

		$meta_specs = array(
			'postmeta'    => array( $wpdb->postmeta, 'meta_id', 'post_id' ),
			'termmeta'    => array( $wpdb->termmeta, 'meta_id', 'term_id' ),
			'usermeta'    => array( $wpdb->usermeta, 'umeta_id', 'user_id' ),
			'commentmeta' => array( $wpdb->commentmeta, 'meta_id', 'comment_id' ),
		);
		foreach ( $meta_specs as $name => $spec ) {
			foreach ( $snapshot['data']['meta'][ $name ] as $row ) {
				$wpdb->delete( $spec[0], array( $spec[2] => $row[ $spec[2] ], 'meta_key' => sakolaan_migration_meta_key( $row['meta_key'] ) ), array( '%d', '%s' ) );
				$wpdb->replace( $spec[0], $row );
			}
			foreach ( $snapshot['data']['destination_meta'][ $name ] as $row ) {
				$wpdb->replace( $spec[0], $row );
			}
		}
		foreach ( $snapshot['data']['posts'] as $row ) {
			$wpdb->update( $wpdb->posts, array( 'post_name' => $row['post_name'], 'post_content' => $row['post_content'] ), array( 'ID' => $row['ID'] ), array( '%s', '%s' ), array( '%d' ) );
		}
		foreach ( $snapshot['data']['theme_terms'] as $row ) {
			$wpdb->update( $wpdb->terms, array( 'name' => $row['name'], 'slug' => $row['slug'] ), array( 'term_id' => $row['term_id'] ), array( '%s', '%s' ), array( '%d' ) );
		}
		if ( $wpdb->last_error ) {
			throw new RuntimeException( $wpdb->last_error );
		}
		$wpdb->query( 'COMMIT' );
		delete_option( 'sakolaan_migration_version' );
		update_option( 'sakolaan_migration_status', 'restored', false );
		wp_cache_flush();
		return true;
	} catch ( Throwable $error ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'sakolaan_restore_failed', $error->getMessage() );
	}
}

function sakolaan_migration_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) || 'failed' !== get_option( 'sakolaan_migration_status' ) ) {
		return;
	}
	$message = get_option( 'sakolaan_migration_error', __( 'Migrasi Sakolaan belum selesai.', 'sakolaan' ) );
	printf( '<div class="notice sakolaan-notice notice-error"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>', esc_html__( 'Migrasi Sakolaan dibatalkan.', 'sakolaan' ), esc_html( $message ), esc_url( sakolaan_admin_url( 'more', array( 'panel' => 'system', 'tool' => 'maintenance' ) ) ), esc_html__( 'Buka alat pemulihan', 'sakolaan' ) );
}
add_action( 'admin_notices', 'sakolaan_migration_admin_notice' );

function sakolaan_handle_retry_migration() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) ); }
	check_admin_referer( 'sakolaan_retry_migration' );
	$result = sakolaan_run_legacy_migration();
	$status = is_wp_error( $result ) ? 'migration-failed' : 'migration-complete';
	wp_safe_redirect( sakolaan_admin_url( 'more', array( 'panel' => 'system', 'tool' => 'maintenance', 'updated' => $status ) ) );
	exit;
}
add_action( 'admin_post_sakolaan_retry_migration', 'sakolaan_handle_retry_migration' );

function sakolaan_handle_restore_migration() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) ); }
	check_admin_referer( 'sakolaan_restore_migration' );
	$confirmation = isset( $_POST['confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmation'] ) ) : '';
	if ( 'PULIHKAN MIGRASI' !== $confirmation ) { wp_die( esc_html__( 'Teks konfirmasi tidak sesuai.', 'sakolaan' ), '', array( 'response' => 400 ) ); }
	$result = sakolaan_migration_restore_snapshot();
	$status = is_wp_error( $result ) ? 'migration-failed' : 'migration-restored';
	if ( is_wp_error( $result ) ) { update_option( 'sakolaan_migration_error', $result->get_error_message(), false ); }
	wp_safe_redirect( sakolaan_admin_url( 'more', array( 'panel' => 'system', 'tool' => 'maintenance', 'updated' => $status ) ) );
	exit;
}
add_action( 'admin_post_sakolaan_restore_migration', 'sakolaan_handle_restore_migration' );

function sakolaan_handle_download_migration_snapshot() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) ); }
	check_admin_referer( 'sakolaan_download_migration_snapshot' );
	$record = get_option( 'sakolaan_migration_snapshot', array() );
	$path = is_array( $record ) ? ( $record['path'] ?? '' ) : '';
	if ( ! $path || ! is_readable( $path ) ) { wp_die( esc_html__( 'Snapshot migrasi tidak ditemukan.', 'sakolaan' ), '', array( 'response' => 404 ) ); }
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( basename( $path ) ) . '"' );
	$contents = file_get_contents( $path );
	if ( false === $contents ) { wp_die( esc_html__( 'Snapshot migrasi tidak dapat dibaca.', 'sakolaan' ), '', array( 'response' => 500 ) ); }
	echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Checksummed JSON download.
	exit;
}
add_action( 'admin_post_sakolaan_download_migration_snapshot', 'sakolaan_handle_download_migration_snapshot' );

/** Render migration status and recovery actions inside System > Maintenance. */
function sakolaan_render_migration_tools() {
	$status = get_option( 'sakolaan_migration_status', 'pending' );
	$record = get_option( 'sakolaan_migration_snapshot', array() );
	echo '<div class="sakolaan-migration-status"><h4>' . esc_html__( 'Migrasi identitas Sakolaan', 'sakolaan' ) . '</h4>';
	printf( '<p>%s</p>', esc_html( sprintf( /* translators: %s: migration status. */ __( 'Status: %s', 'sakolaan' ), $status ) ) );
	if ( 'failed' === $status ) {
		printf( '<p class="description">%s</p>', esc_html( get_option( 'sakolaan_migration_error', '' ) ) );
	}
	echo '<div class="sakolaan-migration-actions">';
	printf( '<a class="button" href="%1$s">%2$s</a>', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sakolaan_retry_migration' ), 'sakolaan_retry_migration' ) ), esc_html__( 'Jalankan ulang migrasi', 'sakolaan' ) );
	if ( ! empty( $record['path'] ) ) {
		printf( '<a class="button" href="%1$s">%2$s</a>', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sakolaan_download_migration_snapshot' ), 'sakolaan_download_migration_snapshot' ) ), esc_html__( 'Unduh snapshot migrasi', 'sakolaan' ) );
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sakolaan_restore_migration">';
		wp_nonce_field( 'sakolaan_restore_migration' );
		echo '<label for="sakolaan-restore-confirmation">' . esc_html__( 'Ketik PULIHKAN MIGRASI untuk mengembalikan snapshot:', 'sakolaan' ) . '</label><input id="sakolaan-restore-confirmation" name="confirmation" type="text" required autocomplete="off"><button class="button button-delete" type="submit">' . esc_html__( 'Pulihkan snapshot', 'sakolaan' ) . '</button></form>';
	}
	echo '</div></div>';
}
