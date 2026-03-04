(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { useState, useEffect } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl,  Spinner, Placeholder, } = wp.components;
    const { ServerSideRender } = wp.editor;


    // Define server rendering loading placeholder
    const LoadingPlaceholder = () => (
        <Placeholder>
        <Spinner />
        </Placeholder>
    );

    const blockName = 'publishpress-authors/author-boxes-block';

    registerBlockType(blockName, {
        title: authorBoxesBlock.block_title,
        icon: {
            src: 'groups',
            foreground: '#655897',
        },
        category: 'common',
        attributes: {
            selectedBoxId: {
                type: 'string',
            },
        },
        example: {
          attributes: {
            selectedBoxId:  ''
          },
        },
        edit: (props) => {
            const { attributes, setAttributes } = props;
            const [posts, setPosts] = useState([]);
            const { selectedBoxId } = attributes;

            useEffect(() => {
                fetch(`${authorBoxesBlock.ajax_url}?action=ppma_block_fetch_author_boxes`)
                    .then((response) => response.json())
                    .then((data) => {
                        setPosts(data);
                        if (!selectedBoxId && data.length > 0) {
                            setAttributes({ selectedBoxId: data[0].id });
                        }
                    });
            }, []);

            const options = posts.map((post) => ({
                label: post.title,
                value: post.id,
            }));

            return (
                <>
                    <InspectorControls>
                        <PanelBody title={authorBoxesBlock.block_title}>
                            <SelectControl
                                label={authorBoxesBlock.select_label}
                                value={selectedBoxId}
                                options={options}
                                onChange={(value) => setAttributes({ selectedBoxId: value })}
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