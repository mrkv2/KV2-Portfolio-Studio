<?php

defined( 'ABSPATH' ) || exit;

final class KV2PS_Schema {
	public static function init() {
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'add_rank_math_graph' ), 999, 2 );
	}

	public static function add_rank_math_graph( $data, $jsonld ) {
		unset( $jsonld );
		$settings = KV2PS_Plugin::settings();
		if ( ! is_singular( KV2PS_Post_Types::POST_TYPE ) ) {
			return $data;
		}
		$post_id   = get_queried_object_id();
		$image_ids = array_unique(
			array_filter(
				array_merge(
					array( get_post_thumbnail_id( $post_id ) ),
					KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_before_images', true ) ),
					KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_after_images', true ) )
				)
			)
		);

		if ( ! empty( $settings['rank_math_schema'] ) && ! self::has_type( $data, 'CreativeWork' ) ) {
			$permalink   = get_permalink( $post_id );
			$images      = array_values( array_filter( array_map( 'wp_get_attachment_url', $image_ids ) ) );
			$description = trim( (string) get_the_excerpt( $post_id ) );
			if ( ! $description ) {
				$description_parts = array();
				foreach ( array( '_kv2ps_problem', '_kv2ps_intervention', '_kv2ps_result' ) as $meta_key ) {
					$value = trim( (string) get_post_meta( $post_id, $meta_key, true ) );
					if ( $value ) {
						$description_parts[] = $value;
					}
				}
				$description = implode( ' ', $description_parts );
			}
			$node   = array(
				'@type'        => 'CreativeWork',
				'@id'          => $permalink . '#realisation',
				'name'         => get_the_title( $post_id ),
				'url'          => $permalink,
				'description'  => wp_html_excerpt( wp_strip_all_tags( $description ), 500, '…' ),
				'dateCreated'  => get_post_meta( $post_id, '_kv2ps_project_date', true ) ?: get_the_date( 'c', $post_id ),
				'dateModified' => get_the_modified_date( 'c', $post_id ),
				'inLanguage'   => get_bloginfo( 'language' ),
			);

			$webpage_id = self::find_type_node_id( $data, 'WebPage' );
			$creator_id = self::find_type_node_id( $data, 'Organization' );
			if ( $webpage_id ) {
				$node['mainEntityOfPage'] = array( '@id' => $webpage_id );
			}
			if ( $creator_id ) {
				$node['creator'] = array( '@id' => $creator_id );
			}

			if ( $images ) {
				$node['image'] = $images;
			}

			$keywords = array();
			foreach ( array( 'kv2_service', 'kv2_meuble', 'kv2_style', 'kv2_technique' ) as $taxonomy ) {
				$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
				if ( ! is_wp_error( $terms ) && $terms ) {
					$keywords = array_merge( $keywords, $terms );
				}
			}
			if ( $keywords ) {
				$node['keywords'] = implode( ', ', array_unique( $keywords ) );
			}

			if ( ! get_post_meta( $post_id, '_kv2ps_confidential', true ) ) {
				$cities = wp_get_post_terms( $post_id, 'kv2_ville', array( 'fields' => 'names' ) );
				if ( ! is_wp_error( $cities ) && $cities ) {
					$node['contentLocation'] = array(
						'@type' => 'Place',
						'name'  => implode( ', ', $cities ),
					);
				}
			}

			$data['kv2ps-creativework'] = array_filter( $node );
		}

		if ( ! empty( $settings['image_schema'] ) ) {
			foreach ( $image_ids as $image_id ) {
				$url = wp_get_attachment_url( $image_id );
				if ( ! $url ) {
					continue;
				}
				$credit    = get_post_meta( $image_id, '_kv2ps_credit', true ) ?: $settings['image_credit'];
				$copyright = get_post_meta( $image_id, '_kv2ps_copyright', true ) ?: $settings['image_copyright'];
				$license   = get_post_meta( $image_id, '_kv2ps_license_url', true ) ?: $settings['image_license_url'];
				$acquire   = get_post_meta( $image_id, '_kv2ps_acquire_license_url', true ) ?: $settings['image_acquire_license_url'];
				$creator   = $settings['image_creator_name'] ? array_filter(
					array(
						'@type' => $settings['image_creator_type'],
						'name'  => $settings['image_creator_name'],
						'url'   => $settings['image_creator_url'],
					)
				) : array();
				$image_node = array_filter(
					array(
						'@type'              => 'ImageObject',
						'@id'                => $url . '#imageobject',
						'contentUrl'         => $url,
						'url'                => $url,
						'name'               => get_the_title( $image_id ),
						'caption'            => wp_get_attachment_caption( $image_id ),
						'creditText'         => $credit,
						'creator'            => $creator,
						'copyrightNotice'    => $copyright,
						'license'            => $license,
						'acquireLicensePage' => $acquire,
					)
				);
				$existing_key = self::find_image_key( $data, $url );
				if ( null !== $existing_key ) {
					foreach ( $image_node as $property => $value ) {
						if ( empty( $data[ $existing_key ][ $property ] ) ) {
							$data[ $existing_key ][ $property ] = $value;
						}
					}
				} else {
					$data[ 'kv2ps-image-' . $image_id ] = $image_node;
				}
			}
		}

		return $data;
	}

	private static function has_type( $data, $type ) {
		foreach ( $data as $node ) {
			if ( ! is_array( $node ) || empty( $node['@type'] ) ) {
				continue;
			}
			$types = (array) $node['@type'];
			if ( in_array( $type, $types, true ) ) {
				return true;
			}
		}
		return false;
	}

	private static function find_type_node_id( $data, $type ) {
		foreach ( $data as $node ) {
			if ( ! is_array( $node ) || empty( $node['@id'] ) || empty( $node['@type'] ) ) {
				continue;
			}
			if ( in_array( $type, (array) $node['@type'], true ) ) {
				return $node['@id'];
			}
		}
		return '';
	}

	private static function find_image_key( $data, $url ) {
		foreach ( $data as $key => $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			if ( ( isset( $node['contentUrl'] ) && $url === $node['contentUrl'] ) || ( isset( $node['url'] ) && $url === $node['url'] && self::has_type( array( $node ), 'ImageObject' ) ) ) {
				return $key;
			}
		}
		return null;
	}
}
