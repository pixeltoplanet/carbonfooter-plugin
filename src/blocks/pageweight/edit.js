import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes } ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block="carbonfooter/pageweight"
				attributes={ attributes }
				EmptyResponsePlaceholder={ () => (
					<p>{ __( 'No page weight data available.', 'carbonfooter' ) }</p>
				) }
			/>
		</div>
	);
}
