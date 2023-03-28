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
        type, required, name, label, default_value, placeholder, minimum, maximum
    } = attributes;

    const inputTypes = [
        { value: 'text', label: __( 'Text' ) },
        { value: 'email', label: __( 'E-mail Address' ) },
        { value: 'url', label: __( 'URL' ) },
        { value: 'tel', label: __( 'Phone' ) },
        { value: 'number', label: __( 'Number' ) },
        { value: 'date', label: __( 'Date' ) },
    ];
    
    if(name === undefined || name === null) {
        setAttributes({name: 'field-' + uniqueId()})
    }
    
    const blockPropsParent = useBlockProps({
        className: className
    });
    
    const inputProps = {
        className: 'madeit-forms-input-field',
        type: type,
        name: name,
        value: default_value,
        placeholder: placeholder,
        disabled: true
    };

    if(type === 'number') {
        if(minimum !== undefined && minimum !== null) {
            inputProps.min = minimum;
        }

        if(maximum !== undefined && maximum !== null) {
            inputProps.max = maximum;
        }
    }
    
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
                <SelectControl label={ __( 'Type' ) } value={ type }
                    options={ inputTypes.map( ( { value, label } ) => ( {
                        value: value,
                        label: label,
                    } ) ) }
                    onChange={ ( value ) => setAttributes( { type: value } ) }
                />
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
                    required={ true }
                    onChange={ ( value ) => {
                        value.toLowerCase().replace(/[^a-z0-9-_]/gi,'');
                        setAttributes( { name: value } )
                    }}
                />
                { type === 'number' && (
                    <div>
                        <TextControl
                            label={ __( 'Minimum' ) }
                            type="number"
                            value={ minimum }
                            onChange={ ( value ) => setAttributes( { minimum: value } ) }
                        />
                        <TextControl
                            label={ __( 'Maximum' ) }
                            type="number"
                            value={ maximum }
                            onChange={ ( value ) => setAttributes( { maximum: value } ) }
                        />
                    </div>
                )}
            </PanelBody>
        </InspectorControls>,
        <div>
            <div { ...blockPropsParent }>
                <div><label>{ label }</label></div>
                <input { ...inputProps } />
            </div>
            {!validName && <div className={'ma-forms-input-error'}>{__('Duplicated name found. Make the name of this field unique.')}</div>}
        </div>
	];
}
