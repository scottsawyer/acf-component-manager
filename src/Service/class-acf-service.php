<?php
/**
 * Contains the AcfService class.
 *
 * @since 0.0.9
 * @package acf-component-manager
 */

declare( strict_types = 1 );

namespace AcfComponentManager\Service;

// If this file is called directly, short.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Provides AcfService class.
 */
class AcfService {

	/**
	 * Get ACF JSON.
	 *
	 * @param string $file_path The full path to the ACF JSON file.
	 *
	 * @return array|null
	 */
	public function get_acf_json( string $file_path ): ?array {
		$file = file_get_contents( $file_path );
		if ( $file ) {
			return json_decode( $file, true );
		}
		return null;
	}

	/**
	 * Get ACF posts.
	 *
	 * Aggregates all ACF posts.
	 *
	 * @since 0.0.4
	 *
	 * @return array
	 *   An array of ACF post (field groups, post types, taxonomies, option page)
	 */
	public function get_acf_posts(): array {
		$acf_posts = get_transient( 'acf_posts' );
		if ( ! $acf_posts ) {
			$acf_field_groups = $this->get_acf_field_groups();
			$acf_post_types = $this->get_acf_post_types();
			$acf_taxonomies = $this->get_acf_taxonomies();
			$acf_option_pages = $this->get_acf_option_pages();
			$acf_posts =  array_merge( $acf_field_groups, $acf_post_types, $acf_taxonomies, $acf_option_pages );
			set_transient( 'acf_posts', $acf_posts, HOUR_IN_SECONDS );
		}
		return $acf_posts;
	}

	/**
	 * Get ACF field groups.
	 *
	 * Retrieves ACF field group data from the database.
	 *
	 * @since 0.0.1
	 *
	 * @return array
	 *   An array of ACF field groups.
	 */
	public function get_acf_field_groups(): array {
		$acf_field_groups = array();
		$args = array(
			'post_type'      => 'acf-field-group',
			'posts_per_page' => -1,
		);
		$field_group_query = new \WP_Query( $args );
		if ( $field_group_query->have_posts() ) {
			$field_group_posts = $field_group_query->get_posts();
			foreach ( $field_group_posts as $field_group_post ) {
				$acf_field_groups[] = array(
					'id'       => $field_group_post->ID,
					'key'      => $field_group_post->post_name,
					'status'   => $field_group_post->post_status,
					'name'     => $field_group_post->post_title,
					'modified' => $field_group_post->post_modified_gmt,
				);
			}
		}
		/* Restore original Post Data */
		wp_reset_postdata();
		return $acf_field_groups;
	}

	/**
	 * Get ACF post types.
	 *
	 * Retrieves ACF post type data from the database.
	 *
	 * @since 0.0.4
	 *
	 * @return array
	 *   An array of ACF post types.
	 */
	public function get_acf_post_types(): array {
		$acf_post_types = array();
		$args = array(
			'post_type'      => 'acf-post-type',
			'posts_per_page' => -1,
		);
		$post_type_query = new \WP_Query( $args );
		if ( $post_type_query->have_posts() ) {
			$acf_post_type_posts = $post_type_query->get_posts();
			foreach ( $acf_post_type_posts as $acf_post_type ) {
				$acf_post_types[] = array(
					'id'       => $acf_post_type->ID,
					'key'      => $acf_post_type->post_name,
					'status'   => $acf_post_type->post_status,
					'name'     => $acf_post_type->post_title,
					'modified' => $acf_post_type->post_modified_gmt,
				);
			}
		}
		return $acf_post_types;
	}

	/**
	 * Get ACF taxonomies.
	 *
	 * Retrieves ACF taxonomy data from the database.
	 *
	 * @since 0.0.4
	 *
	 * @return array
	 *   An array of ACF taxonomies.
	 */
	public function get_acf_taxonomies(): array {
		$acf_taxonomies = array();
		$args = array(
			'post_type'      => 'acf-taxonomy',
			'posts_per_page' => -1,
		);
		$taxonomy_query = new \WP_Query( $args );
		if ( $taxonomy_query->have_posts() ) {
			$acf_taxonomy_posts = $taxonomy_query->get_posts();
			foreach ( $acf_taxonomy_posts as $acf_taxonomy ) {
				$acf_taxonomies[] = array(
					'id'       => $acf_taxonomy->ID,
					'key'      => $acf_taxonomy->post_name,
					'status'   => $acf_taxonomy->post_status,
					'name'     => $acf_taxonomy->post_title,
					'modified' => $acf_taxonomy->post_modified_gmt,
				);
			}
		}
		return $acf_taxonomies;
	}

	/**
	 * Get ACF option pages.
	 *
	 * Retrieves ACF option page data from the database.
	 *
	 * @since 0.0.4
	 *
	 * @return array
	 *   An array of ACF option pages.
	 */
	public function get_acf_option_pages(): array {
		$acf_option_pages = array();
		$args = array(
			'post_type'      => 'acf-ui-options-page',
			'posts_per_page' => -1,
		);
		$option_page_query = new \WP_Query( $args );
		if ( $option_page_query->have_posts() ) {
			$acf_option_page_posts = $option_page_query->get_posts();
			foreach ( $acf_option_page_posts as $acf_option_page ) {
				$acf_option_pages[] = array(
					'id'       => $acf_option_page->ID,
					'key'      => $acf_option_page->post_name,
					'status'   => $acf_option_page->post_status,
					'name'     => $acf_option_page->post_title,
					'modified' => $acf_option_page->post_modified_gmt,
				);
			}
		}
		return $acf_option_pages;
	}

	/**
	 * Get acf post by key.
	 *
	 * @param string $post_type The ACF post type.
	 * @param string $key       The ACF key.
	 *
	 * @return \WP_Post|bool
	 *   The post if found.
	 */
	public function get_acf_post_by_key( string $post_type, string $key ): \WP_Post|bool {
		$args = array(
			'post_name' => $key,
			'post_type' => $post_type,
		);
		$posts = get_posts( $args );
		if ( $posts ) {
			return reset( $posts );
		}
		return false;
	}
}
