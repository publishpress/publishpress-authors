(function ($) {
    'use strict';

    jQuery(document).ready(function ($) {

        if ($('body').hasClass('post-type-ppma_boxes') && $(".publishpress-author-box-editor").length > 0) {
            var author_field_icons = '';
            populate_author_fields_icons();
            /**
             * Author field icon
             */
            $(document).on('click', '.ppma-author-boxes-editor-table .select-new-button', function (e) {
                e.preventDefault();
                if (author_field_icons == '') {
                    populate_author_fields_icons();
                } else {
                    var button = $(this);
                    var icon_field = button.attr('data-input_id');
                    var current_icon = button.closest('.author-boxes-field-icon').find('.selected-field-icon').children().first();
                    var current_icon_html = '';
                    var current_icon_class = '';

                    if (current_icon && current_icon.length > 0) {
                        current_icon_html = current_icon.prop('outerHTML');
                        current_icon_class = current_icon.attr('class');
                    }

                    var popup_header = '<div class="popup-modal-header">';
                    // add text to header
                    popup_header += button.attr('data-button_text') + ' &nbsp;';
                    // add icon to header
                    popup_header += current_icon_html + ' ';
                    // add search box to header
                    popup_header += '<div class="icon-sticky-header">';
                    popup_header += '<input type="text" class="ppma-field-icon-search" placeholder="' + button.attr('data-search_placeholder') + '">';

                    var container = $('<div></div>');

                    var tabs = '<div class="ppma-field-icon-tabs">';
                    var tabContents = $('<div class="author-field-icons-tab-contents"></div>');

                    var first_item = false;
                    $.each(author_field_icons, function(parent, iconLists) {
                        var active_tab = !first_item ? 'active' : '';
                        var tab_content_class = !first_item ? '' : 'hidden';

                        var tabContent = $('<div class="author-field-icons-tab-content ' + tab_content_class + '" id="' + parent.toLowerCase() + '"></div>');
                        tabs += '<button class="author-field-icons-tab-button ' + active_tab + '" data-target="' + parent.toLowerCase() + '">' + parent + '</button>';

                        var iconList = $('<div class="icon-list"></div>');
                        iconLists.forEach(function(icon) {
                            var icon_active_class = '';

                            if (parent == 'Dashicons') {
                                icon_active_class = current_icon_class == 'dashicons ' + icon.class + '' ? 'active' : '';
                                iconList.append('<div class="icon-item icon-' + parent.toLowerCase() + ' ' + icon_active_class + '" data-name="' + icon.name.toLowerCase() + '" data-field="' + icon_field + '"><span class="icon-element"><i class="dashicons ' + icon.class + '"></i></span><span class="icon-name">' + icon.name + '</span></div>');
                            } else if (parent === 'FontAwesome') {
                                icon_active_class = current_icon_class == icon.class ? 'active' : '';
                                iconList.append('<div class="icon-item icon-' + parent.toLowerCase() + ' ' + icon_active_class + '" data-name="' + icon.name.toLowerCase() + '" data-field="' + icon_field + '"><span class="icon-element"><i class="' + icon.class + '"></i></span><span class="icon-name">' + icon.name + '</span></div>');
                            }
                        });
                        tabContent.append(iconList);
                        tabContents.append(tabContent);
                        first_item = true;
                    });
                    tabs += '</div>';
                    // add tabs to header
                    popup_header += tabs;
                    popup_header += '</div>';
                    popup_header += '</div>';

                    container.append(tabContents);
                    $('#author-field-icons-container').html(container);

                    tb_show(popup_header, '#TB_inline?width=600&height=400&inlineId=author-field-icons-modal'); // Open modal

                }
            });

            /**
             * Author field icon tab
             */
            $(document).on('click', '.ppma-field-icon-tabs button', function (e) {
                e.preventDefault();
                $('.author-field-icons-tab-content').addClass('hidden');
                $('#' + $(this).data('target')).removeClass('hidden');
                $('.author-field-icons-tab-button').removeClass('active');
                $(this).addClass('active');
            });

            /**
             * Author field icon search
             */
            $(document).on('input', '.ppma-field-icon-search', function (e) {
                e.preventDefault();
                var searchTerm = $(this).val().toLowerCase();
                $('.author-field-icons-tab-contents .icon-item').each(function() {
                    var iconName = $(this).data('name');
                    if (String(iconName).includes(searchTerm)) {
                        $(this).removeClass('hidden');
                    } else {
                        $(this).addClass('hidden');
                    }
                });
            });

            /**
             * Author field icon select
             */
            $(document).on('click', '.author-field-icons-tab-contents .icon-item', function (e) {
                e.preventDefault();
                var icon_wrap = $(this);
                var icon = icon_wrap.find('.icon-element').html();
                var field = icon_wrap.attr('data-field');
                var field_wrap = $('.ppma-boxes-editor-tab-content.ppma-editor-' + field + '');
                field_wrap.find('input#' + field).val(icon).trigger('change');
                field_wrap.find('.selected-field-icon').html(icon);
                field_wrap.find('.remove-icon-button.action-button').show();
                field_wrap.find('.selected-field-icon.action-button').show();
                field_wrap.find('.select-new-button .button-secondary').html(field_wrap.find('.select-new-button').attr('data-button_change'));
                tb_remove();
            });

            /**
             * Author field icon removal
             */
            $(document).on('click', '.ppma-author-boxes-editor-table .remove-icon-button', function (e) {
                e.preventDefault();
                var button = $(this);
                button.hide();
                button.closest('td').find('input').val('').trigger('change');
                button.closest('td').find('.selected-field-icon').html('');
                button.closest('td').find('.select-new-button .button-secondary').html(button.closest('td').find('.select-new-button').attr('data-button_select'));
                button.closest('td').find('.remove-icon-button.action-button').hide();
                button.closest('td').find('.selected-field-icon.action-button').hide();
            });

            /**
             * color picker init
             */
            $('.pp-editor-color-picker').wpColorPicker({
                change: function (event, ui) {
                    setTimeout(
                        function () {
                            generateEditorPreview(getAllEditorFieldsValues(), false);
                        }, 100);
                },
                clear: function () {
                    setTimeout(
                        function () {
                            generateEditorPreview(getAllEditorFieldsValues(), false);
                        }, 100);
                },
            });

            /**
             * Author field re-order action
             */
            $(document).on('click', '.ppma-editor-field-reorder-btn', function (e) {
                e.preventDefault();
                $('.ppma-field-reorder-thickbox-btn').trigger('click');
                return;
            });
            $(".ppma-re-order-lists").sortable();

            /**
             * Code editor
             */
            if( $(".ppma-author-code-editor").length ) {
                var global_editor_settings = [], editor_settings = '', editor_textarea = '', editor_init = '', textarea_id = '', editor_mode = '', formatted_code = '';
                $(".ppma-author-code-editor").each(function () {
                    editor_textarea = $(this);
                    textarea_id     = editor_textarea.attr('id');
                    editor_mode     = editor_textarea.attr('data-editor_mode');
                    editor_settings = wp.codeEditor.defaultSettings ? _.clone( wp.codeEditor.defaultSettings ) : {};
                    editor_settings.codemirror = _.extend(
                        {},
                        editor_settings.codemirror,
                        {
                            indentUnit: 2,
                            tabSize: 2,
                            mode: "" + editor_mode + "",
                            inputStyle: 'textarea',
                            matchBrackets: true,
                            autoRefresh:true,
                            gutters: ['CodeMirror-lint-markers', 'CodeMirror-foldgutter'],
                            lint: 'php' !== editor_mode,
                            direction: 'ltr',
                            theme: 'php' === editor_mode ? 'monokai' : '',
                            readOnly: 'php' === editor_mode ? true : false
                        }
                    );
                    editor_init = wp.codeEditor.initialize(editor_textarea, editor_settings);
                    global_editor_settings[textarea_id] = editor_init;
                    if ('php' !== editor_mode) {
                        formatted_code = css_beautify(editor_init.codemirror.getValue(), {
                            indent_size: 4,
                            space_in_empty_paren: true
                        });
                        editor_init.codemirror.setValue(formatted_code);
                    }
                });

                $(document).on("keyup", ".ppma-boxes-editor-tab-content.code_editor .CodeMirror-code", function() {
                    var editor_textarea = $(this).closest('td.input').find('textarea.ppma-author-code-editor');
                    var editor_textarea_id = editor_textarea.attr('id');
                    var current_code_editor = global_editor_settings[editor_textarea_id];
                    current_code_editor.codemirror.save();
                    editor_textarea.val(current_code_editor.codemirror.getValue());
                    editor_textarea.trigger("change");
                });
                $(document).on("click", ".ppma-boxes-editor-tab-content.code_editor .clear-code-editor-content", function() {
                    var editor_textarea = $(this).closest('td.input').find('textarea.ppma-author-code-editor');
                    var editor_textarea_id = editor_textarea.attr('id');
                    var current_code_editor = global_editor_settings[editor_textarea_id];
                    current_code_editor.codemirror.setValue("");
                    editor_textarea.val("");
                    editor_textarea.trigger("change");
                });
                $(document).on("click", ".ppma-boxes-editor-tab-content.code_editor .refresh-code-editor", function() {
                    var editor_textarea = $(this).closest('td.input').find('textarea.ppma-author-code-editor');
                    var editor_textarea_id = editor_textarea.attr('id');
                    var current_code_editor = global_editor_settings[editor_textarea_id];
                    current_code_editor.codemirror.refresh();
                });
            }
        }

        /**
         * Author box shortcode delete
         */
        $(document).on('click', '.ppma-boxes-shortcodes-wrap .shortcode-entries .delete-shortcode', function (e) {
            e.preventDefault();
            $(this).closest('tr').remove();
            generateEditorPreview(getAllEditorFieldsValues(), true);
        });

        /**
         * Author box shortcode add
         */
        $(document).on('click', '.ppma-boxes-shortcodes-wrap .shortcode-entries .add-new-shortcode', function (e) {
            e.preventDefault();
            var shortcode = $('.ppma-boxes-shortcodes-wrap .shortcode-form .shortcodes-shortcode-input').val();
            var position  = $('.ppma-boxes-shortcodes-wrap .shortcode-form .shortcodes-position-input').val();

            if (shortcode && !isEmptyOrSpaces(shortcode)) {
                addShortcodeEntry(shortcode, position);
                generateEditorPreview(getAllEditorFieldsValues(), true);
            }
        });

        /**
         * Populate social icon on button click
         */
        $(document).on('click', '.ppma-add-social-icon', function (event) {
            event.preventDefault();
            var field_icon = $(this).attr('data-social');
            $('#profile_fields_' + field_icon + '_display_icon').val('<span class="dashicons dashicons-' + field_icon + '"></span>');

            if (!$('#profile_fields_hide_' + field_icon + '').is(':checked')) {
                generateEditorPreview(getAllEditorFieldsValues(), true);
            }
        });

        /**
         * Profile fields title toggle
         */
        $(document).on('click', '.ppma-editor-profile-header-title', function (event) {
            event.preventDefault();
            var current_header = $(this);
            var current_name = current_header.attr('data-fields_name');

            if (current_header.hasClass('opened')) {
                current_header.removeClass('opened').addClass('closed');
                $('.tabbed-content-' + current_name).hide();
            } else {
                current_header.removeClass('closed').addClass('opened');
                $('.tabbed-content-' + current_name).show();
            }
        });

        /**
         * boxes editor tab switch
         */
        $(document).on('click', '.ppma-author-box-editor-tabs a', function (event) {

            event.preventDefault();

            var clicked_tab = $(this).attr('data-tab');

            //remove active class from all tabs
            $('.ppma-author-box-editor-tabs a').removeClass('active');
            //add active class to current tab
            $(this).addClass('active');

            //hide all tabs contents
            $('.ppma-boxes-editor-tab-content').hide();
            //generate export data if it's export tab
            if (clicked_tab === 'export') {
                generateEditorExportData();
            }
            //clear previously generated template to allow new changes
            if (clicked_tab === 'generate_template') {
                $('#template_action').val('');
            }
            //show this current tab contents
            $('.ppma-' + clicked_tab + '-tab').show();
            //only show active tab content if it's profile tab
            if (clicked_tab === 'profile_fields') {
                toggleProfileFieldsActiveTab();
            }
        });

        /**
         * Export copy to clipboard
         */
        $(document).on('click', '.ppma-editor-copy-clipboard', function (event) {
            event.preventDefault();
            //get the text field
            var data_input = event.target.closest('.input').querySelector('#' + $(this).attr('data-target_input'));
            //select the text field
            data_input.select();
            data_input.setSelectionRange(0, 99999); /* For mobile devices */
            //copy original text to clipboard
            navigator.clipboard.writeText(data_input.value);
            //show notification
            $(this).closest('.input').find(".ppma-editor-copied-to-clipboard").show().delay(2000).fadeOut('slow');
        });

        /**
         * Import data
         */
        $(document).on('click', '.ppma-editor-data-import', function (event) {
            event.preventDefault();
            var import_value = $('#import_action').val();
            if (import_value) {
                try {
                    import_value = atob(import_value);
                } catch (e) {
                    $(".ppma-editor-data-imported").css('color', 'red').html($(this).attr('data-invalid')).show().delay(2000).fadeOut('slow');
                    return;
                }
                try {
                    import_value = JSON.parse(import_value);
                } catch (e) {
                    $(".ppma-editor-data-imported").css('color', 'red').html($(this).attr('data-invalid')).show().delay(2000).fadeOut('slow');
                    return;
                }

                var editor_values = import_value;

                var key = '';
                // clear shortcodes entries
                $('.ppma-boxes-shortcodes-wrap tr.shortcode-entry').remove();
                for (key in editor_values) {
                    var value = editor_values[key];
                    var field = $('[name="' + key + '"]');
                    field.val(value);

                    if (field.attr('type') === 'checkbox') {
                        if (Number(value) > 0) {
                            field.prop('checked', true);
                        } else {
                            field.prop('checked', false);
                        }
                        field.val(1);
                    } else if (field.is('select') && field.prop('multiple')) {
                        field.val(value);
                    } else if (key === 'shortcodes') {
                        if (value.shortcode && value.position) {
                            var shortcodes = value.shortcode;
                            var positions = value.position;
                            for (var i = shortcodes.length - 1; i >= 0; i--) {
                                addShortcodeEntry(shortcodes[i], positions[i]);
                            }
                        }
                    } else if (key === 'box_tab_custom_css') {
                        var editor_textarea = field;
                        var editor_textarea_id = editor_textarea.attr('id');
                        var current_code_editor = global_editor_settings[editor_textarea_id];
                        current_code_editor.codemirror.setValue(value);
                        editor_textarea.val(value);
                        editor_textarea.trigger("change");
                        current_code_editor.codemirror.refresh();
                    }

                    if (field.hasClass('pp-editor-color-picker')) {
                        field.trigger('change');
                    }
                }

                setTimeout(
                    function () {
                        $('#import_action').val('');
                        $('#avatar_show').trigger('change');
                        $('.ppma-editor-data-imported').css('color', 'green').show().delay(2000).fadeOut('slow');
                    }, 500);

            } else {
                $(".ppma-editor-data-imported").css('color', 'red').html($(this).attr('data-invalid')).show().delay(2000).fadeOut('slow');
            }
        });

        /**
         * Generate editor template
         */
        $(document).on('click', '.ppma-editor-generate-template', function (event) {
            event.preventDefault();
            $('.ppma-editor-generate-template').attr('disabled', true);
            $('.author-editor-loading-spinner').addClass('is-active');

            //prepare ajax data
            var editor_values = getAllEditorFieldsValues();
            var data = {
                action: "author_boxes_editor_get_template",
                preview_author_post: $('.editor-preview-author-post .ppma-authors-post-search').val(),
                post_id: authorBoxesEditor.post_id,
                editor_data: $.extend({}, editor_values),
                nonce: authorBoxesEditor.nonce,
            };

            $.post(ajaxurl, data, function (response) {
                var status = response.status;
                var content = response.content;
                $('.ppma-editor-generate-template').attr('disabled', false);
                $('.author-editor-loading-spinner').removeClass('is-active');
                if (status === 'success') {
                    var template_content = content.replaceAll('</?php', '<?php');

                    var editor_textarea = $('#template_action');
                    var editor_textarea_id = editor_textarea.attr('id');
                    var current_code_editor = global_editor_settings[editor_textarea_id];

                    current_code_editor.codemirror.setValue(template_content);
                    editor_textarea.val(template_content);
                    editor_textarea.trigger("change");

                    $('.ppma-editor-template-generated').css('color', 'green').show().delay(2000).fadeOut('slow');
                } else {
                    $('.ppma-editor-template-generated').css('color', 'red').html(content).show().delay(2000).fadeOut('slow');
                }
            });

        });

        /**
         * Save author box order
         */
        $(document).on('click', '.ppma-editor-order-form button.update-order', function (event) {
            event.preventDefault();
            var button = $(this);
            var buttons = $('.ppma-editor-order-form button.update-order');
            buttons.prop('disabled', true);
            button.find('.spinner').addClass('is-active');
            $('.ppma-editor-generate-template').attr('disabled', true);

            var save_for = button.attr('data-save');
            var field_orders = [];
            $("input.sort-field-names").each(function () {
                if ($(this).val() !== '') {
                    field_orders.push($(this).val().toLowerCase());
                }
            });

            //prepare ajax data
            var data = {
                action: "author_boxes_editor_save_fields_order",
                save_for: save_for,
                field_orders: field_orders,
                post_id: authorBoxesEditor.post_id,
                nonce: authorBoxesEditor.nonce,
            };
            $.post(ajaxurl, data, function (response) {
                var status = response.status;
                var status_message = response.content;
                $('.ppma-order-response-message').html('<span class="' + status + '"> ' + status_message + ' </span>');
                buttons.prop('disabled', false);
                button.find('.spinner').removeClass('is-active');
            });

        });

        /**
         * editor live changes
         */
        $(document).on('change input keyup', '.ppma-author-box-editor-fields .input input, .ppma-author-box-editor-fields .input textarea, .ppma-author-box-editor-fields .input select, .editor-preview-author-post .ppma-authors-post-search', function () {
            //get current width and add at as custom height to prevent box from moving
            var box_editor_wrapper = $('.publishpress-author-box-editor .preview-section').closest('.publishpress-author-box-editor');
            box_editor_wrapper.css('min-height', 10);

            var current_field = $(this);
            var current_field_name = current_field.attr('name');

            //update wrapper class with new name
            var box_wrapper_class = ' ' + $('#box_tab_custom_wrapper_class').val();
            var prev_layout_wrapper_classes = $('.pp-multiple-authors-boxes-wrapper').attr('data-original_class');
            $('.pp-multiple-authors-boxes-wrapper').attr('class', prev_layout_wrapper_classes + box_wrapper_class);

            var title_html_tag = $('#title_html_tag').val();
            var title_text = $('#title_text_plural').val();

            //update title based on show/hide title
            if (current_field_name === 'show_title') {
                if (current_field.is(':checked')) {
                    if ($('.pp-multiple-authors-boxes-wrapper .box-header-title').length > 0) {
                        $('.pp-multiple-authors-boxes-wrapper .box-header-title').show();
                    } else {
                        $('.pp-multiple-authors-boxes-wrapper').prepend('<' + title_html_tag + ' class="widget-title box-header-title">' + title_text + '</' + title_html_tag + '>');
                    }
                } else {
                    $('.pp-multiple-authors-boxes-wrapper .box-header-title').hide();
                }
                return;
            }

            //update title / title html tag
            if (current_field_name === 'title_html_tag' || current_field_name === 'title_text' || current_field_name === 'title_text_plural') {
                //remove previous title with tag if exist
                $('.pp-multiple-authors-boxes-wrapper .box-header-title').remove();
                //create new one with updated data
                $('.pp-multiple-authors-boxes-wrapper').prepend('<' + title_html_tag + ' class="widget-title box-header-title">' + title_text + '</' + title_html_tag + '>');
                //make sure title is hidden if not set to show
                if (!$('#show_title').is(':checked')) {
                    $('.pp-multiple-authors-boxes-wrapper .box-header-title').hide();
                }
                return;
            }

            //update name html tag
            if (current_field_name === 'name_html_tag') {
                //this only matter if name is displayed
                if ($('#name_show').is(':checked')) {
                    var name_html_tag = $('#name_html_tag').val();
                    $(".pp-author-boxes-name").replaceWith(function () {
                        return "<" + name_html_tag + " class='pp-author-boxes-name multiple-authors-name'>" + this.innerHTML + "</" + name_html_tag + ">";
                    });
                }
                return;
            }

            //update bio html tag
            if (current_field_name === 'author_bio_html_tag') {
                //this only matter if bio is displayed
                if ($('#author_bio_show').is(':checked')) {
                    var author_bio_html_tag = $('#author_bio_html_tag').val();
                    $(".pp-author-boxes-description").replaceWith(function () {
                        return "<" + author_bio_html_tag + " class='pp-author-boxes-description multiple-authors-description'>" + this.innerHTML + "</" + author_bio_html_tag + ">";
                    });
                }
                return;
            }

            //update meta html tag
            if (current_field_name === 'meta_html_tag') {
                //this only matter if meta is displayed
                if ($('#meta_view_all_show').is(':checked')) {
                    var meta_html_tag = $('#meta_html_tag').val();
                    $(".pp-author-boxes-meta").replaceWith(function () {
                        return "<" + meta_html_tag + " class='pp-author-boxes-meta multiple-authors-links'>" + this.innerHTML + "</" + meta_html_tag + ">";
                    });
                }
                return;
            }

            //update recent posts html tag
            if (current_field_name === 'author_recent_posts_html_tag') {
                //this only matter if recent posts is displayed
                if ($('#author_recent_posts_show').is(':checked')) {
                    var author_recent_posts_html_tag = $('#author_recent_posts_html_tag').val();
                    $(".pp-author-boxes-recent-posts-item").replaceWith(function () {
                        return "<" + author_recent_posts_html_tag + " class='pp-author-boxes-recent-posts-item'>" + this.innerHTML + "</" + author_recent_posts_html_tag + ">";
                    });
                }
                return;
            }

            //update layout prefix value
            if (current_field_name === 'box_tab_layout_prefix') {
                $(".ppma-layout-prefix").html($('#box_tab_layout_prefix').val());
                return;
            }

            //update layout suffix value
            if (current_field_name === 'box_tab_layout_suffix') {
                $(".ppma-layout-suffix").html($('#box_tab_layout_suffix').val());
                return;
            }

            //get editor field values
            var editor_values = getAllEditorFieldsValues();

            //update avatar size
            if (current_field_name === 'avatar_size') {
                //this only matter if avatar is displayed
                if ($('#avatar_show').is(':checked')) {
                    $('.pp-author-boxes-avatar img').attr('width', current_field.val()).attr('height', current_field.val());
                    generateEditorPreviewStyles(editor_values);
                }
                return;
            }

            var post_refresh_trigger = [
                'author_recent_posts_show',
                'author_recent_posts_title_show',
                'author_recent_posts_post_types',
                'author_recent_posts_empty_show',
                'author_recent_posts_limit',
                'author_recent_posts_orderby',
                'author_recent_posts_order'
            ];
            var name_refresh_trigger = [
                'name_show',
                'name_disable_link',
                'name_author_categories',
                'name_author_categories_divider',
                'name_author_categories_position',
                'display_name_position',
                'display_name_prefix',
                'display_name_suffix'
            ];
            var bio_refresh_trigger = [
                'author_bio_show',
                'author_bio_link',
                'author_bio_limit'
            ];
            var avatar_refresh_trigger = [
                'avatar_show',
                'avatar_link'
            ];
            var meta_refresh_trigger = [
                'meta_view_all_show',
                'meta_label'
            ];
            var layout_refresh_trigger = [
                'box_tab_layout_author_separator',
                'author_inline_display'
            ];
            var author_categories_refresh_trigger = [
                'author_categories_group',
                'author_categories_group_option',
                'author_categories_title_html_tag',
                'author_categories_title_prefix',
                'author_categories_title_suffix',
                'author_categories_title_option',
                'author_categories_group_display_style_laptop',
                'author_categories_group_display_style_mobile',
                'author_categories_bottom_space',
                'author_categories_right_space',
                'author_categories_font_size',
                'author_categories_title_font_weight'
            ];

            var profile_fields = JSON.parse(authorBoxesEditor.profileFields);
            var field_key = '';
            var profile_refresh_trigger = [];
            for (field_key in profile_fields) {
                var field_name = profile_fields[field_key];
                profile_refresh_trigger.push('profile_fields_hide_' + field_name);
                profile_refresh_trigger.push('profile_fields_' + field_name + '_author_categories');
                profile_refresh_trigger.push('profile_fields_' + field_name + '_author_categories_divider');
                profile_refresh_trigger.push('profile_fields_' + field_name + '_html_tag');
                profile_refresh_trigger.push('profile_fields_' + field_name + '_value_prefix');
                profile_refresh_trigger.push('profile_fields_' + field_name + '_display');
                profile_refresh_trigger.push('profile_fields_' + field_name + '_display_prefix');
                profile_refresh_trigger.push('profile_fields_' + field_name + '_display_suffix');
                profile_refresh_trigger.push('profile_fields_' + field_name + '_display_icon');
                profile_refresh_trigger.push('profile_fields_' + field_name + '_display_position');
                profile_refresh_trigger.push('profile_fields_' + field_name + '_before_display_prefix');
                profile_refresh_trigger.push('profile_fields_' + field_name + '_after_display_suffix');
            }

            let all_refresh_trigger = post_refresh_trigger.concat(bio_refresh_trigger, avatar_refresh_trigger, meta_refresh_trigger, profile_refresh_trigger, name_refresh_trigger, layout_refresh_trigger, author_categories_refresh_trigger);

            var force_refresh = false;
            if (all_refresh_trigger.includes(current_field_name) || current_field_name === 'preview_author_post') {
                force_refresh = true;
            }

            generateEditorPreview(editor_values, force_refresh);
        });

        /**
         * Author category layout changes
         */
        $(document).on('change', '.ppma-author-box-editor-fields #author_categories_layout', function () {
            var selected_layout = $(this).val();
            if (selected_layout && selected_layout !== '') {
                var layout_option = author_box_category_options(selected_layout);
                if (layout_option) {
                    // reset all fields
                    $('.ppma-author-box-editor-fields .input input, .ppma-author-box-editor-fields .input textarea, .ppma-author-box-editor-fields .input select').each(function () {
                        if ($(this).attr('name') !== 'author_categories_layout') {
                            if ($(this).is(':checkbox')) {
                                $(this).prop('checked', false);
                            } else {
                                $(this).val('');
                            }

                            if ($(this).hasClass('pp-editor-color-picker')) {
                                $(this).trigger('change');
                            }
                        }
                    });
                    // update selected layout values
                    for (var key in layout_option) {
                        if (layout_option.hasOwnProperty(key)) {
                            var value = layout_option[key];
                            var $field = $('.ppma-author-box-editor-fields #' + key);

                            if ($field.is(':checkbox')) {
                                $field.prop('checked', value == 1);
                            } else {
                                $field.val(value);
                            }
                        }
                    }
                    $('.parent_author_box').val(selected_layout);
                    $('#author_categories_group').trigger('change');
                }
            }
        });

        /**
         * Populate author field icons
         */
        function populate_author_fields_icons() {
            //prepare ajax data
            var data = {
                action: "author_boxes_editor_get_fields_icons",
                nonce: authorBoxesEditor.nonce,
            };
            $.post(ajaxurl, data, function (response) {
                if (response.status == 'success') {
                    author_field_icons = response.content;
                }
            });
        }

        /**
         * Toggle profile fields active tab
         */
        function toggleProfileFieldsActiveTab() {
            $('.ppma-editor-profile-header-title').each(function () {
                var current_header = $(this);
                var current_name = current_header.attr('data-fields_name');

                if (current_header.hasClass('opened')) {
                    $('.tabbed-content-' + current_name).show();
                } else {
                    $('.tabbed-content-' + current_name).hide();
                }
            });
        }

        /**
         * Function for generating editor preview
         * @param {*} editor_values
         * @param {*} force_refresh
         */
        function generateEditorPreview(editor_values, force_refresh) {
            //send ajax to refresh needed part
            if (force_refresh) {
                $('.pp-multiple-authors-boxes-wrapper').html('<div class="author-boxes-loading-spinner spinner is-active" style="float: none;"></div>');
                $('.pp-author-boxes-editor-preview-styles').remove();
                //prepare ajax data
                var data = {
                    action: "author_boxes_editor_get_preview",
                    editor_data: $.extend({}, editor_values),
                    author_term_id: authorBoxesEditor.author_term_id,
                    preview_author_post: $('.editor-preview-author-post .ppma-authors-post-search').val(),
                    post_id: authorBoxesEditor.post_id,
                    nonce: authorBoxesEditor.nonce,
                };
                $.post(ajaxurl, data, function (response) {
                    $('.pp-author-boxes-editor-preview-styles').remove();
                    $('.pp-multiple-authors-boxes-wrapper').replaceWith(response.content);
                    //get current width and reset it as custom height incase height increases
                    var box_editor_wrapper = $('.publishpress-author-box-editor .preview-section').closest('.publishpress-author-box-editor');
                    box_editor_wrapper.css('min-height', box_editor_wrapper.height());
                    generateEditorPreviewStyles(editor_values);
                });
            }
            generateEditorPreviewStyles(editor_values);
        }

        /**
         * Return editor fields values
         * @param {*} populate
         */
        function getAllEditorFieldsValues() {
            var editor_values = [];
            var input_value = '';
            var processedMultipleInputs = [];
            $('.ppma-author-box-editor-fields .input input, .ppma-author-box-editor-fields .input textarea, .ppma-author-box-editor-fields .input select').each(function () {
                var excluded_input = ['export_action', 'import_action', 'template_action'];
                var input = $(this);
                var input_name = input.attr('name');
                if (input_name && !excluded_input.includes(input_name)) {
                    if (input.attr('type') === 'checkbox') {
                        input_value = (input.is(':checked')) ? '1' : '';
                        editor_values[input_name] = input_value;
                    } else if (input.is('select') && input.prop('multiple')) {
                        var real_name = input_name;
                        var match = input_name.match(/^(.*?)\[/);
                        if (match) {
                            real_name = match[1];
                        }
                        input_value = input.val() || [];
                        editor_values[real_name] = input_value;
                    } else if (input_name.endsWith('[]')) {
                                var real_name = input_name;
                        var match = input_name.match(/^(.*?)\[/);
                        if (match) {
                            real_name = match[1];
                        }
                        if (!processedMultipleInputs.includes(real_name)) {
                            input_value = collectMultipleInputData(real_name);
                            editor_values[real_name] = input_value;
                            processedMultipleInputs.push(real_name);
                        }

                    } else {
                        input_value = input.val();
                        editor_values[input_name] = input_value;
                    }
                }
            });

            return editor_values;
        }

        /**
         * Get array input values same way php will get $_POST['name]
         * with all sub arrays key in a single name.
         *
         * @param {*} inputName
         * @returns
         */
        function collectMultipleInputData(inputName) {
            var multipleInputData = {};

            var inputs = document.querySelectorAll('input[name^="' + inputName + '["]');

            inputs.forEach(input => {
              const name = input.name.match(/\[([^\]]+)\]\[]/)[1];
              const value = input.value;

              if (!multipleInputData[name]) {
                multipleInputData[name] = [];
              }
              multipleInputData[name].push(value);
            });

            return multipleInputData;
        }


        /**
         * Generate and populate export data
         * @param {*} populate
         * @returns
         */
        function generateEditorExportData(populate = true) {
            var editor_values = getAllEditorFieldsValues();

            editor_values = Object.assign({}, editor_values);
            editor_values = JSON.stringify(editor_values);
            editor_values = btoa(editor_values);

            if (populate) {
                $('#export_action').val(editor_values);
            } else {
                return editor_values;
            }
        }

        /**
         * Function for generating editor preview styles
         * @param {*} editor_values
         */
        function generateEditorPreviewStyles(editor_values) {
            var editor_preview_styles = '';
            var key = '';
            var field_key = '';
            var post_id = authorBoxesEditor.post_id;
            var instance_id = $('.pp-multiple-authors-boxes-wrapper.box-post-id-' + post_id).attr('data-instance_id');
            var additional_class = $('.pp-multiple-authors-boxes-wrapper.box-post-id-' + post_id).attr('data-additional_class');

            if (Number(editor_values.avatar_show) === 0) {
                editor_preview_styles += '.pp-multiple-authors-layout-boxed ul li > div:nth-child(1) {flex: 1 !important;}';
            }
            var profile_fields = JSON.parse(authorBoxesEditor.profileFields);

            //profile styles
            for (field_key in profile_fields) {
                var field_name = profile_fields[field_key];
                if (editor_values['profile_fields_' + field_name + '_size']) {
                    editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-' + field_name + '-profile-data { font-size: ' + editor_values['profile_fields_' + field_name + '_size'] + 'px !important; } ';
                }
                if (editor_values['profile_fields_' + field_name + '_display_icon_size']) {
                    editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-' + field_name + '-profile-data span, .pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-' + field_name + '-profile-data i { font-size: ' + editor_values['profile_fields_' + field_name + '_display_icon_size'] + 'px !important; } ';
                }
                if (editor_values['profile_fields_' + field_name + '_display_icon_background_color']) {
                    editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-' + field_name + '-profile-data { background-color: ' + editor_values['profile_fields_' + field_name + '_display_icon_background_color'] + ' !important; } ';
                }
                if (editor_values['profile_fields_' + field_name + '_display_icon_border_radius']) {
                    editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-' + field_name + '-profile-data { border-radius: ' + editor_values['profile_fields_' + field_name + '_display_icon_border_radius'] + '% !important; } ';
                }
                if (editor_values['profile_fields_' + field_name + '_line_height']) {
                    editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-' + field_name + '-profile-data { line-height: ' + editor_values['profile_fields_' + field_name + '_line_height'] + 'px !important; } ';
                }
                if (editor_values['profile_fields_' + field_name + '_weight']) {
                    editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-' + field_name + '-profile-data { font-weight: ' + editor_values['profile_fields_' + field_name + '_weight'] + ' !important; } ';
                }
                if (editor_values['profile_fields_' + field_name + '_transform']) {
                    editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-' + field_name + '-profile-data { text-transform: ' + editor_values['profile_fields_' + field_name + '_transform'] + ' !important; } ';
                }
                if (editor_values['profile_fields_' + field_name + '_style']) {
                    editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-' + field_name + '-profile-data { font-style: ' + editor_values['profile_fields_' + field_name + '_style'] + ' !important; } ';
                }
                if (editor_values['profile_fields_' + field_name + '_decoration']) {
                    editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-' + field_name + '-profile-data { text-decoration: ' + editor_values['profile_fields_' + field_name + '_decoration'] + ' !important; } ';
                }
                if (editor_values['profile_fields_' + field_name + '_alignment']) {
                    editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-' + field_name + '-profile-data { text-align: ' + editor_values['profile_fields_' + field_name + '_alignment'] + ' !important; } ';
                }
                if (editor_values['profile_fields_' + field_name + '_color']) {
                    editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-' + field_name + '-profile-data { color: ' + editor_values['profile_fields_' + field_name + '_color'] + ' !important; } ';
                }
            }

            for (key in editor_values) {
                var value = editor_values[key];
                switch (key) {
                    //title styles
                    case 'title_bottom_space':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .box-header-title { margin-bottom: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'title_size':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .box-header-title { font-size: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'title_line_height':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .box-header-title { line-height: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'title_weight':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .box-header-title { font-weight: ' + value + ' !important; } ';
                        }
                        break;
                    case 'title_transform':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .box-header-title { text-transform: ' + value + ' !important; } ';
                        }
                        break;
                    case 'title_style':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .box-header-title { font-style: ' + value + ' !important; } ';
                        }
                    case 'title_decoration':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .box-header-title { text-decoration: ' + value + ' !important; } ';
                        }
                        break;
                    case 'title_alignment':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .box-header-title { text-align: ' + value + ' !important; } ';
                        }
                        break;
                    case 'title_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .box-header-title { color: ' + value + ' !important; } ';
                        }
                        break;
                    //avatar styles
                    case 'avatar_size':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-avatar img { width: ' + value + 'px !important; height: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'avatar_border_style':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-avatar img { border-style: ' + value + ' !important; } ';
                        }
                        break;
                    case 'avatar_border_width':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-avatar img { border-width: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'avatar_border_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-avatar img { border-color: ' + value + ' !important; } ';
                        }
                        break;
                    case 'avatar_border_radius':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-avatar img { border-radius: ' + value + '% !important; } ';
                        }
                        break;
                    //name styles
                    case 'name_size':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-name a { font-size: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'name_line_height':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-name a { line-height: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'name_weight':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-name a { font-weight: ' + value + ' !important; } ';
                        }
                        break;
                    case 'name_transform':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-name a { text-transform: ' + value + ' !important; } ';
                        }
                        break;
                    case 'name_style':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-name a { font-style: ' + value + ' !important; } ';
                        }
                        break;
                    case 'name_decoration':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-name a { text-decoration: ' + value + ' !important; } ';
                        }
                        break;
                    case 'name_alignment':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-name { text-align: ' + value + ' !important; } ';
                        }
                        break;
                    case 'name_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-name a { color: ' + value + ' !important; } ';
                        }
                        break;
                    //bio styles
                    case 'author_bio_size':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-description { font-size: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'author_bio_line_height':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-description { line-height: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'author_bio_weight':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-description { font-weight: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_bio_transform':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-description { text-transform: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_bio_style':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-description { font-style: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_bio_decoration':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-description { text-decoration: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_bio_alignment':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-description { text-align: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_bio_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-description { color: ' + value + ' !important; } ';
                        }
                        break;
                    //meta styles
                    case 'meta_size':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-meta a span { font-size: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'meta_line_height':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-meta a span { line-height: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'meta_weight':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-meta a span { font-weight: ' + value + ' !important; } ';
                        }
                        break;
                    case 'meta_transform':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-meta a span { text-transform: ' + value + ' !important; } ';
                        }
                        break;
                    case 'meta_style':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-meta a span { font-style: ' + value + ' !important; } ';
                        }
                        break;
                    case 'meta_decoration':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-meta a span { text-decoration: ' + value + ' !important; } ';
                        }
                        break;
                    case 'meta_alignment':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-meta { text-align: ' + value + ' !important; } ';
                        }
                        break;
                    case 'meta_background_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-meta a { background-color: ' + value + ' !important; } ';
                        }
                        break;
                    case 'meta_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-meta a { color: ' + value + ' !important; } ';
                        }
                        break;
                    case 'meta_link_hover_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-meta a:hover { color: ' + value + ' !important; } ';
                        }
                        break;
                    //recent posts styles
                    case 'author_recent_posts_title_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-recent-posts-title { color: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_recent_posts_title_border_bottom_style':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-recent-posts-title { border-bottom-style: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_recent_posts_title_border_width':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-recent-posts-title { border-width: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'author_recent_posts_title_border_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-recent-posts-title { border-color: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_recent_posts_size':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-recent-posts-item a { font-size: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'author_recent_posts_line_height':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-recent-posts-item a { line-height: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'author_recent_posts_weight':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-recent-posts-item a { font-weight: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_recent_posts_transform':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-recent-posts-item a { text-transform: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_recent_posts_style':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-recent-posts-item a { font-style: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_recent_posts_decoration':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-recent-posts-item a { text-decoration: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_recent_posts_alignment':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-recent-posts-item { text-align: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_recent_posts_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-recent-posts-item a { color: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_recent_posts_icon_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-author-boxes-recent-posts-item span.dashicons { color: ' + value + ' !important; } ';
                        }
                        break;
                    //box layout styles
                    case 'box_layout_margin_top':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { margin-top: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'box_layout_margin_bottom':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { margin-bottom: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'box_layout_margin_left':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { margin-left: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'box_layout_margin_right':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { margin-right: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'box_layout_padding_top':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { padding-top: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'box_layout_padding_bottom':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { padding-bottom: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'box_layout_padding_left':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { padding-left: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'box_layout_padding_right':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { padding-right: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'box_layout_border_style':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { border-style: ' + value + ' !important; } ';
                        }
                        break;
                    case 'box_layout_border_width':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { border-width: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'box_layout_border_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { border-color: ' + value + ' !important; } ';
                        }
                        break;
                    case 'box_layout_box_width':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { width: ' + value + '% !important; } ';
                        }
                        break;
                    case 'box_layout_background_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { background-color: ' + value + ' !important; } ';
                        }
                        break;
                    case 'box_layout_color':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { color: ' + value + ' !important; } ';
                        }
                        break;
                    case 'author_categories_group_display_style_laptop':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-category-wrap { display: ' + value + ' !important; } ';

                            if (value == 'flex') {
                                editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-category-wrap .ppma-category-group { flex: 1; } ';
                            }
                        }
                        break;
                    case 'author_categories_group_display_style_mobile':
                        if (value) {
                            editor_preview_styles += ' @media screen and (max-width: 768px) { .pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-category-wrap { display: ' + value + ' !important; } } ';
                            if (value == 'flex') {
                                editor_preview_styles += ' @media screen and (max-width: 768px) { .pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-category-wrap .ppma-category-group { flex: 1; } } ';
                            }
                        }
                        break;
                    case 'author_categories_bottom_space':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-category-wrap .ppma-category-group { margin-bottom: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'author_categories_right_space':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-category-wrap .ppma-category-group { margin-right: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'author_categories_font_size':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-category-wrap { font-size: ' + value + 'px !important; } ';
                        }
                        break;
                    case 'author_categories_title_font_weight':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .ppma-author-category-wrap .ppma-category-group-title { font-weight: ' + value + ' !important; } ';
                        }
                        break;
                    case 'box_layout_shadow_color':
                        var shadow_color = editor_values['box_layout_shadow_color'];
                        var shadow_horizontal_offset = editor_values['box_layout_shadow_horizontal_offset'];
                        shadow_horizontal_offset = shadow_horizontal_offset ? shadow_horizontal_offset : 0;
                        var shadow_vertical_offset = editor_values['box_layout_shadow_vertical_offset'];
                        shadow_vertical_offset = shadow_vertical_offset ? shadow_vertical_offset : 0;
                        var shadow_blur = editor_values['box_layout_shadow_blur'];
                        shadow_blur = shadow_blur ? shadow_blur : 0;
                        var shadow_speed = editor_values['box_layout_shadow_speed'];
                        shadow_speed = shadow_speed ? shadow_speed : 0;
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { box-shadow: ' + shadow_horizontal_offset + 'px ' + shadow_vertical_offset + 'px ' + shadow_blur + 'px ' + shadow_speed + 'px ' + shadow_color + ' !important; } ';
                        }
                        break;
                    case 'box_layout_border_radius':
                        if (value) {
                            editor_preview_styles += '.pp-multiple-authors-boxes-wrapper.' + additional_class + '  .pp-multiple-authors-boxes-li { border-radius: ' + value + 'px !important; } ';
                        }
                        break;
                    // custom css style
                    case 'box_tab_custom_css':
                        if (value) {
                            editor_preview_styles += value;
                        }
                }
            }
            $('.pp-author-boxes-editor-preview-styles style').html(editor_preview_styles);
        }

        function author_box_category_options(layout) {

            var layouts = {};
            var author_boxes = authorBoxesEditor.author_boxes;

            // add inbuilt layouts
            layouts.boxed_categories = authorBoxesEditor.boxed_categories;
            layouts.two_columns_categories = authorBoxesEditor.two_columns_categories;
            layouts.list_author_category_block = authorBoxesEditor.list_author_category_block;
            layouts.list_author_category_inline = authorBoxesEditor.list_author_category_inline;
            layouts.simple_name_author_category_block = authorBoxesEditor.simple_name_author_category_block;
            layouts.simple_name_author_category_inline = authorBoxesEditor.simple_name_author_category_inline;

            // add user author box layouts
            for (var key in author_boxes) {
                if (author_boxes.hasOwnProperty(key)) {
                    var value = author_boxes[key];
                    //reset not needed fields
                    value['show_title'] = 0;
                    //author category
                    value['author_categories_group'] = 1;
                    value['author_categories_layout'] = layout;
                    value['author_categories_group_option'] = 'inline';
                    value['author_categories_group_display_style_laptop'] = 'flex';
                    value['author_categories_group_display_style_mobile'] = 'block';
                    value['author_categories_title_option'] = 'before_group';
                    value['author_categories_title_font_weight'] = '';
                    value['author_categories_title_html_tag'] = 'h1';
                    value['author_categories_title_prefix'] = '';
                    value['author_categories_title_suffix'] = '';
                    value['author_categories_font_size'] = '';
                    value['author_categories_bottom_space'] = '';
                    value['author_categories_right_space'] = 10;
                    layouts[key] = value;
                }
            }

            return layouts[layout];
        }

        function isEmptyOrSpaces(str) {
            return str == '' || str === null || str.match(/^ *$/) !== null;
        }

        function escAttr(str) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '`': '&#96;'
            };
            return String(str).replace(/[&<>"'`]/g, function(match) {
                return map[match];
            });
        }

        function addShortcodeEntry(shortcode, position) {
            var new_entry = '<tr class="shortcode-entry">';
            new_entry += '<td class="shortcode">' + escAttr(shortcode) + '</td>';
            new_entry += '<td class="position">' + position.charAt(0).toUpperCase() + position.slice(1) + '</td>';
            new_entry += '<td class="action"><input name="shortcodes[shortcode][]" id="shortcodes-shortcode" type="hidden" value="' + escAttr(shortcode) + '"><input name="shortcodes[position][]" id="shortcodes-position" type="hidden" value="' + position + '"><span class="delete-shortcode">' + $('.ppma-boxes-shortcodes-wrap .shortcode-entries .add-new-shortcode').attr('data-delete') +'</span></td>';
            new_entry += '</tr>';
            $('.ppma-boxes-shortcodes-wrap .shortcode-form .shortcodes-shortcode-input').val('');
            $('.ppma-boxes-shortcodes-wrap .shortcode-form').after(new_entry);
        }

    });

})(jQuery);
