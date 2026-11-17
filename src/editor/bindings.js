/**
 * Editor side of the `carbonfooter/stats` block bindings source.
 * The server side (inc/class-block-bindings.php) renders the real values;
 * this shows previews in the editor and lists the fields in the
 * Attributes panel so users can bind without editing markup.
 */
import { registerBlockBindingsSource } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { STORE_NAME } from './store';

export const FIELDS = [
	{ key: 'emissions', label: __( 'CO₂ per visit', 'carbonfooter' ) },
	{ key: 'page_size', label: __( 'Page weight', 'carbonfooter' ) },
	{ key: 'trees', label: __( 'Trees to offset per year', 'carbonfooter' ) },
	{ key: 'driving', label: __( 'Driving distance per year', 'carbonfooter' ) },
	{ key: 'green_hosting', label: __( 'Green hosting', 'carbonfooter' ) },
	{ key: 'last_measured', label: __( 'Last measured', 'carbonfooter' ) },
];

const labelFor = ( key ) =>
	FIELDS.find( ( field ) => field.key === key )?.label ?? key;

if ( typeof registerBlockBindingsSource === 'function' ) {
	registerBlockBindingsSource( {
		name: 'carbonfooter/stats',
		label: __( 'Carbonfooter', 'carbonfooter' ),
		usesContext: [ 'postId' ],
		getValues( { select, context, bindings } ) {
			const stats = context?.postId
				? select( STORE_NAME ).getStats( context.postId )
				: undefined;

			const values = {};
			for ( const [ attribute, binding ] of Object.entries( bindings ) ) {
				const key = binding?.args?.key;
				// Without a post (e.g. a template), show the field name as a placeholder.
				values[ attribute ] =
					stats?.formatted?.[ key ] ?? `[${ labelFor( key ) }]`;
			}
			return values;
		},
		canUserEditValue: () => false,
		getFieldsList: () =>
			FIELDS.map( ( { key, label } ) => ( {
				label,
				type: 'string',
				args: { key },
			} ) ),
	} );
}
