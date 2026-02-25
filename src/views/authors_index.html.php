<div class="pp-multiple-authors-wrapper pp-multiple-authors-index alignwide <?php echo esc_attr($context['css_class']); ?> pp-multiple-authors-layout-<?php echo esc_attr($context['layout']); ?>">
    <?php if (!empty($context['search_box_html'])) : ?>
        <?php echo $context['search_box_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php endif; ?>
    <ul class="author-index-navigation">
        <li class="page-item active" data-item="all"><a class="page-link " href="#"><?php echo esc_html($context['all_text']); ?></a></li>
        <?php foreach ($context['results'] as $key => $value) :
            $display_title = publishpress_authors_get_index_display_title($key);
        ?>
            <li class="page-item" data-item="<?php echo esc_attr($key); ?>">
                <a class="page-link" href="#"><?php echo esc_html(strtoupper($display_title)); ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
    $currentUserIndex = 0;
    foreach ($context['results'] as $alphabet => $users) :
        $display_title = publishpress_authors_get_index_display_title($alphabet); ?>
        <div class="author-index-group author-index-group-<?php echo esc_attr($alphabet); ?>">
            <div class="author-index-header">
                <h4 class="author-list-head author-list-head-<?php echo esc_attr($alphabet); ?>"><?php echo esc_html(strtoupper($display_title)); ?></h4>
            </div>
            <div class="author-index-authors author-index-<?php echo esc_attr($alphabet); ?>">
                <ul>
                    <?php foreach ($users as $author) :
                        $currentUserIndex = $currentUserIndex + 1;
                        ?>
                        <li class="author-index-item author_index_<?php echo esc_attr($currentUserIndex); ?> author_<?php echo esc_attr($author->slug); ?>">
                            <div class="tease-author">
                                <div class="author-index-author-name">
                                    <a href="<?php echo esc_url($author->link); ?>" class="<?php echo esc_attr($context['item_class']); ?>" rel="author" title="<?php echo esc_attr($author->display_name); ?>">
                                        <?php echo $author->display_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </a>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if ($context['pagination']) : ?>
        <nav class="author-boxes-footer-navigation footer-navigation navigation pagination">
            <div class="nav-links">
            <?php echo $context['pagination']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </nav>
    <?php endif; ?>

</div>
