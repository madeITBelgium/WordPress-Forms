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

    const blockPropsParent = useBlockProps({
        className: className
    });

	return [
        <InspectorControls></InspectorControls>,
        <div { ...blockPropsParent }>
            <div class="question-seperator">
                <hr />
            </div>
        </div>
	];
}
