const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		// Existing admin entry
		index: path.resolve( __dirname, 'src', 'index.js' ),

		// Block entries
		'blocks/minimal/index': path.resolve( __dirname, 'src/blocks/minimal', 'index.js' ),
		'blocks/emissions/index': path.resolve( __dirname, 'src/blocks/emissions', 'index.js' ),
		'blocks/trees/index': path.resolve( __dirname, 'src/blocks/trees', 'index.js' ),
		'blocks/driving/index': path.resolve( __dirname, 'src/blocks/driving', 'index.js' ),
		'blocks/pageweight/index': path.resolve( __dirname, 'src/blocks/pageweight', 'index.js' ),
		'blocks/green-hosting/index': path.resolve( __dirname, 'src/blocks/green-hosting', 'index.js' ),
		'blocks/full/index': path.resolve( __dirname, 'src/blocks/full', 'index.js' ),
		'blocks/sticker/index': path.resolve( __dirname, 'src/blocks/sticker', 'index.js' ),
		'blocks/label/index': path.resolve( __dirname, 'src/blocks/label', 'index.js' ),
	},
};
