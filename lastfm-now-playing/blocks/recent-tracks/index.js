/**
 * Last.fm Now Playing - Gutenberg Block
 *
 * @package LastFM_Now_Playing
 */

( function( blocks, element, blockEditor, components, i18n, serverSideRender ) {
	const { registerBlockType } = blocks;
	const { createElement: el, Fragment } = element;
	const { InspectorControls, useBlockProps } = blockEditor;
	const { PanelBody, RangeControl, SelectControl, ToggleControl, Placeholder, ExternalLink } = components;
	const { __ } = i18n;
	const ServerSideRender = serverSideRender;

	// Get settings from localized script.
	const settings = window.lastfmNowPlayingSettings || {};

	// Block icon.
	const blockIcon = el(
		'svg',
		{
			viewBox: '0 0 24 24',
			xmlns: 'http://www.w3.org/2000/svg'
		},
		el( 'path', {
			d: 'M10.584 17.209l-.88-2.392s-1.43 1.595-3.573 1.595c-1.897 0-3.244-1.649-3.244-4.288 0-3.381 1.704-4.591 3.381-4.591 2.42 0 3.189 1.567 3.849 3.574l.88 2.749c.88 2.666 2.529 4.81 7.285 4.81 3.409 0 5.718-1.044 5.718-3.793 0-2.227-1.265-3.381-3.629-3.932l-1.758-.385c-1.21-.275-1.567-.77-1.567-1.595 0-.934.742-1.484 1.952-1.484 1.32 0 2.034.495 2.144 1.677l2.749-.33c-.22-2.474-1.924-3.491-4.729-3.491-2.474 0-4.893.935-4.893 3.932 0 1.87.907 3.051 3.189 3.602l1.87.44c1.402.33 1.869.907 1.869 1.704 0 1.017-.99 1.43-2.86 1.43-2.776 0-3.932-1.456-4.591-3.464l-.907-2.749c-1.155-3.574-2.997-4.894-6.653-4.894C2.144 5.333 0 7.616 0 12.096c0 4.287 2.144 6.433 6.05 6.433 3.107 0 4.534-1.32 4.534-1.32z',
			fill: 'currentColor'
		} )
	);

	// Theme options.
	const themeOptions = [
		{ value: '', label: __( 'Default (from settings)', 'lastfm-now-playing' ) },
		{ value: 'light', label: __( 'Light', 'lastfm-now-playing' ) },
		{ value: 'dark', label: __( 'Dark', 'lastfm-now-playing' ) },
		{ value: 'transparent', label: __( 'Transparent', 'lastfm-now-playing' ) }
	];

	// Register the block.
	registerBlockType( 'lastfm-now-playing/recent-tracks', {
		title: __( "Paige's Last.FM Now Playing", 'lastfm-now-playing' ),
		description: __( 'Display your recently played tracks from Last.fm.', 'lastfm-now-playing' ),
		category: 'widgets',
		icon: blockIcon,
		keywords: [
			__( 'music', 'lastfm-now-playing' ),
			__( 'lastfm', 'lastfm-now-playing' ),
			__( 'scrobble', 'lastfm-now-playing' ),
			__( 'spotify', 'lastfm-now-playing' ),
			__( 'now playing', 'lastfm-now-playing' )
		],
		supports: {
			html: false,
			align: [ 'wide', 'full' ]
		},

		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const { count, theme, showAlbum, showDuration } = attributes;
			const blockProps = useBlockProps();

			// Show configuration notice if not configured.
			if ( ! settings.isConfigured ) {
				return el(
					'div',
					blockProps,
					el(
						Placeholder,
						{
							icon: blockIcon,
							label: __( "Paige's Last.FM Now Playing", 'lastfm-now-playing' ),
							instructions: __( 'Please configure your Last.fm API settings to use this block.', 'lastfm-now-playing' )
						},
						el(
							ExternalLink,
							{
								href: settings.settingsUrl,
								className: 'components-button is-primary'
							},
							__( 'Configure Settings', 'lastfm-now-playing' )
						)
					)
				);
			}

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{
							title: __( 'Display Settings', 'lastfm-now-playing' ),
							initialOpen: true
						},
						el( RangeControl, {
							label: __( 'Number of Tracks', 'lastfm-now-playing' ),
							value: count || settings.defaultCount,
							onChange: function( value ) {
								setAttributes( { count: value } );
							},
							min: 1,
							max: 50,
							help: __( 'How many recent tracks to display.', 'lastfm-now-playing' )
						} ),
						el( SelectControl, {
							label: __( 'Theme', 'lastfm-now-playing' ),
							value: theme,
							options: themeOptions,
							onChange: function( value ) {
								setAttributes( { theme: value } );
							},
							help: __( 'Choose a Spotify-inspired theme.', 'lastfm-now-playing' )
						} ),
						el( ToggleControl, {
							label: __( 'Show Album Name', 'lastfm-now-playing' ),
							checked: showAlbum,
							onChange: function( value ) {
								setAttributes( { showAlbum: value } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Show Track Duration', 'lastfm-now-playing' ),
							checked: showDuration,
							onChange: function( value ) {
								setAttributes( { showDuration: value } );
							}
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'lastfm-now-playing/recent-tracks',
						attributes: attributes
					} )
				)
			);
		},

		save: function() {
			// Server-side rendered, return null.
			return null;
		}
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n,
	window.wp.serverSideRender
);
