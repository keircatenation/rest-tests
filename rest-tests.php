<?php
/**
 * Plugin Name:       REST Tests
 * Plugin URI:        https://github.com/keircatenation/rest-tests
 * GitHub Plugin URI: https://github.com/keircatenation/rest-tests
 * Description:       Code testing various aspects of WordPress's REST API.
 * Version:           1.0.1
 * Author:            Keiran Pillman
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       rest-tests
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */



/**
 * ### Function: `register_post_type`
 */
add_action( 'init', function() {
    $labels = array(
        'name'                => __( 'Exhibitions', 'Post Type General Name', 'rest-tests' ),
        'singular_name'       => __( 'Exhibition', 'Post Type Singular Name', 'rest-tests' ),
        'menu_name'           => __( 'Exhibitions', 'rest-tests' ),
        'parent_item_colon'   => __( 'Parent Exhibition', 'rest-tests' ),
        'all_items'           => __( 'All Exhibitions', 'rest-tests' ),
        'view_item'           => __( 'View Exhibition', 'rest-tests' ),
        'add_new_item'        => __( 'Add New Exhibition', 'rest-tests' ),
        'add_new'             => __( 'Add New Exhibition', 'rest-tests' ),
        'edit_item'           => __( 'Edit Exhibition', 'rest-tests' ),
        'update_item'         => __( 'Update Exhibition', 'rest-tests' ),
        'search_items'        => __( 'Search Exhibitions', 'rest-tests' ),
        'not_found'           => __( 'Not Found', 'rest-tests' ),
        'not_found_in_trash'  => __( 'Not found in Trash', 'rest-tests' ),
    );
    // Set other options for Custom Post Type
    $args = array(
        'label'               => __( 'Exhibitions', 'rest-tests' ),
        'description'         => __( 'Exhibitions at MAG', 'rest-tests' ),
        'labels'              => $labels,
        // Features this CPT supports in Post Editor
        'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', ),
        'public'              => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-bank',
        'taxonomies'          => [''],
        'capability_type'     => 'post',
        'rewrite'             => array( 'slug' => 'exhibitions' ),
        'show_in_rest'        => true,
        'rest_base'           => 'exhibitions',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
    );
    register_post_type( 'exhibition_post_type', $args );
} );


/**
 * ### Function: `register_post_meta`
 */
add_action( 'init', function() {
    register_post_meta( 'exhibition_post_type', 'mag_exh_location', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
    ) );
    register_post_meta( 'exhibition_post_type', 'mag_exh_start', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
    ) );
    register_post_meta( 'exhibition_post_type', 'mag_exh_end', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'default' => 'none'
    ) );
    register_post_meta( 'exhibition_post_type', 'mag_exh_publication', array(
        'type' => 'object',
        'single' => true,
        // 'default' => array(
        //     'title' => null,
        //     'author' => null,
        //     'link' => null,
        // ),
        'show_in_rest' => array(
            'schema' => array(
                'type' => 'object',
                'properties' => array(
                    'title' => array(
                        'type' => array( 'string', 'null' )
                    ),
                    'author' => array(
                        'type' => array( 'string', 'null' )
                    ),
                    'link' => array(
                        'type' => array( 'string', 'null' ),
                        'format' => 'uri'
                    )
                ),
            ),
        ),
    ) );
} );


/**
 * ### Hook: `rest_prepare_{$this->post_type}`
 */
// add_filter( 'rest_prepare_exhibition_post_type', function( $response, $post, $request ){
//     $image = array(
//         'html' => get_the_post_thumbnail( $post->ID ),
//         'url' => wp_get_attachment_image_src( $response->data['featured_media'], 'large' ),
//         'alt' => get_post_meta( $response->data['featured_media'], TRUE )
//     );
//     $response->data['image'] = $image;
//     // $response->data['title'] = $response->data['title']['rendered'] . 'addition';

//     return $response;
// }, 10, 3 );


/**
 * ### Function: `register_rest_field`
 */
// add_action( 'rest_api_init', function(){
//     register_rest_field( 'exhibition_post_type', 'testimonials', array(
//         'get_callback' => function( $object_arr ){
//             // get field value from wherever it's coming from
//             $post_ID = $object_arr['id'];
//             $value = array(
//                 'we love this exhibition!',
//                 'coming back 10 times!'
//             );
//             return $value;
//         },
//         'update_callback' => function( $value, $object ) {
//             // need to do the updating thing!
//             // return true;
//         },
//         'schema' => array(
//             'description' => __( 'Testimonials' ),
//             'type' => 'array',
//             'items' => array(
//                 'type' => 'string'
//             )
//         )
//     ) );
// } );


/**
 * ### Hook: `rest_{$this->post_type}_query`
 */
// add_filter( 'rest_exhibition_post_type_query', function( $args, $request ){
//     $today = wp_date( 'c' );
//     $meta_query = array();

//     if ( isset( $request['date'] ) ) {

//         $date = DateTime::createFromFormat('Y-m-d', $request['date']);

//         if ( $date ) {
//             $meta_query = array(
//                 'relation' => 'AND',
//                 array(
//                     'key'       => 'mag_exh_start',
//                     'value'     => $date->format('c'),
//                     'compare'   => '<=',
//                     'type'      => 'DATETIME'
//                 ),
//                 array(
//                     'relation'   => 'OR',
//                     array(
//                         'key'       => 'mag_exh_end',
//                         'value'     => $date->format('c'),
//                         'compare'   => '>=',
//                         'type'      => 'DATETIME'
//                     ),
//                     array(
//                         'key'       => 'mag_exh_end',
//                         'value'     => 'none',
//                         'compare'   => '=',
//                         'type'      => 'CHAR'
//                     )
//                 )
//             );
//         }
//     }
//     if ( $meta_query ) {
//         $args['meta_query'] = $meta_query;
//     }
//     if ( isset( $request['has_password'] ) ) {
//         $args['has_password'] = !(bool)$request['has_password'];
//     }
//     return $args;
// },  10, 3 );


/**
 * ### Other Response-Modifying Hooks in order of execution
 */
// rest_pre_dispatch -- before the request is processed by WordPress
// add_filter( 'rest_pre_dispatch', function( $result, $server, $request ){
//     if( strpos( $request->get_route(), '/v2/exhibitions' ) !== false && ! is_user_logged_in() ) {
// 		return rest_ensure_response( [
// 			'success'  => false,
// 			'message' => __( 'You must be logged in to access /v2/exhibitions/* routes.', 'rest-tests' ),
//             'hook' => 'rest_pre_dispatch',
// 		] );
// 	}
// 	return $result;
// }, 10, 3 );

// rest_pre_echo_response -- right before WordPress delivers the response
// add_filter( 'rest_pre_echo_response', function( $result, $server, $request ){
//     if( strpos( $request->get_route(), '/v2/exhibitions' ) !== false && ! is_user_logged_in() ) {
// 		return rest_ensure_response( [
// 			'success'  => false,
// 			'message' => __( 'You must be logged in to access /v2/exhibitions/* routes.', 'rest-tests' ),
//             'hook' => 'rest_pre_echo_response',
// 		] );
// 	}
// 	return $result;
// }, 10, 3 );

/**
 * ### Hook: `pre_get_posts`
 */
// if ( strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false ) {
//     add_filter( 'pre_get_posts', function( $query ) {
//         if ( $query->get( 'post_type' ) != 'exhibition_post_type' ) {
// 			return $query;
// 		}
//         if ( isset( $_GET['has_password'] ) ) {
//             $query->set( 'has_password', !(bool)$_GET['has_password'] );
//         }
//         return $query;
//     } );
// }

// include_once 'proxy_endpoint.php';
// add_action( 'rest_api_init', function() {
//     $controller = new Smartsheet_Controller();
//     $controller->register_routes();
// } );