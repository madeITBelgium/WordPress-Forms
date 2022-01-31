import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export default function save(props) {
    
     const {
        attributes,
        className,
        clientId
    } = props;
    
    const {
        type, required, name, label, default_value, placeholder
    } = attributes;
    
    
    const blockPropsParent = useBlockProps.save({
        className: className
    });
    
    const inputProps = {
        className: 'madeit-forms-input-field',
        type: type,
        name: name,
        required: required,
        value: default_value,
        placeholder: placeholder
    };
    
	return (
        <div { ...blockPropsParent }>
            <div><label>{ label }</label></div>
            <input { ...inputProps } />
        </div>
	);
}
