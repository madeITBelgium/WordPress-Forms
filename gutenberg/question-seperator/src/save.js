import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export default function save(props) {
     const {
        className
    } = props;
    
    const blockPropsParent = useBlockProps.save({
        className: className
    });
    
	return (
        <div { ...blockPropsParent }></div>
	);
}
