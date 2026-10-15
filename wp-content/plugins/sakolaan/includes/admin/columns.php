<?php
/**
 * Kolom daftar di admin: administrator melihat data penting tanpa membuka tiap entri.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Definisi kolom per post type.
 *
 * Setiap kolom: label, type (photo|meta|event_status|gallery_count|order), key (meta tanpa awalan),
 * sortable (nilai orderby, opsional).
 *
 * @return array
 */
function sakolaan_get_admin_columns() {
	$schema = sakolaan_get_meta_schema();
	$result = array();
	foreach ( sakolaan_content_registry() as $post_type => $item ) {
		if ( empty( $item['columns'] ) ) {
			continue;
		}
		$result[ $post_type ] = array();
		foreach ( $item['columns'] as $column ) {
			$key = 'sakolaan_' . ( 'event_status' === $column ? 'status' : ( 'gallery_count' === $column ? 'count' : $column ) );
			if ( 'photo' === $column ) {
				$result[ $post_type ][ $key ] = array( 'label' => 'hero_slide' === $post_type ? __( 'Gambar', 'sakolaan' ) : __( 'Foto', 'sakolaan' ), 'type' => 'photo' );
			} elseif ( 'order' === $column ) {
				$result[ $post_type ][ $key ] = array( 'label' => __( 'Urutan', 'sakolaan' ), 'type' => 'order', 'sortable' => 'menu_order' );
			} elseif ( 'event_status' === $column ) {
				$result[ $post_type ][ $key ] = array( 'label' => __( 'Status', 'sakolaan' ), 'type' => 'event_status' );
			} elseif ( 'gallery_count' === $column ) {
				$result[ $post_type ][ $key ] = array( 'label' => __( 'Jumlah foto', 'sakolaan' ), 'type' => 'gallery_count' );
			} else {
				$definition = array( 'label' => $schema[ $post_type ]['fields'][ $column ]['label'] ?? ucfirst( str_replace( '_', ' ', $column ) ), 'type' => 'meta', 'key' => $column );
				if ( in_array( $column, array( 'tanggal', 'tanggal_mulai', 'tahun' ), true ) ) {
					$definition['sortable'] = '_sakolaan_' . $column;
				}
				$result[ $post_type ][ $key ] = $definition;
			}
		}
	}
	return $result;
}

/**
 * Pasang filter kolom untuk setiap post type.
 *
 * @return void
 */
function sakolaan_register_admin_columns() {
	foreach ( array_keys( sakolaan_get_admin_columns() ) as $post_type ) {
		add_filter( "manage_{$post_type}_posts_columns", 'sakolaan_filter_columns' );
		add_action( "manage_{$post_type}_posts_custom_column", 'sakolaan_render_column', 10, 2 );
		add_filter( "manage_edit-{$post_type}_sortable_columns", 'sakolaan_filter_sortable_columns' );
	}
	add_action( 'restrict_manage_posts', 'sakolaan_render_order_toolbar', 20, 2 );
	add_action( 'admin_notices', 'sakolaan_render_order_form', 1 );
}
add_action( 'admin_init', 'sakolaan_register_admin_columns' );

/**
 * Tambah kolom setelah judul.
 *
 * @param array $columns Kolom saat ini.
 * @return array
 */
function sakolaan_filter_columns( $columns ) {
	$screen = get_current_screen();
	$config = $screen ? sakolaan_get_admin_columns() : array();

	if ( ! $screen || empty( $config[ $screen->post_type ] ) ) {
		return $columns;
	}

	$result = array();

	foreach ( $columns as $key => $label ) {
		$result[ $key ] = $label;

		if ( 'title' === $key ) {
			foreach ( $config[ $screen->post_type ] as $column => $definition ) {
				$result[ $column ] = $definition['label'];
			}
		}
	}

	return $result;
}

/**
 * Isi sel kolom. Semua output di-escape.
 *
 * @param string $column  Slug kolom.
 * @param int    $post_id ID post.
 * @return void
 */
function sakolaan_render_column( $column, $post_id ) {
	$post_type = get_post_type( $post_id );
	$config    = sakolaan_get_admin_columns();

	if ( ! $post_type || empty( $config[ $post_type ][ $column ] ) ) {
		return;
	}

	$definition = $config[ $post_type ][ $column ];

	switch ( $definition['type'] ) {
		case 'photo':
			echo has_post_thumbnail( $post_id ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output WordPress core.
				? get_the_post_thumbnail( $post_id, array( 48, 48 ), array( 'alt' => '' ) )
				: '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'Belum ada foto', 'sakolaan' ) . '</span>';
			break;

		case 'meta':
			$value = get_post_meta( $post_id, '_sakolaan_' . $definition['key'], true );
			echo is_scalar( $value ) && '' !== (string) $value ? esc_html( (string) $value ) : '<span aria-hidden="true">&mdash;</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Konstanta HTML statis.
			break;

		case 'event_status':
			$labels = sakolaan_get_event_status_labels();
			$status = sakolaan_get_event_status( $post_id );
			echo esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : '' );
			break;

		case 'gallery_count':
			$ids = get_post_meta( $post_id, '_sakolaan_gambar_ids', true );
			echo esc_html( (string) ( is_array( $ids ) ? count( $ids ) : 0 ) );
			break;

		case 'order':
			$position = (int) get_post_field( 'menu_order', $post_id );
			if ( sakolaan_is_order_mode() ) {
				echo '<span class="sakolaan-order-handle dashicons dashicons-menu" draggable="true" data-order-handle aria-hidden="true"></span>';
				echo '<button type="button" class="button-link sakolaan-order-move" data-move="up" aria-label="' . esc_attr__( 'Naikkan item', 'sakolaan' ) . '"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>';
				echo '<button type="button" class="button-link sakolaan-order-move" data-move="down" aria-label="' . esc_attr__( 'Turunkan item', 'sakolaan' ) . '"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>';
			}
			echo '<span class="sakolaan-order-badge">' . esc_html( (string) $position ) . '</span>';
			break;
	}
}

/**
 * Tandai kolom yang dapat diurutkan.
 *
 * @param array $columns Kolom yang dapat diurutkan.
 * @return array
 */
function sakolaan_filter_sortable_columns( $columns ) {
	$screen = get_current_screen();
	$config = sakolaan_get_admin_columns();

	if ( ! $screen || empty( $config[ $screen->post_type ] ) ) {
		return $columns;
	}

	foreach ( $config[ $screen->post_type ] as $column => $definition ) {
		if ( ! empty( $definition['sortable'] ) ) {
			$columns[ $column ] = $definition['sortable'];
		}
	}

	return $columns;
}

/**
 * Terapkan pengurutan: meta untuk kolom meta, dan urutan tampilan sebagai default guru.
 *
 * @param WP_Query $query Query admin.
 * @return void
 */
function sakolaan_admin_sort_query( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$post_type = (string) $query->get( 'post_type' );
	$config    = sakolaan_get_admin_columns();

	if ( empty( $config[ $post_type ] ) ) {
		return;
	}

	if ( sakolaan_is_order_mode() && in_array( $post_type, sakolaan_get_orderable_post_types(), true ) ) {
		$query->set( 'posts_per_page', -1 );
		$query->set( 'nopaging', true );
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		$query->set( 'order', 'ASC' );
	}

	$orderby = $query->get( 'orderby' );
	// Multi-column ordering is already complete; never cast it to a string.
	if ( is_array( $orderby ) ) {
		return;
	}
	$orderby = (string) $orderby;
	$allowed = array( '_sakolaan_tanggal', '_sakolaan_tanggal_mulai', '_sakolaan_tahun' );

	if ( in_array( $orderby, $allowed, true ) ) {
		$query->set( 'meta_key', $orderby );
		$query->set( 'orderby', 'meta_value' );

		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Hanya membaca parameter urutan tampilan.
	if ( '' === $orderby && in_array( $post_type, sakolaan_get_orderable_post_types(), true ) && empty( $_GET['orderby'] ) ) {
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			)
		);
	}
}
add_action( 'pre_get_posts', 'sakolaan_admin_sort_query' );

/**
 * Apakah layar daftar sedang berada pada mode ordering.
 *
 * @return bool
 */
function sakolaan_is_order_mode() {
	$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'post'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Mode tampilan.
	$mode      = isset( $_GET['sakolaan_order_mode'] ) ? sanitize_key( wp_unslash( $_GET['sakolaan_order_mode'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list display mode.

	return '1' === $mode && in_array( $post_type, sakolaan_get_orderable_post_types(), true );
}

/**
 * Tombol masuk/keluar mode ordering di toolbar list.
 *
 * @param string $post_type Post type.
 * @param string $which Posisi toolbar.
 * @return void
 */
function sakolaan_render_order_toolbar( $post_type, $which ) {
	if ( 'top' !== $which || ! in_array( $post_type, sakolaan_get_orderable_post_types(), true ) ) {
		return;
	}
	$base_url = admin_url( 'edit.php' . ( 'post' === $post_type ? '' : '?post_type=' . $post_type ) );
	if ( sakolaan_is_order_mode() ) {
		printf( '<span class="sakolaan-order-toolbar"><span class="sakolaan-order-mode-label">%1$s</span><button type="submit" form="sakolaan-order-form" class="button button-primary">%2$s</button> <a class="button" href="%3$s">%4$s</a></span>', esc_html__( 'Mode manual', 'sakolaan' ), esc_html__( 'Simpan Urutan', 'sakolaan' ), esc_url( $base_url ), esc_html__( 'Batal', 'sakolaan' ) );
	} else {
		printf( '<span class="sakolaan-order-toolbar"><span class="sakolaan-order-mode-label">%1$s</span><a class="button sakolaan-order-launch" href="%2$s">%3$s</a></span>', esc_html__( 'Manual', 'sakolaan' ), esc_url( add_query_arg( 'sakolaan_order_mode', '1', $base_url ) ), esc_html__( 'Atur Urutan', 'sakolaan' ) );
	}
}

/**
 * Tampilkan feedback penyimpanan ordering dan taxonomy pada list screen.
 *
 * @return void
 */
function sakolaan_render_list_admin_notices() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit' !== $screen->base ) {
		return;
	}

	$order_status = isset( $_GET['sakolaan_order_status'] ) ? sanitize_key( wp_unslash( $_GET['sakolaan_order_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Status hanya berasal dari redirect admin.
	$taxonomy_status  = isset( $_GET['sakolaan_taxonomy_status'] ) ? sanitize_key( wp_unslash( $_GET['sakolaan_taxonomy_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Status hanya berasal dari redirect admin.
	$taxonomy_message = isset( $_GET['sakolaan_taxonomy_message'] ) ? sanitize_text_field( wp_unslash( $_GET['sakolaan_taxonomy_message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Pesan hanya berasal dari redirect admin.

	if ( 'success' === $order_status ) {
		printf( '<div class="notice sakolaan-notice notice-success is-dismissible"><p>%s</p></div>', esc_html__( 'Urutan berhasil disimpan.', 'sakolaan' ) );
	} elseif ( 'partial' === $order_status ) {
		printf( '<div class="notice sakolaan-notice notice-warning is-dismissible"><p>%s</p></div>', esc_html__( 'Sebagian urutan tidak dapat disimpan. Item yang valid sudah dipertahankan.', 'sakolaan' ) );
	}

	if ( 'success' === $taxonomy_status ) {
		printf( '<div class="notice sakolaan-notice notice-success is-dismissible"><p>%s</p></div>', esc_html__( 'Taksonomi berhasil diperbarui.', 'sakolaan' ) );
	} elseif ( 'error' === $taxonomy_status ) {
		printf( '<div class="notice sakolaan-notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $taxonomy_message ? $taxonomy_message : __( 'Taksonomi tidak dapat diperbarui.', 'sakolaan' ) ) );
	}
}
add_action( 'admin_notices', 'sakolaan_render_list_admin_notices' );

/**
 * Form penyimpanan mode ordering di luar form tabel WordPress.
 *
 * @return void
 */
function sakolaan_render_order_form() {
	if ( ! sakolaan_is_order_mode() ) {
		return;
	}
	$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only form rendering.
	$object = $post_type ? get_post_type_object( $post_type ) : null;
	if ( ! $object || ! current_user_can( $object->cap->edit_posts ) ) {
		return;
	}
	?>
	<form id="sakolaan-order-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sakolaan-order-form">
		<input type="hidden" name="action" value="sakolaan_save_content_order">
		<input type="hidden" name="post_type" value="<?php echo esc_attr( $post_type ); ?>">
		<?php wp_nonce_field( 'sakolaan_content_order_' . $post_type ); ?>
		<div data-sakolaan-order-inputs></div>
		<p class="sakolaan-order-status" role="status" aria-live="polite"><?php esc_html_e( 'Seret baris menggunakan ikon menu, atau gunakan tombol keyboard pada baris.', 'sakolaan' ); ?></p>
	</form>
	<?php
}

/**
 * Muat gaya kolom di halaman daftar.
 *
 * @param string $hook_suffix Halaman admin saat ini.
 * @return void
 */
function sakolaan_enqueue_list_assets( $hook_suffix ) {
	if ( 'edit.php' !== $hook_suffix ) {
		return;
	}

	$screen = get_current_screen();

	if ( $screen && isset( sakolaan_get_admin_columns()[ $screen->post_type ] ) ) {
		sakolaan_enqueue_admin_style();
		if ( in_array( $screen->post_type, sakolaan_get_orderable_post_types(), true ) ) {
			wp_enqueue_script( 'sakolaan-list-order', SAKOLAAN_URL . 'assets/js/list-order.js', array(), sakolaan_admin_asset_version( 'assets/js/list-order.js' ), true );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'sakolaan_enqueue_list_assets' );
