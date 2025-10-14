jQuery(document).ready(function ($) {
    // Tabs

    var $tabsWrapper = $('#publishpress-authors-settings-tabs');
    $tabsWrapper.find('li').click(function (e) {
        e.preventDefault();
        $tabsWrapper.children('li').filter('.nav-tab-active').removeClass('nav-tab-active');
        var $customSettings = $('.ppma-settings-wrap.custom-settings').length > 0;
        $(this).addClass('nav-tab-active');

        var panel = $(this).find('a').first().attr('data-tab-content');

        if ($customSettings) {
            $('table[id^="ppma-"] tr').hide();
        } else {
            if (browserSupportStorage()) {
                saveStorageData('ppma_settings_active_tab', panel.slice(1));
            }
            $('table[id^="ppma-"]').hide();
        }

        $(panel).show();
    });

    var ppmaTab = 'ppma-tab-general';

    if (typeof ppmaSettings != 'undefined' && typeof ppmaSettings.tab != 'undefined' && ppmaSettings.tab !== '') {
       ppmaTab = ppmaSettings.tab;
       $('#publishpress-authors-settings-tabs a[href="#' + ppmaTab + '"]').click();
    } else if (browserSupportStorage() && getStorageData('ppma_settings_active_tab')) {
        // Find the tab link
        const $tabLink = $('#publishpress-authors-settings-tabs a[href="#' + getStorageData('ppma_settings_active_tab') + '"]');
        const $tabLi = $tabLink.closest('li');

        // Check if it exists and is visible
        if ($tabLink.length && $tabLi.is(':visible')) {
            ppmaTab = getStorageData('ppma_settings_active_tab');
            $tabLink.click();
        }
    }

    var $hiddenFields = $('input[id^="ppma-tab-"]');
    var $customSettings = $('.ppma-settings-wrap.custom-settings').length > 0;

    if ($customSettings) {
        ppmaTab = 'ppma-tab-author-pages';
    }

    $hiddenFields.each(function () {
        var $this = $(this);
        var $wrapper = $this.next('table');
        $wrapper.attr('id', $this.attr('id'));
        $this.remove();

        if ($wrapper.attr('id') !== ppmaTab) {
            $wrapper.hide();
        }
    });

    if ('ppma-tab-maintenance' == ppmaTab) {
        if (typeof ppmaSettings.runScript != 'undefined') {
            switch (ppmaSettings.runScript) {
                case 'sync-user-login':
                    var intSyncUserLogin = setInterval( function() {
                        if ($('#publishpress-authors-sync-author-slug div input').length) {
                            $('#publishpress-authors-sync-author-slug div input').click();

                            clearInterval(intSyncUserLogin);
                        }
                    }, 100);

                    break;

                default:
            }
        }
    }

    $('.default-authors-select2').ppma_select2({
        placeholder: $(this).data("placeholder"),
        allowClear: true,
        ajax: {
            url:
                window.ajaxurl +
                "?action=authors_search&nonce=" +
                $('.default-authors-select2').data("nonce"),
            dataType: "json",
            data: function(params) {
                var ignored = [];
                $('.default-authors-select2')
                    .closest("div")
                    .find(".authors-list input")
                    .each(function() {
                        ignored.push($(this).val());
                    });
                return {
                    q: params.term,
                    ignored: ignored
                };
            }
        }
    });

    $('.fallback-user-search-select2').each(function () {
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

    // Show color scheme field only when boxed or centered layouts are selected
    $('#multiple_authors_multiple_authors_options_layout').on('change', function(){
        $selected_layout = $(this).val();
        if($selected_layout == 'centered' || $selected_layout == 'boxed') {
            $('.ppauthors-color-scheme-field').show();
        } else {
            $('.ppauthors-color-scheme-field').hide();
        }
        hideDisplayLegacySettings();
    });

    //hide legacy layout settings
    hideDisplayLegacySettings();
    if ($('#multiple_authors_multiple_authors_options_layout').length > 0) {
        hideDisplayLegacySettings();
    }

    /**
     * Hide or Show legacy layout settings
     */
    function hideDisplayLegacySettings() {
        var selected_settings = $('#multiple_authors_multiple_authors_options_layout option:selected').text();

        if (selected_settings.endsWith('(Legacy)')) {
            $('#multiple_authors_multiple_authors_options_title_appended_to_content').closest('tr').show();
            $('tr.ppauthors-color-scheme-field').show();
            $('#multiple_authors_multiple_authors_options_show_email_link').closest('tr').show();
            $('#multiple_authors_multiple_authors_options_show_site_link').closest('tr').show();
        } else {
            $('#multiple_authors_multiple_authors_options_title_appended_to_content').closest('tr').hide();
            $('tr.ppauthors-color-scheme-field').hide();
            $('#multiple_authors_multiple_authors_options_show_email_link').closest('tr').hide();
            $('#multiple_authors_multiple_authors_options_show_site_link').closest('tr').hide();
        }
    }

    /**
     * Check if browser support local storage
     * @returns
     */
    function browserSupportStorage() {
      if (typeof (Storage) !== "undefined") {
        return true;
      } else {
        return false;
      }
    }
    /**
     * Save local storage data
     * @param {*} storageName
     * @param {*} storageValue
     */
    function saveStorageData(storageName, storageValue) {
      removeStorageData(storageName);
      window.localStorage.setItem(storageName, JSON.stringify(storageValue));
    }

    /**
     * Get local storage data
     * @param {*} storageName
     * @returns
     */
    function getStorageData(storageName) {
      return JSON.parse(window.localStorage.getItem(storageName));
    }

    /**
     * Remove local storage data
     * @param {*} storageName
     */
    function removeStorageData(storageName) {
      window.localStorage.removeItem(storageName);
    }
});
