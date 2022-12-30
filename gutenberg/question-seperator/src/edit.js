import { __ } from '@wordpress/i18n';
import { uniqueId } from 'lodash';

import { useBlockProps, InspectorControls} from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl } from "@wordpress/components";

import './editor.scss';

export default function Edit( props ) {

	return [
        <div>
            <div>
                <hr />
            </div>
        </div>
	];
}
