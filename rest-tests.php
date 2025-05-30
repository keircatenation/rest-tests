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

// custom post type
add_action( 'init', function() {
    register_post_type( 'fun_posts', array(
        'label'               => __( 'fun posts', 'rest-tests' ),
        'supports'            => array( 'title', 'editor', 'author', 'excerpt', 'thumbnail', 'custom-fields', ),
        'public'              => true,
        'menu_position'       => 5,
        'capability_type'     => 'post',
        'show_in_rest'        => true,
    ) );
}, 0 );

// custom meta
add_action( 'init', function(){
    register_post_meta( 'fun_posts', 'fun_post_meta', array(
        'type' => 'string',
        'label' => 'Fun Post Meta',
        'show_in_rest' => true,
        'single' => true,
    ) );
} );

add_filter( 'rest_prepare_post', function( $response, $post, $request ){
    $image = array(
        'html' => get_the_post_thumbnail( $post->ID ),
        'url' => wp_get_attachment_image_src( $response->data['featured_media'], 'large' ),
        'alt' => get_post_meta( $response->data['featured_media'], TRUE )
    );
    $response->data['image'] = $image;
    // $response->data['title'] = $response->data['title']['rendered'] . 'addition';

    // $categories = wp_get_post_categories( $post->ID, array( 'fields' => 'all' ) );
    // $response->data['categories'] = $categories;

    return $response;
}, 10, 3 );

// add_filter( 'rest_pre_echo_response', function( $result, $server, $request ){
//     if( strpos( $request->get_route(), '/v2/users' ) !== false && ! is_user_logged_in() ) {
// 		return rest_ensure_response( [
// 			'success'  => false,
// 			'message' => __( 'You must be logged in to access /v2/users/* routes.', 'rest-tests' ),
//             'hook' => 'rest_pre_echo_response',
// 		] );
// 	}

// 	return $result;
// }, 10, 3 );

// add_filter( 'rest_pre_dispatch', function( $result, $server, $request ){
//     if( strpos( $request->get_route(), '/v2/users' ) !== false && ! is_user_logged_in() ) {
// 		return rest_ensure_response( [
// 			'success'  => false,
// 			'message' => __( 'You must be logged in to access /v2/users/* routes.', 'rest-tests' ),
//             'hook' => 'rest_pre_dispatch',
// 		] );
// 	}

// 	return $result;
// }, 10, 3 );


add_action( 'rest_api_init', function(){
    register_rest_field( 'post', 'fun_rest_field', array(
        'get_callback' => function( $object_arr ){
            $value = array(
                'wink' => 'hello there',
                'wonk' => 5
            );
            return $value;
        },
        'update_callback' => function( $value, $object ) {
            // need to do the updating thing!
            // $ret = wp_update_comment( array(
            //     'comment_ID'    => $comment_obj->comment_ID,
            //     'comment_karma' => $karma
            // ) );
            // if ( false === $ret ) {
            //     return new WP_Error(
            //       'rest_comment_karma_failed',
            //       __( 'Failed to update comment karma.' ),
            //       array( 'status' => 500 )
            //     );
            // }
            // return true;
        },
        'schema' => array(
            'description' => __( 'A Fun REST Field!' ),
            'type' => 'object',
            'properties' => array(
                'wink' => array(
                    'type' => 'string'
                ),
                'wonk' => array(
                    'type' => 'integer'
                )
            )
        )
    ) );
} );