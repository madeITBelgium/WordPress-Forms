/**
 * Registers the Made I.T. Form block.
 */
import { registerBlockType } from '@wordpress/blocks';

import './editor.scss';
import './style.scss';

import metadata from '../block.json';
import Edit from './edit';
import save from './save';

registerBlockType(metadata.name, {
	edit: Edit,
	save,
});
