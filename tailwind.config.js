module.exports = {
	prefix: 'sopenid-',
	content: ['./resources/views/**/*.php', './resources/js/**/*.js'],
	theme: {
		extend: {},
	},
	plugins: [require('prettier-plugin-tailwindcss')],
};
