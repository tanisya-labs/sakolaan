<?php
define( 'ABSPATH', __DIR__ . '/' );

class WP_Error {
	private array $errors = array();
	public function __construct( string $code = '', string $message = '' ) { if ( $code ) { $this->add( $code, $message ); } }
	public function add( string $code, string $message ): void { $this->errors[ $code ][] = $message; }
	public function has_errors(): bool { return (bool) $this->errors; }
	public function get_error_code(): string { return (string) array_key_first( $this->errors ); }
	public function get_error_message(): string { $messages = reset( $this->errors ); return $messages[0] ?? ''; }
}

function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function __( string $text, string $domain = '' ): string { return $text; }
$GLOBALS['sakolaan_test_filters'] = array();
$GLOBALS['sakolaan_test_options'] = array();
function add_filter( string $hook, callable $callback ): void { $GLOBALS['sakolaan_test_filters'][ $hook ][] = $callback; }
function remove_all_filters( string $hook ): void { unset( $GLOBALS['sakolaan_test_filters'][ $hook ] ); }
function has_filter( string $hook ): bool { return ! empty( $GLOBALS['sakolaan_test_filters'][ $hook ] ); }
function has_action( string $hook ): bool { return false; }
function apply_filters( string $hook, $value, ...$args ) {
	foreach ( $GLOBALS['sakolaan_test_filters'][ $hook ] ?? array() as $callback ) {
		$value = $callback( $value, ...$args );
	}
	return $value;
}
function get_option( string $name, $default = false ) { return $GLOBALS['sakolaan_test_options'][ $name ] ?? $default; }
function update_option( string $name, $value, bool $autoload = false ): bool { $GLOBALS['sakolaan_test_options'][ $name ] = $value; return true; }
function current_time( string $type, bool $gmt = false ): string { return '2026-10-17 10:00:00'; }
function sanitize_key( $value ): string { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ): string { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ): string { return trim( strip_tags( (string) $value ) ); }
function sanitize_email( $value ): string { return filter_var( $value, FILTER_SANITIZE_EMAIL ); }
function is_email( $value ): bool { return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function wp_unslash( $value ) { return $value; }
function wp_strip_all_tags( $value ): string { return trim( strip_tags( (string) $value ) ); }
function esc_html( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_textarea( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( string $text, string $domain = '' ): string { return esc_html( $text ); }
function esc_attr__( string $text, string $domain = '' ): string { return esc_attr( $text ); }
function selected( $selected, $current = true, bool $display = true ): string { $result = (string) $selected === (string) $current ? ' selected="selected"' : ''; if ( $display ) { echo $result; } return $result; }
function checked( $checked, $current = true, bool $display = true ): string { $result = (string) $checked === (string) $current ? ' checked="checked"' : ''; if ( $display ) { echo $result; } return $result; }

require_once dirname( __DIR__ ) . '/includes/class-labels.php';
require_once dirname( __DIR__ ) . '/includes/class-availability.php';
require_once dirname( __DIR__ ) . '/includes/class-configuration.php';
require_once dirname( __DIR__ ) . '/includes/class-mail-diagnostics.php';
require_once dirname( __DIR__ ) . '/includes/class-forms.php';
require_once dirname( __DIR__ ) . '/includes/class-workflow.php';
require_once dirname( __DIR__ ) . '/includes/class-frontend.php';
