/**
 * Photo Booth block editor component.
 *
 * @wordpress
 * @package VirtualPhotoBooth
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * Edit component.
 *
 * @param {Object} props Component props.
 * @param {Object} props.attributes Block attributes.
 * @param {Function} props.setAttributes Set attributes function.
 * @return {JSX.Element} Edit component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { eventId, frameId, showGalleryLink, containerClass } = attributes;
	const blockProps = useBlockProps();

	// Fetch events from REST API.
	const [ events, setEvents ] = useState( [] );
	const [ loading, setLoading ] = useState( true );

	useEffect( () => {
		fetch( '/wp-json/virtual-photo-booth/v1/events' )
			.then( ( response ) => response.json() )
			.then( ( data ) => {
				setEvents( data );
				setLoading( false );
			} )
			.catch( () => {
				setLoading( false );
			} );
	}, [] );

	// Get frame image URL if frameId is set.
	const frameImage = useSelect(
		( select ) => {
			if ( ! frameId ) {
				return null;
			}
			return select( 'core' ).getMedia( frameId );
		},
		[ frameId ]
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Photo Booth Settings', 'virtual-photo-booth' ) }>
					<SelectControl
						label={ __( 'Event', 'virtual-photo-booth' ) }
						value={ eventId || 0 }
						options={ [
							{ label: __( '— Use Default —', 'virtual-photo-booth' ), value: 0 },
							...events.map( ( event ) => ( {
								label: event.title,
								value: event.id,
							} ) ),
						] }
						onChange={ ( value ) => setAttributes( { eventId: parseInt( value, 10 ) || 0 } ) }
						disabled={ loading }
					/>

					<TextControl
						label={ __( 'Frame Image ID', 'virtual-photo-booth' ) }
						value={ frameId || '' }
						onChange={ ( value ) => setAttributes( { frameId: parseInt( value, 10 ) || 0 } ) }
						help={ __( 'Enter a media library image ID to use as a frame overlay.', 'virtual-photo-booth' ) }
					/>

					<ToggleControl
						label={ __( 'Show Gallery Link', 'virtual-photo-booth' ) }
						checked={ showGalleryLink }
						onChange={ ( value ) => setAttributes( { showGalleryLink: value } ) }
					/>

					<TextControl
						label={ __( 'Container CSS Class', 'virtual-photo-booth' ) }
						value={ containerClass }
						onChange={ ( value ) => setAttributes( { containerClass: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="pbe-photo-booth-preview">
					<div className="pbe-preview-camera">
						<div className="pbe-camera-placeholder">
							<span className="dashicons dashicons-camera"></span>
							<p>{ __( 'Camera Preview', 'virtual-photo-booth' ) }</p>
						</div>
						{ frameImage && (
							<div className="pbe-frame-preview">
								<img
									src={ frameImage.source_url }
									alt={ __( 'Frame Preview', 'virtual-photo-booth' ) }
									style={ { maxWidth: '100%', opacity: 0.5 } }
								/>
							</div>
						) }
					</div>
					<div className="pbe-preview-controls">
						<button className="button" disabled>
							{ __( 'Capture', 'virtual-photo-booth' ) }
						</button>
						<button className="button" disabled>
							{ __( 'Retake', 'virtual-photo-booth' ) }
						</button>
						<button className="button" disabled>
							{ __( 'Upload', 'virtual-photo-booth' ) }
						</button>
					</div>
					<p className="pbe-preview-note">
						{ __( 'Photo booth interface will appear on the frontend.', 'virtual-photo-booth' ) }
					</p>
				</div>
			</div>
		</>
	);
}


