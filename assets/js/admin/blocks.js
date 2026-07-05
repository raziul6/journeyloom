/**
 * WP Travel Machine — Gutenberg Blocks
 *
 * Server-rendered blocks with a live ServerSideRender preview and full
 * Content + Style inspector controls. All output flows through the shared PHP
 * Renderer, so blocks match the Elementor widgets and shortcodes exactly.
 */
(function (blocks, element, blockEditor, components, i18n, serverSideRender) {
    'use strict';

    var el = element.createElement;
    var Fragment = element.Fragment;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelColorSettings = blockEditor.PanelColorSettings;
    var PanelBody = components.PanelBody;
    var RangeControl = components.RangeControl;
    var SelectControl = components.SelectControl;
    var TextControl = components.TextControl;
    var Button = components.Button;
    var useState = element.useState;
    var __ = i18n.__;
    var ServerSideRender = serverSideRender;

    /* Style attributes shared by every block (must match PHP). */
    var styleAttributes = {
        gap: { type: 'number' },
        cardRadius: { type: 'number' },
        accent: { type: 'string', default: '' },
        titleColor: { type: 'string', default: '' },
        textColor: { type: 'string', default: '' },
        btnBg: { type: 'string', default: '' },
        btnColor: { type: 'string', default: '' },
        align: { type: 'string', default: '' }
    };

    /* The reusable Style panels (AI + Layout + Colors). */
    function stylePanels(props) {
        var a = props.attributes;
        var set = props.setAttributes;
        return [
            el(PanelBody, { title: __('Layout & Spacing', 'byteflows-travel-hotel-booking'), initialOpen: false, key: 'layout' },
                el(SelectControl, {
                    label: __('Alignment', 'byteflows-travel-hotel-booking'),
                    value: a.align || '',
                    options: [
                        { label: __('Default', 'byteflows-travel-hotel-booking'), value: '' },
                        { label: __('Left', 'byteflows-travel-hotel-booking'), value: 'left' },
                        { label: __('Center', 'byteflows-travel-hotel-booking'), value: 'center' },
                        { label: __('Right', 'byteflows-travel-hotel-booking'), value: 'right' }
                    ],
                    onChange: function (v) { set({ align: v }); }
                }),
                el(RangeControl, { label: __('Grid Gap (px)', 'byteflows-travel-hotel-booking'), value: a.gap, onChange: function (v) { set({ gap: v }); }, min: 0, max: 80, allowReset: true }),
                el(RangeControl, { label: __('Card Radius (px)', 'byteflows-travel-hotel-booking'), value: a.cardRadius, onChange: function (v) { set({ cardRadius: v }); }, min: 0, max: 40, allowReset: true })
            ),
            el(PanelColorSettings, {
                title: __('Colors', 'byteflows-travel-hotel-booking'),
                initialOpen: false,
                key: 'colors',
                colorSettings: [
                    { value: a.accent, onChange: function (v) { set({ accent: v || '' }); }, label: __('Accent / Price', 'byteflows-travel-hotel-booking') },
                    { value: a.titleColor, onChange: function (v) { set({ titleColor: v || '' }); }, label: __('Title', 'byteflows-travel-hotel-booking') },
                    { value: a.textColor, onChange: function (v) { set({ textColor: v || '' }); }, label: __('Text', 'byteflows-travel-hotel-booking') },
                    { value: a.btnBg, onChange: function (v) { set({ btnBg: v || '' }); }, label: __('Button Background', 'byteflows-travel-hotel-booking') },
                    { value: a.btnColor, onChange: function (v) { set({ btnColor: v || '' }); }, label: __('Button Text', 'byteflows-travel-hotel-booking') }
                ]
            })
        ];
    }

    /* Factory: register a server-rendered block with content + style controls. */
    function registerWptmBlock(name, cfg) {
        registerBlockType(name, {
            title: cfg.title,
            description: cfg.description,
            icon: cfg.icon,
            category: 'wptm',
            keywords: cfg.keywords || [],
            attributes: Object.assign({}, cfg.attributes, styleAttributes),
            edit: function (props) {
                return el(Fragment, {},
                    el(InspectorControls, {},
                        el(PanelBody, { title: __('Content', 'byteflows-travel-hotel-booking'), initialOpen: true }, cfg.controls(props)),
                        stylePanels(props)
                    ),
                    el('div', { className: 'wptm-blk-editor' },
                        el(ServerSideRender, { block: name, attributes: props.attributes })
                    )
                );
            },
            save: function () { return null; }
        });
    }

    var orderByOptions = [
        { label: __('Newest', 'byteflows-travel-hotel-booking'), value: 'date' },
        { label: __('Title', 'byteflows-travel-hotel-booking'), value: 'title' },
        { label: __('Price', 'byteflows-travel-hotel-booking'), value: 'price' },
        { label: __('Random', 'byteflows-travel-hotel-booking'), value: 'rand' },
        { label: __('Menu Order', 'byteflows-travel-hotel-booking'), value: 'menu_order' }
    ];
    var orderOptions = [
        { label: __('Descending', 'byteflows-travel-hotel-booking'), value: 'DESC' },
        { label: __('Ascending', 'byteflows-travel-hotel-booking'), value: 'ASC' }
    ];

    function gridControls(props, withActivity) {
        var a = props.attributes, set = props.setAttributes;
        var rows = [
            el(RangeControl, { key: 'count', label: __('Number of items', 'byteflows-travel-hotel-booking'), value: a.count, onChange: function (v) { set({ count: v }); }, min: 1, max: 24 }),
            el(SelectControl, {
                key: 'layout', label: __('Layout', 'byteflows-travel-hotel-booking'), value: a.layout || 'grid',
                options: [
                    { label: __('Grid', 'byteflows-travel-hotel-booking'), value: 'grid' },
                    { label: __('List', 'byteflows-travel-hotel-booking'), value: 'list' }
                ],
                onChange: function (v) { set({ layout: v }); }
            }),
            el(RangeControl, { key: 'cols', label: __('Columns (grid)', 'byteflows-travel-hotel-booking'), value: a.columns, onChange: function (v) { set({ columns: v }); }, min: 1, max: 4, disabled: a.layout === 'list' }),
            el(SelectControl, { key: 'orderby', label: __('Order By', 'byteflows-travel-hotel-booking'), value: a.orderby, options: orderByOptions, onChange: function (v) { set({ orderby: v }); } }),
            el(SelectControl, { key: 'order', label: __('Order', 'byteflows-travel-hotel-booking'), value: a.order, options: orderOptions, onChange: function (v) { set({ order: v }); } }),
            el(TextControl, { key: 'dest', label: __('Destination slug (optional)', 'byteflows-travel-hotel-booking'), value: a.destination, onChange: function (v) { set({ destination: v }); } })
        ];
        if (withActivity) {
            rows.push(el(TextControl, { key: 'act', label: __('Activity slug (optional)', 'byteflows-travel-hotel-booking'), value: a.activity, onChange: function (v) { set({ activity: v }); } }));
        }
        return rows;
    }

    /* ── Trip Grid ── */
    registerWptmBlock('wptm/trip-grid', {
        title: __('Trip Grid', 'byteflows-travel-hotel-booking'),
        description: __('A grid of travel trips with full content and style controls.', 'byteflows-travel-hotel-booking'),
        icon: 'palmtree',
        keywords: ['trip', 'travel', 'tour'],
        attributes: {
            count: { type: 'number', default: 6 },
            columns: { type: 'number', default: 3 },
            layout: { type: 'string', default: 'grid' },
            orderby: { type: 'string', default: 'date' },
            order: { type: 'string', default: 'DESC' },
            destination: { type: 'string', default: '' },
            activity: { type: 'string', default: '' }
        },
        controls: function (props) { return gridControls(props, true); }
    });

    /* ── Hotel Grid ── */
    registerWptmBlock('wptm/hotel-grid', {
        title: __('Hotel Grid', 'byteflows-travel-hotel-booking'),
        description: __('A grid of hotels with full content and style controls.', 'byteflows-travel-hotel-booking'),
        icon: 'building',
        keywords: ['hotel', 'room', 'stay'],
        attributes: {
            count: { type: 'number', default: 6 },
            columns: { type: 'number', default: 3 },
            layout: { type: 'string', default: 'grid' },
            orderby: { type: 'string', default: 'date' },
            order: { type: 'string', default: 'DESC' },
            destination: { type: 'string', default: '' }
        },
        controls: function (props) { return gridControls(props, false); }
    });

    /* ── Travel Search Form ── */
    registerWptmBlock('wptm/search-form', {
        title: __('Travel Search Form', 'byteflows-travel-hotel-booking'),
        description: __('The trip & hotel search form.', 'byteflows-travel-hotel-booking'),
        icon: 'search',
        keywords: ['search', 'filter', 'find'],
        attributes: { style: { type: 'string', default: 'horizontal' } },
        controls: function (props) {
            return [
                el(SelectControl, {
                    key: 'style',
                    label: __('Layout', 'byteflows-travel-hotel-booking'),
                    value: props.attributes.style,
                    options: [
                        { label: __('Horizontal', 'byteflows-travel-hotel-booking'), value: 'horizontal' },
                        { label: __('Vertical', 'byteflows-travel-hotel-booking'), value: 'vertical' }
                    ],
                    onChange: function (v) { props.setAttributes({ style: v }); }
                })
            ];
        }
    });

    /* ── Destinations Grid ── */
    registerWptmBlock('wptm/destinations', {
        title: __('Destinations Grid', 'byteflows-travel-hotel-booking'),
        description: __('A grid of destination categories.', 'byteflows-travel-hotel-booking'),
        icon: 'location-alt',
        keywords: ['destination', 'location', 'place'],
        attributes: { count: { type: 'number', default: 8 } },
        controls: function (props) {
            return [
                el(RangeControl, { key: 'count', label: __('Number of destinations', 'byteflows-travel-hotel-booking'), value: props.attributes.count, onChange: function (v) { props.setAttributes({ count: v }); }, min: 1, max: 24 })
            ];
        }
    });

    /* ── Booking Form ── */
    registerWptmBlock('wptm/booking-form', {
        title: __('Booking Form', 'byteflows-travel-hotel-booking'),
        description: __('The trip/hotel booking form.', 'byteflows-travel-hotel-booking'),
        icon: 'calendar-alt',
        keywords: ['booking', 'reserve', 'book'],
        attributes: { id: { type: 'number', default: 0 } },
        controls: function (props) {
            return [
                el(TextControl, {
                    key: 'id',
                    label: __('Trip / Hotel ID (0 = current)', 'byteflows-travel-hotel-booking'),
                    type: 'number',
                    value: props.attributes.id,
                    onChange: function (v) { props.setAttributes({ id: parseInt(v, 10) || 0 }); }
                })
            ];
        }
    });

})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n, window.wp.serverSideRender);
