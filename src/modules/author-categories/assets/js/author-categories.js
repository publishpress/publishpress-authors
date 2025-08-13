(function ($) {
    'use strict';

    jQuery(document).ready(function ($) {

        /**
         * New author category form submission
         */
        $('#addauthorcategory').submit(function (event) {
            event.preventDefault();
            var form = $(this);
            var role_table = $('table.authorcategories tbody');

            $('#ajax-response').empty();
            form.find('.spinner').addClass('is-active');
            form.find('#submit').attr('disabled', true);

            var data = {
                action: "save_ppma_author_category",
                category_name: form.find('#category-name').val(),
                plural_name: form.find('#category-plural-name').val(),
                schema_property: form.find('#category-schema-property').val(),
                post_types: authorCategories.proActive ? form.find('#category-post-types').val() : [],
                enabled_category: form.find('#category-enabled-category').is(':checked') ? 1 : 0,
                nonce: authorCategories.nonce,
            };
            $.post(ajaxurl, data, function (response) {
                var status = response.status;
                var content = response.content;
                var message = response.message;

                $('#ajax-response').empty();
                form.find('.spinner').removeClass('is-active');
                form.find('#submit').attr('disabled', false);
                $('#ajax-response').append(message);

                if (status === 'success') {
                    role_table.prepend(content);
                    role_table.find('.no-items').remove();
                    $('input[type="text"]:visible', form).val('');
                } else {

                }
            }).fail(function (xhr, textStatus, errorThrown) {
                form.find('.spinner').removeClass('is-active');
                form.find('#submit').attr('disabled', false);
            });

            return false;

        });

    });

})(jQuery);