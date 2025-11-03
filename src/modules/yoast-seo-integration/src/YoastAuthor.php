<?php
/**
 * @package PublishPress Authors
 * @author  PublishPress
 *
 * Copyright (C) 2018 PublishPress
 *
 * This file is part of PublishPress Authors
 *
 * PublishPress Authors is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace PPAuthors\YoastSEO;

use Yoast\WP\SEO\Config\Schema_IDs;
use Yoast\WP\SEO\Generators\Schema\Author;
use MultipleAuthors\Classes\Objects\Author as PPAuthor;
use MA_Author_Custom_Fields as PPAuthorFields;

class YoastAuthor extends Author
{

    /**
     * The user ID of the author we're generating data for.
     *
     * @var int $user_id
     */
    private $user_id;

    /**
     * Determine whether we should return Person schema.
     *
     * @return bool
     */
    public function is_needed()
    {
        return true;
    }

    /**
     * Returns Person Schema data.
     *
     * @return bool|array Person data on success, false on failure.
     */
    public function generate()
    {
        $user_id = $this->determine_user_id();
        if (! $user_id) {
            return false;
        }
        $author_data    = PPAuthor::get_by_user_id($user_id);
        $data           = $this->build_person_data($user_id, true);
        $data['name']   = $author_data->display_name;
        if (isset($data['image']['caption'])) {
            $data['image']['caption']   = $author_data->display_name;
        }
        $data['name']   = $author_data->display_name;

        $data['@type'] = 'Person';
        unset($data['logo']);

        // If this is a post and the author archives are enabled, set the author archive url as the author url.
        if ($this->helpers->options->get('disable-author') !== true) {
            $data['url'] = $this->helpers->user->get_the_author_posts_url($user_id);
        }

        $data = $this->add_author_schema_property($data, $author_data);

        return $data;
    }

    /**
     * Generate the Person data given a user ID.
     *
     * @param int $user_id User ID.
     *
     * @return array|bool
     */
    public function generate_from_user_id($user_id)
    {
        $this->user_id = $user_id;

        return $this->generate();
    }

    /**
     * Generate the Person data given a Guest Author object.
     *
     * @param object $guest_author The Guest Author object.
     *
     * @return array|bool
     */
    public function generate_from_guest_author($guest_author)
    {
        $data = $this->build_person_data_for_guest_author($guest_author, true);

        $data['@type'] = 'Person';
        unset($data['logo']);

        // If this is a post and the author archives are enabled, set the author archive url as the author url.
        if ($this->helpers->options->get('disable-author') !== true) {
            $data['url'] = \get_author_posts_url($guest_author->ID, $guest_author->user_nicename);
        }

        return $data;
    }

    /**
     * Determines a User ID for the Person data.
     *
     * @return bool|int User ID or false upon return.
     */
    protected function determine_user_id()
    {
        return $this->user_id;
    }

    /**
     * Builds our array of Schema Person data for a given Guest Author.
     *
     * @param object $guest_author The Guest Author object.
     * @param bool   $add_hash Wether or not the person's image url hash should be added to the image id.
     *
     * @return array An array of Schema Person data.
     */
    protected function build_person_data_for_guest_author($guest_author, $add_hash = false)
    {
        if (!is_object($guest_author)) {
            return [];
        }
        $schema_id = $this->context->site_url . Schema_IDs::PERSON_HASH;
        $data      = [
            '@type' => $this->type,
            '@id'   => $schema_id . \wp_hash($guest_author->term_id . $guest_author->ID . 'guest'),
        ];

        $data['name'] = $this->helpers->schema->html->smart_strip_tags($guest_author->display_name);

        $data = $this->set_image_from_avatar($data, $guest_author, $schema_id, $add_hash);

        $author_avatar = $guest_author->get_avatar_url();
        if (is_array($author_avatar)) {
            $author_avatar = $author_avatar['url'];
        }
        //overwrite with custom avatar
        $avatar_meta   = [
            'url'    => $author_avatar,
            'width'  => '',
            'height' => '',
        ];
        $data['image'] = $this->helpers->schema->image->generate_from_attachment_meta($schema_id, $avatar_meta, $data['name'], $add_hash);

        if (! empty($guest_author->description)) {
            $data['description'] = $this->helpers->schema->html->smart_strip_tags($guest_author->description);
        }

        $data = $this->add_author_schema_property($data, $guest_author);

        return $data;
    }

    /**
     * Builds our SameAs array.
     *
     * @param array   $data         The Person schema data.
     * @param WP_User $author The user data object.
     *
     * @return array The Person schema data.
     */
    protected function add_author_schema_property($data, $author)
    {

        $author_fields = get_posts(
            [
                'post_type' => PPAuthorFields::POST_TYPE_CUSTOM_FIELDS,
                'posts_per_page' => 100,
                'post_status' => 'publish',
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'relation' => 'AND',
                        [
                            'key' => 'ppmacf_social_profile',
                            'value' => 1,
                            'type' => 'NUMERIC',
                            'compare' => '='
                        ],
                        [
                            'key' => 'ppmacf_type',
                            'value' => 'url',
                            'compare' => '='
                        ]
                    ],
                    [
                        'key' => 'ppmacf_schema_property',
                        'value' => '',
                        'compare' => '!='
                    ]
                ],
            ]
        );

        $same_as_urls = [];

        if (! empty($author->user_url)) {
            $same_as_urls[] = $author->user_url;
        }

        if (!empty($author_fields)) {
            foreach ($author_fields as $author_field) {
                $field_value = isset($author->{$author_field->post_name}) ? $author->{$author_field->post_name} : '';
                if (! empty(trim($field_value))) {
                    $ppmacf_social_profile = get_post_meta($author_field->ID, 'ppmacf_social_profile', true);
                    $ppmacf_type = get_post_meta($author_field->ID, 'ppmacf_type', true);
                    $ppmacf_schema_property = get_post_meta($author_field->ID, 'ppmacf_schema_property', true);

                    if ($ppmacf_type == 'url' && (int) $ppmacf_social_profile === 1) {
                        $same_as_urls[] = $field_value;
                    }

                    if (!empty($ppmacf_schema_property)) {
                        if (isset($data[$ppmacf_schema_property])) {
                            $schema_property_value = \array_values(\array_unique((array)$data[$ppmacf_schema_property]));
                            $schema_property_value[] = strip_tags($field_value);
                            $data[$ppmacf_schema_property] = $schema_property_value;
                        } else {
                            $data[$ppmacf_schema_property] = strip_tags($field_value);
                        }

                    }
                }
            }
        }

        // When CAP adds it, add the social profiles here.
        if (! empty($same_as_urls)) {
            $same_as_urls   = \array_values(\array_unique($same_as_urls));
            $data['sameAs'] = $same_as_urls;
        }

        return $data;
    }
}
