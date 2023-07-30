<?php
/**
 * Plugin Name: Influactive Forms
 * Description: A plugin to create custom forms and display them anywhere on
 * your website.
 * Version: 1.2.6
 * Author: Influactive
 * Author URI: https://influactive.com
 * Text Domain: influactive-forms
 * Domain Path: /languages
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @throws RuntimeException If the WordPress environment is not loaded.
 * @package Influactive Forms
 **/

if ( ! defined( 'ABSPATH' ) ) {
	throw new RuntimeException( 'WordPress environment not loaded. Exiting...' );
}

include( plugin_dir_path( __FILE__ ) . 'back-end/post-type/definitions.php' );
include( plugin_dir_path( __FILE__ ) . 'back-end/post-type/listing.php' );
include( plugin_dir_path( __FILE__ ) . 'back-end/post-type/edit.php' );
include( plugin_dir_path( __FILE__ ) . 'back-end/settings/captchas.php' );
include( plugin_dir_path( __FILE__ ) . 'front-end/shortcode.php' );

/**
 * Adds a settings link to the plugin page.
 *
 * @param array $links An array of existing links on the plugin page.
 *
 * @return array An updated array of links including the new settings link.
 */
function influactive_forms_add_settings_link( array $links ): array {
	$link          = 'edit.php?post_type=influactive-forms&page=influactive-form-settings';
	$link_text     = __( 'Captchas', 'influactive-forms' );
	$settings_link = '<a href="' . $link . '">' . $link_text . '</a>';
	$links[]       = $settings_link;

	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'influactive_forms_add_settings_link' );

/**
 * Enqueues scripts and styles for editing an Influactive form.
 *
 * @param string $hook The current admin page hook.
 *
 * @return void
 * @throws RuntimeException If the Form ID is not found.
 */
function influactive_form_edit( string $hook ): void {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}

	wp_enqueue_script(
		'influactive-form',
		plugin_dir_url( __FILE__ ) . 'dist/backEndForm.bundled.js',
		array(
			'wp-tinymce',
			'influactive-tabs',
			'influactive-form-layout',
		),
		'1.2.6',
		true
	);
	wp_localize_script(
		'influactive-form',
		'influactiveFormsTranslations',
		array(
			'addOptionText'        => __( 'Add option', 'influactive-forms' ),
			'removeOptionText'     => __( 'Remove option', 'influactive-forms' ),
			'removeFieldText'      => __( 'Remove the field', 'influactive-forms' ),
			'typeLabelText'        => __( 'Type', 'influactive-forms' ),
			'labelLabelText'       => __( 'Label', 'influactive-forms' ),
			'nameLabelText'        => __( 'Name', 'influactive-forms' ),
			'optionLabelLabelText' => __( 'Option Label', 'influactive-forms' ),
			'optionValueLabelText' => __( 'Option Value', 'influactive-forms' ),
			'gdprTextLabelText'    => __( 'Text', 'influactive-forms' ),
			'fieldAddedText'       => __( 'Field added!', 'influactive-forms' ),
			'optionAddedText'      => __( 'Option added!', 'influactive-forms' ),
			'optionRemovedText'    => __( 'Option removed!', 'influactive-forms' ),
			'Text'                 => __( 'Text', 'influactive-forms' ),
			'Textarea'             => __( 'Textarea', 'influactive-forms' ),
			'Select'               => __( 'Select', 'influactive-forms' ),
			'Email'                => __( 'Email', 'influactive-forms' ),
			'GDPR'                 => __( 'GDPR', 'influactive-forms' ),
			'Number'               => __( 'Number', 'influactive-forms' ),
			'Freetext'             => __( 'Free text', 'influactive-forms' ),
		)
	);
	wp_enqueue_style(
		'influactive-form',
		plugin_dir_url( __FILE__ )
		. 'dist/backForm.bundled.css',
		array(),
		'1.2.6'
	);

	wp_enqueue_script(
		'influactive-tabs',
		plugin_dir_url( __FILE__ )
		. 'dist/backEndTab.bundled.js',
		array(),
		'1.2.6',
		true
	);
	wp_enqueue_style(
		'influactive-tabs',
		plugin_dir_url( __FILE__ )
		. 'dist/tab.bundled.css',
		array(),
		'1.2.6'
	);

	wp_enqueue_style(
		'influactive-form-layout',
		plugin_dir_url( __FILE__ )
		. 'dist/layout.bundled.css',
		array(),
		'1.2.6'
	);
	wp_enqueue_script(
		'influactive-form-layout',
		plugin_dir_url( __FILE__ ) . 'dist/backEndLayout.bundled.js',
		array(),
		'1.2.6',
		true
	);
	wp_localize_script(
		'influactive-form-layout',
		'influactiveFormsTranslations',
		array(
			'delete_layout' => __( 'Delete layout', 'influactive-forms' ),
		)
	);

	wp_enqueue_style(
		'influactive-form-style',
		plugin_dir_url( __FILE__ ) . 'dist/style.bundled.css',
		array(),
		'1.2.6'
	);

	$form_id = get_post_meta( get_the_ID(), 'influactive_form_id', true );
	if ( ! $form_id ) {
		throw new RuntimeException( 'Form ID not found. Exiting...' );
	}
	wp_enqueue_style(
		'influactive-form-dynamic-style',
		plugin_dir_url( __FILE__ ) . 'front-end/dynamic-style.php?post_id=' . $form_id,
		array(),
		'1.2.6'
	);
}

add_action( 'admin_enqueue_scripts', 'influactive_form_edit' );

/**
 * Enqueues the necessary scripts and styles for the Influactive form shortcode.
 *
 * @return void
 * @throws RuntimeException If the WordPress environment is not loaded.
 */
function influactive_form_shortcode_enqueue(): void {
	if ( is_admin() ) {
		throw new RuntimeException( 'WordPress environment not loaded. Exiting...' );
	}

	if ( wp_script_is( 'google-captcha' ) || wp_script_is( 'google-recaptcha' ) ) {
		throw new RuntimeException( 'Google Captcha script already loaded. Exiting...' );
	}

	$options_captcha = get_option( 'influactive-forms-captcha-fields' ) ?? array();
	$public_site_key = $options_captcha['google-captcha']['public-site-key'] ?? null;
	$secret_site_key = $options_captcha['google-captcha']['secret-site-key'] ?? null;

	if ( ! empty( $public_site_key ) && ! empty( $secret_site_key ) ) {
		wp_enqueue_script(
			'google-captcha',
			"https://www.google.com/recaptcha/api.js?render=$public_site_key",
			array(),
			'1.2.6',
			true
		);
		$script_handle = array( 'google-captcha' );
	} else {
		$script_handle = array();
	}

	wp_enqueue_script(
		'influactive-form',
		plugin_dir_url( __FILE__ ) .
		'dist/frontEnd.bundled.js',
		$script_handle,
		'1.2.6',
		true
	);
	wp_enqueue_style(
		'influactive-form',
		plugin_dir_url( __FILE__ ) . 'dist/frontForm.bundled.css',
		array(),
		'1.2.6'
	);

	wp_localize_script(
		'influactive-form',
		'ajaxObject',
		array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
	);
}

add_action( 'wp_enqueue_scripts', 'influactive_form_shortcode_enqueue' );

/**
 * Loads the Influactive Forms text domain for localization.
 *
 * @return void
 */
function load_influactive_forms_textdomain(): void {
	load_plugin_textdomain(
		'influactive-forms',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}

add_action( 'plugins_loaded', 'load_influactive_forms_textdomain' );

/**
 * Requires the WordPress core file from the given possible paths.
 *
 * @param array $possible_paths The possible paths to the WordPress core file.
 *
 * @return void
 */
function require_wordpress_core( array $possible_paths ): void {
	$base_path = sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) ?? '';
	foreach ( $possible_paths as $possible_path ) {
		$full_path = $base_path . DIRECTORY_SEPARATOR . ltrim( $possible_path, '/' );
		if ( file_exists( $full_path ) ) {
			require_once( $full_path );
			break;
		}
	}
}
