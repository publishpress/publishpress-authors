<?php
global $ppma_custom_settings;

$section_content = get_ppma_section_content($context['options_group_name']);

if (is_array($ppma_custom_settings)) {
    $wrapper_class = 'custom-settings';

    $parts = explode('<input type="hidden" id="', $section_content);
    $modifiedHtml = array_shift($parts);
    foreach ($parts as $part) {
        list($id_part, $rest_part) = explode('" />', $part, 2);
        $hidden_input_tag = '<input type="hidden" id="' . $id_part . '" />';
        if ($id_part == 'ppma-tab-author-pages') {
            // remove the ending table tag
            $rest_part = str_replace('</tr></table>', '</tr>', $rest_part);
            // add class to author pages tr
            $author_table_html = '<table class="form-table" role="presentation">';
            $author_table_parts = explode('<tr>', $rest_part);
            foreach ($author_table_parts as $author_table_part) {
                if (substr($author_table_part, -5) === '</tr>') {
                    $tr_class = 'ppma-author-pages-tab-general';
                    if (preg_match('/<(input|select)[^>]*id="([^"]*)"/i', $author_table_part, $matches)) {
                        $input_id = $matches[2];
                        switch ($input_id) {
                            case 'multiple_authors_multiple_authors_options_author_pages_posts_limit':
                            case 'multiple_authors_multiple_authors_options_author_pages_layout':
                            case 'multiple_authors_multiple_authors_options_author_pages_grid_layout_column':
                                $tr_class = 'ppma-author-pages-tab-layout';
                                break;
                            case 'multiple_authors_multiple_authors_options_show_author_pages_bio':
                            case 'multiple_authors_multiple_authors_options_author_pages_bio_layout':
                                $tr_class = 'ppma-author-pages-tab-author-bio';
                                break;
                            case 'multiple_authors_multiple_authors_options_show_author_page_title':
                            case 'multiple_authors_multiple_authors_options_author_post_excerpt_ellipsis':
                            case 'multiple_authors_multiple_authors_options_author_pages_title_header':
                                $tr_class = 'ppma-author-pages-tab-author-page-title';
                                break;
                            case 'multiple_authors_multiple_authors_options_author_post_title_header':
                            case 'multiple_authors_multiple_authors_options_show_author_post_featured_image':
                            case 'multiple_authors_multiple_authors_options_link_author_post_featured_image':
                            case 'multiple_authors_multiple_authors_options_author_post_custom_width':
                            case 'multiple_authors_multiple_authors_options_author_post_custom_height':
                            case 'multiple_authors_multiple_authors_options_show_author_post_excerpt':
                            case 'multiple_authors_multiple_authors_options_show_author_post_authors':
                            case 'multiple_authors_multiple_authors_options_show_author_post_date':
                            case 'multiple_authors_multiple_authors_options_show_author_post_comments':
                            case 'multiple_authors_multiple_authors_options_show_author_post_category':
                            case 'multiple_authors_multiple_authors_options_show_author_post_tags':
                            case 'multiple_authors_multiple_authors_options_show_author_post_readmore':
                                $tr_class = 'ppma-author-pages-tab-posts';
                                break;
                        }
                    }

                    if ($tr_class == 'ppma-author-pages-tab-general') {
                        $author_table_part_tr = '<tr class="'. $tr_class .'">';
                    } else {
                        $author_table_part_tr = '<tr class="'. $tr_class .'" style="display: none;">';
                    }
                    $author_table_html .= $author_table_part_tr . $author_table_part;
                }
            }
            $rest_part = $author_table_html . '</table>';
        } else {
            // Add style="display: none;" to the table following this hidden input
            $rest_part = str_replace(
                '<table class="form-table" role="presentation">',
                '<table class="form-table" role="presentation" style="display: none;">',
                $rest_part
            );
        }

        // Reconstruct the modified HTML content
        $modifiedHtml .= $hidden_input_tag . $rest_part;
    }

    $section_content = $modifiedHtml;
} else {
    $wrapper_class = '';
}
?>
<?php settings_fields('multiple_authors_options'); ?>
<div class="ppma-settings-wrap <?php echo esc_attr($wrapper_class); ?>">
    <?php echo $section_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<?php wp_nonce_field('edit-publishpress-settings'); ?>

<input type="hidden" name="multiple_authors_module_name[]" value="<?php echo esc_attr($context['module_name']); ?>"/>
<input type="hidden" name="action" value="update"/>

<script>
    jQuery(function ($) {
        $(".chosen-select").chosen({
            'width': '95%'
        });
    });
</script>
