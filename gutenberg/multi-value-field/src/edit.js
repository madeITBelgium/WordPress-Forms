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
        type, required, name, label, default_value, placeholder, values
    } = attributes;
    console.log(props);
    const inputTypes = [
        { value: 'select', label: __( 'Dropdown' ) },
        { value: 'radio', label: __( 'Radio' ) },
        { value: 'checkbox', label: __( 'Checkbox' ) },
        { value: 'multi-select', label: __( 'Multi Select' ) },
    ];
    
    if(name === undefined || name === null) {
        setAttributes({name: 'field-' + uniqueId()})
    }
    
    const blockPropsParent = useBlockProps({
        className: className
    });
    
    const inputProps = {
        className: 'madeit-forms-multi-value-field',
        type: type,
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
    
    var html = [];
    var splitedValues = values.split(/\r?\n/);
    if(type === 'select' || type === 'multi-select') {
        if(placeholder !== null && placeholder !== '') {
            html.push(<option>{ placeholder }</option>);
        }
        for(var i = 0; i < splitedValues.length; i++) {
            html.push(<option value={ splitedValues[i] } selected={default_value === splitedValues[i] }>{ splitedValues[i] }</option>);
        }
    } else if(type === 'radio') {
        for(var i = 0; i < splitedValues.length; i++) {
            html.push(<div className={'madeit-forms-radio-field'}><input type={type} name={name} value={ splitedValues[i] } checked={default_value === splitedValues[i] } />{ splitedValues[i] }</div>);
        }
    } else if(type === 'checkbox') {
        for(var i = 0; i < splitedValues.length; i++) {
            html.push(<div className={'madeit-forms-checkbox-field'}><input type={type} name={name +'[]'} value={ splitedValues[i] } checked={default_value === splitedValues[i] } />{ splitedValues[i] }</div>);
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
                    onChange={ ( value ) => {
                        value.toLowerCase().replace(/[^a-z0-9-_]/gi,'');
                        setAttributes( { name: value } )
                    }}
                />
                <TextareaControl
                    label={ __( 'Values' ) }
                    help={ __( 'Values, each line is a new value.' ) }
                    value={ values }
                    onChange={ ( value ) => { setAttributes( { values: value } ) }}
                />
            </PanelBody>
        </InspectorControls>,
        <div>
            <div { ...blockPropsParent }>
                <div><label>{ label }</label></div>
                {
                    type === 'select' && <select {...inputProps}>
                        { html }
                    </select>
                }
                {
                    (type === 'radio' || type === 'checkbox') && <div>{ html }</div>
                }
                {
                    type === 'multi-select' && <select multiple {...inputProps}>
                        { html }
                    </select>
                }
            </div>
            {!validName && <div className={'ma-forms-input-error'}>{__('Duplicated name found. Make the name of this field unique.')}</div>}
        </div>
	];
}
