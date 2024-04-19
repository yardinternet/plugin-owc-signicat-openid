const { registerBlockVariation } = wp.blocks;

const variations = [
	{
		name: 'digid',
		title: 'DigiD',
		description: __('DigiD login', 'owc-signicat-openid'),
		attributes: { idp: 'digid' },
		isActive: ['idp'],
	},
	{
		name: 'eherkenning',
		title: 'eHerkenning',
		description: __('eHerkenning login', 'owc-signicat-openid'),
		attributes: { idp: 'eherkenning' },
		isActive: ['idp'],
	},
];

variations.forEach((variation) => {
	wp.blocks.registerBlockVariation('owc-signicat-openid/openid', variations);
});
