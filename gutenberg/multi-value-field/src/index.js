/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType, createBlock } from '@wordpress/blocks';
import { list as icon } from '@wordpress/icons';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

/**
 * Internal dependencies
 */
import Edit from './edit';
import save from './save';

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType('madeitforms/multi-value-field', {
    icon: icon,
	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * @see ./save.js
	 */
	save,

    deprecated: [
        {
            attributes: {
                "type": {
                    "type": "string",
                    "default": "select",
                    "enum": ["select", "multi-select", "radio", "checkbox"]
                },
                "required": {
                    "type": "boolean",
                    "default": false
                },
                "name": {
                    "type": "string"
                },
                "default_value": {
                    "type": "string"
                },
                "placeholder": {
                    "type": "string"
                },
                "label": {
                    "type": "string",
                    "default": "Label"
                },
                "values": {
                    "type": "string",
                    "default": "Waarde 1\nWaarde 2"
                }
            },

            supports: {
                html: false
            },

            save( props ) {
                
                const {
                    attributes,
                    className,
                } = props;
                
                const {
                    type, required, name, label, default_value, placeholder, values
                } = attributes;
                
                
                const blockPropsParent = useBlockProps.save({
                    className: className
                });
                
                const inputProps = {
                    className: 'madeit-forms-multi-value-field',
                    type: type,
                    name: name,
                    required: required,
                    placeholder: placeholder
                };
                
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
                        html.push(<div className={'madeit-forms-radio-field'}><label><input type={type} name={name} value={ splitedValues[i] } checked={default_value === splitedValues[i] } /><span>{ splitedValues[i] }</span></label></div>);
                    }
                } else if(type === 'checkbox') {
                    for(var i = 0; i < splitedValues.length; i++) {
                        html.push(<div className={'madeit-forms-checkbox-field'}><label><input type={type} name={name +'[]'} value={ splitedValues[i] } checked={default_value === splitedValues[i] } /><span>{ splitedValues[i] }</span></label></div>);
                    }
                }
                
                return (
                    <div { ...blockPropsParent }>
                        <div><label>{ label }</label></div>
                        <div>
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
                    </div>
                );
            },
        },
        {
            attributes: {
                "type": {
                    "type": "string",
                    "default": "select",
                    "enum": ["select", "multi-select", "radio", "checkbox"]
                },
                "required": {
                    "type": "boolean",
                    "default": false
                },
                "name": {
                    "type": "string"
                },
                "default_value": {
                    "type": "string"
                },
                "placeholder": {
                    "type": "string"
                },
                "label": {
                    "type": "string",
                    "default": "Label"
                },
                "values": {
                    "type": "string",
                    "default": "Waarde 1\nWaarde 2"
                }
            },

            supports: {
                html: false
            },

            save( props ) {
                
                const {
                    attributes,
                    className,
                } = props;
                
                const {
                    type, required, name, label, default_value, placeholder, values
                } = attributes;
                
                
                const blockPropsParent = useBlockProps.save({
                    className: className
                });
                
                const inputProps = {
                    className: 'madeit-forms-multi-value-field',
                    type: type,
                    name: name,
                    required: required,
                    placeholder: placeholder
                };
                
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
                
                return (
                    <div { ...blockPropsParent }>
                        <div><label>{ label }</label></div>
                        <div>
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
                    </div>
                );
            },
        },
    ],

    transforms: {
        from: [
            {
                type: 'block',
                blocks: [ 'madeitforms/largeinput-field' ],
                transform: ( attributes ) => {
                    return createBlock( 'madeitforms/multi-value-field', {
                        type: 'checkbox',
                        required: attributes.required,
                        name: attributes.name,
                        default_value: attributes.default_value,
                        placeholder: attributes.placeholder,
                        label: attributes.label
                    } );
                },
            },
            {
                type: 'block',
                blocks: [ 'madeitforms/input-field' ],
                transform: ( attributes ) => {
                    return createBlock( 'madeitforms/multi-value-field', {
                        type: 'checkbox',
                        required: attributes.required,
                        name: attributes.name,
                        default_value: attributes.default_value,
                        placeholder: attributes.placeholder,
                        label: attributes.label
                    } );
                },
            },
        ],
        to: [
            {
                type: 'block',
                blocks: [ 'madeitforms/largeinput-field' ],
                transform: ( attributes ) => {
                    return createBlock( 'madeitforms/largeinput-field', {
                        required: attributes.required,
                        name: attributes.name,
                        default_value: attributes.default_value,
                        placeholder: attributes.placeholder,
                        label: attributes.label
                    } );
                },
            },
            {
                type: 'block',
                blocks: [ 'madeitforms/input-field' ],
                transform: ( attributes ) => {
                    return createBlock( 'madeitforms/input-field', {
                        type: 'text',
                        required: attributes.required,
                        name: attributes.name,
                        default_value: attributes.default_value,
                        placeholder: attributes.placeholder,
                        label: attributes.label,
                        values: attributes.default_value,
                    } );
                },
            },
        ]
    },
});
