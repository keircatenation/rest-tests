# More than an Extension: Customizing the WordPress REST API
Keiran Pillman
Webmaster, Memorial Art Gallery of the University of Rochester
Digital Collegium Pennsylvania Regional Conference, June 2025

## Basics of WordPress's REST API

### A Little Background

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