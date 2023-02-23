/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType, createBlock } from '@wordpress/blocks';
import { Path, SVG } from '@wordpress/primitives';
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
registerBlockType('madeitforms/largeinput-field', {
    icon: <SVG viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
		<Path d="M20 6H4c-1.1 0-2 .9-2 2v9c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm.5 11c0 .3-.2.5-.5.5H4c-.3 0-.5-.2-.5-.5V8c0-.3.2-.5.5-.5h16c.3 0 .5.2.5.5v9zM10" />
	</SVG>,
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
                required: {
                    type: "boolean",
                    default: false
                },
                name: {
                    type: "string"
                },
                default_value: {
                    type: "string"
                },
                placeholder: {
                    type: "string"
                },
                label: {
                    type: "string"
                }
            },

            supports: {
                html: false,
            },

            save( props ) {
                const {
                    attributes,
                    className,
                    clientId
                } = props;
                
                const {
                    required, name, label, default_value, placeholder
                } = attributes;
                
                
                const blockPropsParent = useBlockProps.save({
                    className: className
                });
                
                const inputProps = {
                    className: 'madeit-forms-largeinput-field',
                    name: name,
                    required: required,
                    placeholder: placeholder
                };
                
                return (
                    <div { ...blockPropsParent }>
                        <div><label>{ label }</label></div>
                        <textarea { ...inputProps }>{default_value}</textarea>
                    </div>
                );
            },
        }
    ],

});
