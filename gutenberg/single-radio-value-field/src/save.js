import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export default function save(props) {
    
     const {
        attributes,
        className,
        clientId
    } = props;
    
    const {
        selected, name, id, label, value
    } = attributes;
    
    const blockPropsParent = useBlockProps.save({
        className: className
    });
    
    var html = [];
    html.push(<div className={'madeit-forms-single-radio-field'}><label><input type="radio" id={id} name={name} value={ value } checked={selected } /><span>{ label }</span></label></div>);
    
	return (
        <div { ...blockPropsParent }>
            { html }
        </div>
	);
}
