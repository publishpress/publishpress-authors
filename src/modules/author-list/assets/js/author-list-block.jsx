(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { useState, useEffect } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, Spinner, Placeholder } = wp.components;
    const { ServerSideRender } = wp.editor;

    // Define server rendering loading placeholder
    const LoadingPlaceholder = () => (
        <Placeholder>
            <Spinner />
        </Placeholder>
    );

    const blockName = 'publishpress-authors/author-list-block';

    registerBlockType(blockName, {
        title: authorListBlock.block_title,
        icon: {
            src: 'list-view',
            foreground: '#655897',
        },
        category: 'common',
        attributes: {
            selectedListId: {
                type: 'string',
            },
        },
        example: {
            attributes: {
                selectedListId: '',
            },
        },
        edit: (props) => {
            const { attributes, setAttributes } = props;
            const [lists, setLists] = useState([]);
            const { selectedListId } = attributes;

            useEffect(() => {
                fetch(`${authorListBlock.ajax_url}?action=ppma_block_fetch_author_lists`)
                    .then((response) => response.json())
                    .then((data) => {
                        setLists(data);
                        if (!selectedListId && data.length > 0) {
                            setAttributes({ selectedListId: data[0].id });
                        }
                    });
            }, []);

            const options = lists.map((list) => ({
                label: list.title,
                value: list.id,
            }));

            return (
                <>
                    <InspectorControls>
                        <PanelBody title={authorListBlock.block_title}>
                            <SelectControl
                                label={authorListBlock.select_label}
                                value={selectedListId}
                                options={options}
                                onChange={(value) => setAttributes({ selectedListId: value })}
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
