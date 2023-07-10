import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export default function save(props) {
    
     const {
        attributes,
        className,
        clientId
    } = props;
    
    const {
        filetype, required, name, label, default_value, placeholder, minimum, maximum
    } = attributes;
    
    
    const blockPropsParent = useBlockProps.save({
        className: className
    });
    
    const inputProps = {
        className: 'madeit-forms-upload-field',
        type: 'file',
        name: name,
        required: required,
        value: default_value,
        placeholder: placeholder,
        accept: filetype,
    };

	return (
        <div { ...blockPropsParent }>
            { label !== undefined && label !== null && label.length > 0 ? <div><label>{ label }</label></div> : null }
            <input { ...inputProps } />
        </div>
	);
}
