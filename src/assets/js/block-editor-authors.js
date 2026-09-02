(function (wp, $) {
    if (!wp || !wp.plugins || !wp.element || !wp.components || !$) {
        return;
    }

    var settings = window.PublishPressAuthorsBlockEditor || {};
    var PluginDocumentSettingPanel = wp.editor && wp.editor.PluginDocumentSettingPanel
        ? wp.editor.PluginDocumentSettingPanel
        : wp.editPost && wp.editPost.PluginDocumentSettingPanel;
    var createElement = wp.element.createElement;
    var useEffect = wp.element.useEffect;
    var useRef = wp.element.useRef;
    var useState = wp.element.useState;
    var Spinner = wp.components.Spinner;
    var Notice = wp.components.Notice;
    var postSavingLock = 'publishpress-authors-block-editor';

    if (!PluginDocumentSettingPanel) {
        return;
    }

    function getSelect2Language(strings) {
        strings = strings || {};

        function getString(key) {
            return strings[key] || '';
        }

        function formatString(key, count) {
            return getString(key).replace('%d', count);
        }

        return {
            errorLoading: function () {
                return getString('error_loading');
            },
            inputTooLong: function (args) {
                var overChars = args.input.length - args.maximum;
                var key = overChars === 1 ? 'input_too_long_single' : 'input_too_long_plural';

                return formatString(key, overChars);
            },
            inputTooShort: function (args) {
                var remainingChars = args.minimum - args.input.length;
                var key = remainingChars === 1 ? 'input_too_short_single' : 'input_too_short_plural';

                return formatString(key, remainingChars);
            },
            loadingMore: function () {
                return getString('loading_more');
            },
            maximumSelected: function (args) {
                var key = args.maximum === 1 ? 'maximum_selected_single' : 'maximum_selected_plural';

                return formatString(key, args.maximum);
            },
            noResults: function () {
                return getString('no_results');
            },
            searching: function () {
                return getString('searching');
            },
            removeAllItems: function () {
                return getString('remove_all_items');
            }
        };
    }

    function withSelect2Language(options) {
        return $.extend(true, {}, {language: getSelect2Language(settings.select2_i18n)}, options);
    }

    function htmlDecode(value) {
        if (window.htmlEnDeCode && window.htmlEnDeCode.htmlDecode) {
            return window.htmlEnDeCode.htmlDecode(value);
        }

        return $('<textarea />').html(value).text();
    }

    function canInitializeSelection() {
        return $.fn.ppma_select2;
    }

    function waitForSelectionDependencies(callback, attempts) {
        attempts = attempts || 0;

        if (canInitializeSelection()) {
            callback();
            return;
        }

        if (attempts >= 30) {
            return;
        }

        window.setTimeout(function () {
            waitForSelectionDependencies(callback, attempts + 1);
        }, 100);
    }

    function hasAuthorsSelectionMarkup(container) {
        var $container = $(container);
        return $container.find(".authors-select2.authors-search").length > 0
            && $container.find(".authors-list").length > 0;
    }

    function authorExistsInCategory($authorsList, authorId, $excludeAuthorItem) {
        var exists = false;
        var authorIdValue = String(authorId);

        $authorsList.find("li:not(.sortable-placeholder) input.author_term").each(function () {
            var $authorItem = $(this).closest("li");

            if ($excludeAuthorItem && $authorItem.is($excludeAuthorItem)) {
                return;
            }

            if (String($(this).val()) === authorIdValue) {
                exists = true;
                return false;
            }
        });

        return exists;
    }

    function getAvailableCategoryList($context, authorData) {
        var $targetList = $context.find(".authors-list.authors-category-" + authorData.category_id).first();

        if (settings.allow_author_multiple_categories !== 'yes') {
            if (!$targetList.length) {
                $targetList = $context.find(".authors-list:first");
            }

            if ($targetList.length && authorExistsInCategory($targetList, authorData.id)) {
                return $();
            }

            return $targetList;
        }

        if ($targetList.length && !authorExistsInCategory($targetList, authorData.id)) {
            return $targetList;
        }

        $targetList = $();
        $context.find(".authors-list").each(function () {
            var $authorsList = $(this);

            if (!authorExistsInCategory($authorsList, authorData.id)) {
                $targetList = $authorsList;
                return false;
            }
        });

        return $targetList;
    }

    function handleUsersAuthorField($context) {
        var $authorsUserField = $context.find('#publishpress-authors-user-author-wrapper');
        var $selectedAuthors = $context.find('.authors-list li:not(.sortable-placeholder)');
        var guestAuthorsCount = 0;

        $selectedAuthors.each(function () {
            if ($(this).data('is-guest') == 1) {
                guestAuthorsCount++;
            }
        });

        if ($selectedAuthors.length && guestAuthorsCount === $selectedAuthors.length) {
            $authorsUserField.show();
        } else {
            $authorsUserField.hide();
        }
    }

    function handleAuthorCategory($context) {
        $context.find('.authors-list').each(function () {
            var $authorsList = $(this);
            var categoryId = $authorsList.attr('data-category_id');

            if ($authorsList.children().length === 1) {
                $authorsList.find('.sortable-placeholder').show();
            } else {
                $authorsList.find('.sortable-placeholder').hide();
            }

            $authorsList.find('.author_categories').each(function () {
                var authorTerm = $(this).closest('li').find('.author_term').val();

                if (settings.allow_author_multiple_categories === 'yes') {
                    $(this).attr('name', 'author_categories[' + authorTerm + '][]');
                } else {
                    $(this).attr('name', 'author_categories[' + authorTerm + ']');
                }

                $(this).val(categoryId);
            });
        });
    }

    function triggerSelectionChange($context) {
        $context.trigger('publishpressAuthors:changed');
    }

    function initAuthorsSelect($context) {
        var $select = $context.find(".authors-select2.authors-search");

        $select.each(function () {
            var $authorsSelect = $(this);

            if ($authorsSelect.data('ppma_select2')) {
                return;
            }

            $authorsSelect.ppma_select2(withSelect2Language({
                placeholder: $authorsSelect.data("placeholder"),
                allowClear: true,
                ajax: {
                    url: settings.ajax_url + "?action=authors_search&nonce=" + $authorsSelect.data("nonce"),
                    dataType: "json",
                    data: function (params) {
                        var ignored = [];

                        $context.find(".authors-list input.author_term").each(function () {
                            if (settings.allow_author_multiple_categories !== 'yes') {
                                ignored.push($(this).val());
                            }
                        });

                        return {
                            q: params.term,
                            ignored: ignored
                        };
                    }
                }
            }));

            $authorsSelect.on("ppma_select2:select", function (e) {
                var $targetList = getAvailableCategoryList($context, e.params.data);
                var template;

                if ($targetList.length && wp.template) {
                    template = wp.template("authors-author-partial");
                    $targetList.append(htmlDecode(template(e.params.data)));
                }

                $authorsSelect.val(null).trigger("change");
                handleUsersAuthorField($context);
                handleAuthorCategory($context);
                triggerSelectionChange($context);
            });
        });
    }

    function initFallbackUserSelect($context) {
        $context.find(".authors-user-search").each(function () {
            var $select = $(this);

            if ($select.data('ppma_select2')) {
                return;
            }

            $select.ppma_select2(withSelect2Language({
                placeholder: $select.data("placeholder"),
                allowClear: true,
                ajax: {
                    url: settings.ajax_url + "?action=authors_users_search&nonce=" + $select.data("nonce"),
                    dataType: "json",
                    data: function (params) {
                        return {
                            q: params.term,
                            ignored: []
                        };
                    }
                }
            }));
        });
    }

    function initSortable($context) {
        if (!$.fn.sortable) {
            return;
        }

        $context.find(".authors-current-user-can-assign").sortable({
            connectWith: ".authors-list",
            items: "> li:not(.no-drag)",
            placeholder: "sortable-placeholder",
            update: function () {
                handleAuthorCategory($context);
                triggerSelectionChange($context);
            },
            receive: function (event, ui) {
                if (authorExistsInCategory($(this), ui.item.find('.author_term').val(), ui.item)) {
                    if (ui.sender && ui.sender.length) {
                        ui.sender.sortable('cancel');
                    } else {
                        $(this).sortable('cancel');
                    }

                    handleAuthorCategory($context);
                    triggerSelectionChange($context);
                    return;
                }

                $(this).find('.sortable-placeholder').hide();
            },
            remove: function () {
                if ($(this).children().length === 1) {
                    $(this).find('.sortable-placeholder').show();
                }
            }
        }).on("click", ".author-remove", function () {
            $(this).closest("li").remove();
            handleUsersAuthorField($context);
            handleAuthorCategory($context);
            triggerSelectionChange($context);
        });
    }

    function initAuthorsSelection(container) {
        var $context = $(container);

        if (!hasAuthorsSelectionMarkup(container)) {
            return false;
        }

        initAuthorsSelect($context);
        initFallbackUserSelect($context);
        $context.find(".authors-select2-default-select").each(function () {
            var $select = $(this);

            if ($select.data('ppma_select2')) {
                return;
            }

            $select.ppma_select2(withSelect2Language({}));
        });
        initSortable($context);
        handleUsersAuthorField($context);
        handleAuthorCategory($context);

        $context.find('#ppma_author_box_select, #publishpress-authors-user-author-select').on('change', function () {
            triggerSelectionChange($context);
        });

        return true;
    }

    function collectAuthorsSelection(container) {
        var $context = $(container);
        var selectedAuthors = [];
        var selectedAuthorCategories = {};

        $context.find(".authors-list input.author_term").each(function () {
            var selectedVal = parseInt($(this).val(), 10);
            selectedAuthors.push(selectedVal);

            if (settings.allow_author_multiple_categories === 'yes') {
                var selectedCategory = $(this).closest('ul').attr('data-category_id');

                if (typeof selectedAuthorCategories[selectedVal] === 'undefined') {
                    selectedAuthorCategories[selectedVal] = [];
                }

                if (selectedAuthorCategories[selectedVal].indexOf(selectedCategory) === -1) {
                    selectedAuthorCategories[selectedVal].push(selectedCategory);
                }
            } else {
                selectedAuthorCategories[selectedVal] = $(this).closest('ul').attr('data-category_id');
            }
        });

        return {
            authors: selectedAuthors,
            author_categories: selectedAuthorCategories,
            fallback_author_user: $context.find('#publishpress-authors-user-author-select').val(),
            ppma_author_box_select: $context.find('#ppma_author_box_select').val()
        };
    }

    function saveAuthors(container, setSaving, setError) {
        var data = collectAuthorsSelection(container);

        setSaving(true);
        setError('');

        if (wp.data && wp.data.dispatch('core/editor')) {
            wp.data.dispatch('core/editor').lockPostSaving(postSavingLock);
        }

        $.ajax({
            url: settings.ajax_url,
            type: 'POST',
            data: $.extend(
                {
                    action: 'ppma_save_block_editor_authors',
                    post_id: settings.post_id,
                    nonce: settings.nonce
                },
                data
            )
        }).done(function (response) {
            if (!response || !response.success) {
                setError(settings.save_error);
            }
        }).fail(function () {
            setError(settings.save_error);
        }).always(function () {
            setSaving(false);

            if (wp.data && wp.data.dispatch('core/editor')) {
                wp.data.dispatch('core/editor').unlockPostSaving(postSavingLock);
            }
        });
    }

    function AuthorsPanel() {
        var panelRef = useRef(null);
        var saveTimer = useRef(null);
        var retryTimer = useRef(null);
        var initialized = useRef(false);
        var setHtmlState = useState('');
        var html = setHtmlState[0];
        var setHtml = setHtmlState[1];
        var savingState = useState(false);
        var saving = savingState[0];
        var setSaving = savingState[1];
        var errorState = useState('');
        var error = errorState[0];
        var setError = errorState[1];

        function bindAuthorsPanel(container) {
            if (!container || initialized.current) {
                return;
            }

            panelRef.current = container;

            function initializeWhenReady() {
                waitForSelectionDependencies(function () {
                    if (initAuthorsSelection(container)) {
                        initialized.current = true;
                        return;
                    }

                    retryTimer.current = window.setTimeout(initializeWhenReady, 100);
                });
            }

            initializeWhenReady();

            $(container).on('publishpressAuthors:changed', function () {
                window.clearTimeout(saveTimer.current);
                saveTimer.current = window.setTimeout(function () {
                    saveAuthors(container, setSaving, setError);
                }, 700);
            });
        }

        useEffect(function () {
            $.ajax({
                url: settings.ajax_url,
                type: 'GET',
                data: {
                    action: 'ppma_render_block_editor_authors',
                    post_id: settings.post_id,
                    nonce: settings.nonce
                }
            }).done(function (response) {
                if (response && response.success && response.data && response.data.html) {
                    setHtml(response.data.html);
                } else {
                    setError(settings.load_error);
                }
            }).fail(function () {
                setError(settings.load_error);
            });
        }, []);

        useEffect(function () {
            var fallbackTimer;

            if (!html || initialized.current) {
                return;
            }

            fallbackTimer = window.setTimeout(function () {
                var container = document.querySelector(
                    '.publishpress-authors-block-editor-panel [data-ppma-authors-panel="1"]'
                );

                bindAuthorsPanel(container);
            }, 100);

            return function () {
                window.clearTimeout(fallbackTimer);
            };
        }, [html]);

        useEffect(function () {
            var container = panelRef.current;

            return function () {
                if (container) {
                    $(container).off('publishpressAuthors:changed');
                }

                window.clearTimeout(retryTimer.current);
                window.clearTimeout(saveTimer.current);
            };
        }, []);

        return createElement(
            PluginDocumentSettingPanel,
            {
                name: 'publishpress-authors',
                title: settings.title,
                className: 'publishpress-authors-block-editor-panel'
            },
            error ? createElement(Notice, {status: 'error', isDismissible: false}, error) : null,
            saving ? createElement('p', {className: 'description'}, settings.saving) : null,
            html ? createElement('div', {
                'data-ppma-authors-panel': '1',
                ref: bindAuthorsPanel,
                dangerouslySetInnerHTML: {__html: html}
            }) : createElement(Spinner)
        );
    }

    wp.plugins.registerPlugin('publishpress-authors-block-editor', {
        render: AuthorsPanel
    });
}(window.wp, window.jQuery));
