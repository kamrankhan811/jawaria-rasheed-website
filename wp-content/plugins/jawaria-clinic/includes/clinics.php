<?php
/**
 * Clinic locations: names, addresses, opening days/times and booking slots.
 *
 * One line per clinic in Settings → Clinic Info → "Clinic locations":
 *
 *   Name | City | Address | Mo-Fr 15:00-19:00 | Map link | Slot minutes
 *
 * Only the first four parts are required. The hours part uses the same compact
 * format Google understands, with a comma between groups:
 *
 *   Mo-Fr 15:00-19:00, Sa 10:00-13:00
 *
 * Adding a third city later means adding one more line here — nothing in the
 * theme, the booking form or the schema needs to change.
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

/**
 * Day tokens in week order, with their short labels and JS getDay() index.
 *
 * @return array<string, array{0:string,1:int}>
 */
function jr_day_map() {
	return array(
		'Mo' => array( 'Mon', 1 ),
		'Tu' => array( 'Tue', 2 ),
		'We' => array( 'Wed', 3 ),
		'Th' => array( 'Thu', 4 ),
		'Fr' => array( 'Fri', 5 ),
		'Sa' => array( 'Sat', 6 ),
		'Su' => array( 'Sun', 0 ),
	);
}

/**
 * Parse "Mo-Fr 15:00-19:00, Sa 10:00-13:00" into day/time groups.
 *
 * @param string $raw Compact hours string.
 * @return array<int, array{days:string[], start:string, end:string, raw:string}>
 */
function jr_parse_hours( $raw ) {
	$order  = array_keys( jr_day_map() );
	$groups = array();
	// Each group is "<days> <start>-<end>"; the day part may itself hold commas
	// (Mo,We,Fr), so match the groups instead of splitting the string on commas.
	preg_match_all( '/([A-Za-z]{2}(?:\s*[-–,]\s*[A-Za-z]{2})*)\s+(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})/', (string) $raw, $found, PREG_SET_ORDER );
	foreach ( $found as $m ) {
		$days = array();
		foreach ( explode( ',', $m[1] ) as $chunk ) {
			$chunk = trim( $chunk );
			if ( preg_match( '/^([A-Za-z]{2})\s*[-–]\s*([A-Za-z]{2})$/', $chunk, $d ) ) {
				$from = array_search( ucfirst( strtolower( $d[1] ) ), $order, true );
				$to   = array_search( ucfirst( strtolower( $d[2] ) ), $order, true );
				if ( false === $from || false === $to ) {
					continue;
				}
				// Wraps past Sunday, e.g. Sa-Mo.
				for ( $i = $from; ; $i++ ) {
					$days[] = $order[ $i % 7 ];
					if ( $i % 7 === $to ) {
						break;
					}
					if ( $i - $from > 7 ) {
						break;
					}
				}
			} else {
				$token = ucfirst( strtolower( $chunk ) );
				if ( in_array( $token, $order, true ) ) {
					$days[] = $token;
				}
			}
		}
		if ( $days ) {
			$groups[] = array(
				'days'  => array_values( array_unique( $days ) ),
				'start' => jr_pad_time( $m[2] ),
				'end'   => jr_pad_time( $m[3] ),
				'raw'   => $m[0],
			);
		}
	}
	return $groups;
}

/**
 * "9:05" → "09:05".
 *
 * @param string $t Time.
 * @return string
 */
function jr_pad_time( $t ) {
	list( $h, $m ) = array_pad( explode( ':', $t ), 2, '00' );
	return sprintf( '%02d:%02d', (int) $h, (int) $m );
}

/**
 * "15:30" → "3:30 PM".
 *
 * @param string $t 24-hour time.
 * @return string
 */
function jr_time_12( $t ) {
	list( $h, $m ) = array_pad( explode( ':', $t ), 2, '00' );
	$h             = (int) $h;
	$suffix        = $h >= 12 ? 'PM' : 'AM';
	$h12           = $h % 12;
	if ( 0 === $h12 ) {
		$h12 = 12;
	}
	return $h12 . ':' . sprintf( '%02d', (int) $m ) . ' ' . $suffix;
}

/**
 * ['Mo','Tu','We','Th','Fr'] → "Mon – Fri"; non-consecutive → "Mon, Wed, Fri".
 *
 * @param string[] $days Day tokens.
 * @return string
 */
function jr_days_label( array $days ) {
	$map   = jr_day_map();
	$order = array_keys( $map );
	$idx   = array();
	foreach ( $days as $d ) {
		$pos = array_search( $d, $order, true );
		if ( false !== $pos ) {
			$idx[] = $pos;
		}
	}
	sort( $idx );
	$idx = array_values( array_unique( $idx ) );
	if ( ! $idx ) {
		return '';
	}
	$consecutive = ( $idx[ count( $idx ) - 1 ] - $idx[0] ) === ( count( $idx ) - 1 );
	if ( $consecutive && count( $idx ) > 2 ) {
		return $map[ $order[ $idx[0] ] ][0] . ' – ' . $map[ $order[ $idx[ count( $idx ) - 1 ] ] ][0];
	}
	$out = array();
	foreach ( $idx as $i ) {
		$out[] = $map[ $order[ $i ] ][0];
	}
	return implode( ', ', $out );
}

/**
 * All clinic locations, parsed. Falls back to the single-clinic settings.
 *
 * @return array<int, array<string, mixed>>
 */
function jr_clinics() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$default_slot = max( 5, (int) jr_opt( 'slot_minutes' ) ?: 15 );
	$cache        = array();
	foreach ( jr_lines( jr_opt( 'clinics' ) ) as $i => $line ) {
		$p = array_map( 'trim', explode( '|', $line ) );
		if ( empty( $p[0] ) ) {
			continue;
		}
		$hours    = isset( $p[3] ) ? $p[3] : '';
		$cache[] = array(
			'key'     => 'c' . ( $i + 1 ),
			'name'    => $p[0],
			'city'    => isset( $p[1] ) ? $p[1] : '',
			'address' => isset( $p[2] ) ? $p[2] : '',
			'hours'   => $hours,
			'map'     => isset( $p[4] ) ? $p[4] : '',
			'slot'    => isset( $p[5] ) && (int) $p[5] > 0 ? (int) $p[5] : $default_slot,
			'groups'  => jr_parse_hours( $hours ),
		);
	}
	if ( ! $cache ) {
		// No clinic lines configured — use the older single-clinic settings.
		$hours   = jr_opt( 'opening_hours' );
		$cache[] = array(
			'key'     => 'c1',
			'name'    => jr_opt( 'doctor_name' ),
			'city'    => jr_opt( 'city' ),
			'address' => jr_opt( 'address' ),
			'hours'   => $hours,
			'map'     => jr_opt( 'map_link' ),
			'slot'    => $default_slot,
			'groups'  => jr_parse_hours( $hours ),
		);
	}
	return $cache;
}

/**
 * One clinic by its key.
 *
 * @param string $key Clinic key.
 * @return array|null
 */
function jr_clinic( $key ) {
	foreach ( jr_clinics() as $c ) {
		if ( $c['key'] === $key ) {
			return $c;
		}
	}
	return null;
}

/**
 * "Mon – Fri, 3:00 PM – 7:00 PM" (groups joined with "; ").
 *
 * @param array $clinic Clinic.
 * @return string
 */
function jr_clinic_hours_label( array $clinic ) {
	$out = array();
	foreach ( $clinic['groups'] as $g ) {
		$days  = jr_days_label( $g['days'] );
		$join  = false !== strpos( $days, ',' ) ? ' · ' : ', ';
		$out[] = $days . $join . jr_time_12( $g['start'] ) . ' – ' . jr_time_12( $g['end'] );
	}
	return implode( '; ', $out );
}

/**
 * Short label for a clinic: city when set, else the name.
 *
 * @param array $clinic Clinic.
 * @return string
 */
function jr_clinic_short( array $clinic ) {
	return $clinic['city'] ? $clinic['city'] : $clinic['name'];
}

/**
 * [ "Bahawalpur : Mon – Fri, 3:00 PM – 7:00 PM", ... ] — one per clinic.
 *
 * @return string[]
 */
function jr_hours_lines() {
	$out = array();
	foreach ( jr_clinics() as $c ) {
		$label = jr_clinic_hours_label( $c );
		if ( $label ) {
			$out[] = jr_clinic_short( $c ) . ' : ' . $label;
		}
	}
	return $out;
}

/**
 * All clinic timings on one line, for the top bar.
 *
 * @param string $sep Separator.
 * @return string
 */
function jr_hours_inline( $sep = '  |  ' ) {
	return implode( $sep, jr_hours_lines() );
}

/**
 * Booking slots for a clinic, e.g. "3:00 PM – 3:15 PM".
 *
 * @param array $clinic Clinic.
 * @return string[]
 */
function jr_clinic_slots( array $clinic ) {
	$step  = max( 5, (int) $clinic['slot'] );
	$slots = array();
	foreach ( $clinic['groups'] as $g ) {
		$start = jr_minutes( $g['start'] );
		$end   = jr_minutes( $g['end'] );
		for ( $t = $start; $t + $step <= $end; $t += $step ) {
			$slots[] = jr_time_12( jr_hhmm( $t ) ) . ' – ' . jr_time_12( jr_hhmm( $t + $step ) );
		}
	}
	return array_values( array_unique( $slots ) );
}

/**
 * Slot list with the "Any time" option first.
 *
 * @param array $clinic Clinic.
 * @return string[]
 */
function jr_clinic_slot_choices( array $clinic ) {
	$any = array();
	foreach ( $clinic['groups'] as $g ) {
		$any[] = jr_time_12( $g['start'] ) . ' – ' . jr_time_12( $g['end'] );
	}
	$label = $any ? sprintf( /* translators: %s: time range */ __( 'Any time (%s)', 'jawaria-clinic' ), implode( ', ', $any ) ) : __( 'Any time', 'jawaria-clinic' );
	return array_merge( array( $label ), jr_clinic_slots( $clinic ) );
}

/**
 * "15:30" → 930 minutes.
 *
 * @param string $t Time.
 * @return int
 */
function jr_minutes( $t ) {
	list( $h, $m ) = array_pad( explode( ':', $t ), 2, '00' );
	return ( (int) $h * 60 ) + (int) $m;
}

/**
 * 930 → "15:30".
 *
 * @param int $m Minutes since midnight.
 * @return string
 */
function jr_hhmm( $m ) {
	return sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
}

/**
 * JS getDay() indexes a clinic is open on.
 *
 * @param array $clinic Clinic.
 * @return int[]
 */
function jr_clinic_weekdays( array $clinic ) {
	$map  = jr_day_map();
	$days = array();
	foreach ( $clinic['groups'] as $g ) {
		foreach ( $g['days'] as $d ) {
			if ( isset( $map[ $d ] ) ) {
				$days[] = $map[ $d ][1];
			}
		}
	}
	return array_values( array_unique( $days ) );
}

/**
 * Everything the booking form's JavaScript needs.
 *
 * @return array
 */
function jr_clinics_for_js() {
	$out = array();
	foreach ( jr_clinics() as $c ) {
		$out[ $c['key'] ] = array(
			'label'    => jr_clinic_short( $c ),
			'hours'    => jr_clinic_hours_label( $c ),
			'address'  => $c['address'],
			'weekdays' => jr_clinic_weekdays( $c ),
			'slots'    => jr_clinic_slot_choices( $c ),
		);
	}
	return $out;
}

/**
 * openingHours strings for schema, per clinic.
 *
 * @param array $clinic Clinic.
 * @return string[]
 */
function jr_clinic_schema_hours( array $clinic ) {
	$out = array();
	foreach ( $clinic['groups'] as $g ) {
		$out[] = implode( ',', $g['days'] ) . ' ' . $g['start'] . '-' . $g['end'];
	}
	return $out;
}
