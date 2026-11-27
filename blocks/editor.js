/**
 * Editor UI for the Ethiopian Calendar blocks.
 *
 * Plain JavaScript (no build step). Block metadata comes from each block.json;
 * previews are rendered by PHP through ServerSideRender, so the editor shows
 * exactly what visitors will see.
 */
( function ( wp ) {
	'use strict';

	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { InspectorControls, BlockControls, AlignmentControl, useBlockProps } = wp.blockEditor;
	const { PanelBody, SelectControl, ToggleControl, Disabled } = wp.components;
	const ServerSideRender = wp.serverSideRender;
	const { __ } = wp.i18n;

	const languageOptions = [
		{ label: __( 'Site setting', 'ethiopian-calendar' ), value: '' },
		{ label: 'English', value: 'en' },
		{ label: 'አማርኛ (Amharic)', value: 'am' },
	];
	const numeralOptions = [
		{ label: __( 'Site setting', 'ethiopian-calendar' ), value: '' },
		{ label: '1 2 3', value: 'arabic' },
		{ label: "፩ ፪ ፫ (Ge'ez)", value: 'geez' },
	];
	const formatOptions = [
		{ label: __( 'Site setting', 'ethiopian-calendar' ), value: '' },
		{ label: '18th Hidar, 2019 E.C.', value: '1' },
		{ label: '18th Hidar, 2019', value: '2' },
		{ label: '18/03/2019 E.C.', value: '3' },
		{ label: '18/03/2019', value: '4' },
		{ label: 'Friday, Hidar 18, 2019', value: '5' },
	];
	const displayOptions = [
		{ label: __( 'Site setting', 'ethiopian-calendar' ), value: '' },
		{ label: __( 'Ethiopian only', 'ethiopian-calendar' ), value: 'ethiopian' },
		{ label: __( 'Ethiopian and Gregorian', 'ethiopian-calendar' ), value: 'both' },
	];

	/**
	 * Build an edit component: sidebar controls plus a server-rendered preview.
	 *
	 * @param {string}   name     Block name.
	 * @param {Function} controls ( attributes, setAttributes ) => array of controls.
	 * @param {boolean}  align    Whether to show the text alignment toolbar.
	 */
	function edit( name, controls, align ) {
		return function Edit( { attributes, setAttributes } ) {
			const blockProps = useBlockProps( {
				className: attributes.textAlign ? `has-text-align-${ attributes.textAlign }` : undefined,
			} );
			return el(
				Fragment,
				null,
				align &&
					el(
						BlockControls,
						{ group: 'block' },
						el( AlignmentControl, {
							value: attributes.textAlign,
							onChange: ( textAlign ) => setAttributes( { textAlign } ),
						} )
					),
				el(
					InspectorControls,
					null,
					el( PanelBody, { title: __( 'Settings', 'ethiopian-calendar' ) }, ...controls( attributes, setAttributes ) )
				),
				el(
					'div',
					blockProps,
					el( Disabled, null, el( ServerSideRender, { block: name, attributes: { ...attributes, textAlign: undefined } } ) )
				)
			);
		};
	}

	const select = ( label, key, options, attributes, setAttributes ) =>
		el( SelectControl, {
			key,
			label,
			value: attributes[ key ],
			options,
			onChange: ( value ) => setAttributes( { [ key ]: value } ),
			__nextHasNoMarginBottom: true,
		} );

	registerBlockType( 'ethiopian-calendar/today', {
		edit: edit(
			'ethiopian-calendar/today',
			( a, set ) => [
				select( __( 'Language', 'ethiopian-calendar' ), 'language', languageOptions, a, set ),
				select( __( 'Numbers', 'ethiopian-calendar' ), 'numerals', numeralOptions, a, set ),
				select( __( 'Format', 'ethiopian-calendar' ), 'format', formatOptions, a, set ),
				select( __( 'Show', 'ethiopian-calendar' ), 'display', displayOptions, a, set ),
			],
			true
		),
		save: () => null,
	} );

	registerBlockType( 'ethiopian-calendar/clock', {
		edit: edit(
			'ethiopian-calendar/clock',
			( a, set ) => [
				select( __( 'Language', 'ethiopian-calendar' ), 'language', languageOptions, a, set ),
				el( ToggleControl, {
					key: 'showDate',
					label: __( "Show today's date", 'ethiopian-calendar' ),
					checked: a.showDate,
					onChange: ( showDate ) => set( { showDate } ),
					__nextHasNoMarginBottom: true,
				} ),
			],
			true
		),
		save: () => null,
	} );

	registerBlockType( 'ethiopian-calendar/converter', {
		edit: edit(
			'ethiopian-calendar/converter',
			( a, set ) => [
				select( __( 'Language', 'ethiopian-calendar' ), 'language', languageOptions, a, set ),
				select( __( 'Numbers', 'ethiopian-calendar' ), 'numerals', numeralOptions, a, set ),
			],
			false
		),
		save: () => null,
	} );

	registerBlockType( 'ethiopian-calendar/calendar', {
		edit: edit(
			'ethiopian-calendar/calendar',
			( a, set ) => [
				select( __( 'Language', 'ethiopian-calendar' ), 'language', languageOptions, a, set ),
				select( __( 'Numbers', 'ethiopian-calendar' ), 'numerals', numeralOptions, a, set ),
			],
			false
		),
		save: () => null,
	} );
} )( window.wp );
