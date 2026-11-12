---
title: "The PublishPress Authors API"
order: 64
doc_category: publishpress-authors
---

# The PublishPress Authors API

Table of Contents

- [The function get_multiple_authors](#0-toc-title)
- [The Author class](#1-toc-title)

This guide will show you how to use the API in the [PublishPress Authors](https://publishpress.com/links/authors) plugin. This API can help you build integrations with [PublishPress Authors](https://publishpress.com/links/authors).

## The function get_multiple_authors [#](#0-toc-title)

Here is the function signature for the *get_multiple_authors*:

```
/**
 * Get all authors for a post.
 *
 * @param WP_Post|int|null $post              Post to fetch authors for. Defaults to global post.
 * @param bool             $filter_the_author If false, will not trigger the filter for the author, to avoid infinite
 *                                            loop.
 * @param bool             $archive           If true, will ignore the $post param and return the current author
 *                                            specified by the "author_name" URL param - for author pages. 
 * @param bool             $ignoreCache       This cache cause sometimes errors in data received especially
 *                                            in quick edit after saving.
 *                                            That's why in Post_Editor we called this function with overriding
 *                                            ignoreCache value to be equal true.
 *
 * @return array Array of Author objects, a single WP_User object, or empty.
 */
function get_multiple_authors($post = 0, $filter_the_author = true, $archive = false, $ignoreCache = false);
```

The function returns an array of instances of the *MultipleAuthors\Classes\Objects\Author* class, described below.

## The Author class [#](#1-toc-title)

Here is the representation of the Author class:

```
namespace MultipleAuthors\Classes\Objects;

/**
 * Representation of an individual author.
 *
 * @property int $ID
 * @property string $slug
 * @property string $nickname
 * @property string $description
 * @property string $user_description
 * @property string $first_name
 * @property string $user_firstname
 * @property string $last_name
 * @property string $user_lastname
 * @property string $user_nicename
 * @property string $user_email
 * @property string $user_url
 * @property string $display_name
 * @property string $link
 * @property string $user_id
 * @property int $term_id
 */
class Author
{
    /**
     * Create a new author object from an existing WordPress user.
     *
     * @param WP_User|int $user WordPress user to clone.
     *
     * @return Author|WP_Error
     */
    public static function create_from_user($user) {}

    /**
     * Get a author object based on its user id.
     *
     * @param int $user_id ID for the author's user.
     *
     * @return Author|false
     */
    public static function get_by_user_id($user_id) {}

    /**
     * Create a new author object
     *
     * @param array $args Arguments with which to create the new object.
     *
     * @return Author|false
     */
    public static function create($args) {}

    /**
     * Update the author's data based on the user's data.
     *
     * @param $term_id
     * @param $user_id
     */
    public static function update_author_from_user($term_id, $user_id) {}

    /**
     * Remove the link between the author and user. Convert into a guest author.
     *
     * @param $term_id
     */
    public static function convert_into_guest_author($term_id) {}

    /**
     * Get a author object based on its term id.
     *
     * @param int $term_id ID for the author term.
     *
     * @return Author|false
     */
    public static function get_by_term_id($term_id) {}

    /**
     * Get a author object based on its term slug.
     *
     * @param string $slug Slug for the author term.
     *
     * @return Author|false
     */
    public static function get_by_term_slug($slug) {}

    /**
     * Return the URL for the avatar image.
     *
     * @param int $size
     *
     * @return string|array
     */
    public function get_avatar_url($size = 96) {}

    /**
     * Get a metadata for the author term. If not found and is mapped to a user, returns the user's meta.
     *
     * @param string $key
     * @param bool $single
     *
     * @return mixed
     */
    public function get_meta($key, $single = true) {}

    /**
     * Returns true if the author has a custom avatar image.
     *
     * @return bool
     */
    public function has_custom_avatar() {}

    /**
     * For guest authors, returns the custom avatar, if set. If not set, the WordPress' default profile picture.
     * For user mapped authors, returns the custom avatar if set. If not set, returns the user's avatar.
     *
     * @param int $size
     *
     * @return string
     */
    public function get_avatar($size = 96) {}

    /**
     * Return the user object of an author mapped to a user.
     *
     * @return bool|WP_User
     */
    public function get_user_object() {}

    /**
     * Returns true if the author is a guest author. Guest authors are authors that are not
     * mapped to a site user.
     *
     * @return bool
     */
    public function is_guest() {}

    /**
     * Get an author searching it by the email address. This function can cause performance issues
     * if called too many times on the same request.
     *
     * @param $emailAddress
     *
     * @return false|mixed|Author
     */
    public static function get_by_email($emailAddress) {}
}
```

The *Author* class is compatible with the *WP_User* class.

Guest authors use the same class, but will always have the property *$user_id* equals to 0.
