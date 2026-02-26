<div class="pp-multiple-authors-wrapper pp-multiple-authors-recent alignwide <?php echo esc_attr($context['css_class']); ?> pp-multiple-authors-layout-<?php echo esc_attr($context['layout']); ?>">
    <?php if (!empty($context['search_box_html'])) : ?>
        <?php echo $context['search_box_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php endif; ?>
    <div class="ppma-row">
        <?php foreach ($context['results'] as $index => $result) :
            $author = $result['author'];
            ?>
            <div class="author_index_<?php echo esc_attr($index); ?> author_<?php echo esc_attr($author->slug); ?> ppma-author-entry ppma-col-md-3 ppma-col-sm-4 ppma-col-12">
                <div class="name-row"><a href="<?php echo esc_url($author->link); ?>" class="<?php echo esc_attr($context['item_class']); ?>" rel="author" title="<?php echo esc_attr($author->display_name); ?>">
                    <h4><?php echo $author->display_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h4>
                    </a>
                    <a href="<?php echo esc_url($author->link); ?>" title="<?php echo esc_attr($author->display_name); ?>">
                    <?php if ($author->get_avatar()) : ?>
                        <?php echo $author->get_avatar(107);  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php else : ?>
                        <?php echo get_avatar($author->user_email, 107); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php endif; ?>
                    </a>
                </div>
                <div class="ppma-row-article-block main-block">
                    <div class="ppma-row">
                        <?php if ($result['recent_posts']) : ?>
                            <?php foreach ($result['recent_posts'] as $post_id => $post) : ?>
                                <?php if ($post['featuired_image']) : ?>
                                    <div class="ppma-col-5 featured-image-col post-<?php echo esc_attr($post_id); ?>">
                                        <a href="<?php echo esc_url($post['permalink']); ?>">
                                            <img src="<?php echo esc_url($post['featuired_image']); ?>">
                                        </a>
                                    </div>

                                    <div class="ppma-col-5 post-<?php echo esc_attr($post_id); ?>">
                                        <div class="text">
                                            <a href="<?php echo esc_url($post['permalink']); ?>" class="headline">
                                                <?php echo esc_html($post['post_title']); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php else : ?>
                                    <div class="ppma-col-12 post-column post-<?php echo esc_attr($post_id); ?>">
                                        <div class="ppma-row-article-block secondary">
                                            <div class="ppma-col-12">
                                                <div class="text">
                                                    <a href="<?php echo esc_url($post['permalink']); ?>" class="headline">
                                                        <?php echo esc_html($post['post_title']); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <?php if ($result['view_link']) : ?>
                                <div class="ppma-col-12 all-author-post-link">
                                    <a href="<?php echo esc_url($author->link); ?>">
                                        <div class="ppma-col-sm-12 article-cta">
                                            <p><?php echo $result['view_link']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                                        </div>
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php else : ?>
                            <div class="ppma-col-12">
                                <div class="ppma-row-article-block secondary">
                                    <div class="ppma-col-12">
                                        <div class="text">
                                            <p class="no-post"><?php echo $context['no_post_text']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($context['pagination']) : ?>
        <nav class="author-boxes-footer-navigation footer-navigation navigation pagination">
            <div class="nav-links">
            <?php echo $context['pagination']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </nav>
    <?php endif; ?>

</div>
