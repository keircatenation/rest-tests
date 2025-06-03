# More than an Extension: Customizing the WordPress REST API
Keiran Pillman<br>
Webmaster, Memorial Art Gallery of the University of Rochester<br>
Digital Collegium Pennsylvania Regional Conference, June 2025

1. [Basics](#basics-of-wordpresss-rest-api-basics)
2. [Modifying It](#modifying-default-responses)
3. [D.I.Y. It](#diy-it)
4. [References](#helpful-references)

---

## Basics of WordPress's REST API
WordPress's REST API was started as a plugin, but was fully integrated into core with 4.7 in 2016. It's the foundation of the block editor, enables useage of WordPress as a headless CMS, and its ability to be modified allows for custom routes and endpoints that can do quite a bit more.

Content that is public on your site is generally publicly accessible via the REST API, while private content, password-protected content, internal users, custom post types, and metadata is only available with authentication or if you specifically set it to be so.

### Terminology
- A **route** is the name used to access an endpoint; the URI that can be mapped to different HTTP methods
- An **endpoint** is the function called when an individual HTTP method is called on a route
- A **namespace** is a way to group routes
- A **request** is an instance of the `WP_REST_Request` class, which stores and retrieves information for the current request
- A **response** is data you get back from the API, either what's requested or an error. The `WP_REST_Response` class can be used to interact with this data
- A **controller class** is used to unify and coordinate all the logic for a given route, which usually represents a specific type of data object in your site. Make a custom controller a subclass of `WP_REST_Controller` for easier schema validation
- **Schema** defines the structure of the data that will be worked with in an endpoint, and helps create maintainable, discoverable, and extensible endpoints

### How do you access it?
WordPress’s default routes are found at `/wp-json/wp/v2/*`, and you can see all routes at `/wp-json/`.

Some of the default routes include:
- [/wp-json/wp/v2/posts](https://developer.wordpress.org/rest-api/reference/posts/)
- [/wp-json/wp/v2/users](https://developer.wordpress.org/rest-api/reference/users/)
  - I've found the default behavior is just listing users who have published posts, not all users
- [/wp-json/wp/v2/media](https://developer.wordpress.org/rest-api/reference/media/)
- [/wp-json/wp/v2/taxonomies](https://developer.wordpress.org/rest-api/reference/taxonomies/)

On sites without pretty permalinks, the route is added to the URL as the rest_route parameter. For example, the URL to access the `posts` route would be `http://example.com/?rest_route=/wp/v2/posts`.

### _fields global parameter
WordPress has global parameters that apply to every resource and control how the API handles the request. The `_fields` parameter in particular is useful for narrowing down which fields are included in the response.

For example, `/wp-json/wp/v2/fun_posts/?_fields=title,content,meta` would return only the title, content, and meta fields for the `fun_posts` GET endpoint.

These parameters work on custom API endpoints as well!

---

## Modifying Default Responses
**WARNING**: You can mess up the block editor (or other plugins) if you remove or change core fields in the response objects. No matter what you want from the API response, it’s best to add an entirely new field instead of changing a field that already exists.

### Function: `register_post_type`
- Used to create custom post types
- `show_in_rest`: must be set to true
- If true, then a default endpoint is created at `/wp-json/wp/v2/{$this->post_type}`
- `rest_base`: can be used to specify a custom REST base, like a custom slug
- `rest_controller_class`: can be used to distinguish a custom controller class
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
### Functions: `register_meta` and `register_post_meta`
- Used to create custom meta for objects
- `show_in_rest`: must be set to true *or* set to a schema object when defining an array or object
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
### Hook: `rest_prepare_{$this->post_type}`
- Called when preparing a single object of a single post type
- The dynamic portion of the hook refers to the slug of the post type
- `$response`: WP_REST_Response, the data that will be returned
- `$post`: WP_Post, the current post object
- `$request`: WP_REST_Request, the current request, including parameters
```
add_filter( 'rest_prepare_post', function ( $response, $post, $request ) {
	$response->data['thumbnail'] = get_the_post_thumbnail( $post->ID );
      return $response;
} , 10, 3);
```
### Function: `register_rest_field`
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
### Hook: `rest_{$this->post_type}_query`
- Filters WP_Query arguments when querying posts via the REST API, takes place after the tax_query arg is generated
- The dynamic portion of the hook refers to the slug of the post type
  - Example: `rest_fun_posts_query`
- `$args`: array of arguments for WP_Query
- `$request`: WP_REST_Request, the current request, including parameters
```
add_filter( 'rest_fun_posts_query', function( $args, $request ){
    if( isset( $request['fun_post_meta'] ) ) {
        $args['meta_key'] = 'fun_post_meta';
        $args['meta_value'] = esc_attr( $request['fun_post_meta'] );
    }
    return $args;
} );
```
### Other Response-Modifying Hooks in order of execution
These hooks allow for modification and inspection of the request at different stages of the process. Hooks that happen later in the order will override hooks that happen earlier, if you include multiple.

1. `rest_pre_dispatch`: triggered before the request is processed at all by WordPress; filters the pre-calculated result of a REST API dispatch request
2. `rest_request_before_callbacks`: filters response before executing any REST API callbacks
3. `rest_dispatch_request`: filters the REST API dispatch request result
4. `rest_request_after_callbacks`: filters the response immediately after executing any REST API callbacks
5. `rest_post_dispatch`: filters the REST API response
6. `rest_pre_echo_response`: allows modification of the response data after inserting ambedded data; called right before the REST API gives the response to the user

### Hook: `pre_get_posts`
Not a REST API-centric hook, but if you check the request URI for `/wp-json/`, you can make sure your function will only fire on REST API requests.
```
if ( strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false ) {
    add_filter( 'pre_get_posts', function( $query ) {
        if ( $query->get( 'post_type' ) != 'fun_posts' ) {
			return $query;
		}
        if ( isset( $_GET['fun_post_meta'] ) ) {
            $query->set( 'meta_key', 'fun_post_meta' );
            $query->set( 'meta_value', $_GET['fun_post_meta'] );
        }
        return $query;
    } );
}
```
---

## D.I.Y. It

### Custom Controllers
You can specify a custom controller to replace the default `WP_REST_Posts_Controller` controller for custom post types. It's useful to extend the `WP_Rest_Controller`, the base class for all core WordPress endpoints, to take advantage of built-in methods that standardize the output of resources.

Included methods include:
- `prepare_response_for_collection`
- `add_additional_fields_to_object`: append any registered REST fields to your prepared response object
- `get_fields_for_response`: inspect the `_fields` query parameter to determine which response fields have been requested
- `get_context_param`
- `filter_response_by_context`
- `get_collection_params`

Endpoint-specific methods *do* need to be added, such as:
- `get_item`
- `register_routes`
- `update_item_permissions_check`

### PROXY ME, BABY: `register_rest-route`
One of the coolest things you can do with custom REST API endpoints is create proxy routes and endpoints. Any data that you can get via curl can be accessed with `wp_remote_get` (or whatever method you prefer) and exposed with a proxy endpoint. This data can be publicly available, or you can restrict it with authentication.

The basic registration just needs the function call:
```
add_action( 'rest_api_init', function(){
    register_rest_route( 'NAMESPACE', '/RESOURCE_NAME', $arguments_array, $override );
} );
```
But there are benefits to following the controller format:
```
class My_Cool_Controller {
    public function __construct() {
        $this->namespace = 'NAMESPACE';
        $this->resource_name = 'RESOURCE_NAME';
        $this->whatever = 'shrug emoji';
    }
    public function register_routes(){
        register_rest_route( $this->namespace, '/' . $this->resource_name, _array_, $override );
    }
    ...
}
add_action( 'rest_api_init', function(){
    $controller = new My_Cool_Controller();
    $controller->register_routes();
} );
```
#### `register_rest_route` arguments array
Either an array of options for the endpoint, or an array of arrays for multiple methods.
```
$args = array(
    'methods' => 'GET',
    'callback' => array( $this, 'get_items' ),
    'permission_callback' => __return_true,
    'args' => aray(
        'id' => array(
            'description' => esc_html__( 'Smartsheet ID to get data from' ),
            'type' => 'integer',
            'enum' => array(  ),
            'validate_callback' => function() {
                // check if the argument matches what you want it to
            },
            'sanitize_callback' => function(){
                // sanitize value to strip out unwanter data or transform it into a desired format
            }
        )
    ),
);

$args_with_schema = array(
    array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => array( $this, 'get_items' ),
        'permission_callback' => array( $this, 'get_permissions_check' ),
    ),
    array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => array( $this, 'post_items' ),
        'permission_callback' => array( $this, 'get_permissions_check' ),
    ),
    'schema' => array(
        //schema
    )
);
```
- **methods**: the method for the particular endpoint. Use constants like `WP_REST_Server::READABLE`
- **callback**: the function that executes the endpoint
- **permission_callback**: the function that determines who can access/operate the endpoint. For public data, you can use `__return_true`, or you can create a function that checks against permissions
- **`enum`** specifies what values the argument can take on -- if the input doesn't exist within that array, then the REST API will throw an error
- **`sanitize_callback`** isn't necessary if you're restricting acceptable values via `enum`, but if you accept more values then it's extremely valuable, especially if you're updating a field.
- **resorce schema**: indicates what fields are present for a particular object. Once a schema is provided, you can make sure that each object follows that schema pattern

#### `rest_ensure_response`
This function wraps the data we want to return into a WP_REST_Response, and ensures it will be properly returned.

---

## Helpful References
- [REST API FAQ](https://developer.wordpress.org/rest-api/frequently-asked-questions/)
- [REST API Handbook](https://developer.wordpress.org/rest-api/)
- [WordPress API Reference](https://developer.wordpress.org/rest-api/reference/)
- [WP_REST_Request](https://developer.wordpress.org/reference/classes/wp_rest_request/)
- [WP_REST_Response](https://developer.wordpress.org/reference/classes/wp_rest_response/)
- [Routes and Endpoints](https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/)
- [JSON Schema Basics](https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#json-schema-basics)
- [JSON Primitive Types](https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#primitive-types)
- [Controller Classes](https://developer.wordpress.org/rest-api/extending-the-rest-api/controller-classes/)
- [WP_REST_Controller Methods](https://developer.wordpress.org/reference/classes/wp_rest_controller/#methods)
- [register_post_type](https://developer.wordpress.org/reference/functions/register_post_type/)
- [register_post_meta](https://developer.wordpress.org/reference/functions/register_post_meta/)
- [register_meta](https://developer.wordpress.org/reference/functions/register_meta/)
- [rest_prepare_post_type](https://developer.wordpress.org/reference/functions/register_post_type/)
- [register_rest_field](https://developer.wordpress.org/reference/functions/register_rest_field/)
- [Adding Custom Fields to API Responses](https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/#adding-custom-fields-to-api-responses)
- [register_rest_field vs register_meta](https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/#using-register_rest_field-vs-register_meta)
- [rest_post_type_query](https://developer.wordpress.org/reference/hooks/rest_this-post_type_query/)
- [rest_pre_dispatch](https://developer.wordpress.org/reference/hooks/rest_pre_dispatch/)
- [Manipulate Incoming WordPress REST API Requests](https://tommcfarlin.com/incoming-wordpress-rest-api-requests/)
- [rest_pre_echo_response](https://developer.wordpress.org/reference/hooks/rest_pre_echo_response/)
- [Intercept WordPress API Requests](https://thriftydeveloper.com/2020/08/20/intercept-wordpress-api-requests/)
- [List of WordPress Post Route Arguments](https://developer.wordpress.org/rest-api/reference/posts/#arguments)
- [register_rest_route](https://developer.wordpress.org/reference/functions/register_rest_route/)
- [Creating Endpoints](https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/#creating-endpoints)
- [rest_ensure_response](https://developer.wordpress.org/reference/functions/rest_ensure_response/)