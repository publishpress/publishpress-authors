<?php
/**
 * The template for author taxonomy
 *
 * This template can be overridden by copying this file in '/publishpress-authors/templates/'
 * of your root theme or child theme's directory. E.g:
 * /publishpress-authors/templates/author-pages-list.php to your theme or child theme's directory
 * and customize.
 *
 * @package PublishPress
 */

// Do not allow directly accessing this file.
if (!defined('ABSPATH')) {
    exit('Direct script access denied.');
}

use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;

$legacyPlugin              = Factory::getLegacyPlugin();
$current_author_term_id    = get_queried_object_id();
$current_author_data       = Author::get_by_term_id($current_author_term_id);

$author_pages_bio_layout   = $legacyPlugin->modules->multiple_authors->options->author_pages_bio_layout;
$show_author_pages_bio     = $legacyPlugin->modules->multiple_authors->options->show_author_pages_bio === 'yes';
$show_post_featured_image  = $legacyPlugin->modules->multiple_authors->options->show_author_post_featured_image === 'yes';
$link_post_featured_image  = isset($legacyPlugin->modules->multiple_authors->options->link_author_post_featured_image)
    && $legacyPlugin->modules->multiple_authors->options->link_author_post_featured_image === 'yes';
$show_post_excerpt         = $legacyPlugin->modules->multiple_authors->options->show_author_post_excerpt === 'yes';
$show_post_authors         = $legacyPlugin->modules->multiple_authors->options->show_author_post_authors === 'yes';
$show_post_date            = $legacyPlugin->modules->multiple_authors->options->show_author_post_date === 'yes';
$show_post_comments        = $legacyPlugin->modules->multiple_authors->options->show_author_post_comments === 'yes';
$show_post_category        = $legacyPlugin->modules->multiple_authors->options->show_author_post_category === 'yes';
$show_post_tags            = $legacyPlugin->modules->multiple_authors->options->show_author_post_tags === 'yes';
$show_post_readmore        = $legacyPlugin->modules->multiple_authors->options->show_author_post_readmore === 'yes';
$show_author_page_title    = $legacyPlugin->modules->multiple_authors->options->show_author_page_title === 'yes';
$author_pages_title_header = $legacyPlugin->modules->multiple_authors->options->author_pages_title_header;
$author_post_title_header  = $legacyPlugin->modules->multiple_authors->options->author_post_title_header;
$author_post_custom_width  = (int) $legacyPlugin->modules->multiple_authors->options->author_post_custom_width;
$author_post_custom_height = (int) $legacyPlugin->modules->multiple_authors->options->author_post_custom_height;

$extra_post_class          = 'ppma-article';
$extra_post_class          .= ($show_post_featured_image) ? ' has-featured-image' : ' no-featured-image';

$featured_image_style      = '';
if ($author_post_custom_width > 0) {
    $featured_image_style  .= 'width: '.$author_post_custom_width.'px;';
}
if ($author_post_custom_height > 0) {
    $featured_image_style  .= 'height: '.$author_post_custom_height.'px;max-height: '.$author_post_custom_height.'px;';
}
?>
<div class="ppma-author-pages site-main alignwide has-global-padding">
    <div class="ppma-page-header">
        <?php
        if ($show_author_page_title) {
            the_archive_title('<'.$author_pages_title_header.' class="ppma-page-title page-title">', '</'.$author_pages_title_header.'>');
        } ?>
        <?php if ($show_author_pages_bio) : ?>
            <div class="ppma-author-pages-author-box-wrap">
               <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo do_shortcode('[publishpress_authors_box archive="1" show_title="false" layout="'. $author_pages_bio_layout .'"]');
               ?>
            </div>
        <?php endif; ?>
    </div><!-- .page-header -->

    <div class="ppma-page-content list">
        <?php if (have_posts()) : ?>
            <?php while ( have_posts() ) : the_post(); ?>
                    <?php
                    $featured_image = '';
                    $featured_image_alt = '';
                    if (has_post_thumbnail()) {
                        $image_data = wp_get_attachment_image_src(get_post_thumbnail_id(), 'single-post-thumbnail');
                        if (!empty($image_data) && is_array($image_data)) {
                            $featured_image = $image_data[0];
                            $featured_image_id = get_post_thumbnail_id();
                            $featured_image_alt = get_post_meta($featured_image_id, '_wp_attachment_image_alt', true);
                        }
                    }
                    $post_categories  = ($show_post_category) ? get_the_category_list(', ') : false;
                    $post_tags        = ($show_post_tags) ? get_the_tags() : [];
                    $post_authors     = ($show_post_authors) ? get_post_authors() : [];
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class($extra_post_class); ?>>
                        <div class="article-content">
                            <div class="article-image">
                                <?php if ($show_post_featured_image) : ?>
                                    <?php if ($link_post_featured_image) : ?>
                                        <a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php echo esc_attr(get_the_title()); ?>">
                                    <?php endif; ?>
                                    <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($featured_image_alt); ?>" style="<?php echo esc_attr($featured_image_style); ?>">
                                    <?php if ($link_post_featured_image) : ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <div class="article-body">
                                <header class="article-header">
                                    <?php if ($show_post_category && $post_categories) : ?>
                                        <span class="category-link cat-links">
                                          <?php echo $post_categories; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php echo '<'.$author_post_title_header.' class="article-title entry-title title">'; ?>
                                        <a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php echo esc_attr(get_the_title()); ?>"><?php the_title(); ?></a>
                                    <?php echo '</'.$author_post_title_header.'>'; ?>
                                    <div class="article-meta post-meta meta">
                                        <?php if ($show_post_authors && !empty($post_authors)) : ?>
                                                <span class="article-meta-item">by <span class="author vcard">
                                                    <?php foreach ($post_authors as $index => $post_author) : $index++; ?>
                                                        <?php $term_link = get_term_link($post_author->term_id); ?>
                                                        <a href="<?php echo ($term_link) ? esc_url($term_link) : ''; ?>"
                                                        title="<?php echo esc_attr($post_author->display_name); ?>">
                                                            <?php echo esc_html($post_author->display_name); ?><?php
                                                            if (count($post_authors) !== $index) {
                                                                echo ', ';
                                                            }
                                                            ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </span></span>
                                        <?php endif; ?>
                                        <?php if ($show_post_date ) : ?>
                                            <span class="article-meta-item entry-meta-item post-meta-item post-meta meta">
                                                <span class="dashicons dashicons-clock"></span>
                                                <a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php echo esc_html__('Published date', 'publishpress-authors'); ?>">
                                                    <time class="article-date published" datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date()); ?></time>
                                                </a>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($show_post_comments ) : ?>
                                        <span class="article-meta-item entry-meta-item post-meta-item post-meta meta">
                                            <a href="<?php echo esc_url(the_permalink() . '#comments'); ?>" title="<?php echo esc_html__('Comment counts', 'publishpress-authors'); ?>">
                                                <span class="dashicons dashicons-admin-comments"></span><?php echo esc_html(get_comments_number()); ?>
                                            </a>
                                        </span>
                                        <?php endif; ?>
                                    </div><!-- .article-meta -->
                                </header><!-- .article-header -->
                                <?php if ($show_post_excerpt ) : ?>
                                    <div class="article-entry-excerpt post-entry-excerpt entry-excerpt excerpt">
                                        <?php Utils::ppma_article_excerpt(160, 'excerpt', true, $show_post_readmore ); ?>
                                    </div>
                                <?php endif; ?>
                                <footer class="article-footer entry-footer post-footer">
                                    <?php if ($post_tags && !empty($post_tags)) : ?>
                                            <span class="tags-links">
                                                <?php foreach($post_tags as $post_tag) : ?>
                                                    <a href="<?php echo esc_url(get_tag_link($post_tag->term_id)); ?>" rel="tag" title="<?php echo esc_attr($post_tag->name); ?>">
                                                        <?php echo esc_html($post_tag->name); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </span>
                                    <?php endif; ?>
                                </footer><!-- .entry-footer -->
                            </div>
                        </div>
                    </article>
            <?php endwhile; ?>

            <div class="ppma-article-pagination">
                <?php the_posts_pagination(
                    [
                        'mid_size'  => 2,
                        'prev_text' => esc_html__('Prev', 'publishpress-authors'),
                        'next_text' => esc_html__('Next', 'publishpress-authors'),
                    ]
                );
                ?>
            </div>
        <?php else : ?>
            <h2><?php esc_html_e('Post not found for the author', 'publishpress-authors'); ?></h2>
        <?php endif; ?>
    </div> <!-- #main-content -->
</div>
