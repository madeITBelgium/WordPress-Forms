import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export default function save(props) {
    
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
            { label !== undefined && label !== null && label.length > 0 ? <div><label>{ label }</label></div> : null }
            <textarea { ...inputProps }>{default_value}</textarea>
        </div>
	);
}
