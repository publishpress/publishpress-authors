jQuery(document).ready(function ($) {

    var i18n = window.ppmaAuthorsIndex || {};

    /**
     * Polite live region used to announce authors index results to assistive technologies.
     * It lives outside the index wrapper so it survives the AJAX replacement.
     */
    var statusRegion = $();
    if ($('.pp-multiple-authors-index').length) {
        statusRegion = $('<div class="ppma-authors-index-status" role="status" aria-live="polite" aria-atomic="true"></div>');
        $('body').append(statusRegion);
    }

    function announce(message) {
        statusRegion.text('');
        setTimeout(function () {
            statusRegion.text(message || '');
        }, 100);
    }

    /**
     * Requery the authors index through AJAX when an alphabet filter is selected.
     */
    $(document).on('click', '.author-index-navigation .page-link', function (e) {
        e.preventDefault();
        var link = $(this);
        var wrapper = link.closest('.pp-multiple-authors-index');
        var navigation = link.closest('.author-index-navigation');
        var letter = link.attr('data-letter');
        var letterText = $.trim(link.text());
        var ajaxUrl = wrapper.attr('data-ajax-url');
        var ajaxNonce = wrapper.attr('data-ajax-nonce');
        var ajaxInstance = wrapper.attr('data-ajax-instance');
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete('ppma_page');
        currentUrl.searchParams.delete('paged');
        if (letter) {
            currentUrl.searchParams.set('ppma_author_letter', letter);
        } else {
            currentUrl.searchParams.delete('ppma_author_letter');
        }
        window.history.replaceState({}, '', currentUrl.toString());
        var skeleton = '<div class="pp-authors-index-skeleton" aria-hidden="true">' +
            '<div class="pp-authors-index-skeleton-title"></div>' +
            '<div class="pp-authors-index-skeleton-content"></div>' +
            '<div class="pp-authors-index-skeleton-content"></div>' +
            '<div class="pp-authors-index-skeleton-content"></div>' +
            '</div>';

        // Keep the letter navigation (and the focused link) in place while loading.
        navigation.find('.page-item').removeClass('active');
        navigation.find('.page-link').removeAttr('aria-current');
        link.attr('aria-current', 'page').closest('.page-item').addClass('active');
        navigation.nextAll().remove();
        navigation.after(skeleton);
        wrapper.attr('aria-busy', 'true');
        announce(i18n.loading);

        var showError = function (message) {
            var errorMessage = $('<p></p>').html(message || i18n.loadError || '');
            wrapper.find('.pp-authors-index-skeleton').replaceWith(errorMessage);
            wrapper.removeAttr('aria-busy');
            announce(errorMessage.text());
        };

        $.post(ajaxUrl, {
            action: 'ppma_authors_index_filter',
            nonce: ajaxNonce,
            instance: ajaxInstance,
            letter: letter,
            url: currentUrl.toString()
        }).done(function (response) {
            if (response.success) {
                var newWrapper = $($.parseHTML($.trim(response.data)));
                var hadFocus = link.is(document.activeElement);
                wrapper.replaceWith(newWrapper);

                var newIndex = newWrapper.filter('.pp-multiple-authors-index').add(newWrapper.find('.pp-multiple-authors-index')).first();
                var newLink = newIndex.find('.author-index-navigation .page-link').filter(function () {
                    return ($(this).attr('data-letter') || '') === (letter || '');
                }).first();

                if (hadFocus && newLink.length) {
                    newLink.trigger('focus');
                }

                var message;
                if (!newIndex.find('.author-index-item').length) {
                    message = i18n.noResults;
                } else if (letter) {
                    message = (i18n.showingLetter || '').replace('%s', letterText);
                } else {
                    message = i18n.showingAll;
                }
                announce(message);
            } else {
                showError(response.data);
            }
        }).fail(function () {
            showError(i18n.loadError);
        });
    });
});
