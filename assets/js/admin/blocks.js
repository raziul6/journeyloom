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

    /* The reusable Style panels (Layout + Colors). */
    function stylePanels(props) {
        var a = props.attributes;
        var set = props.setAttributes;
        return [
            el(PanelBody, { title: __('Layout & Spacing', 'wp-travel-machine'), initialOpen: false, key: 'layout' },
                el(SelectControl, {
                    label: __('Alignment', 'wp-travel-machine'),
                    value: a.align || '',
                    options: [
                        { label: __('Default', 'wp-travel-machine'), value: '' },
                        { label: __('Left', 'wp-travel-machine'), value: 'left' },
                        { label: __('Center', 'wp-travel-machine'), value: 'center' },
                        { label: __('Right', 'wp-travel-machine'), value: 'right' }
                    ],
                    onChange: function (v) { set({ align: v }); }
                }),
                el(RangeControl, { label: __('Grid Gap (px)', 'wp-travel-machine'), value: a.gap, onChange: function (v) { set({ gap: v }); }, min: 0, max: 80, allowReset: true }),
                el(RangeControl, { label: __('Card Radius (px)', 'wp-travel-machine'), value: a.cardRadius, onChange: function (v) { set({ cardRadius: v }); }, min: 0, max: 40, allowReset: true })
            ),
            el(PanelColorSettings, {
                title: __('Colors', 'wp-travel-machine'),
                initialOpen: false,
                key: 'colors',
                colorSettings: [
                    { value: a.accent, onChange: function (v) { set({ accent: v || '' }); }, label: __('Accent / Price', 'wp-travel-machine') },
                    { value: a.titleColor, onChange: function (v) { set({ titleColor: v || '' }); }, label: __('Title', 'wp-travel-machine') },
                    { value: a.textColor, onChange: function (v) { set({ textColor: v || '' }); }, label: __('Text', 'wp-travel-machine') },
                    { value: a.btnBg, onChange: function (v) { set({ btnBg: v || '' }); }, label: __('Button Background', 'wp-travel-machine') },
                    { value: a.btnColor, onChange: function (v) { set({ btnColor: v || '' }); }, label: __('Button Text', 'wp-travel-machine') }
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
                        el(PanelBody, { title: __('Content', 'wp-travel-machine'), initialOpen: true }, cfg.controls(props)),
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
        { label: __('Newest', 'wp-travel-machine'), value: 'date' },
        { label: __('Title', 'wp-travel-machine'), value: 'title' },
        { label: __('Price', 'wp-travel-machine'), value: 'price' },
        { label: __('Random', 'wp-travel-machine'), value: 'rand' },
        { label: __('Menu Order', 'wp-travel-machine'), value: 'menu_order' }
    ];
    var orderOptions = [
        { label: __('Descending', 'wp-travel-machine'), value: 'DESC' },
        { label: __('Ascending', 'wp-travel-machine'), value: 'ASC' }
    ];

    function gridControls(props, withActivity) {
        var a = props.attributes, set = props.setAttributes;
        var rows = [
            el(RangeControl, { key: 'count', label: __('Number of items', 'wp-travel-machine'), value: a.count, onChange: function (v) { set({ count: v }); }, min: 1, max: 24 }),
            el(RangeControl, { key: 'cols', label: __('Columns', 'wp-travel-machine'), value: a.columns, onChange: function (v) { set({ columns: v }); }, min: 1, max: 4 }),
            el(SelectControl, { key: 'orderby', label: __('Order By', 'wp-travel-machine'), value: a.orderby, options: orderByOptions, onChange: function (v) { set({ orderby: v }); } }),
            el(SelectControl, { key: 'order', label: __('Order', 'wp-travel-machine'), value: a.order, options: orderOptions, onChange: function (v) { set({ order: v }); } }),
            el(TextControl, { key: 'dest', label: __('Destination slug (optional)', 'wp-travel-machine'), value: a.destination, onChange: function (v) { set({ destination: v }); } })
        ];
        if (withActivity) {
            rows.push(el(TextControl, { key: 'act', label: __('Activity slug (optional)', 'wp-travel-machine'), value: a.activity, onChange: function (v) { set({ activity: v }); } }));
        }
        return rows;
    }

    /* ── Trip Grid ── */
    registerWptmBlock('wptm/trip-grid', {
        title: __('Trip Grid', 'wp-travel-machine'),
        description: __('A grid of travel trips with full content and style controls.', 'wp-travel-machine'),
        icon: 'palmtree',
        keywords: ['trip', 'travel', 'tour'],
        attributes: {
            count: { type: 'number', default: 6 },
            columns: { type: 'number', default: 3 },
            orderby: { type: 'string', default: 'date' },
            order: { type: 'string', default: 'DESC' },
            destination: { type: 'string', default: '' },
            activity: { type: 'string', default: '' }
        },
        controls: function (props) { return gridControls(props, true); }
    });

    /* ── Hotel Grid ── */
    registerWptmBlock('wptm/hotel-grid', {
        title: __('Hotel Grid', 'wp-travel-machine'),
        description: __('A grid of hotels with full content and style controls.', 'wp-travel-machine'),
        icon: 'building',
        keywords: ['hotel', 'room', 'stay'],
        attributes: {
            count: { type: 'number', default: 6 },
            columns: { type: 'number', default: 3 },
            orderby: { type: 'string', default: 'date' },
            order: { type: 'string', default: 'DESC' },
            destination: { type: 'string', default: '' }
        },
        controls: function (props) { return gridControls(props, false); }
    });

    /* ── Travel Search Form ── */
    registerWptmBlock('wptm/search-form', {
        title: __('Travel Search Form', 'wp-travel-machine'),
        description: __('The trip & hotel search form.', 'wp-travel-machine'),
        icon: 'search',
        keywords: ['search', 'filter', 'find'],
        attributes: { style: { type: 'string', default: 'horizontal' } },
        controls: function (props) {
            return [
                el(SelectControl, {
                    key: 'style',
                    label: __('Layout', 'wp-travel-machine'),
                    value: props.attributes.style,
                    options: [
                        { label: __('Horizontal', 'wp-travel-machine'), value: 'horizontal' },
                        { label: __('Vertical', 'wp-travel-machine'), value: 'vertical' }
                    ],
                    onChange: function (v) { props.setAttributes({ style: v }); }
                })
            ];
        }
    });

    /* ── Destinations Grid ── */
    registerWptmBlock('wptm/destinations', {
        title: __('Destinations Grid', 'wp-travel-machine'),
        description: __('A grid of destination categories.', 'wp-travel-machine'),
        icon: 'location-alt',
        keywords: ['destination', 'location', 'place'],
        attributes: { count: { type: 'number', default: 8 } },
        controls: function (props) {
            return [
                el(RangeControl, { key: 'count', label: __('Number of destinations', 'wp-travel-machine'), value: props.attributes.count, onChange: function (v) { props.setAttributes({ count: v }); }, min: 1, max: 24 })
            ];
        }
    });

    /* ── Booking Form ── */
    registerWptmBlock('wptm/booking-form', {
        title: __('Booking Form', 'wp-travel-machine'),
        description: __('The trip/hotel booking form.', 'wp-travel-machine'),
        icon: 'calendar-alt',
        keywords: ['booking', 'reserve', 'book'],
        attributes: { id: { type: 'number', default: 0 } },
        controls: function (props) {
            return [
                el(TextControl, {
                    key: 'id',
                    label: __('Trip / Hotel ID (0 = current)', 'wp-travel-machine'),
                    type: 'number',
                    value: props.attributes.id,
                    onChange: function (v) { props.setAttributes({ id: parseInt(v, 10) || 0 }); }
                })
            ];
        }
    });

})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n, window.wp.serverSideRender);
