import { __ } from '@wordpress/i18n';
import { uniqueId } from 'lodash';

import { useBlockProps, InspectorControls} from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl, TextareaControl } from "@wordpress/components";

import './editor.scss';

export default function Edit( props ) {
    const {
        attributes,
        setAttributes,
        className,
        clientId
    } = props;
    
    const {
        selected, name, id, label, value
    } = attributes;
    
    if(name === undefined || name === null) {
        setAttributes({name: 'field-' + uniqueId()})
    }
    
    const blockPropsParent = useBlockProps({
        className: className
    });
    
    var blocks = wp.data.select( 'core/block-editor' ).getBlocks();
    var validName = true;
    /*
    for(var i = 0; i < blocks.length; i++) {
        if( blocks[i].clientId !== clientId && blocks[i].attributes.name !== undefined && blocks[i].attributes.name === name) {
            validName = false;
        }
    }
    */
    
    var html = [];
    html.push(<div className={'madeit-forms-single-radio-field'}><input type="radio" id={id} name={name} value={ value } checked={selected } />{ label }</div>);
   
    
	return [
        <InspectorControls>
            <PanelBody title={__('Field settings')} initialOpen={true}>
                <TextControl
                    label={ __( 'Label' ) }
                    value={ label }
                    onChange={ ( value ) => setAttributes( { label: value } ) }
                />
                <TextControl
                    label={ __( 'ID' ) }
                    value={ id }
                    onChange={ ( value ) => setAttributes( { id: value } ) }
                />
                <ToggleControl
                    label={ __( 'Selected' ) }
                    checked={ selected }
                    onChange={ ( value ) => setAttributes( { selected: value } ) }
                />
                <TextControl
                    label={ __( 'Name' ) }
                    help={ __( 'Deze naam kan je gebruiken in de acties. Enkel letters, cijfers, - of _ zijn toegelaten.' ) }
                    value={ name }
                    onChange={ ( value ) => {
                        value.toLowerCase().replace(/[^a-z0-9-_]/gi,'');
                        setAttributes( { name: value } )
                    }}
                />
                <TextControl
                    label={ __( 'Value' ) }
                    value={ value }
                    onChange={ ( value ) => setAttributes( { value: value } ) }
                />
            </PanelBody>
        </InspectorControls>,
        <div>
            <div { ...blockPropsParent }>
                <div>{ html }</div>
            </div>
            {!validName && <div className={'ma-forms-input-error'}>{__('Duplicated name found. Make the name of this field unique.')}</div>}
        </div>
	];
}
