const path = require('path');

const defaultConfig = require('@wordpress/scripts/config/webpack.config.js');

module.exports = (env, argv) => {
	const production = argv.mode === 'production';

	return {
		...defaultConfig,
		entry: {
			...defaultConfig.entry,
			'eherkenning/eherkenning': path.resolve(
				__dirname,
				'resources/blocks/eherkenning/index.js'
			),
			'eherkenning-output/eherkenning-output': path.resolve(
				__dirname,
				'resources/blocks/eherkenning-output/index.js'
			),
			'eidas/eidas': path.resolve(__dirname, 'resources/blocks/eidas/index.js'),
			'eidas-output/eidas-output': path.resolve(
				__dirname,
				'resources/blocks/eidas-output/index.js'
			),
			modal: path.resolve(__dirname, 'resources/js/modal.js'),
			front: path.resolve(__dirname, 'resources/js/front.js'),
		},
		output: {
			path: path.resolve(__dirname, 'dist'),
		},
	};
};
