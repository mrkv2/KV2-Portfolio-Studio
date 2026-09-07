<?php

defined( 'ABSPATH' ) || exit;

/**
 * Strict reader for the two administrator-only JSON import tools.
 */
final class KV2PS_JSON {
	public static function read( $pasted_json, $uploaded_file, $max_bytes ) {
		$json = is_string( $pasted_json ) ? trim( $pasted_json ) : '';

		if ( is_array( $uploaded_file ) && ! empty( $uploaded_file['tmp_name'] ) ) {
			$json = self::read_upload( $uploaded_file, $max_bytes );
			if ( is_wp_error( $json ) ) {
				return $json;
			}
		}

		if ( ! $json ) {
			return new WP_Error( 'empty_json', __( 'Ajoutez un fichier JSON ou collez son contenu.', 'kv2-portfolio-studio' ) );
		}
		if ( strlen( $json ) > $max_bytes ) {
			return new WP_Error( 'json_too_large', __( 'Le contenu JSON dépasse la taille autorisée.', 'kv2-portfolio-studio' ) );
		}

		$json = preg_replace( '/^\xEF\xBB\xBF/', '', $json );
		$data = json_decode( $json, true, 64 );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
			return new WP_Error( 'bad_json', sprintf( __( 'JSON illisible : %s', 'kv2-portfolio-studio' ), json_last_error_msg() ) );
		}

		return $data;
	}

	private static function read_upload( $file, $max_bytes ) {
		$error    = isset( $file['error'] ) ? absint( $file['error'] ) : UPLOAD_ERR_NO_FILE;
		$size     = isset( $file['size'] ) ? absint( $file['size'] ) : 0;
		$name     = isset( $file['name'] ) ? sanitize_file_name( $file['name'] ) : '';
		$tmp_name = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';

		if ( UPLOAD_ERR_OK !== $error || ! $tmp_name || ! $size || $size > $max_bytes ) {
			return new WP_Error( 'invalid_upload', __( 'Le fichier JSON est invalide ou dépasse la taille autorisée.', 'kv2-portfolio-studio' ) );
		}
		if ( 'json' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
			return new WP_Error( 'invalid_extension', __( 'Seuls les fichiers .json sont acceptés.', 'kv2-portfolio-studio' ) );
		}
		if ( ! is_uploaded_file( $tmp_name ) || ! is_readable( $tmp_name ) ) {
			return new WP_Error( 'invalid_upload_source', __( 'Le fichier ne provient pas d’un téléversement HTTP valide.', 'kv2-portfolio-studio' ) );
		}

		$filetype = wp_check_filetype( $name, array( 'json' => 'application/json' ) );
		if ( 'json' !== $filetype['ext'] || 'application/json' !== $filetype['type'] ) {
			return new WP_Error( 'invalid_filetype', __( 'Le type de fichier transmis n’est pas autorisé.', 'kv2-portfolio-studio' ) );
		}

		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$mime  = $finfo ? finfo_file( $finfo, $tmp_name ) : false;
			if ( $finfo && PHP_VERSION_ID < 80100 ) {
				finfo_close( $finfo );
			}
			if ( $mime && ! in_array( strtolower( $mime ), array( 'application/json', 'application/x-json', 'text/plain' ), true ) ) {
				return new WP_Error( 'invalid_mime', __( 'Le contenu du fichier ne correspond pas à un document JSON.', 'kv2-portfolio-studio' ) );
			}
		}

		$json = file_get_contents( $tmp_name ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $json || ! $json || strlen( $json ) > $max_bytes ) {
			return new WP_Error( 'unreadable_json', __( 'Le fichier JSON est vide, illisible ou trop volumineux.', 'kv2-portfolio-studio' ) );
		}

		return $json;
	}
}
