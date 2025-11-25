/**
 * Gallery block editor component.
 *
 * @wordpress
 * @package VirtualPhotoBooth
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, TextControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';

/**
 * Edit component.
 *
 * @param {Object} props Component props.
 * @param {Object} props.attributes Block attributes.
 * @param {Function} props.setAttributes Set attributes function.
 * @return {JSX.Element} Edit component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { eventId, columns, limit, order, containerClass } = attributes;
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

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Gallery Settings', 'virtual-photo-booth' ) }>
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

					<RangeControl
						label={ __( 'Columns', 'virtual-photo-booth' ) }
						value={ columns }
						onChange={ ( value ) => setAttributes( { columns: value } ) }
						min={ 1 }
						max={ 6 }
					/>

					<RangeControl
						label={ __( 'Limit', 'virtual-photo-booth' ) }
						value={ limit }
						onChange={ ( value ) => setAttributes( { limit: value } ) }
						min={ 1 }
						max={ 100 }
					/>

					<SelectControl
						label={ __( 'Order', 'virtual-photo-booth' ) }
						value={ order }
						options={ [
							{ label: __( 'Newest First', 'virtual-photo-booth' ), value: 'DESC' },
							{ label: __( 'Oldest First', 'virtual-photo-booth' ), value: 'ASC' },
						] }
						onChange={ ( value ) => setAttributes( { order: value } ) }
					/>

					<TextControl
						label={ __( 'Container CSS Class', 'virtual-photo-booth' ) }
						value={ containerClass }
						onChange={ ( value ) => setAttributes( { containerClass: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="pbe-gallery-preview">
					<div className="pbe-gallery-grid" style={ { gridTemplateColumns: `repeat(${ columns }, 1fr)` } }>
						{ Array.from( { length: Math.min( limit, 6 ) } ).map( ( _, i ) => (
							<div key={ i } className="pbe-gallery-item">
								<span className="dashicons dashicons-format-image"></span>
							</div>
						) ) }
					</div>
					<p className="pbe-preview-note">
						{ __( 'Gallery will display approved photos on the frontend.', 'virtual-photo-booth' ) }
					</p>
				</div>
			</div>
		</>
	);
}

