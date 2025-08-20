(function ($) {
    'use strict';

    jQuery(document).ready(function ($) {

        if ($(".chosen-select").length > 0) {
            $(".chosen-select").chosen({
                'width': '99%'
            });
        }

        /**
         * Update static shortcode on input change
         */
        $(document).on('input', '.author-list-tab-content .input input, .author-list-tab-content .input select', function (event) {
            $('.author-list-wrap .shortcode-textarea.static').val(getShortCodes());

            if ($(this).attr('id') == 'layout') {
                if ($(this).val() == 'authors_index') {
                    $('.ppma-author-list-editor-tab-content.ppma-editor-group_by').show();
                } else {
                    $('.ppma-author-list-editor-tab-content.ppma-editor-group_by').hide();
                }
                if ($(this).val() == 'authors_recent') {
                    $('.ppma-author-list-editor-tab-content.ppma-editor-featured_image_size').show();
                } else {
                    $('.ppma-author-list-editor-tab-content.ppma-editor-featured_image_size').hide();
                }
            }
        });

        /**
         * Author list editor tab switch
         */
        if ($('.author-list-wrap .shortcode-textarea.static').length) {
            $('.author-list-wrap .shortcode-textarea.static').val(getShortCodes());
        }
        $(document).on('click', '.author-list-tab li', function (event) {

            event.preventDefault();

            var clicked_tab = $(this).attr('data-tab');

            //remove active class from all tabs
            $('.author-list-tab li').removeClass('active');
            //add active class to current tab
            $(this).addClass('active');

            //hide all tabs contents
            $('.author-list-tab-content table.author-list-table').hide();
            //show this current tab contents
            $('.author-list-tab-content table.author-list-table.' + clicked_tab).show();
            //generate preview
            if (clicked_tab === 'preview') {
                generateAuthorListPreview();
            }
        });

        /**
         * Generate shortcode based on author list configuration
         */
        function getShortCodes() {
            var pro_active = authorList.isAuthorsProActive;

            var shortcode = '[publishpress_authors_list';
            // add layout
            var layout = $('.author-list-tab-content .input #layout').val();
            if (!isEmptyOrSpaces(layout)) {
                shortcode += ' layout="' + layout + '"';
            }
            // add layout_columns
            var layout_columns = $('.author-list-tab-content .input #layout_columns').val();
            if (!isEmptyOrSpaces(layout_columns)) {
                if (layout === 'authors_recent') {
                    shortcode += ' authors_recent_col="' + layout_columns + '"';
                    // add featured_image_size
                    var featured_image_size = $('.author-list-tab-content .input #featured_image_size').val();
                    if (!isEmptyOrSpaces(featured_image_size)) {
                        shortcode += ' featured_image_size="' + featured_image_size + '"';
                    }
                } else {
                    shortcode += ' layout_columns="' + layout_columns + '"';
                }
            }
            // add group_by
            var group_by = $('.author-list-tab-content .input #group_by').val();
            if (!isEmptyOrSpaces(group_by)) {
                shortcode += ' group_by="' + group_by + '"';
            }
            // add user roles, authors, term_id or category_id
            var author_type = $('.author-list-tab-content .input input[name="author_list[author_type]"]:checked').val();
            var roles = $('.author-list-tab-content .input #author_type-roles').val();
            var authors = $('.author-list-tab-content .input #author_type-authors').val();
            var term_id = $('.author-list-tab-content .input #author_type-term_id').val();
            var category_id = $('.author-list-tab-content .input #author_type-category_id').val();
            if (author_type == 'roles' && roles.length > 0) {
                shortcode += ' roles="' + roles.join(',') + '"';
            } else if (author_type == 'authors' && authors.length > 0) {
                shortcode += ' authors="' + authors.join(',') + '"';
            } else if (author_type == 'term_id' && term_id.length > 0) {
                shortcode += ' term_id="' + term_id.join(',') + '"';
            } else if (author_type == 'category_id' && category_id.length > 0) {
                shortcode += ' category_id="' + category_id.join(',') + '"';
            }

            // add exclude user roles, authors, term_id or category_id
            var author_type_exclude = $('.author-list-tab-content .input input[name="author_list[author_type_exclude]"]:checked').val();
            var exclude_roles = $('.author-list-tab-content .input #author_type_exclude-exclude_roles').val();
            var exclude_authors = $('.author-list-tab-content .input #author_type_exclude-exclude_authors').val();
            var exclude_term_id = $('.author-list-tab-content .input #author_type_exclude-exclude_term_id').val();
            var exclude_category_id = $('.author-list-tab-content .input #author_type_exclude-exclude_category_id').val();
            if (exclude_roles && exclude_roles.length > 0) {
                shortcode += ' exclude_roles="' + exclude_roles.join(',') + '"';
            } if (exclude_authors && exclude_authors.length > 0) {
                shortcode += ' exclude_authors="' + exclude_authors.join(',') + '"';
            } if (exclude_term_id && exclude_term_id.length > 0) {
                shortcode += ' exclude_term_id="' + exclude_term_id.join(',') + '"';
            } if (exclude_category_id && exclude_category_id.length > 0) {
                shortcode += ' exclude_category_id="' + exclude_category_id.join(',') + '"';
            }

            // add limit_per_page
            var limit_per_page = $('.author-list-tab-content .input #limit_per_page').val();
            if (pro_active && !isEmptyOrSpaces(limit_per_page)) {
                shortcode += ' limit_per_page="' + limit_per_page + '"';
            }
            if (pro_active) {
                // add show_empty
                var show_empty = $('.author-list-tab-content .input #show_empty').is(':checked') ? 1 : 0;
                shortcode += ' show_empty="' + show_empty + '"';
            }
            // add orderby
            var orderby = $('.author-list-tab-content .input #orderby').val();
            if (pro_active && !isEmptyOrSpaces(orderby)) {
                shortcode += ' orderby="' + orderby + '"';
            }
            // add order
            var order = $('.author-list-tab-content .input #order').val();
            if (pro_active && !isEmptyOrSpaces(order)) {
                shortcode += ' order="' + order + '"';
            }
            // add last_article_date
            var last_article_date = $('.author-list-tab-content .input #last_article_date').val();
            if (pro_active && !isEmptyOrSpaces(last_article_date)) {
                shortcode += ' last_article_date="' + last_article_date + '"';
            }
            // add search_box
            var search_box = $('.author-list-tab-content .input #search_box').is(':checked');
            if (pro_active && search_box) {
                shortcode += ' search_box="true"';
            }
            // add search_field
            var search_field = $('.author-list-tab-content .input #search_field').val();
            if (pro_active && search_field.length > 0) {
                shortcode += ' search_field="' + search_field.join(',') + '"';
            }

            shortcode += ']';

            return shortcode;
        }

        function generateAuthorListPreview() {
            $('.author-list-wrap .preview-skeleton').show();
            $('.preview-shortcode-wrap').hide();

            var data = {
                action: "author_list_editor_do_shortcode",
                shortcode:  $('.author-list-wrap .shortcode-textarea.static').val(),
                nonce: authorList.nonce,
            };
            $.post(ajaxurl, data, function (response) {
                $('.author-list-wrap .preview-skeleton').hide();
                $('.preview-shortcode-wrap').html(response.content).show();
            });
        }

        function isEmptyOrSpaces(str) {
            return !str || str == '' || str === null || str.match(/^ *$/) !== null;
        }
    });

})(jQuery);
