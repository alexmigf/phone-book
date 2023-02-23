<?php

defined( 'ABSPATH' ) || die();


/**
 * Returns the Phone Book CSV specifications
 *
 * @return array
 */
function phone_book_csv_specs() {
	return apply_filters( 'phone_book_csv_specs', [
		'delimiter' => ',',
		'enclosure' => '"',
		'escape'    => "\\",
	] );
}

/**
 * Returns the Phone Book CSV columns in their respective order
 *
 * @return array
 */
function phone_book_csv_columns() {
	return apply_filters( 'phone_book_csv_columns', [
		'first_name',
		'last_name',
		'company',
		'position',
		'department',
		'email',
		'country_code',
		'phone_number',
		'birthday',
		'website',
	] );
}

/**
 * Returns the Phone Book CSV import batch number
 *
 * @return int
 */
function phone_book_csv_import_batch_number() {
	return apply_filters( 'phone_book_csv_import_batch_number', 50 );
}