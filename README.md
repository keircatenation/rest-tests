# More than an Extension: Customizing the WordPress REST API
Keiran Pillman
Webmaster, Memorial Art Gallery of the University of Rochester
Digital Collegium Pennsylvania Regional Conference, June 2025

## Basics of WordPress's REST API

### A little background

### What is it used for?

### What can you use it for?

### Terminology
- A **route** is
- An **endpoint** is
- A **namespace** is

### How do you access it?
WordPress’s default routes are found at /wp-json/wp/v2/*, and you can see all routes at /wp-json/.

Some of the default routes include:
- [/wp-json/wp/v2/posts](https://developer.wordpress.org/rest-api/reference/posts/)
- [/wp-json/wp/v2/users](https://developer.wordpress.org/rest-api/reference/users/)
- - I've found the default behavior is just listing users who have published posts, not all users
- [/wp-json/wp/v2/media](https://developer.wordpress.org/rest-api/reference/media/)
- [/wp-json/wp/v2/taxonomies](https://developer.wordpress.org/rest-api/reference/taxonomies/)

A full list of endpoints is found at [https://developer.wordpress.org/rest-api/reference/](https://developer.wordpress.org/rest-api/reference/).

## Modifying Default Responses
**WARNING**: You can mess up the block editor (or other plugins) if you remove or change core fields in the response objects. No matter what you want from the API response, it’s best to add an entirely new field instead of changing a field that already exists.

### Modifying the data that's returned
Contained here are a selection of functions and hooks that modify data in WordPress’s default REST API routes.

#### Function: `register_post_type`
- Used to create custom post types
- `show_in_rest`: must be set to true
- If true, then a default endpoint is created at `/wp-json/wp/v2/{$this->post_type}`
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
