(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { useState, useEffect } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, TextControl, ToggleControl, Spinner, Placeholder } = wp.components;
    const { ServerSideRender } = wp.editor;

    // Define server rendering loading placeholder
    const LoadingPlaceholder = () => (
        <Placeholder>
            <Spinner />
        </Placeholder>
    );

    const blockName = 'publishpress-authors/author-data-block';

    registerBlockType(blockName, {
        title: authorDataBlock.block_title,
        icon: {
            src: 'id-alt',
            foreground: '#655897',
        },
        category: 'common',
        attributes: {
            field: { type: 'string', default: 'display_name' },
            separator: { type: 'string', default: ', ' },
            postId: { type: 'string', default: '' },
            termId: { type: 'string', default: '' },
            archive: { type: 'boolean', default: false },
            authorCategories: { type: 'string', default: '' },
        },
        example: {
            attributes: {
                field: 'display_name',
            },
        },
        edit: (props) => {
            const { attributes, setAttributes } = props;
            const { field, separator, postId, termId, archive, authorCategories } = attributes;
            const [fields, setFields] = useState([]);

            useEffect(() => {
                fetch(`${authorDataBlock.ajax_url}?action=ppma_block_fetch_author_fields`)
                    .then((response) => response.json())
                    .then((data) => {
                        if (Array.isArray(data) && data.length > 0) {
                            setFields(data);
                        }
                    });
            }, []);

            const fieldOptions = fields.length > 0
                ? fields
                : [{ label: authorDataBlock.default_field_label, value: 'display_name' }];

            return (
                <>
                    <InspectorControls>
                        <PanelBody title={authorDataBlock.block_title}>
                            <SelectControl
                                label={authorDataBlock.field_label}
                                value={field}
                                options={fieldOptions}
                                onChange={(value) => setAttributes({ field: value })}
                            />
                            <TextControl
                                label={authorDataBlock.separator_label}
                                value={separator}
                                onChange={(value) => setAttributes({ separator: value })}
                            />
                            <ToggleControl
                                label={authorDataBlock.archive_label}
                                checked={archive}
                                onChange={(value) => setAttributes({ archive: value })}
                            />
                            <TextControl
                                label={authorDataBlock.post_id_label}
                                help={authorDataBlock.post_id_help}
                                value={postId}
                                onChange={(value) => setAttributes({ postId: value })}
                            />
                            <TextControl
                                label={authorDataBlock.term_id_label}
                                help={authorDataBlock.term_id_help}
                                value={termId}
                                onChange={(value) => setAttributes({ termId: value })}
                            />
                            <TextControl
                                label={authorDataBlock.author_categories_label}
                                help={authorDataBlock.author_categories_help}
                                value={authorCategories}
                                onChange={(value) => setAttributes({ authorCategories: value })}
                            />
                        </PanelBody>
                    </InspectorControls>
                    <ServerSideRender
                        block={blockName}
                        attributes={attributes}
                        LoadingResponsePlaceholder={LoadingPlaceholder}
                    />
                </>
            );
        },
        save: function () {
            return "";
        },
    });
})(window.wp);
