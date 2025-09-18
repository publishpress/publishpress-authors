<?php
/**
 * PublishPress Authors commands for the WP-CLI framework
 *
 * @package     MultipleAuthors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 * @see     https://github.com/wp-cli/wp-cli
 */

namespace MultipleAuthors;

use MultipleAuthors\Classes\Installer;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use WP_CLI_Command;
use WP_Query;

class WP_Cli extends WP_CLI_Command
{
    public function _logCallback($message, $messageType = 'log')
    {
        if ('log' === $messageType) {
            \WP_CLI::line($message);
        } elseif ('error' === $messageType) {
            \WP_CLI::error($message);
        } elseif ('success' === $messageType) {
            \WP_CLI::success($message);
        }
    }

    /**
     * List all of the posts without assigned co-authors terms
     *
     * @since      3.0
     *
     * @subcommand list-posts-without-terms
     * @synopsis [--post_type=<ptype>] [--posts_per_page=<num>] [--paged=<page>] [--order=<order>] [--orederby=<orderby>]
     */
    public function list_posts_without_terms($args, $assocArgs)
    {
        $defaults   = [
            'post_type'      => 'post',
            'order'          => 'ASC',
            'orderby'        => 'ID',
            'posts_per_page' => 300,
            'paged'          => 1,
        ];
        $parsedArgs = wp_parse_args($assocArgs, $defaults);

        $posts = Installer::getPostsWithoutAuthorTerms($parsedArgs);

        if (count($posts) === 0) {
            \WP_CLI::success(__('No posts without author terms were found', 'publishpress-authors'));
            return;
        }

        foreach ($posts as $singlePost) {
            $postData = [
                $singlePost->ID,
                addslashes($singlePost->post_title),
                get_permalink($singlePost->ID),
                $singlePost->post_date,
            ];
            \WP_CLI::line('"' . implode('","', $postData) . '"');
        }
        \WP_CLI::success(
            sprintf(
                __('%d posts were found without author terms', 'publishpress-authors'),
                count($posts)
            )
        );
    }

    /**
     * Create author terms for all posts that don't have them
     *
     * @subcommand create-terms-for-posts
     * @synopsis [--post_type=<ptype>] [--posts_per_page=<num>] [--paged=<page>]
     */
    public function create_terms_for_posts($args, $assocArgs)
    {
        Installer::createAuthorTermsForPostsWithLegacyCoreAuthors($assocArgs, [$this, '_logCallback']);

        \WP_CLI::success('Finished');
    }

    /**
     * Clear all of the caches for memory management
     */
    private function clearAllCaches()
    {
        global $wpdb, $wp_object_cache;

        $wpdb->queries = []; // or define( 'WP_IMPORTING', true );

        if (!is_object($wp_object_cache)) {
            return;
        }

        $wp_object_cache->group_ops      = [];
        $wp_object_cache->stats          = [];
        $wp_object_cache->memcache_debug = [];
        $wp_object_cache->cache          = [];

        if (is_callable($wp_object_cache, '__remoteset')) {
            $wp_object_cache->__remoteset(); // important
        }
    }

    /**
     * Subcommand to assign coauthors to a post based on a given meta key
     *
     * @since      3.0
     *
     * @subcommand assign-author-by-meta-key
     * @synopsis [--meta_key=<key>] [--post_type=<ptype>] [--append_author=<bool>] [--posts_per_page=<num>] [--paged=<page>] [--post_status=<string>]
     */
    public function assign_author_by_meta_key($args, $assocArgs)
    {
        $defaults   = [
            'meta_key'         => '_original_import_author', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'post_type'        => 'post',
            'order'            => 'ASC',
            'orderby'          => 'ID',
            'posts_per_page'   => 100,
            'paged'            => 1,
            'append_author' => false,
        ];
        $this->args = wp_parse_args($assocArgs, $defaults);

        // For global use and not a part of WP_Query
        $appendAuthor = $this->args['append_author'];
        unset($this->args['append_author']);

        $postsTotal             = 0;
        $postsAlreadyAssociated = 0;
        $postsMissingCoauthor   = 0;
        $postsAssociated        = 0;
        $missionCoauthors       = [];

        $posts = new WP_Query($this->args);
        while ($posts->post_count) {
            foreach ($posts->posts as $singlePost) {
                $postsTotal++;

                // See if the value in the post meta field is the same as any of the existing coauthors
                $originalAuthor    = get_post_meta($singlePost->ID, $this->args['meta_key'], true);
                $existingCoauthors = get_post_authors($singlePost->ID);
                $isAlreadyAssociated = false;
                foreach ($existingCoauthors as $existingCoauthor) {
                    if ($originalAuthor == $existingCoauthor->user_nicename) {
                        $isAlreadyAssociated = true;
                    }
                }
                if ($isAlreadyAssociated && $appendAuthor) {
                    $postsAlreadyAssociated++;
                    \WP_CLI::line(
                        $postsTotal . ': Post #' . $singlePost->ID . ' already has "' . $originalAuthor . '" associated as a coauthor'
                    );
                    continue;
                }

                $coauthor = Author::get_by_term_slug(sanitize_title($originalAuthor));
                if (false === $coauthor || is_wp_error($coauthor)) {
                    $postsMissingCoauthor++;
                    $missionCoauthors[] = $originalAuthor;
                    \WP_CLI::line(
                        $postsTotal . ': Post #' . $singlePost->ID . ' does not have "' . $originalAuthor . '" associated as a coauthor but there is not a coauthor profile'
                    );
                    continue;
                }


                if ($appendAuthor) {
                    $coauthors = $existingCoauthors;
                    $coauthors[] = $coauthor;
                } else {
                    $coauthors = [$coauthor];
                }

                Utils::set_post_authors($singlePost->ID, $coauthors, true);

                \WP_CLI::line(
                    $postsTotal . ': Post #' . $singlePost->ID . ' has been assigned "' . $originalAuthor . '" as the author'
                );
                $postsAssociated++;
                clean_post_cache($singlePost->ID);
            }

            $this->args['paged']++;
            $this->clearAllCaches();
            $posts = new WP_Query($this->args);
        }

        \WP_CLI::line('All done! Here are your results:');
        if ($postsAlreadyAssociated) {
            \WP_CLI::line("- {$postsAlreadyAssociated} posts already had the coauthor assigned");
        }
        if ($postsMissingCoauthor) {
            \WP_CLI::line("- {$postsMissingCoauthor} posts reference coauthors that don't exist. These are:");
            \WP_CLI::line('  ' . implode(', ', array_unique($missionCoauthors)));
        }
        if ($postsAssociated) {
            \WP_CLI::line("- {$postsAssociated} posts now have the proper coauthor");
        }
    }
}
