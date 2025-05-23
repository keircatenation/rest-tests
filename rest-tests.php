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


// rest_prepare_{$this->post_type}
add_filter( 'rest_prepare_post', function( $response, $post, $request ){
    $image = array(
        'html' => get_the_post_thumbnail( $post->ID ),
        'url' => wp_get_attachment_image_src( $response->data['featured_media'], 'large' ),
        'alt' => get_post_meta( $response->data['featured_media'], TRUE )
    );
    $response->data['image'] = $image;
    // $response->data['request_params'] = $request->get_params();
    // $response->data['title'] = $response->data['title']['rendered'] . 'addition';
    return $response;
}, 10, 3 );

// rest_pre_echo_response
// add_filter( 'rest_pre_echo_response', function( $result, $server, $request ){
//     $result[] = $request->get_params();
//     return $result;
// }, 10, 3 );


// custom post type
add_action( 'init', function() {
    register_post_type( 'fun_posts', array(
        'label'               => __( 'fun posts', 'rest-tests' ),
        'labels'              => array(
            'name'                => __( 'fun posts', 'Post Type General Name', 'rest-tests' ),
            'singular_name'       => __( 'fun post', 'Post Type Singular Name', 'rest-tests' ),
            'menu_name'           => __( 'fun posts', 'rest-tests' ),
            'parent_item_colon'   => __( 'Parent fun post', 'rest-tests' ),
            'all_items'           => __( 'All fun posts', 'rest-tests' ),
            'view_item'           => __( 'View fun post', 'rest-tests' ),
            'add_new_item'        => __( 'Add New fun post', 'rest-tests' ),
            'add_new'             => __( 'Add New fun post', 'rest-tests' ),
            'edit_item'           => __( 'Edit fun post', 'rest-tests' ),
            'update_item'         => __( 'Update fun post', 'rest-tests' ),
            'search_items'        => __( 'Search fun posts', 'rest-tests' ),
            'not_found'           => __( 'Not Found', 'rest-tests' ),
            'not_found_in_trash'  => __( 'Not found in Trash', 'rest-tests' ),
        ),
        'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', ),
        'public'              => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-bank',
        'capability_type'     => 'post',
        'show_in_rest'        => true,
        'rewrite'             => array( 'slug' => 'fun-posts' ),
        'taxonomies'          => [''],
    ) );
}, 0 );

add_action( 'init', function(){
    register_post_meta( 'fun_posts', 'fun_post_meta', array(
        'type' => 'string',
        'label' => 'Fun Post Meta',
        'show_in_rest' => true,
        'single' => true,
    ) );
} );
