jQuery(document).ready(function ($) {

    /**
     * Requery the authors index through AJAX when an alphabet filter is selected.
     */
    $(document).on('click', '.author-index-navigation .page-link', function (e) {
        e.preventDefault();
        var link = $(this);
        var wrapper = link.closest('.pp-multiple-authors-index');
        var letter = link.attr('data-letter');
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
        var skeleton = '<div class="pp-authors-index-skeleton" aria-busy="true">' +
            '<div class="pp-authors-index-skeleton-title"></div>' +
            '<div class="pp-authors-index-skeleton-content"></div>' +
            '<div class="pp-authors-index-skeleton-content"></div>' +
            '<div class="pp-authors-index-skeleton-content"></div>' +
            '</div>';

        wrapper.html(skeleton);
        $.post(ajaxUrl, {
            action: 'ppma_authors_index_filter',
            nonce: ajaxNonce,
            instance: ajaxInstance,
            letter: letter,
            url: currentUrl.toString()
        }).done(function (response) {
            if (response.success) {
                wrapper.replaceWith(response.data);
            } else {
                wrapper.html('<p>' + (response.data || 'Unable to load authors.') + '</p>');
            }
        }).fail(function () {
            wrapper.html('<p>Unable to load authors.</p>');
        });
    });
});