import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
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

	// Make the row element itself the inner blocks container, so the stat
	// blocks are its direct children and the .cf-block-full__row grid applies
	// in the editor exactly like on the frontend (no extra wrapper divs).
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'cf-block-full__row' },
		{
			allowedBlocks: STAT_BLOCK_NAMES,
			template: TEMPLATE,
			orientation: 'horizontal',
		}
	);

	return (
		<div { ...blockProps }>
			<div { ...innerBlocksProps } />
			<div className="cf-block-full__cta">
				<span>
					{ __( 'Want to learn more?', 'carbonfooter' ) }{ ' ' }
					<strong>Carbonfooter.nl</strong>
				</span>
			</div>
		</div>
	);
}
