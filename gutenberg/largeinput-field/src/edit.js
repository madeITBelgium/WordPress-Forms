import { __ } from '@wordpress/i18n';
import { uniqueId } from 'lodash';

import { useBlockProps, InspectorControls} from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl } from "@wordpress/components";

import './editor.scss';

export default function Edit( props ) {
    const {
        attributes,
        setAttributes,
        className,
        clientId
    } = props;
    
    const {
        required, name, label, default_value, placeholder
    } = attributes;

    if(name === undefined || name === null) {
        setAttributes({name: 'field-' + uniqueId()})
    }
    
    const blockPropsParent = useBlockProps({
        className: className
    });
    
    const inputProps = {
        className: 'madeit-forms-largeinput-field',
        name: name,
        placeholder: placeholder,
        disabled: true
    };
    
    var blocks = wp.data.select( 'core/block-editor' ).getBlocks();
    var validName = true;
    for(var i = 0; i < blocks.length; i++) {
        if( blocks[i].clientId !== clientId && blocks[i].attributes.name !== undefined && blocks[i].attributes.name === name) {
            validName = false;
        }
    }
    
	return [
        <InspectorControls>
            <PanelBody title={__('Field settings')} initialOpen={true}>
                <TextControl
                    label={ __( 'Label' ) }
                    value={ label }
                    onChange={ ( value ) => setAttributes( { label: value } ) }
                />
                <TextControl
                    label={ __( 'Default Value' ) }
                    value={ default_value }
                    onChange={ ( value ) => setAttributes( { default_value: value } ) }
                />
                <TextControl
                    label={ __( 'Placeholder' ) }
                    value={ placeholder }
                    onChange={ ( value ) => setAttributes( { placeholder: value } ) }
                />
                <ToggleControl
                    label={ __( 'Required' ) }
                    checked={ required }
                    onChange={ ( value ) => setAttributes( { required: value } ) }
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
            </PanelBody>
        </InspectorControls>,
        <div>
            <div { ...blockPropsParent }>
                <div><label>{ label }</label></div>
                <textarea { ...inputProps }>{ default_value }</textarea>
            </div>
            {!validName && <div className={'ma-forms-input-error'}>{__('Duplicated name found. Make the name of this field unique.')}</div>}
        </div>
	];
}
