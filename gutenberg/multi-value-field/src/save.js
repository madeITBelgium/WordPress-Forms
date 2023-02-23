import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export default function save(props) {
    
     const {
        attributes,
        className,
        clientId
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
            { label !== null && label !== undefined && label.length > 0 ? <div><label>{ label }</label></div> : null }
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
}
