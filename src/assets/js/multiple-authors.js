/**
 * @package   MultipleAuthors
 * @author    PublishPress <help@publishpress.com>
 * @copyright Copyright (C) 2018 PublishPress. All rights reserved.
 * @license   GPLv2 or later
 * @since     1.0.0
 */
jQuery(document).ready(function ($) {
    // Copied from ExtJS.
    window.htmlEnDeCode = (function () {
        var charToEntityRegex, entityToCharRegex, charToEntity, entityToChar;

        function resetCharacterEntities() {
            charToEntity = {};
            entityToChar = {};
            // add the default set
            addCharacterEntities({
                "&amp;": "&",
                "&gt;": ">",
                "&lt;": "<",
                "&quot;": '"',
                "&#39;": "'"
            });
        }

        function addCharacterEntities(newEntities) {
            var charKeys = [],
                entityKeys = [],
                key,
                echar;
            for (key in newEntities) {
                echar = newEntities[key];
                entityToChar[key] = echar;
                charToEntity[echar] = key;
                charKeys.push(echar);
                entityKeys.push(key);
            }
            charToEntityRegex = new RegExp("(" + charKeys.join("|") + ")", "g");
            entityToCharRegex = new RegExp(
                "(" + entityKeys.join("|") + "|&#[0-9]{1,5};" + ")",
                "g"
            );
        }

        function htmlEncode(value) {
            var htmlEncodeReplaceFn = function (match, capture) {
                return charToEntity[capture];
            };

            return !value
                ? value
                : String(value).replace(charToEntityRegex, htmlEncodeReplaceFn);
        }

        function htmlDecode(value) {
            var htmlDecodeReplaceFn = function (match, capture) {
                return capture in entityToChar
                    ? entityToChar[capture]
                    : String.fromCharCode(parseInt(capture.substr(2), 10));
            };

            return !value
                ? value
                : String(value).replace(entityToCharRegex, htmlDecodeReplaceFn);
        }

        resetCharacterEntities();

        return {
            htmlEncode: htmlEncode,
            htmlDecode: htmlDecode
        };
    })();

    //==================================================================
    /**
     * Based on Bylines.
     */
    function authorsSelect2(selector) {
        selector.each(function () {
            var authorsSearch = $(this).ppma_select2({
                placeholder: $(this).data("placeholder"),
                allowClear: true,
                ajax: {
                    url:
                        window.ajaxurl +
                        "?action=authors_search&nonce=" +
                        $(this).data("nonce"),
                    dataType: "json",
                    data: function (params) {
                        var ignored = [];
                        selector
                            .closest("div")
                            .find(".authors-list input.author_term")
                            .each(function () {
                                ignored.push($(this).val());
                            });
                        return {
                            q: params.term,
                            ignored: ignored
                        };
                    }
                }
            });
            authorsSearch.on("ppma_select2:select", function (e) {
                var template = wp.template("authors-author-partial");
                if ($('.authors-list.authors-category-' + e.params.data.category_id).length) {
                    $('.authors-list.authors-category-' + e.params.data.category_id).append(
                        window.htmlEnDeCode.htmlDecode(template(e.params.data))
                    );
                } else {
                    $(".authors-list:first").append(
                        window.htmlEnDeCode.htmlDecode(template(e.params.data))
                    );
                }
                authorsSearch.val(null).trigger("change");
                handleUsersAuthorField();
                handleAuthorCategory();
            });
        });
    }

    function hasSelectedOnlyGuests(parent) {
        if (typeof parent === 'undefined' || parent.length == 0) {
            parent = $('.authors-list');
        }

        let selectedAuthors = $(parent).find('li');
        let guestAuthorsCount = 0;

        for (let i = 0; i < selectedAuthors.length; i++) {
            if ($(selectedAuthors.get(i)).data('is-guest') == 1) {
                guestAuthorsCount++;
            }
        }

        return guestAuthorsCount === selectedAuthors.length;
    }

    function handleUsersAuthorField(parent) {
        let $authorsUserField = $('#publishpress-authors-user-author-wrapper');
        if (hasSelectedOnlyGuests(parent)) {
            $authorsUserField.show();
        } else {
            $authorsUserField.hide();
        }
    }

    function handleAuthorCategory() {
        let $authorsCategoryId = '';
        let $authorsCategoryTerm = '';
        $('.authors-list').each(function () {
            if ($(this).children().length === 1) {
                $(this).find('.sortable-placeholder').show();
            } else {
                $(this).find('.sortable-placeholder').hide();
            }
            $authorsCategoryId = $(this).attr('data-category_id');
            $(this).find('.author_categories').each(function () {
                $authorsCategoryTerm = $(this).closest('li').find('.author_term').val();
                $(this).attr('name', 'author_categories[' + $authorsCategoryTerm + ']');
                $(this).val($authorsCategoryId);
            });
        });
    }

    function authorsUserSelect2(selector) {
        selector.each(function () {
            var authorsSearch = $(this).ppma_select2({
                placeholder: $(this).data("placeholder"),
                allowClear: true,
                ajax: {
                    url:
                        window.ajaxurl +
                        "?action=authors_users_search&nonce=" +
                        $(this).data("nonce"),
                    dataType: "json",
                    data: function (params) {
                        return {
                            q: params.term,
                            ignored: []
                        };
                    }
                }
            });
        });
    }

    function authorsPostSearchSelect2(selector) {
        selector.each(function () {
            var postsSearch = $(this).ppma_select2({
                placeholder: $(this).data("placeholder"),
                allowClear: $(this).data("allow-clear"),
                ajax: {
                    url:
                        window.ajaxurl +
                        "?action=authors_filter_posts_search&nonce=" +
                        $(this).data("nonce") + "&post_type=" +
                        $(this).data("post_type"),
                    dataType: "json",
                    data: function (params) {
                        return {
                            q: params.term
                        };
                    }
                }
            });
        });
    }

    function authorsUserTermIdSelect2(selector) {
        selector.each(function () {
            var authorsSearch = $(this).ppma_select2({
                placeholder: $(this).data("placeholder"),
                allowClear: true,
                ajax: {
                    url:
                        window.ajaxurl +
                        "?action=authors_filter_authors_search&field=term_id&nonce=" +
                        $(this).data("nonce"),
                    dataType: "json",
                    data: function (params) {
                        return {
                            q: params.term,
                            ignored: []
                        };
                    }
                }
            });
        });
    }

    function authorsUserSlugSelect2(selector) {
        selector.each(function () {
            var authorsSearch = $(this).ppma_select2({
                placeholder: $(this).data("placeholder"),
                allowClear: true,
                ajax: {
                    url:
                        window.ajaxurl +
                        "?action=authors_filter_authors_search&field=slug&nonce=" +
                        $(this).data("nonce"),
                    dataType: "json",
                    data: function (params) {
                        return {
                            q: params.term,
                            ignored: []
                        };
                    }
                }
            });
        });
    }

    if ($("body").hasClass("post-php") || $("body").hasClass("post-new-php")) {
        authorsSelect2($(".authors-select2.authors-search"));
        authorsUserSelect2($('.authors-user-search'));
        sortedAuthorsList($(".authors-current-user-can-assign"));
        handleUsersAuthorField();
        if ($('.authors-user-slug-search').length > 0) {
            authorsUserSlugSelect2($('.authors-user-slug-search'));
        }
    }
    if ($('.authors-user-term-id-search').length > 0) {
        authorsUserTermIdSelect2($('.authors-user-term-id-search'));
    }

    if ($("body").hasClass("post-php") || $("body").hasClass("post-new-php")  || $("body").hasClass("edit-php")) {
        //prevent deletion of default field
            var default_fields = ['first_name', 'last_name', 'user_email', 'user_url'];
        if ($('input[name="ppmacf_slug"]').length > 0) {
            if (default_fields.includes($('input[name="ppmacf_slug"]').val())) {
                $('input[name="ppmacf_slug"]').attr('readonly', true);
                $('select[name="ppmacf_type"] option:not(:selected)').attr('disabled', true);
                $('#submitdiv .edit-post-status').hide();
                $('#submitdiv .edit-visibility').hide();
                $('#submitdiv .edit-timestamp').hide();
                $('#major-publishing-actions #delete-action').hide();
            }
        }
        if ($('body.edit-php.post-type-ppmacf_field table.wp-list-table tbody tr').length > 0) {
            $('body.edit-php.post-type-ppmacf_field table.wp-list-table tbody tr').each(function () {
                var current_slug = $(this).find('td.column-slug').html();
                if (default_fields.includes(current_slug)) {
                    $(this).find('.check-column input').attr('disabled', true);
                    // 1. Hide .row-actions .trash
                    $(this).find('.column-primary .row-actions .trash').hide();
                    // 2. Remove the separator (|) after Edit button inside the .edit span
                    $(this).find('.column-primary .row-actions .edit').contents().filter(function() {
                        return this.nodeType === 3; // Filter out text nodes
                      }).remove();
                }
            });
        }
        if ($('.ppma-authors-post-search').length > 0) {
            authorsPostSearchSelect2($('.ppma-authors-post-search'));
        }
    }

    if ($("body").hasClass("edit-php")) {
        authorsUserSlugSelect2($('.authors-user-slug-search'));
    }

    /****************
     * Quick Edit
     ****************/
    $(document).on('click', '.editinline', function () {
        var postId = $(this)
            .closest('tr')
            .attr('id')
            .replace('post-', '')
            .trim();

        var timeoutFn = setTimeout(function () {
            var $quickEditTr = $('#edit-' + postId);
            var $select = $quickEditTr.find('.authors-select2.authors-search');
            var $authorsList = $quickEditTr.find('.authors-current-user-can-assign');
            var $usersList = $quickEditTr.find('.authors-user-search');
            var $authorList = '';

            authorsSelect2($select);
            authorsUserSelect2($usersList);
            $authorsList.find("li:not(.sortable-placeholder)").remove();

            $('#post-' + postId)
                .find('td.column-authors > a.author_name')
                .each(function () {
                    var listItemTmpl = wp.template("authors-author-partial");
                    if ($quickEditTr.find('.authors-current-user-can-assign.authors-category-' + $(this).data('author-category-id')).length > 0) {
                        $authorList = $quickEditTr.find('.authors-current-user-can-assign.authors-category-' + $(this).data('author-category-id'));
                    } else {
                        $authorList = $quickEditTr.find('.authors-current-user-can-assign:first');
                    }

                    $authorList.append(
                        window.htmlEnDeCode.htmlDecode(
                            listItemTmpl({
                                'display_name': $(this).data('author-display-name'),
                                'id': $(this).data('author-term-id'),
                                'is_guest': $(this).data('author-is-guest')
                            })
                        )
                    );
                });

            sortedAuthorsList($authorsList);
            $select.val(null).trigger('change');
            handleUsersAuthorField();
            handleAuthorCategory();

            clearTimeout(timeoutFn);
        }, 50);
    });

    /**************
     * Bulk edit
     *************/
    $("#wpbody").on("click", "#doaction, #doaction2", function () {
        var action = $(this).is("#doaction")
            ? $("#bulk-action-selector-top").val()
            : $("#bulk-action-selector-bottom").val();
        if (action === "edit") {
            authorsSelect2($("#bulk-edit .authors-select2.authors-search"));
            sortedAuthorsList($("#bulk-edit .authors-current-user-can-assign"));
            authorsUserSelect2($("#bulk-edit .authors-user-search"));
            handleUsersAuthorField();
            handleAuthorCategory();
            $("#bulk-edit .authors-current-user-can-assign")
                .find("li:not(.sortable-placeholder)")
                .each(function () {
                    $(this).remove();
                });
        }
    });

    // Apply ajax request on bulk edit.
    $(document).on("click", "#bulk_edit", function () {
        // define the bulk edit row
        var $bulk_row = $("#bulk-edit");

        // get the selected post ids that are being edited
        var $post_ids = new Array();
        $bulk_row
            .find("#bulk-titles")
            .children()
            .each(function () {
                var new_id = Number($(this)
                    .attr("id")
                    .replace(/^(ttle)/i, ""));
                if (new_id > 0) {
                    $post_ids.push(new_id);
                }
            });

        if (!$post_ids.length) {
            $bulk_row
                .find("#bulk-titles .ntdelitem .button-link")
                .each(function () {
                    var new_id = Number($(this)
                        .attr("id")
                        .replace(/\D/g,''));
                    if (new_id > 0) {
                        $post_ids.push(new_id);
                    }
                });
        }

        // get the data
        var selectedAuthors = [];
        var selectedAuthorCategories = {};
        var selectedVal = '';
        $bulk_row.find(".authors-list input.author_term").each(function () {
            selectedVal = parseInt($(this).val());
            selectedAuthors.push(selectedVal);
            selectedAuthorCategories[selectedVal] = $(this).closest('ul').attr('data-category_id');
        });

        var selectedFallbackUser = $('#publishpress-authors-user-author-select').val();

        // save the data
        $.ajax({
            url: ajaxurl,
            type: "POST",
            async: false,
            cache: false,
            data: {
                action: "save_bulk_edit_authors",
                post_ids: $post_ids, // and these are the 2 parameters we're passing to our function
                authors_ids: selectedAuthors,
                author_categories: selectedAuthorCategories,
                fallback_author_user: selectedFallbackUser,
                bulkEditNonce: bulkEditNonce.nonce
            }
        });
    });

    function sortedAuthorsList(selector) {
        selector.sortable({
            connectWith: ".authors-list",
            items: "> li:not(.no-drag)",
            placeholder: "sortable-placeholder",
            update: function (event, ui) {
                handleAuthorCategory();
            },
            receive: function (event, ui) {
                $(this).find('.sortable-placeholder').hide();
            },
            remove: function (event, ui) {
                if ($(this).children().length === 1) {
                    $(this).find('.sortable-placeholder').show();
                }
            },
        }).on("click", ".author-remove", function () {
            var el = $(this);
            el.closest("li").remove();
            handleUsersAuthorField($(this).parent('.authors-list'));
        });
    }

    $(".authors-select2-user-select").each(function () {
        $(this).ppma_select2({
            allowClear: true,
            placeholder: $(this).attr("placeholder"),
            ajax: {
                url:
                    window.ajaxurl +
                    "?action=authors_users_search&nonce=" +
                    $(this).data("nonce"),
                dataType: "json",
                data: function (params) {
                    return {
                        q: params.term
                    };
                }
            }
        });
    });

    $(".authors-select2-default-select").each(function () {
        $(this).ppma_select2({
            placeholder: $(this).attr("placeholder")
        });
    });

    $(".author-image-field-wrapper").each(function () {
        var frame,
            target = $(this), // Your meta box id here
            deleteImgLink = target.find(".select-author-image-field"),
            delImgLink = target.find(".delete-author-image-field"),
            imgContainer = target.find(".author-image-field-container"),
            imgIdInput = target.find(".author-image-field-id");

        deleteImgLink.on("click", function (event) {
            event.preventDefault();

            if (frame) {
                frame.open();
                return;
            }
            frame = wp.media({
                // title: 'title',
                // button: {
                //     text: 'select'
                // },
                multiple: false,
                library: {
                    type: "image"
                }
            });
            frame.on("select", function () {
                var attachment = frame
                    .state()
                    .get("selection")
                    .first()
                    .toJSON();
                var attachment_src =
                    "undefined" === typeof attachment.sizes.thumbnail
                        ? attachment.url
                        : attachment.sizes.thumbnail.url;
                var imgEl = $("<img />");
                imgEl.attr("src", attachment_src);
                imgContainer.append(imgEl);
                imgIdInput.val(attachment.id);
                deleteImgLink.addClass("hidden");
                delImgLink.removeClass("hidden");
            });

            frame.open();
        });

        delImgLink.on("click", function (event) {
            event.preventDefault();
            imgContainer.html("");
            deleteImgLink.removeClass("hidden");
            delImgLink.addClass("hidden");
            imgIdInput.val("");
        });
    });
    //==================================================================

    // Fix the admin menu selection for Authors.
    // phpcs:disable
    if (
        (window.location.pathname === "/wp-admin/edit-tags.php" ||
            window.location.pathname === "/wp-admin/term.php") &&
        window.location.search.search("taxonomy=author") === 1
    ) {
        $("#menu-posts")
            .removeClass("wp-has-current-submenu wp-menu-open")
            .addClass("wp-not-current-submenu");
        $("#menu-posts > a")
            .removeClass("wp-current-submenu wp-has-current-submenu wp-menu-open")
            .addClass(
                "wp-not-current-submenu wp-menu-open open-if-no-js menu-top-first"
            );
        $("#toplevel_page_" + MultipleAuthorsStrings.menu_slug)
            .removeClass("wp-not-current-submenu")
            .addClass(
                "wp-has-current-submenu wp-menu-open toplevel_page_" +
                MultipleAuthorsStrings.menu_slug
            );
        $("#toplevel_page_" + MultipleAuthorsStrings.menu_slug + " > a")
            .removeClass("wp-not-current-submenu")
            .addClass(
                "wp-has-current-submenu wp-menu-open open-if-no-js menu-top-first"
            );
    }
    // phpcs:enable

    var $mappedUser = $('select[name="authors-user_id"]');
    var $slug = $("#slug");

    // Add action to the Mapped User field in the Author form.
    if ($mappedUser.length > 0) {
        // Disable the slug field if there is a mapped user.
        $slug.attr("disabled", $mappedUser.val() !== "");

        // Fix the order of fields
        $(
            $slug
                .parent()
                .parent()
                .before($mappedUser.parent().parent())
        );

        $mappedUser.on("change", function (event) {
            var selected = $mappedUser.val();

            // Update the status of the slug field
            $slug.attr("disabled", $mappedUser.val() !== "");

            if (selected === "") {
                return;
            }

            $.getJSON(
                MultipleAuthorsStrings.ajax_get_author_data_url,
                {
                    user_id: selected
                },
                function (data) {
                    var fields = ["first_name", "last_name", "user_email", "user_url"];

                    $.each(fields, function (i, item) {
                        var $field = $('input[name="authors-' + item + '"]');
                        if ($field.val() === "") {
                            $field.val(data[item]);
                        }
                    });

                    var $field = $('textarea[name="authors-description"]');
                    if ($field.val() === "") {
                        setEditorContentIfEmpty('authors-description', data.description);
                    }

                    // Slug always change to be in sync
                    $slug.val(data.slug);
                }
            );
        });
    }

    function setEditorContentIfEmpty(editorId, content) {
        var editor = tinymce.get(editorId);

        if (editor) {
            var currentContent = editor.getContent().trim();
            if (!currentContent) {
                editor.setContent(content);
            }
        } else {
            var textarea = $('#' + editorId);
            if (!textarea.val().trim()) {
                textarea.val(content);
            }
        }
    }

    // Add action to the Mapped User Field in the New Author form.
    $mappedUser = $(".taxonomy-author .authors-select2-user-select");

    if ($mappedUser.length > 0) {
        $mappedUser.on("change", function () {
            if ($("#tag-name").val() == "") {
                $("#tag-name").val(
                    $mappedUser[0].options[$mappedUser[0].selectedIndex].text
                );
            }
        });
    }

    // Reset the field after the form was submitted.
    $("#submit").click(function (event) {
        window.setTimeout(function () {
            $mappedUser.val("").trigger("change");
            $("#tag-name").focus();
        }, 1000);

        return true;
    });

    /**
     * Displays a confirmation popup before clicking on the restore authors buttons.
     */

    var buttons = [
        "#create_post_authors",
        "#create_role_authors",
        "#delete_mapped_authors",
        "#delete_guest_authors"
    ];
    var msg;

    $.each(buttons, function (index, item) {
        $(item).click(function (event) {
            msg = "confirm_" + item.replace("#", "");
            if (confirm(MultipleAuthorsStrings[msg])) {
                return true;
            }

            event.preventDefault();
            return false;
        });
    });

    if ($('body').hasClass('taxonomy-author')) {
        /**
         * Add tab class to author editor's tr without tab
         *
         * This will add general tab class to 'Name' and Author URL
         * or any tab that's rendered by default or third party
         *  without tab attribute
         */
        $('form#edittag tr.form-field:not(.ppma-tab-content)')
            .addClass('ppma-tab-content ppma-general-tab')
            .attr('data-tab', 'general');

        /**
         * Add view link to author url field
         */
        $('form#edittag tr.form-field #slug').after('<a href="' + MultipleAuthorsStrings.term_author_link + '" class="button-secondary" target="_blank">' + MultipleAuthorsStrings.view_text + '</a>');

        /**
         * Update name field
         */
        $('form#edittag tr.form-field.term-name-wrap th label').html(MultipleAuthorsStrings.name_label);
        $('form#addtag .form-field.term-name-wrap label').html(MultipleAuthorsStrings.new_name_label);

        /**
         * Add required to display name field
         */
        $('form#edittag tr.form-field.term-name-wrap').addClass('required-tab');
        $('form#edittag tr.form-field.term-name-wrap th label').after(' <span class="required">*</span>');

        /**
         * Update display name options on input changed
         * if display name format is set by admin
         */
        var display_name_format = MultipleAuthorsStrings.display_name_format;
        var custom_display_name = false;
        if (display_name_format == 'custom') {
            custom_display_name = true;
        } else {
            var display_name_input       = $( '#name' );
            var first_name_input          = $( '#authors-first_name' );
            var last_name_input          = $( '#authors-last_name' );
            if ( display_name_input.length
                && (
                    display_name_format === 'first_name_last_name' && first_name_input.length && last_name_input.length
                    || display_name_format === 'last_name_first_name' && first_name_input.length && last_name_input.length
                    || display_name_format === 'first_name' && first_name_input.length
                    || display_name_format === 'last_name' && last_name_input.length
                    )
                ) {
                display_name_input.addClass('ppma-display-name-fields');
                var new_display_name_field    = display_name_input.clone(true);
                new_display_name_field.insertAfter('#slug');
                display_name_input.removeAttr('name id').prop('disabled', true);
                $( '#name' ).hide();

                $('#authors-first_name, #authors-last_name ').on( 'input', function() {
                    var display_name_value = '';

                    if (display_name_format === 'first_name_last_name') {
                        display_name_value = first_name_input.val() + ' ' + last_name_input.val();
                    } else if (display_name_format === 'last_name_first_name') {
                        display_name_value = last_name_input.val() + ' ' + first_name_input.val();
                    } else if (display_name_format === 'first_name') {
                        display_name_value = first_name_input.val();
                    } else if (display_name_format === 'last_name') {
                        display_name_value = last_name_input.val();
                    }

                    if (isEmptyOrSpaces(display_name_value)) {
                        display_name_value = MultipleAuthorsStrings.author_user_login;
                    }
                    $('.ppma-display-name-fields').val(display_name_value);
                });
                $('#authors-first_name, #authors-last_name ').trigger("input");
            } else {
                custom_display_name = true;
            }
        }

        if (custom_display_name) {
            /**
             * Update name input to select
             */
            $('form#edittag tr.form-field.term-name-wrap td input#name').replaceWith(MultipleAuthorsStrings.display_name_html);

            /**
             * Update display name options on input changed
             */
            var display_name_select       = $( '#name' );
            if ( display_name_select.length ) {
                $('#authors-first_name, #authors-last_name, #authors-nickname').on( 'change', function() {
                    var dub = [],
                        inputs = {
                            display_nickname  : MultipleAuthorsStrings.author_details.nickname || '',
                            display_username  : MultipleAuthorsStrings.author_details.user_login || '',
                            display_firstname : $('#authors-first_name').val() || '',
                            display_lastname  : $('#authors-last_name').val() || ''
                        };

                    if ( inputs.display_firstname && inputs.display_lastname ) {
                        inputs.display_firstlast = inputs.display_firstname + ' ' + inputs.display_lastname;
                        inputs.display_lastfirst = inputs.display_lastname + ' ' + inputs.display_firstname;
                    }

                    $.each( $('option', display_name_select), function( i, el ){
                        dub.push( el.value );
                    });

                    $.each(inputs, function( id, value ) {
                        if ( ! value ) {
                            return;
                        }

                        var val = value.replace(/<\/?[a-z][^>]*>/gi, '');

                        if ( inputs[id].length && $.inArray( val, dub ) === -1 ) {
                            dub.push(val);
                            $('<option />', {
                                'text': val
                            }).appendTo( display_name_select );
                        }
                    });
                });
            }
        }

    }

    /**
     * Author button group click
     */
    $(document).on("click", ".ppma-button-group label", function () {
        var current_button = $(this);
        var target_value   = current_button.find('input').val();
        var button_group   = current_button.closest('.ppma-button-group');

        //remove active class
        button_group.find('label.selected').removeClass('selected');
        //hide descriptions
        button_group.closest('.ppma-group-wrap').find('.ppma-button-description').hide();
        //add active class to current select
        current_button.addClass('selected');
        //show selected tab descriptions
        button_group.closest('.ppma-group-wrap').find('.ppma-button-description.' + target_value).show();

        // hide/show fields
        if (target_value !== 'existing_user') {
            $('.form-field.term-user_id-wrap').hide();
        } else {
            $('.form-field.term-user_id-wrap').show();
        }
        if (target_value !== 'new_user') {
            $('.form-field.term-author_email-wrap').hide();
        } else {
            $('.form-field.term-author_email-wrap').show();
        }
    });

    /**
     * Author editor tab switch
     */
    $(document).on('click', '.ppma-editor-tabs a', function (event) {

        event.preventDefault();

        var clicked_tab = $(this).attr('data-tab');

        //remove active class from all tabs
        $('.ppma-editor-tabs a').removeClass('nav-tab-active');
        //add active class to current tab
        $(this).addClass('nav-tab-active');

        //hide all tabs contents
        $('.ppma-tab-content').hide();
        //show this current tab contents
        $('.ppma-' + clicked_tab + '-tab').show();

        // Make sure the description field is hidden. It was being displayed after navigating throw tabs.
        $('.form-field.term-description-wrap').hide();
    });

    /**
     * Author image avatar source option toggle
     */
    $(document).on('click', 'input[name="authors-avatar-options"]', function () {
        var clicked_element = $(this);

        if (clicked_element.val() === 'custom_image') {
            clicked_element.closest('tr').find('.author-image-field-wrapper').show();
        } else {
            //trigger image remove action
            clicked_element.closest('tr').find('.delete-author-image-field').trigger('click');
            //hide image field wrapper
            clicked_element.closest('tr').find('.author-image-field-wrapper').hide();
        }
    });

    /**
     * Switch focus to general email from image tab handler
     */
    $(document).on('click', '.ppma-image-general-author-focus', function (event) {
        event.preventDefault();
        //triger click on general tab
        $('.ppma-editor-tabs a[data-tab="general"]').trigger('click');
        //set focus on email field
        $('input[name="authors-user_email"]').focus();
    });

    //process a request to validate author mapped user.
    $('body.taxonomy-author form#edittag').submit(function (event) {

        var $mappedUser = $('select[name="authors-user_id"]').val();
        var $authorSlug = $('input[name="slug"]').val();
        var $termId = $('input[name="tag_ID"]').val();
        var $form = $(this);

        $('.author-response-notice').remove();
        $('form#edittag tr.form-field').removeClass('form-invalid');

        event.preventDefault();

        //validate required fields
        var field_label,
        field_object,
        field_error_count = 0,
        field_error_message = '<div style="color:red;">' + MultipleAuthorsStrings.isRequiredWarning + '</div><ul>';

        $.each($('form#edittag tr.form-field.required-tab'), function (i, field) {
            field_object = $(this).find('td input');
            if (field_object.length === 0) {
                field_object = $(this).find('td select');
            }
            if (field_object.length === 0) {
                field_object = $(this).find('td textarea');
            }
            if (isEmptyOrSpaces(field_object.val())) {
                field_label = field_object.closest('tr').addClass('form-invalid').find('label').html();
                field_error_count = 1;
                field_error_message += '<li>' + field_label + ' ' + MultipleAuthorsStrings.isRequired + ' <span class="required">*</span></li>';
            }
        });
        field_error_message += '</ul>';

        if (field_error_count > 0) {
            $('.ppma-thickbox-modal-content').html(field_error_message);
            $('.ppma-required-field-thickbox-botton').trigger('click');
          return;
        }

        //prepare ajax data
        var data = {
            action: "mapped_author_validation",
            author_id: $mappedUser,
            author_slug: $authorSlug,
            term_id: $termId,
            nonce: MultipleAuthorsStrings.mapped_author_nonce,
        };

        if ($('.author-loading-spinner').length === 0) {
            $('.edit-tag-actions input[type="submit"]').after('<div class="author-loading-spinner spinner is-active" style="float: none;"></div>');
        }

        $('.author-loading-spinner').addClass('is-active');

        $.post(ajaxurl, data, function (response) {
            if (response.status === 'error') {
                $('.edit-tag-actions').after('<div class="author-response-notice notice notice-error" style="margin-top: 10px;"><p> ' + response.content + ' </p></div>');
                $('.author-loading-spinner').removeClass('is-active');
            } else {
                $form.unbind('submit').submit();
            }
        });

    });

    //prevent custon field submission if title is empty.
    $('body.post-type-ppmacf_field form#post').submit(function (event) {

        if (isEmptyOrSpaces($('input[name="post_title"]').val())) {
            event.preventDefault();
            var field_error_message = '<div style="color:red;">' + MultipleAuthorsStrings.isRequiredWarning + '</div><ul>';
            field_error_message += '<li>' + MultipleAuthorsStrings.fieldTitleRequired + ' <span class="required">*</span></li>';
            field_error_message += '</ul>';
            $('.ppma-thickbox-modal-content').html(field_error_message);
            $('.ppma-general-thickbox-botton').trigger('click');
          return;
        }

    });

    //change submit button to enable slug generation on custom button click
    if ($('body.taxonomy-author form#addtag #submit').length > 0) {
        var buttonTimeoutFn = setTimeout(function () {
            $('body.taxonomy-author form#addtag #submit').hide();
            $('body.taxonomy-author form#addtag #submit').after('<input type="button" id="author-submit" class="button button-primary" value="' + MultipleAuthorsStrings.new_button + '">');
            clearTimeout(buttonTimeoutFn);
        }, 50);
    }

    //generate author slug when adding author.
    $(document).on('click', 'body.taxonomy-author form#addtag #author-submit', function (event) {

        var $authorName = $('input[name="tag-name"]').val();
        var $form = $(this).closest('form#addtag');

        event.preventDefault();

        //prepare ajax data
        var data = {
            action: "handle_author_slug_generation",
            author_name: $authorName,
            nonce: MultipleAuthorsStrings.generate_author_slug_nonce,
        };

        $form.find('.spinner').addClass('is-active');

        $.post(ajaxurl, data, function (response) {
            $form.find('.spinner').removeClass('is-active');
            if (response.author_slug) {
                $('input[name="slug"]').val(response.author_slug);
                $('body.taxonomy-author form#addtag #submit').trigger('click');
            } else {
                $('body.taxonomy-author form#addtag #submit').trigger('click');
            }
        });

    });

    /**
     * Settings shortcode copy to clipboard
     */
    $(document).on('click', '.ppma-copy-clipboard', function (event) {
        //get the text field
        var shortcode_input = event.target.closest('.ppma-settings-shortcodes-shortcode').querySelector('.shortcode-field');
        //select the text field
        shortcode_input.select();
        shortcode_input.setSelectionRange(0, 99999); /* For mobile devices */
        //copy the text inside the text field
        navigator.clipboard.writeText(shortcode_input.value);
        //update tooltip notification
        event.target.closest('.ppma-settings-shortcodes-shortcode')
            .querySelector('.ppma-copy-clipboard span')
            .innerHTML = event.target.closest('.ppma-settings-shortcodes-shortcode').querySelector('.ppma-copy-clipboard span')
                .getAttribute('data-copied');
    });

    /**
     * Copy to clipboard copied text change
     */
    $(document).on('mouseleave', '.ppma-copy-clipboard', function (event) {
        //update tooltip text
        event.target.closest('.ppma-settings-shortcodes-shortcode')
            .querySelector('.ppma-copy-clipboard span')
            .innerHTML = event.target.closest('.ppma-settings-shortcodes-shortcode').querySelector('.ppma-copy-clipboard span')
                .getAttribute('data-copy');
    });

    /**
     * Author profile edit active class for when
     * user is editing own profile.
     *
     * i.) Remove active class from main author
     * profile if it has one .
     *
     * ii.) Add active class to new author profile
     * link
     */
    if ($('body').hasClass('own-profile-edit')) {
        var main_menu   = $("#toplevel_page_ppma-authors");
        var profile_menu = $("li[class*=' toplevel_page_term?taxonomy=author&tag_ID']");

        //remove active from main author menu
        main_menu
            .addClass('wp-not-current-submenu')
            .removeClass('wp-has-current-submenu')
            .removeClass('wp-menu-open')
            .removeClass('current')
            .find('ul li.current')
            .removeClass('current');

        //add class to user author menu
        profile_menu
            .removeClass('wp-not-current-submenu')
            .addClass('current');

        profile_menu
            .find('a')
            .removeClass('wp-not-current-submenu')
            .addClass('current');

    }

    if ($("body").hasClass("post-type-ppmacf_field") && $("#ppmacf_type").length > 0) {
        showHideSocialProfileField();
    }

    /**
     * Fix Authors menu and admin menu conflict
     */
    if ($('#adminmenu a[href="ppma-authors"]').length > 0) {
        // change menu link
        $('#adminmenu a[href="ppma-authors"]').attr('href', MultipleAuthorsStrings.author_menu_link);
        // remove duplicate authors
        $('#adminmenu ul.wp-submenu a[href="edit-tags.php?taxonomy=author"]').closest('li').remove();
    }

    /**
     * Show or Hide profile field on profile type change
     */
    $(document).on('change', '#ppmacf_type', function () {
        showHideSocialProfileField();
    });

    function showHideSocialProfileField() {
        var selectedType = $("#ppmacf_type").val();
        if (selectedType === 'url') {
            $('.cmb2-id-ppmacf-social-profile').show();
            $('.cmb2-id-ppmacf-rel').show();
            $('.cmb2-id-ppmacf-target').show();
        } else {
            $('.cmb2-id-ppmacf-social-profile').hide();
            $('.cmb2-id-ppmacf-rel').hide();
            $('.cmb2-id-ppmacf-target').hide();
        }
    }

    function isEmptyOrSpaces(str) {
      return str == '' || str === null || str.match(/^ *$/) !== null;
    }

});

if (typeof console === "undefined") {
    var console = {};
    console.log = console.error = function () {
    };
}
