# More than an Extension: Customizing the WordPress REST API
Keiran Pillman
Webmaster, Memorial Art Gallery of the University of Rochester
Digital Collegium Pennsylvania Regional Conference, June 2025

1. [Basics](#basics-of-wordpresss-rest-api-basics)
2. [Modifying It](#modifying-default-responses)
3. [D.I.Y. It](#diy-it)
4. [References](#helpful-references)

## Basics of WordPress's REST API

### A little background

### What is it used for?

### What can you use it for?

### Terminology
- A **route** is
- An **endpoint** is
- A **namespace** is
https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/
https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/
https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/

### How do you access it?
WordPress’s default routes are found at /wp-json/wp/v2/*, and you can see all routes at /wp-json/.

Some of the default routes include:
- [/wp-json/wp/v2/posts](https://developer.wordpress.org/rest-api/reference/posts/)
- [/wp-json/wp/v2/users](https://developer.wordpress.org/rest-api/reference/users/)
  - I've found the default behavior is just listing users who have published posts, not all users
- [/wp-json/wp/v2/media](https://developer.wordpress.org/rest-api/reference/media/)
- [/wp-json/wp/v2/taxonomies](https://developer.wordpress.org/rest-api/reference/taxonomies/)

A full list of endpoints is found at [https://developer.wordpress.org/rest-api/reference/](https://developer.wordpress.org/rest-api/reference/).

### Schema
https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#json-schema-basics

## Modifying Default Responses
**WARNING**: You can mess up the block editor (or other plugins) if you remove or change core fields in the response objects. No matter what you want from the API response, it’s best to add an entirely new field instead of changing a field that already exists.

### Modifying the data that's returned
Contained here are a selection of functions and hooks that modify data in WordPress’s default REST API routes.

#### Function: `register_post_type`
- Used to create custom post types
- `show_in_rest`: must be set to true
- If true, then a default endpoint is created at `/wp-json/wp/v2/{$this->post_type}`
- If using custom fields, you need `custom-fields` support
```
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
```
#### Functions: `register_meta` and `register_post_meta`
- Used to create custom meta for objects
- `show_in_rest`: must be set to true
- If true, then it automatically appears in the `meta` array in default routes
```
add_action( 'init', function(){
    register_post_meta( 'fun_posts', 'fun_post_meta', array(
        'type' => 'string',
        'label' => 'Fun Post Meta',
        'show_in_rest' => true,
        'single' => true,
    ) );
} );
add_action( 'init', function(){
    register_post_meta( 'fun_posts', 'fun_post_object_meta', array(
        'label' => 'Fun Post Meta',
        'single' => true,
        'type' => 'object',
        'show_in_rest' => array(
            'schema' => array(
                'type' => 'object',
                'properties' => array(
                    ...
                )
            )
        ),
    ) );
} );
```
#### Hook: `rest_prepare_{$this->post_type}`
- Called when preparing a single object of a single post type
- The dynamic portion of the hook refers to the slug of the post type
  - Example: `rest_prepare_fun_posts`
- `$response`: WP_REST_Response, the data that will be returned
- `$post`: WP_Post, the current post object
- `$request`: WP_REST_Request, the current request, including parameters
```
add_filter( 'rest_prepare_post', function ( $response, $post, $request ) {
	$response->data['thumbnail'] = get_the_post_thumbnail( $post->ID );
      return $response;
} , 10, 3);
```
#### Hook: `rest_pre_echo_response`
- Called right before the REST API gives its data to the user; the last hook called
- Can lock down certain routes if you want to prevent users (all, or just non-logged-in users) from accessing routes
- Can just modify the results array
- `$result`: array, results array from API request
- `$server`: WP_REST_Server, server instance
- `$request`: WP_REST_Request, the incoming request
```
add_filter( 'rest_pre_echo_response', function ( $result, $server, $request ) {
    if( strpos( $request->get_route(), '/v2/users' ) !== false && ! is_user_logged_in() ) {
        return rest_ensure_response( [
            'success'  => false,
            'message' => __( 'You must be logged in to access /v2/users/* routes.', 'td-lockdown-users-api' ),
        ] );
    }
} , 10, 3);
```
#### Hook: `rest_pre_dispatch`
- Triggered before the request is processed by WordPress, allows for intercepting for manipulation and inspection
- Can be overridden by rest_pre_echo_response
- `$result`: various, different types of data returned to the client
- `$server`: WP_REST_Server, server instance
- `$request`: information about the request
```
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
    if( strpos( $request->get_route(), '/v2/users' ) !== false && ! is_user_logged_in() ) {
        return rest_ensure_response( [
            'success'  => false,
            'message' => __( 'You must be logged in to access /v2/users/* routes.', 'td-lockdown-users-api' ),
        ] );
    }
} , 10, 3);
```
#### Function: `register_rest_field`
- Adds data to the global variable `$wp_rest_additional_fields`, which contains an array of field definitions containing the callbacks used to retrieve or update the field’s value
- This field gets folded into the JSON object before values added using `rest_prepare_{$this->post_type}`
- `$object_type`: name of the object
- `$attribute`: the name of the attribute/field
- `$args`: an array with keys defining the callback functions for the field
  - `get_callback`: optional, function used to get the value for the field
  - `update_callback`: optional, function used to update the value of the field
  - `schema`: optional, object defining the field’s schema
```
add_action( 'rest_api_init', function(){
    register_rest_field( 'post', 'rest_field', array(
        'get_callback' => function( $object_arr ){
            $value;
            return $value;
        },
        'update_callback' => function( $value, $object ){
            // do a thing
            return true;
        },
        'schema' => array(
            'description' => 'A REST Field',
            'type' => 'string'|'number'|'integer'|'boolean'|'array'|'object'|'null',
            ...
        )
    ) );
} );
```

### Changing what data is selected

#### custom controllers
https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-rest-api-support-for-custom-content-types/

#### rest_X_query

#### rest_post_search_query

#### rest_term_search_query

#### rest_request_before_callbacks

#### pre_get_posts
- firing on /wp-json/ URLs only

## D.I.Y. It

You should use the validate_callback for your arguments to verify whether the input you are receiving is valid. The sanitize_callback should be used to transform the argument input or clean out unwanted parts out of the argument, before the argument is processed by the main callback.

### rest_ensure_response
https://developer.wordpress.org/reference/functions/rest_ensure_response/

### CURIEs
https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/#adding-links-to-the-api-response

## Helpful References
- [WordPress API Reference](https://developer.wordpress.org/rest-api/reference/)
- [JSON Schema Basics](https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#json-schema-basics)
- [register_post_type](https://developer.wordpress.org/reference/functions/register_post_type/)
- [register_post_meta](https://developer.wordpress.org/reference/functions/register_post_meta/)
- [register_meta](https://developer.wordpress.org/reference/functions/register_meta/)
- [rest_prepare_post_type](https://developer.wordpress.org/reference/functions/register_post_type/)
- [rest_pre_echo_response](https://developer.wordpress.org/reference/hooks/rest_pre_echo_response/)
- [Intercept WordPress API Requests](https://thriftydeveloper.com/2020/08/20/intercept-wordpress-api-requests/)
- [rest_pre_dispatch](https://developer.wordpress.org/reference/hooks/rest_pre_dispatch/)
- [Manipulate Incoming WordPress REST API Requests](https://tommcfarlin.com/incoming-wordpress-rest-api-requests/)
- [Adding Custom Fields to API Responses](https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/#adding-custom-fields-to-api-responses)
- [register_rest_field](https://developer.wordpress.org/reference/functions/register_rest_field/)
- [register_rest_field vs _register_meta](https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/#using-register_rest_field-vs-register_meta)
