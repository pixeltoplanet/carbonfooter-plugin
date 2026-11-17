/**
 * Small data store holding per-post Carbonfooter stats for the editor.
 * Shared by the document sidebar panel and the block bindings source.
 */
import { createReduxStore, register } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

export const STORE_NAME = 'carbonfooter/stats';

const DEFAULT_STATE = { stats: {}, measuring: {}, errors: {} };

const actions = {
	receiveStats( postId, stats ) {
		return { type: 'RECEIVE_STATS', postId, stats };
	},
	setError( postId, message ) {
		return { type: 'SET_ERROR', postId, message };
	},
	measure( postId ) {
		return async ( { dispatch } ) => {
			dispatch( { type: 'MEASURE_START', postId } );
			try {
				const stats = await apiFetch( {
					path: `/carbonfooter/v1/posts/${ postId }/measure`,
					method: 'POST',
				} );
				dispatch.receiveStats( postId, stats );
			} catch ( error ) {
				dispatch.setError( postId, error?.message || String( error ) );
			}
		};
	},
};

function reducer( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case 'RECEIVE_STATS':
			return {
				stats: { ...state.stats, [ action.postId ]: action.stats },
				measuring: { ...state.measuring, [ action.postId ]: false },
				errors: { ...state.errors, [ action.postId ]: null },
			};
		case 'MEASURE_START':
			return {
				...state,
				measuring: { ...state.measuring, [ action.postId ]: true },
				errors: { ...state.errors, [ action.postId ]: null },
			};
		case 'SET_ERROR':
			return {
				...state,
				measuring: { ...state.measuring, [ action.postId ]: false },
				errors: { ...state.errors, [ action.postId ]: action.message },
			};
	}
	return state;
}

const selectors = {
	getStats: ( state, postId ) => state.stats[ postId ],
	isMeasuring: ( state, postId ) => !! state.measuring[ postId ],
	getError: ( state, postId ) => state.errors[ postId ] || null,
};

const resolvers = {
	getStats( postId ) {
		return async ( { dispatch } ) => {
			if ( ! postId ) {
				return;
			}
			try {
				const stats = await apiFetch( {
					path: `/carbonfooter/v1/posts/${ postId }/stats`,
				} );
				dispatch.receiveStats( postId, stats );
			} catch ( error ) {
				dispatch.setError( postId, error?.message || String( error ) );
			}
		};
	},
};

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
	resolvers,
} );

register( store );
