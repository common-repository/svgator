(function (wp, $) {
    let SVGatorEditorBlock = function() {
        let createEl = wp.element.createElement;

        const BlockControls = wp.blockEditor.BlockControls;
        const ResizableBox = wp.components.ResizableBox;
        const Button = wp.components.Button;

        let plcHandler = {
            empty: function(onSvgatorMediaOpen) {
                const placeholderContent = [
                    createEl(
                        'div',
                        {
                            key: 'wp-svgator-placeholder-title',
                            className: 'wp-svgator-placeholder-title',
                        },
                        'SVGator',
                    ),
                    createEl(
                        'div',
                        {
                            key: 'wp-svgator-placeholder-description',
                            className: 'wp-svgator-placeholder-description',
                        },
                        'Import a SVG from your SVGator library, or add one from your WordPress library',
                    ),
                    createEl(
                        'button',
                        {
                            key: 'wp-svgator-placeholder-content',
                            className: 'wp-svgator-placeholder-button',
                            onClick: () => onSvgatorMediaOpen(),
                        },
                        'Select SVG'
                    ),
                ];
                return createEl(
                    'div',
                    {
                        key: 'wp-svgator-placeholder',
                        className: 'wp-svgator-image wp-svgator-placeholder',
                    },
                    placeholderContent,
                );
            },
            preview: function(props) {
                let attr = props?.attributes;
                if(!attr || !Object.keys(attr).length) {
                    return;
                }
                let svg = plcHandler.save(attr);
                let initialSize = {};
                let elProps = {
                    key: 'placeholder-resizer',
                    showHandle: props.isSelected,
                    lockAspectRatio: true,
                    onResizeStart: function(e, direction, ref) {
                        let $img = $(ref).find('.wp-svgator-image');
                        initialSize.width = $img.width();
                        initialSize.height = $img.height();
                    },
                    onResizeStop: function(e, direction, ref, d) {
                        props.setAttributes({
                            responsive: '',
                            width: initialSize.width + d.width,
                            height: initialSize.height + d.height,
                        });
                    }
                };

                if (!attr.responsive && attr.width && attr.height) {
                    elProps.size = {
                        width: attr.width,
                        height: attr.height,
                    };
                } else {
                    elProps.size = {
                        width: '100%',
                        height: '100%',
                    };
                }

                return createEl(
                    ResizableBox,
                    elProps,
                    svg
                );
            },
            save: function(attr){
                if (!attr.src) {
                    return false;
                }

                let elProps = {
                    src: attr.src,
                    'data-attachment-id': attr.attachmentId,
                    className: 'wp-svgator-image',
                };

                if (!attr.responsive && attr.width && attr.height) {
                    elProps.width = attr.width;
                    elProps.height = attr.height;
                    elProps.responsive = '';
                } else {
                    elProps.responsive = 'true';
                }

                let img = createEl(
                    'img',
                    elProps
                );

                return createEl(
                    'div',
                    {
                        key: 'placeholder',
                        className: 'wp-svgator-container',
                    },
                    img
                );
            },
        };

        function createBlockControlButton(text, callback){
            let key = text.toLowerCase().replace(/[^a-z0-9\-]+/, '-');
            key = key.replace(/^-+|-+$/i, '');
            return createEl(
                Button,
                {
                    key,
                    onClick: function(){
                        callback();
                    },
                },
                text
            )
        }

        let svgatorMedia = new SVGatorMedia({
            onSelect: function() {}
        });

        let icon = createEl(
            'img',
            {
                src: wp_svgator.plugin_logo,
                width: 24,
            }
        );

        function registerBlock()
        {
            wp.blocks.registerBlockType(
                'wp-svgator/insert-svg',
                {
                    title: 'SVGator',
                    icon: icon,
                    category: 'media',
                    supports: {
                        // Remove support for an HTML mode.
                        html: false,
                        alignWide: true,
                        className: false,
                        customClassName: false,
                        defaultStylePicker: false,
                    },
                    attributes: {
                        responsive: {
                            type: 'string',
                            source: 'attribute',
                            selector: 'img.wp-svgator-image',
                            attribute: 'data-responsive',
                        },
                        width: {
                            type: 'string',
                            source: 'attribute',
                            selector: 'img.wp-svgator-image',
                            attribute: 'width',
                        },
                        height: {
                            type: 'string',
                            source: 'attribute',
                            selector: 'img.wp-svgator-image',
                            attribute: 'height',
                        },
                        src: {
                            type: 'string',
                            source: 'attribute',
                            selector: 'img.wp-svgator-image',
                            attribute: 'src',
                        },
                        attachmentId: {
                            type: 'string',
                            source: 'attribute',
                            selector: 'img.wp-svgator-image',
                            attribute: 'data-attachment-id',
                        }
                    },
                    /*
                     * The edit function describes the structure of your block in the context of the editor.
                     * This represents what the editor will render when the block is used.
                     */
                    edit: function(props) {
                        const svgatorMediaOptions = {
                            onSelect: function(attachment) {
                                let attrs = {
                                    src: attachment.icon,
                                    attachmentId: attachment.id.toString(),
                                    responsive: 'true',
                                };
                                if (!attachment.responsive && attachment.width && attachment.height) {
                                    attrs.width = attachment.width;
                                    attrs.height = attachment.height;
                                    attrs.responsive = '';
                                }
                                props.setAttributes(attrs);
                            }
                        };

                        const placeholder = plcHandler.preview(props) || plcHandler.empty(() => svgatorMedia.open(svgatorMediaOptions));

                        if (props.isSelected && !props.attributes.src) {
                            wp.element.useEffect(function() {
                                svgatorMedia.open(svgatorMediaOptions);
                            }, []);
                        }

                        let childElements = [];
                        childElements.push(createBlockControlButton(
                            'Select SVG',
                            function() {
                                svgatorMedia.open(svgatorMediaOptions);
                            })
                        );

                        if (!props.attributes.responsive && props.attributes.width && props.attributes.height) {
                            childElements.push(createBlockControlButton(
                                'Responsive',
                                function(){
                                    props.setAttributes({
                                        width: '',
                                        height: '',
                                        responsive: 'true',
                                    });
                                })
                            );
                        }

                        return [
                            createEl(
                                BlockControls,
                                { key: 'controls' },
                                childElements
                            ),
                            placeholder
                        ];
                    },
                    /*
                     * The save function defines the way in which the different attributes should be combined into the final markup, which is then serialized into post_content.
                     */
                    save: function(props) {
                        return plcHandler.save(props.attributes);
                    },
                }
            );
        }

        this.registerBlock = registerBlock;
    };

    let svgator_bp = new SVGatorEditorBlock();
    svgator_bp.registerBlock();
})(window.wp, jQuery);
