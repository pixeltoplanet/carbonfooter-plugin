/**
 * "Carbonfooter" panel in the post/page document sidebar.
 * Shows the current page's measured numbers with a "Measure now" button.
 */
import { registerPlugin } from '@wordpress/plugins';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button, Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { STORE_NAME } from './store';

const ROWS = [
	[ 'emissions', __( 'CO₂ per visit', 'carbonfooter' ) ],
	[ 'page_size', __( 'Page weight', 'carbonfooter' ) ],
	[ 'trees', __( 'Trees to offset per year', 'carbonfooter' ) ],
	[ 'driving', __( 'Driving distance per year', 'carbonfooter' ) ],
	[ 'last_measured', __( 'Last measured', 'carbonfooter' ) ],
];

function CarbonfooterPanel() {
	const { postId, isViewable, isPublished } = useSelect( ( select ) => {
		const editor = select( editorStore );
		const postType = select( coreStore ).getPostType(
			editor.getCurrentPostType()
		);
		return {
			postId: editor.getCurrentPostId(),
			isViewable: !! postType?.viewable,
			isPublished: editor.getCurrentPostAttribute( 'status' ) === 'publish',
		};
	}, [] );

	const { stats, measuring, error } = useSelect(
		( select ) => {
			if ( ! postId || ! isViewable ) {
				return {};
			}
			const cf = select( STORE_NAME );
			return {
				stats: cf.getStats( postId ),
				measuring: cf.isMeasuring( postId ),
				error: cf.getError( postId ),
			};
		},
		[ postId, isViewable ]
	);

	const { measure } = useDispatch( STORE_NAME );

	if ( ! postId || ! isViewable ) {
		return null;
	}

	let help = null;
	if ( stats && ! stats.data_collection ) {
		help = __( 'Data collection is turned off in Carbonfooter settings.', 'carbonfooter' );
	} else if ( ! isPublished ) {
		help = __( 'Publish this page to measure it.', 'carbonfooter' );
	}

	return (
		<PluginDocumentSettingPanel
			name="carbonfooter-panel"
			title={ __( 'Carbonfooter', 'carbonfooter' ) }
			className="carbonfooter-editor-panel"
		>
			{ ! stats && ! error && <Spinner /> }

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			{ stats && (
				<>
					{ ! stats.measured && (
						<p>
							{ __(
								'This page has not been measured yet. Values below use the site average.',
								'carbonfooter'
							) }
						</p>
					) }
					<table className="carbonfooter-editor-panel__table" style={ { width: '100%' } }>
						<tbody>
							{ ROWS.map( ( [ key, label ] ) => (
								<tr key={ key }>
									<th scope="row" style={ { textAlign: 'left', fontWeight: 400 } }>
										{ label }
									</th>
									<td style={ { textAlign: 'right', fontWeight: 600 } }>
										{ stats.formatted?.[ key ] }
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</>
			) }

			<Button
				variant="secondary"
				onClick={ () => measure( postId ) }
				isBusy={ measuring }
				disabled={ measuring || !! help || ! stats }
				accessibleWhenDisabled
				style={ { marginTop: '12px' } }
			>
				{ measuring
					? __( 'Measuring…', 'carbonfooter' )
					: __( 'Measure now', 'carbonfooter' ) }
			</Button>

			{ help && (
				<p className="components-base-control__help" style={ { marginTop: '8px' } }>
					{ help }
				</p>
			) }
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'carbonfooter', {
	render: CarbonfooterPanel,
	icon: 'cloud',
} );
