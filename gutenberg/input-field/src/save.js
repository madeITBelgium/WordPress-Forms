import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export default function save(props) {
    
     const {
        attributes,
        className,
        clientId
    } = props;
    
    const {
        type, required, name, label, default_value, placeholder, minimum, maximum
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

    if(type === 'number') {
        if(minimum !== undefined && minimum !== null) {
            inputProps.min = minimum;
        }

        if(maximum !== undefined && maximum !== null) {
            inputProps.max = maximum;
        }
    }
    
	return (
        <div { ...blockPropsParent }>
            { label !== undefined && label !== null && label.length > 0 ? <div><label>{ label }</label></div> : null }
            <input { ...inputProps } />
        </div>
	);
}
