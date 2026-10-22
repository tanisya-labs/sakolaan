<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Shared, read-only admission availability rules for frontend and REST output. */
final class Availability {
	public static function classify( array $row, ?int $now = null ): string {
		$now = null === $now ? time() : $now;
		if ( 'active' !== ( $row['institution_status'] ?? 'active' ) || 'published' !== ( $row['program_status'] ?? 'published' ) || 'published' !== ( $row['period_status'] ?? 'published' ) ) {
			return 'unavailable';
		}
		$opens  = strtotime( (string) $row['opens_at'] . ' UTC' );
		$closes = strtotime( (string) $row['closes_at'] . ' UTC' );
		if ( ! $opens || ! $closes || $closes < $now ) {
			return 'closed';
		}
		if ( $opens > $now ) {
			return 'upcoming';
		}
		$program_full = null !== ( $row['program_quota'] ?? null ) && (int) $row['program_enrolled'] >= (int) $row['program_quota'];
		$period_full  = null !== ( $row['period_quota'] ?? null ) && (int) $row['period_enrolled'] >= (int) $row['period_quota'];
		return $program_full || $period_full ? 'full' : 'open';
	}

	public static function periods( bool $include_closed = false ): array {
		global $wpdb;
		$rows   = (array) $wpdb->get_results(
			'SELECT p.id program_id,p.institution_id,p.name program_name,p.slug,p.description,p.intake,p.status program_status,p.quota program_quota,p.enrolled_count program_enrolled,p.fee_required,p.fee_amount,p.currency,i.name institution_name,i.status institution_status,pe.id period_id,pe.name period_name,pe.status period_status,pe.opens_at,pe.closes_at,pe.quota period_quota,pe.enrolled_count period_enrolled FROM ' . Installer::table( 'programs' ) . ' p INNER JOIN ' . Installer::table( 'institutions' ) . ' i ON i.id=p.institution_id INNER JOIN ' . Installer::table( 'periods' ) . " pe ON pe.program_id=p.id WHERE p.status='published' AND i.status='active' AND pe.status='published' ORDER BY pe.opens_at,p.name",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$output = array();
		foreach ( $rows as $row ) {
			$row['availability'] = self::classify( $row );
			if ( $include_closed || in_array( $row['availability'], array( 'open', 'upcoming', 'full' ), true ) ) {
				$output[] = $row;
			}
		}
		usort(
			$output,
			static function ( array $left, array $right ): int {
				$order              = array(
					'open'        => 0,
					'upcoming'    => 1,
					'full'        => 2,
					'closed'      => 3,
					'unavailable' => 4,
				);
				$availability_order = $order[ $left['availability'] ] <=> $order[ $right['availability'] ];
				return 0 !== $availability_order ? $availability_order : strcmp( $left['closes_at'], $right['closes_at'] );
			}
		);
		return $output;
	}

	public static function for_program( int $program_id ): ?array {
		foreach ( self::periods() as $row ) {
			if ( $program_id === (int) $row['program_id'] ) {
				return $row;
			}
		}
		return null;
	}

	public static function for_period( int $program_id, int $period_id ): ?array {
		foreach ( self::periods( true ) as $row ) {
			if ( $program_id === (int) $row['program_id'] && $period_id === (int) $row['period_id'] ) {
				return $row;
			}
		}
		return null;
	}

	public static function remaining( array $row ): ?int {
		$remaining = array();
		if ( null !== $row['program_quota'] ) {
			$remaining[] = max( 0, (int) $row['program_quota'] - (int) $row['program_enrolled'] );
		}
		if ( null !== $row['period_quota'] ) {
			$remaining[] = max( 0, (int) $row['period_quota'] - (int) $row['period_enrolled'] );
		}
		return $remaining ? min( $remaining ) : null;
	}
}
