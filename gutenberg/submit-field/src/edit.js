import './editor.scss';
/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from '@wordpress/element';
import {
	Button,
	ButtonGroup,
	PanelBody,
	TextControl,
	ToolbarButton,
	Popover,
} from '@wordpress/components';
import {
	BlockControls,
	InspectorControls,
	RichText,
	useBlockProps,
	__experimentalUseBorderProps as useBorderProps,
	__experimentalUseColorProps as useColorProps,
	__experimentalGetSpacingClassesAndStyles as useSpacingProps,
} from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';

import './editor.scss';

function WidthPanel( { selectedWidth, setAttributes } ) {
	function handleChange( newWidth ) {
		// Check if we are toggling the width off
		const width = selectedWidth === newWidth ? undefined : newWidth;

		// Update attributes
		setAttributes( { width } );
	}

	return (
		<PanelBody title={ __( 'Width settings' ) }>
			<ButtonGroup aria-label={ __( 'Button width' ) }>
				{ [ 25, 50, 75, 100 ].map( ( widthValue ) => {
					return (
						<Button
							key={ widthValue }
							isSmall
							variant={
								widthValue === selectedWidth
									? 'primary'
									: undefined
							}
							onClick={ () => handleChange( widthValue ) }
						>
							{ widthValue }%
						</Button>
					);
				} ) }
			</ButtonGroup>
		</PanelBody>
	);
}

function ButtonEdit( props ) {
	const {
		attributes,
		setAttributes,
		className,
		isSelected,
		onReplace,
		mergeBlocks,
	} = props;
    
	const {
		style,
		text,
		width,
	} = attributes;
    
	function setButtonText( newText ) {
		// Remove anchor tags from button text content.
		setAttributes( { text: newText.replace( /<\/?a[^>]*>/g, '' ) } );
	}

	const borderProps = useBorderProps( attributes );
	const colorProps = useColorProps( attributes );
	const spacingProps = useSpacingProps( attributes );
	const blockProps = useBlockProps();

	
	return (
		<>
			<div
				{ ...blockProps }
				className={ classnames( blockProps.className, {
					[ 'has-custom-width wp-block-madeitforms-submit-button__width-' + width ]: width,
					[ 'has-custom-font-size' ]: blockProps.style.fontSize,
				} ) }
			>
				<RichText
					aria-label={ __( 'Button text' ) }
					placeholder={ __( 'Add text…' ) }
					value={ text }
					onChange={ ( value ) => setButtonText( value ) }
					withoutInteractiveFormatting
					className={ classnames(
						className,
						'wp-block-madeitforms-submit-button__link',
						colorProps.className,
						borderProps.className,
						{
							// For backwards compatibility add style that isn't
							// provided via block support.
							'no-border-radius': style?.border?.radius === 0,
						}
					) }
					style={ {
						...borderProps.style,
						...colorProps.style,
						...spacingProps.style,
					} }
					onReplace={ onReplace }
					onMerge={ mergeBlocks }
					identifier="text"
				/>
			</div>
			
			<InspectorControls>
				<WidthPanel
					selectedWidth={ width }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
		</>
	);
}

export default ButtonEdit;