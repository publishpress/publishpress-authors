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

use MultipleAuthors\Classes\Utils;
use PPAuthors\YoastSEO\YoastAuthor;
use Yoast\WP\SEO\Context\Meta_Tags_Context;
use MultipleAuthors\Classes\Objects\Author as PPAuthor;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;
use WP_User;

use function wp_hash;

class SchemaFacade
{
    public function addSupportForMultipleAuthors()
    {
        add_filter('wpseo_schema_graph', [$this, 'filter_graph' ], 11, 2);
        add_filter('wpseo_schema_graph', [$this, 'filter_author_term_graph' ], 11, 2);
        add_filter('wpseo_schema_author', [$this, 'filter_author_graph' ], 11, 4);
        add_filter('wpseo_meta_author', [$this, 'filter_author_meta' ], 11, 2);
        add_filter('wpseo_opengraph_title', [$this, 'handleAuthorWpseoTitle']);
        add_filter('wpseo_title', [$this, 'handleAuthorWpseoTitle']);
        add_filter('wpseo_enhanced_slack_data', [$this, 'filterSlackData' ], 10, 2);
    }

    /**
     * Filters the graph output to add authors.
     *
     * @param array                   $data                   The schema graph.
     * @param Meta_Tags_Context       $context                The context object.
     * @param Abstract_Schema_Piece   $graph_piece_generator  The graph piece generator.
     * @param Abstract_Schema_Piece[] $graph_piece_generators The graph piece generators.
     *
     * @return array The (potentially altered) schema graph.
     */
    public function filter_author_graph($data, $context, $graph_piece_generator, $graph_piece_generators)
    {
        if (! isset($data['image']['url'])) {
            return $data;
        }

        if (isset($data['image']['@id'])) {
            $data['image']['@id'] .= md5($data['image']['url']);
        }

        if (isset($data['logo']['@id'])) {
            $data['logo']['@id'] .= md5($data['image']['url']);
        }

        return $data;
    }

    /**
     * Filters author term graph output.
     *
     * @param array             $data    The schema graph.
     * @param Meta_Tags_Context $context Context object.
     *
     * @return array The (potentially altered) schema graph.
     */
    public function filter_author_term_graph($data, $context)
    {
        if (! is_tax('author')) {
            return $data;
        }

        $author    = PPAuthor::get_by_term_id($context->indexable->object_id);

        $author_generator          = new YoastAuthor();
        $author_generator->context = $context;
        $author_generator->helpers = YoastSEO()->helpers;

        if ($author->ID > 0) {
            $author_data = $author_generator->generate_from_user_id($author->ID);
        } else {
            $author_data = $author_generator->generate_from_guest_author($author);
        }

        if (!is_array($author_data) || !isset($author_data['url'])) {
            return $data;
        }

        if (! empty($author_data)) {
            if (isset($author_data['image']['caption'])) {
                $author_data['image']['caption']   = $author->display_name;
            }
            if (isset($author_data['name'])) {
                $author_data['name']   = $author->display_name;
            }
            $author_data['mainEntityOfPage'] = ['@id' => $author_data['url']];

            $data[] = $author_data;
        }

        if (! empty($author_data)) {
            foreach ($data as $key => $piece) {
                if ($piece['@type'] === 'CollectionPage') {
                    $data[$key]['@type'] = 'ProfilePage';
                    $data[$key]['potentialAction'][] = [
                        '@type' => 'ReadAction',
                        'target' => [$author_data['url']]
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Filters the graph output to add authors.
     *
     * @param array             $data    The schema graph.
     * @param Meta_Tags_Context $context Context object.
     *
     * @return array The (potentially altered) schema graph.
     */
    public function filter_graph($data, $context)
    {
        if (!is_singular(Utils::get_enabled_post_types()) || !$context->post) {
            return $data;
        }

        if (!function_exists('publishpress_authors_get_post_authors')) {
            require_once PP_AUTHORS_BASE_PATH . 'functions/template-tags.php';
        }

        $author_objects = get_post_authors($context->post->ID, false, false);

        $ids     = [];
        $authors_schema_data = [];
        $existing_person_ids = [];

        // Collect existing Person schema IDs to avoid duplicates
        foreach ($data as $piece) {
            if (isset($piece['@type']) && $piece['@type'] === 'Person' && isset($piece['@id'])) {
                $existing_person_ids[] = $piece['@id'];
            }
        }

        // Add the authors to the schema.
        foreach ($author_objects as $author) {
            if (is_object($author) && isset($author->ID)) {
                $author_generator          = new YoastAuthor();
                $author_generator->context = $context;
                $author_generator->helpers = YoastSEO()->helpers;

                if ($author->ID > 0) {
                    $author_data = $author_generator->generate_from_user_id($author->ID);
                } else {
                    $author_data = $author_generator->generate_from_guest_author($author);
                }

                if (! empty($author_data)) {
                    if (isset($author_data['image']['caption'])) {
                        $author_data['image']['caption']   = $author->display_name;
                    }
                    if (isset($author_data['name'])) {
                        $author_data['name']   = $author->display_name;
                    }

                    $ids[]     = [ '@id' => $author_data['@id'] ];

                    // Only add to graph if not already present
                    if (!in_array($author_data['@id'], $existing_person_ids)) {
                        $data[] = $author_data;
                        $existing_person_ids[] = $author_data['@id'];
                    }

                    $authors_schema_data[$author->user_email] = $author_data;
                }
            }
        }

        // Set authors based on count
        $authors = null;
        if (count($author_objects) === 1) {
            $authors = $ids[0];
        } elseif (count($author_objects) > 1) {
            $authors = $ids;
        }

        foreach ($data as $key => $piece) {
            if (!is_array($piece)) {
                continue;
            }

            // Add author property to Article and WebPage schema types
            if (isset($piece['@type']) && !is_array($piece['@type'])) {
                if (in_array($piece['@type'], ['Article', 'WebPage']) && !empty($authors)) {
                    $data[$key]['author'] = $authors;
                }

                // add author category schema property
                if ($piece['@type'] === 'WebPage') {
                    $author_categorized = false;
                    foreach (get_ppma_author_categories() as $author_category) {
                        if (!empty($author_category['schema_property'])) {
                            if (!$author_categorized) {
                                $author_categorized = ppma_post_authors_categorized();
                            }
                            if (isset($author_categorized[$author_category['slug']])) {
                                $category_schema = [];
                                foreach ($author_categorized[$author_category['slug']] as $author_categorized_author) {
                                    if (isset($authors_schema_data[$author_categorized_author->user_email])) {
                                        $category_schema[] = $authors_schema_data[$author_categorized_author->user_email];
                                    }
                                }
                                if (count($category_schema) > 0) {
                                    $data[$key][$author_category['schema_property']] = count($category_schema) > 1 ? $category_schema : $category_schema[0];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Filters the author meta tag
     *
     * @param string                 $author_name  The article author's display name. Return empty to disable the tag.
     * @param Indexable_Presentation $presentation The presentation of an indexable.
     * @return string
     */
    public function filter_author_meta($author_name, $presentation)
    {

        if (!function_exists('publishpress_authors_get_post_authors')) {
            require_once PP_AUTHORS_BASE_PATH . 'functions/template-tags.php';
        }

        $author_objects = get_post_authors($presentation->context->post->id, false, false);

        // Fallback in case of error.
        if (empty($author_objects)) {
            return $author_name;
        }

        $output = '';
        foreach ($author_objects as $i => $author) {
            $output .= $author->display_name;
            if ($i <= (count($author_objects) - 2)) {
                $output .= ', ';
            }
        }
        return $output;
    }

    /**
     * Replace author name for yoast SEO
     *
     * @param string $title current page title
     *
     * @return string
     */
    public function handleAuthorWpseoTitle($title)
    {
        if (is_author()) {
            $titleAuthorName = get_the_author();
            $realAuthorData  = get_queried_object();
            if (is_object($realAuthorData) && !is_wp_error($realAuthorData) && isset($realAuthorData->display_name)) {
                $title = str_replace($titleAuthorName, $realAuthorData->display_name, $title);
            }
        }

        return $title;
    }

    /**
    * Change Enhanced Slack sharing data labels.
    *
    * @param array                  $data         The Slack labels + data.
    * @param $presentation The indexable presentation object.
    *
    * @return array The Slack labels + data.
    */
   public function filterSlackData( array $data, $presentation ) {

        if (!is_singular(Utils::get_enabled_post_types()) || empty($presentation->model->object_type)) {
            return $data;
        }

        if (!function_exists('publishpress_authors_get_post_authors')) {
            require_once PP_AUTHORS_BASE_PATH . 'functions/template-tags.php';
        }

        if (array_key_exists(\__( 'Written by', 'wordpress-seo' ), $data)) {
            $author_objects = get_post_authors($presentation->model->object_id, false, false);
            $author_names = [];
            foreach ($author_objects as $author) {
                if (is_object($author) && isset($author->display_name)) {
                    $author_names[] = $author->display_name;
                }
            }

            $data[\__( 'Written by', 'wordpress-seo' )] = join(', ', $author_names);
        }

       return $data;
   }
}
