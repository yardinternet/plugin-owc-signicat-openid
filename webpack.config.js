const path = require('path');

const defaultConfig = require('@wordpress/scripts/config/webpack.config.js');

module.exports = (env, argv) => {
	const production = argv.mode === 'production';

	return {
		...defaultConfig,
		entry: {
			...defaultConfig.entry,
			'openid/openid': path.resolve(
				__dirname,
				'resources/blocks/openid/index.js'
			),
			modal: path.resolve(__dirname, 'resources/js/modal.js'),
			front: path.resolve(__dirname, 'resources/js/front.js'),
		},
		output: {
			path: path.resolve(__dirname, 'dist'),
		},
	};
};
