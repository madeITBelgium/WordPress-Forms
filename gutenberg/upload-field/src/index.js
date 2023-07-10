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

const attributes = {
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
    },
    minimum: {
        type: "number"
    },
    maximum: {
        type: "number"
    },
    filetype: {
        type: "string",
        default: "application/pdf"
    }
};

const supports = {
    "html": false
};

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType('madeitforms/upload-field', {
    icon: <SVG viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
		<Path d="M20 6H4c-1.1 0-2 .9-2 2v9c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm.5 11c0 .3-.2.5-.5.5H4c-.3 0-.5-.2-.5-.5V8c0-.3.2-.5.5-.5h16c.3 0 .5.2.5.5v9zM10" />
	</SVG>,

    supports,
    attributes,

	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * @see ./save.js
	 */
	save: save,

    deprecated: [
        
    ],

    transforms: {
        from: [
            {
                type: 'block',
                blocks: [ 'madeitforms/largeinput-field' ],
                transform: ( attributes ) => {
                    return createBlock( 'madeitforms/upload-field', {
                        required: attributes.required,
                        name: attributes.name,
                        default_value: attributes.default_value,
                        placeholder: attributes.placeholder,
                        label: attributes.label,
                        filetype: "application/pdf"
                    } );
                },
            },
            {
                type: 'block',
                blocks: [ 'madeitforms/multi-value-field' ],
                transform: ( attributes ) => {
                    return createBlock( 'madeitforms/upload-field', {
                        required: attributes.required,
                        name: attributes.name,
                        default_value: attributes.default_value,
                        placeholder: attributes.placeholder,
                        label: attributes.label,
                        filetype: "application/pdf"
                    } );
                },
            },
            {
                type: 'block',
                blocks: [ 'madeitforms/input-field' ],
                transform: ( attributes ) => {
                    return createBlock( 'madeitforms/upload-field', {
                        required: attributes.required,
                        name: attributes.name,
                        default_value: attributes.default_value,
                        placeholder: attributes.placeholder,
                        label: attributes.label,
                        filetype: "application/pdf"
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
                        label: attributes.label
                    } );
                },
            },
            {
                type: 'block',
                blocks: [ 'madeitforms/multi-value-field' ],
                transform: ( attributes ) => {
                    return createBlock( 'madeitforms/multi-value-field', {
                        type: 'checkbox',
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
