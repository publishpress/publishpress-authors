<?php
/**
 * @package     MultipleAuthors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.1.0
 */

namespace MultipleAuthors\Classes;

use MultipleAuthors\Classes\Objects\Author;
use WP_CLI;

/**
 * Manage authors.
 *
 * Based on Bylines.
 *
 * @package MultipleAuthors\Classes
 * @deprecated Since 3.13.1, to be removed on 3.15.0
 */
class CLI
{

    /**
     * Convert co-authors to authors.
     *
     * Generates a author term for each co-author (if one doesn't exist) and
     * assigns the author terms to the post.
     *
     * ## OPTIONS
     *
     * <post-id>...
     * : One or more post ids to process.
     *
     * @subcommand convert-coauthor
     */
    public function convert_coauthor($args, $assoc_args)
    {
        if (empty($GLOBALS['coauthors_plus'])) {
            WP_CLI::error('Co-Authors Plus must be installed and active.');
        }

        $successes = 0;
        $failures  = 0;
        $total     = count($args);
        foreach ($args as $i => $post_id) {
            if ($i && 0 === $i % 500) {
                WP_CLI\Utils\wp_clear_object_cache();
            }
            $result = Utils::convert_post_coauthors($post_id);
            if (is_wp_error($result)) {
                $failures++;
                WP_CLI::warning($result->get_error_message());
                continue;
            }

            $message = [];
            if ($result->created) {
                $part = "created {$result->created} author";
                if ($result->created > 1) {
                    $part .= 's';
                }
                $message[] = $part;
            }
            if ($result->existing) {
                $part = "found {$result->existing} existing author";
                if ($result->existing > 1) {
                    $part .= 's';
                }
                $message[] = $part;
            }
            $message = ucfirst(implode(', ', $message));
            WP_CLI::log("{$message} and assigned to post {$post_id}.");
            $successes++;
        } // End foreach().

        WP_CLI\Utils\report_batch_operation_results('co-author post', 'convert', $total, $successes, $failures);
    }

    /**
     * Convert post authors to authors.
     *
     * Generates a author term for the post author (if one doesn't already exist)
     * and assigns the term to the post.
     *
     * ## OPTIONS
     *
     * <post-id>...
     * : One or more post ids to process.
     *
     * @subcommand convert-post-author
     */
    public function convert_post_author($args, $assoc_args)
    {
        $successes = 0;
        $failures  = 0;
        $total     = count($args);
        foreach ($args as $i => $post_id) {
            if ($i && 0 === $i % 500) {
                WP_CLI\Utils\wp_clear_object_cache();
            }
            $post = get_post($post_id);
            if (!$post) {
                WP_CLI::warning("Invalid post: {$post_id}");
                $failures++;
                continue;
            }
            $authors = get_the_terms($post_id, 'author');
            if ($authors && !is_wp_error($authors)) {
                WP_CLI::warning("Post {$post_id} already has authors.");
                $failures++;
                continue;
            }

            if (!$post->post_author) {
                WP_CLI::warning("Post {$post_id} doesn't have an author.");
                $failures++;
                continue;
            }

            $author = Author::get_by_user_id($post->post_author);
            if ($author) {
                Utils::set_post_authors($post_id, [$author], true);
                WP_CLI::log("Found existing author and assigned to post {$post_id}.");
            } else {
                $author = Author::create_from_user((int)$post->post_author);
                if (is_wp_error($author)) {
                    WP_CLI::warning($author->get_error_message());
                    $failures++;
                    continue;
                }
                Utils::set_post_authors($post_id, [$author], true);
                WP_CLI::log("Created author and assigned to post {$post_id}.");
            }

            $successes++;
        } // End foreach().

        do_action('publishpress_authors_flush_cache_for_post', $args);

        WP_CLI\Utils\report_batch_operation_results('post author', 'convert', $total, $successes, $failures);
    }

}
