<?php
/**
 * Halaman Pengaturan Sakolaan berbasis Settings API.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine which settings assets the current Sakolaan route actually needs.
 *
 * @return array{script:bool,media:bool,sortable:bool}
 */
function sakolaan_settings_asset_requirements() {
	$requirements = array( 'script' => false, 'media' => false, 'sortable' => false );
	$screen = get_current_screen();
	if ( ! current_user_can( 'manage_options' ) || ! $screen || 'toplevel_page_sakolaan' !== $screen->id ) {
		return $requirements;
	}

	$view      = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'summary'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Navigation only.
	$panel     = isset( $_GET['panel'] ) ? sanitize_key( wp_unslash( $_GET['panel'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Navigation only.
	$area      = isset( $_GET['area'] ) ? sanitize_key( wp_unslash( $_GET['area'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Navigation only.
	$component = isset( $_GET['component'] ) ? sanitize_key( wp_unslash( $_GET['component'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Navigation only.
	$tool      = isset( $_GET['tool'] ) ? sanitize_key( wp_unslash( $_GET['tool'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Navigation only.

	if ( 'identity' === $view && in_array( $panel, array( 'identity', 'principal', 'contact', 'social', 'statistics' ), true ) ) {
		$requirements['script'] = true;
		$requirements['media']  = in_array( $panel, array( 'identity', 'principal' ), true );
	} elseif ( 'seo' === $view ) {
		$requirements['script'] = true;
		$requirements['media']  = true;
	} elseif ( 'content' === $view ) {
		if ( 'ppdb' === $panel ) {
			$requirements['script'] = true;
		} elseif ( 'appearance' === $panel && in_array( $area, array( 'header', 'footer' ), true ) ) {
			$requirements['script'] = true;
		} elseif ( 'appearance' === $panel && 'homepage' === $area && in_array( $component, array( 'sections', 'hero', 'quick-links', 'listing' ), true ) ) {
			$requirements['script']   = true;
			$requirements['media']    = in_array( $component, array( 'sections', 'hero', 'quick-links' ), true );
			$requirements['sortable'] = in_array( $component, array( 'sections', 'quick-links' ), true );
		}
	} elseif ( 'more' === $view && ( 'demo' === $panel || ( 'system' === $panel && 'advanced' === $tool ) ) ) {
		$requirements['script'] = true;
	}

	return $requirements;
}

/** Enqueue only the settings assets required by the current route. */
function sakolaan_enqueue_route_settings_assets() {
	$requirements = sakolaan_settings_asset_requirements();
	if ( $requirements['script'] ) {
		sakolaan_enqueue_settings_assets( $requirements );
	}
}
add_action( 'admin_enqueue_scripts', 'sakolaan_enqueue_route_settings_assets', 10 );

/**
 * Daftarkan option, section, dan field.
 *
 * @return void
 */
function sakolaan_register_settings() {
	register_setting(
		'sakolaan_settings_group',
		'sakolaan_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'sakolaan_sanitize_settings',
			'default'           => sakolaan_get_settings_defaults(),
			'show_in_rest'      => false,
		)
	);

	foreach ( sakolaan_get_settings_schema() as $group => $definition ) {
		$page = 'sakolaan-' . $group;

		add_settings_section(
			'sakolaan_section_' . $group,
			'',
			'sakolaan_render_section',
			$page,
			array(
				'group' => $group,
				'intro' => isset( $definition['intro'] ) ? $definition['intro'] : '',
			)
		);

		foreach ( $definition['fields'] as $key => $field ) {
			add_settings_field(
				'sakolaan_' . $group . '_' . $key,
				$field['label'],
				'sakolaan_render_field',
				$page,
				'sakolaan_section_' . $group,
				array(
					'group'     => $group,
					'key'       => $key,
					'field'     => $field,
					'class'     => 'sakolaan-settings-row--' . sanitize_html_class( $field['type'] ),
					'label_for' => 'sakolaan-' . $group . '-' . $key,
				)
			);
		}
	}
}
add_action( 'admin_init', 'sakolaan_register_settings' );

/**
 * Muat media uploader dan asset halaman pengaturan.
 *
 * @return void
 */
function sakolaan_enqueue_settings_assets( $requirements = array() ) {
	$requirements = wp_parse_args( $requirements, array( 'media' => true, 'sortable' => true ) );
	if ( $requirements['media'] ) {
		wp_enqueue_media();
	}
	$dependencies = array( 'jquery' );
	if ( $requirements['sortable'] ) {
		wp_enqueue_script( 'jquery-ui-sortable' );
		$dependencies[] = 'jquery-ui-sortable';
	}
	sakolaan_enqueue_admin_style();
	wp_enqueue_script( 'sakolaan-settings', SAKOLAAN_URL . 'assets/js/settings.js', $dependencies, sakolaan_admin_asset_version( 'assets/js/settings.js' ), true );
	wp_script_add_data( 'sakolaan-settings', 'defer', true );
	wp_localize_script(
		'sakolaan-settings',
		'sakolaanSettings',
		array(
			'title'        => __( 'Pilih gambar', 'sakolaan' ),
			'button'       => __( 'Gunakan gambar ini', 'sakolaan' ),
			'imageSelected' => __( 'Gambar dipilih. Simpan perubahan untuk menerapkannya.', 'sakolaan' ),
			'imageRemoved'  => __( 'Gambar dihapus. Simpan perubahan untuk menerapkannya.', 'sakolaan' ),
			'mediaUnavailable' => __( 'Pustaka media tidak tersedia. Muat ulang halaman lalu coba kembali.', 'sakolaan' ),
			'saving'       => __( 'Menyimpan pengaturan…', 'sakolaan' ),
			'processing'   => __( 'Memproses data demo. Jangan tutup halaman ini…', 'sakolaan' ),
		)
	);
}

/**
 * Render pembuka section: penanda group dan teks pengantar.
 *
 * @param array $args Argumen section.
 * @return void
 */
function sakolaan_render_section( $args ) {
	$section_group = isset( $args['id'] ) ? str_replace( 'sakolaan_section_', '', $args['id'] ) : '';

	printf(
		'<input type="hidden" name="%s" value="1">',
		esc_attr( 'sakolaan_settings[' . $section_group . '][__present]' )
	);

	$schema = sakolaan_get_settings_schema();

	if ( ! empty( $schema[ $section_group ]['intro'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $schema[ $section_group ]['intro'] ) );
	}
}

/**
 * Render satu field.
 *
 * @param array $args Argumen field.
 * @return void
 */
function sakolaan_render_field( $args ) {
	$group = $args['group'];
	$key   = $args['key'];
	$field = $args['field'];

	$settings = sakolaan_get_settings();
	$value    = isset( $settings[ $group ][ $key ] ) ? $settings[ $group ][ $key ] : '';
	$name     = 'sakolaan_settings[' . $group . '][' . $key . ']';
	$marker_name = 'sakolaan_settings[' . $group . '][' . $key . '_field]';
	$id       = $args['label_for'];
	$description_id   = $id . '-description';
	$describedby_attr = ! empty( $field['description'] ) ? ' aria-describedby="' . esc_attr( $description_id ) . '"' : '';

	printf( '<input type="hidden" name="%s" value="1">', esc_attr( $marker_name ) );

	switch ( $field['type'] ) {
		case 'quick_links':
			sakolaan_render_quick_links_field( $id, $name, $value );
			break;

		case 'textarea':
			printf(
				'<textarea id="%1$s" name="%2$s" rows="4" class="large-text"%4$s>%3$s</textarea>',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_textarea( (string) $value ),
				$describedby_attr // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Atribut disusun dari ID yang di-escape.
			);
			break;

		case 'checkbox':
			printf(
				'<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s%5$s> %4$s</label>',
				esc_attr( $id ),
				esc_attr( $name ),
				checked( ! empty( $value ), true, false ),
				esc_html__( 'Aktif', 'sakolaan' ),
				$describedby_attr // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Atribut disusun dari ID yang di-escape.
			);
			break;

		case 'image':
			sakolaan_render_image_field( $id, $name, (int) $value, ! empty( $field['description'] ) ? $description_id : '' );
			break;

		case 'select':
			printf( '<select id="%1$s" name="%2$s"%3$s>', esc_attr( $id ), esc_attr( $name ), $describedby_attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Atribut disusun dari ID yang di-escape.
			foreach ( isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array() as $option_value => $option_label ) {
				printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $option_value ), selected( (string) $value, (string) $option_value, false ), esc_html( $option_label ) );
			}
			echo '</select>';
			break;

		case 'section_order':
			sakolaan_render_section_order_field( $id, $name, is_array( $value ) ? $value : array() );
			break;

		case 'number':
			$min      = isset( $field['min'] ) ? (int) $field['min'] : 0;
			$max_attr = isset( $field['max'] ) ? ' max="' . esc_attr( (string) (int) $field['max'] ) . '"' : '';
			$step     = isset( $field['step'] ) ? (int) $field['step'] : 1;
			printf(
				'<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text" min="%4$s"%5$s step="%6$s"%7$s>',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( (string) $value ),
				esc_attr( (string) $min ),
				$max_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Nilai integer sudah di-escape.
				esc_attr( (string) $step ),
				$describedby_attr // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Atribut disusun dari ID yang di-escape.
			);
			break;

		default:
			$type = in_array( $field['type'], array( 'url', 'email' ), true ) ? $field['type'] : 'text';

			printf(
				'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text"%5$s>',
				esc_attr( $type ),
				esc_attr( $id ),
				esc_attr( $name ),
				'url' === $type ? esc_url( (string) $value ) : esc_attr( (string) $value ),
				$describedby_attr // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Atribut disusun dari ID yang di-escape.
			);
	}

	if ( ! empty( $field['description'] ) ) {
		printf( '<p id="%1$s" class="description">%2$s</p>', esc_attr( $description_id ), esc_html( $field['description'] ) );
	}
}

/**
 * Render editor tautan cepat.
 *
 * @param string $id    ID field.
 * @param string $name Nama field.
 * @param mixed  $value Data tersimpan.
 * @return void
 */
function sakolaan_render_quick_links_field( $id, $name, $value ) {
	if ( false === $value ) {
		$value = array(
			array( 'label' => __( 'Berita', 'sakolaan' ), 'url' => '/berita/', 'enabled' => true ),
			array( 'label' => __( 'Pengumuman', 'sakolaan' ), 'url' => '/pengumuman/', 'enabled' => true ),
			array( 'label' => __( 'Agenda', 'sakolaan' ), 'url' => '/agenda/', 'enabled' => true ),
			array( 'label' => __( 'PPDB', 'sakolaan' ), 'url' => '/ppdb/', 'enabled' => true ),
			array( 'label' => __( 'Kontak', 'sakolaan' ), 'url' => '/kontak/', 'enabled' => true ),
		);
	}
	$value = is_array( $value ) ? array_values( $value ) : array();
	?>
	<div class="sakolaan-quick-links" id="<?php echo esc_attr( $id ); ?>" data-quick-links data-name="<?php echo esc_attr( $name ); ?>">
		<div class="sakolaan-quick-links__rows">
			<?php foreach ( $value as $index => $item ) : ?>
				<?php sakolaan_render_quick_link_row( $name, $index, $item ); ?>
			<?php endforeach; ?>
		</div>
		<div class="sakolaan-quick-links__toolbar">
			<button type="button" class="button button-primary" data-quick-link-add><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Tambah akses cepat', 'sakolaan' ); ?></button>
			<p class="description"><?php esc_html_e( 'Atur nama, tujuan, ikon, dan visibilitas tiap tautan. Seret untuk mengubah urutan atau gunakan tombol panah.', 'sakolaan' ); ?></p>
		</div>
		<template data-quick-link-template><?php sakolaan_render_quick_link_row( $name, '__INDEX__', array( 'label' => '', 'url' => '', 'enabled' => true ) ); ?></template>
	</div>
	<?php
}

/** Render satu baris tautan cepat. */
function sakolaan_render_quick_link_row( $name, $index, $item ) {
	$base = $name . '[' . $index . ']';
	$icon_options = array(
		''           => __( 'Tanpa ikon', 'sakolaan' ),
		'book'       => __( 'Buku', 'sakolaan' ),
		'calendar'   => __( 'Kalender', 'sakolaan' ),
		'bell'       => __( 'Pengumuman', 'sakolaan' ),
		'graduation' => __( 'Pendidikan', 'sakolaan' ),
		'phone'      => __( 'Telepon', 'sakolaan' ),
		'school'     => __( 'Sekolah', 'sakolaan' ),
		'external'   => __( 'Tautan eksternal', 'sakolaan' ),
	);
	?>
	<div class="sakolaan-quick-link" data-quick-link-row>
		<div class="sakolaan-quick-link__header">
			<span class="sakolaan-quick-link__handle dashicons dashicons-move" aria-hidden="true"></span>
			<span class="sakolaan-quick-link__heading" data-empty-label="<?php esc_attr_e( 'Tautan akses cepat', 'sakolaan' ); ?>"><?php echo esc_html( ! empty( $item['label'] ) ? $item['label'] : __( 'Tautan akses cepat', 'sakolaan' ) ); ?></span>
			<span class="sakolaan-quick-link__hint"><?php esc_html_e( 'Seret untuk mengurutkan', 'sakolaan' ); ?></span>
			<button type="button" class="button sakolaan-quick-link__toggle" data-quick-link-toggle data-expand-label="<?php esc_attr_e( 'Buka pengaturan', 'sakolaan' ); ?>" data-collapse-label="<?php esc_attr_e( 'Tutup pengaturan', 'sakolaan' ); ?>" aria-expanded="true" aria-label="<?php esc_attr_e( 'Tutup pengaturan', 'sakolaan' ); ?>" hidden><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>
		</div>
		<div class="sakolaan-quick-link__fields">
			<label><span><?php esc_html_e( 'Label tautan', 'sakolaan' ); ?></span><input type="text" name="<?php echo esc_attr( $base . '[label]' ); ?>" value="<?php echo esc_attr( isset( $item['label'] ) ? $item['label'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Contoh: Berita sekolah', 'sakolaan' ); ?>"></label>
			<label><span><?php esc_html_e( 'URL tujuan', 'sakolaan' ); ?></span><input type="text" inputmode="url" name="<?php echo esc_attr( $base . '[url]' ); ?>" value="<?php echo esc_attr( isset( $item['url'] ) ? $item['url'] : '' ); ?>" placeholder="/berita/"><small><?php esc_html_e( 'Gunakan /alamat/ untuk halaman internal.', 'sakolaan' ); ?></small></label>
			<label><span><?php esc_html_e( 'Ikon preset', 'sakolaan' ); ?></span><select name="<?php echo esc_attr( $base . '[icon]' ); ?>"><?php foreach ( $icon_options as $icon => $icon_label ) : ?><option value="<?php echo esc_attr( $icon ); ?>" <?php selected( isset( $item['icon'] ) ? $item['icon'] : '', $icon ); ?>><?php echo esc_html( $icon_label ); ?></option><?php endforeach; ?></select></label>
			<div class="sakolaan-quick-link__media">
				<span class="sakolaan-quick-link__field-label"><?php esc_html_e( 'Ikon gambar', 'sakolaan' ); ?></span>
				<div class="sakolaan-media">
					<input type="hidden" name="<?php echo esc_attr( $base . '[icon_id]' ); ?>" value="<?php echo esc_attr( isset( $item['icon_id'] ) ? absint( $item['icon_id'] ) : 0 ); ?>">
					<div class="sakolaan-media__preview"><?php if ( ! empty( $item['icon_id'] ) && wp_attachment_is_image( absint( $item['icon_id'] ) ) ) { echo wp_get_attachment_image( absint( $item['icon_id'] ), 'thumbnail' ); } ?></div>
					<button type="button" class="button sakolaan-media__select"><?php esc_html_e( 'Pilih gambar', 'sakolaan' ); ?></button>
					<button type="button" class="button-link sakolaan-media__remove"><?php esc_html_e( 'Hapus', 'sakolaan' ); ?></button>
				</div>
			</div>
			<label class="sakolaan-quick-link__alt"><span><?php esc_html_e( 'Teks alternatif ikon gambar', 'sakolaan' ); ?></span><input type="text" name="<?php echo esc_attr( $base . '[icon_alt]' ); ?>" value="<?php echo esc_attr( isset( $item['icon_alt'] ) ? $item['icon_alt'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Opsional', 'sakolaan' ); ?>"><small><?php esc_html_e( 'Kosongkan jika ikon hanya dekoratif.', 'sakolaan' ); ?></small></label>
		</div>
		<div class="sakolaan-quick-link__footer">
			<label class="sakolaan-quick-link__enabled"><input type="hidden" name="<?php echo esc_attr( $base . '[enabled_field]' ); ?>" value="1"><input type="checkbox" name="<?php echo esc_attr( $base . '[enabled]' ); ?>" value="1" <?php checked( ! isset( $item['enabled'] ) || $item['enabled'], true ); ?>> <?php esc_html_e( 'Tampilkan tautan ini', 'sakolaan' ); ?></label>
			<div class="sakolaan-quick-link__actions">
				<button type="button" class="button" data-move="up" aria-label="<?php esc_attr_e( 'Naikkan tautan', 'sakolaan' ); ?>"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span><?php esc_html_e( 'Naik', 'sakolaan' ); ?></button>
				<button type="button" class="button" data-move="down" aria-label="<?php esc_attr_e( 'Turunkan tautan', 'sakolaan' ); ?>"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span><?php esc_html_e( 'Turun', 'sakolaan' ); ?></button>
				<button type="button" class="button sakolaan-quick-link__delete" data-quick-link-remove><span class="dashicons dashicons-editor-unlink" aria-hidden="true"></span><?php esc_html_e( 'Hapus tautan', 'sakolaan' ); ?></button>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render pengurut section yang dapat dipakai dengan mouse maupun keyboard.
 *
 * @param string   $id    ID elemen.
 * @param string   $name  Nama input dasar.
 * @param string[] $value Urutan tersimpan.
 * @return void
 */
function sakolaan_render_section_order_field( $id, $name, $value ) {
	$labels  = sakolaan_get_homepage_section_labels();
	$value   = array_values( array_filter( array_map( static function ( $item ) { return is_scalar( $item ) ? sanitize_key( (string) $item ) : ''; }, $value ) ) );
	$ordered = array_values( array_unique( array_merge( array_intersect( $value, array_keys( $labels ) ), array_keys( $labels ) ) ) );
	?>
	<p class="description"><?php esc_html_e( 'Seret menggunakan pegangan, atau gunakan tombol Naik dan Turun. Perubahan diterapkan setelah pengaturan disimpan.', 'sakolaan' ); ?></p>
	<ol id="<?php echo esc_attr( $id ); ?>" class="sakolaan-section-order" data-sakolaan-section-order>
		<?php foreach ( $ordered as $slug ) : ?>
			<li class="sakolaan-section-order__item" data-section="<?php echo esc_attr( $slug ); ?>">
				<span class="dashicons dashicons-move sakolaan-section-order__handle" aria-hidden="true"></span>
				<span class="sakolaan-section-order__label"><?php echo esc_html( $labels[ $slug ] ); ?></span>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $slug ); ?>">
				<div class="sakolaan-section-order__actions">
					<button type="button" class="button-link" data-move="up" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: section name. */ __( 'Naikkan %s', 'sakolaan' ), $labels[ $slug ] ) ); ?>"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>
					<button type="button" class="button-link" data-move="down" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: section name. */ __( 'Turunkan %s', 'sakolaan' ), $labels[ $slug ] ) ); ?>"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
	<p class="sakolaan-section-order__status" role="status" aria-live="polite"></p>
	<?php
}

/** Render visibility and ordering as one homepage section control. */
function sakolaan_render_homepage_section_manager() {
	$settings = sakolaan_get_settings();
	$section_settings = isset( $settings['sections'] ) && is_array( $settings['sections'] ) ? $settings['sections'] : array();
	$labels = sakolaan_get_homepage_section_labels();
	$order = isset( $section_settings['order'] ) && is_array( $section_settings['order'] ) ? $section_settings['order'] : array_keys( $labels );
	$order = array_values( array_unique( array_merge( array_intersect( $order, array_keys( $labels ) ), array_keys( $labels ) ) ) );
	?>
	<input type="hidden" name="sakolaan_settings[sections][__present]" value="1">
	<input type="hidden" name="sakolaan_settings[sections][order_field]" value="1">
	<p class="description"><?php esc_html_e( 'Centang section yang ingin ditampilkan, lalu seret atau gunakan tombol untuk mengatur urutannya. Hero selalu berada paling atas.', 'sakolaan' ); ?></p>
	<ol class="sakolaan-section-order sakolaan-section-manager" data-sakolaan-section-order>
		<?php foreach ( $order as $slug ) : ?>
			<li class="sakolaan-section-order__item" data-section="<?php echo esc_attr( $slug ); ?>">
				<span class="dashicons dashicons-move sakolaan-section-order__handle" aria-hidden="true"></span>
				<label class="sakolaan-section-order__label">
					<input type="hidden" name="sakolaan_settings[sections][show_<?php echo esc_attr( $slug ); ?>_field]" value="1">
					<input type="checkbox" name="sakolaan_settings[sections][show_<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( ! empty( $section_settings[ 'show_' . $slug ] ) ); ?>>
					<?php echo esc_html( $labels[ $slug ] ); ?>
				</label>
				<input type="hidden" name="sakolaan_settings[sections][order][]" value="<?php echo esc_attr( $slug ); ?>">
				<?php $count_key = 'items_' . $slug; $count_field = sakolaan_get_settings_schema()['sections']['fields'][ $count_key ] ?? null; ?>
				<?php if ( $count_field ) : ?>
					<label class="sakolaan-section-order__count"><span><?php esc_html_e( 'Jumlah', 'sakolaan' ); ?></span><?php sakolaan_render_field( array( 'group' => 'sections', 'key' => $count_key, 'field' => $count_field, 'label_for' => 'sakolaan-sections-' . $count_key ) ); ?></label>
				<?php elseif ( sakolaan_homepage_section_edit_url( $slug ) ) : ?>
					<a class="sakolaan-section-order__edit" href="<?php echo esc_url( sakolaan_homepage_section_edit_url( $slug ) ); ?>"><?php esc_html_e( 'Atur isi', 'sakolaan' ); ?></a>
				<?php endif; ?>
				<div class="sakolaan-section-order__actions">
					<?php /* translators: %s: homepage section label. */ ?>
					<button type="button" class="button-link" data-move="up" aria-label="<?php echo esc_attr( sprintf( __( 'Naikkan %s', 'sakolaan' ), $labels[ $slug ] ) ); ?>"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>
					<?php /* translators: %s: homepage section label. */ ?>
					<button type="button" class="button-link" data-move="down" aria-label="<?php echo esc_attr( sprintf( __( 'Turunkan %s', 'sakolaan' ), $labels[ $slug ] ) ); ?>"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
	<p class="sakolaan-section-order__status" role="status" aria-live="polite"></p>
	<?php
}

/** Link static homepage sections to their single editing location. */
function sakolaan_homepage_section_edit_url( $slug ) {
	$routes = array(
		'quick_links' => array( 'content', array( 'panel' => 'appearance', 'area' => 'homepage', 'component' => 'quick-links' ) ),
		'introduction' => array( 'content', array( 'panel' => 'appearance', 'area' => 'homepage', 'component' => 'sections' ) ),
		'statistics' => array( 'identity', array( 'panel' => 'statistics' ) ),
		'principal' => array( 'identity', array( 'panel' => 'principal' ) ),
		'ppdb' => array( 'content', array( 'panel' => 'ppdb' ) ),
	);
	return isset( $routes[ $slug ] ) && function_exists( 'sakolaan_admin_url' ) ? sakolaan_admin_url( $routes[ $slug ][0], $routes[ $slug ][1] ) : '';
}

/** Render Settings API feedback once, even when one screen contains multiple forms. */
function sakolaan_render_settings_notices() {
	static $rendered = false;
	if ( $rendered ) {
		return;
	}
	ob_start();
	settings_errors();
	$notices = (string) ob_get_clean();
	$notices = preg_replace( '/class=(["\'])notice\b/', 'class=$1notice sakolaan-notice', $notices );
	echo wp_kses_post( (string) $notices );
	$rendered = true;
}

/** Render a submit button with a unique ID for multi-form editor screens. */
function sakolaan_render_settings_submit_button() {
	static $button_index = 0;
	++$button_index;
	submit_button( __( 'Simpan perubahan', 'sakolaan' ), 'primary', 'sakolaan-submit-' . $button_index );
}

/** Render selected fields from one or more schema groups without resetting hidden fields. */
function sakolaan_render_settings_subset( $groups ) {
	if ( ! current_user_can( 'manage_options' ) || ! is_array( $groups ) ) {
		return;
	}
	$schema = sakolaan_get_settings_schema();
	sakolaan_render_settings_notices();
	echo '<div class="sakolaan-settings"><form method="post" action="options.php" class="sakolaan-settings-form">';
	settings_fields( 'sakolaan_settings_group' );
	foreach ( $groups as $group => $keys ) {
		if ( ! isset( $schema[ $group ] ) || ! is_array( $keys ) ) {
			continue;
		}
		echo '<section class="sakolaan-settings-group sakolaan-settings-group--' . esc_attr( $group ) . '"><input type="hidden" name="sakolaan_settings[' . esc_attr( $group ) . '][__present]" value="1">';
		if ( ! empty( $schema[ $group ]['intro'] ) ) {
			echo '<p class="description">' . esc_html( $schema[ $group ]['intro'] ) . '</p>';
		}
		echo '<table class="form-table" role="presentation"><tbody>';
		foreach ( $keys as $key ) {
			if ( ! isset( $schema[ $group ]['fields'][ $key ] ) ) {
				continue;
			}
			$field = $schema[ $group ]['fields'][ $key ];
			echo '<tr class="sakolaan-settings-row--' . esc_attr( sanitize_html_class( $field['type'] ) ) . '"><th scope="row"><label for="sakolaan-' . esc_attr( $group . '-' . $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
			sakolaan_render_field( array( 'group' => $group, 'key' => $key, 'field' => $field, 'label_for' => 'sakolaan-' . $group . '-' . $key ) );
			echo '</td></tr>';
		}
		echo '</tbody></table></section>';
	}
	sakolaan_render_settings_submit_button();
	echo '<p class="sakolaan-submit-status" role="status" aria-live="polite"></p></form></div>';
}

/**
 * Render field gambar dengan media library.
 *
 * @param string $id            ID elemen.
 * @param string $name          Nama input.
 * @param int    $attachment_id ID attachment saat ini.
 * @param string $description_id ID deskripsi field (opsional).
 * @return void
 */
function sakolaan_render_image_field( $id, $name, $attachment_id, $description_id = '' ) {
	$status_id   = $id . '-media-status';
	$describedby = trim( $description_id . ' ' . $status_id );
	?>
	<div class="sakolaan-media">
		<input type="hidden" id="<?php echo esc_attr( $id . '-value' ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>">
		<div class="sakolaan-media__preview">
			<?php
			if ( $attachment_id ) {
				echo wp_get_attachment_image( $attachment_id, 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output WordPress core.
			}
			?>
		</div>
		<button type="button" id="<?php echo esc_attr( $id ); ?>" class="button sakolaan-media__select" aria-describedby="<?php echo esc_attr( $describedby ); ?>"><?php esc_html_e( 'Pilih gambar', 'sakolaan' ); ?></button>
		<button type="button" class="button-link sakolaan-media__remove" aria-describedby="<?php echo esc_attr( $describedby ); ?>"><?php esc_html_e( 'Hapus', 'sakolaan' ); ?></button>
		<span id="<?php echo esc_attr( $status_id ); ?>" class="sakolaan-media__status" role="status" aria-live="polite"></span>
	</div>
	<?php
}

/**
 * Render halaman pengaturan.
 *
 * @return void
 */
function sakolaan_render_settings_page( $embedded = false, $groups = null, $title = '' ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$schema = sakolaan_get_settings_schema();
	$groups = is_array( $groups ) ? array_values( array_intersect( $groups, array_keys( $schema ) ) ) : array_keys( $schema );
	if ( ! $groups ) {
		return;
	}
	?>
	<div class="sakolaan-settings">
		<?php if ( $title ) : ?><?php echo $embedded ? '<h2>' : '<h1>'; ?><?php echo esc_html( $title ); ?><?php echo $embedded ? '</h2>' : '</h1>'; ?><?php endif; ?>
		<?php sakolaan_render_settings_notices(); ?>
		<form method="post" action="options.php" class="sakolaan-settings-form">
			<?php
			settings_fields( 'sakolaan_settings_group' );
			foreach ( $groups as $group ) {
				echo '<section class="sakolaan-settings-group sakolaan-settings-group--' . esc_attr( $group ) . '"><h3>' . esc_html( $schema[ $group ]['label'] ) . '</h3>';
				if ( 'sections' === $group ) {
					sakolaan_render_homepage_section_manager();
				} else {
					do_settings_sections( 'sakolaan-' . $group );
				}
				echo '</section>';
			}
			sakolaan_render_settings_submit_button();
			?>
			<p class="sakolaan-submit-status" role="status" aria-live="polite"></p>
		</form>
	</div>
	<?php
}
