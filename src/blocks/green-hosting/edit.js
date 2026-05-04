import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes } ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block="carbonfooter/green-hosting"
				attributes={ attributes }
				EmptyResponsePlaceholder={ () => (
					<p>{ __( 'No hosting data available.', 'carbonfooter' ) }</p>
				) }
			/>
		</div>
	);
}
