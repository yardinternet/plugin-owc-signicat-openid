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
			front: path.resolve(__dirname, 'resources/js/front.js'),
		},
		output: {
			path: path.resolve(__dirname, 'dist'),
		},
	};
};
