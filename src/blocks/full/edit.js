import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { STAT_BLOCK_NAMES } from '../shared/constants';

const TEMPLATE = [
	[ 'carbonfooter/green-hosting' ],
	[ 'carbonfooter/pageweight' ],
	[ 'carbonfooter/emissions' ],
	[ 'carbonfooter/driving' ],
	[ 'carbonfooter/trees' ],
];

export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'cf-block-full',
	} );

	return (
		<div { ...blockProps }>
			<div className="cf-block-full__row">
				<InnerBlocks
					allowedBlocks={ STAT_BLOCK_NAMES }
					template={ TEMPLATE }
					orientation="horizontal"
				/>
			</div>
			<div className="cf-block-full__cta">
				<span>
					{ __( 'Want to learn more?', 'carbonfooter' ) }{ ' ' }
					<strong>Carbonfooter.nl</strong>
				</span>
			</div>
		</div>
	);
}
