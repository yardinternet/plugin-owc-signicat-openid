/**
 * External dependencies.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.css';

export default function Edit() {
	const blockProps = useBlockProps();

	return (
		<>
			<div {...useBlockProps()}>
				<Placeholder
					label={__(
						'This block displays the eIDAS output on the front-end',
						'owc-signicat-openid'
					)}
				/>
			</div>
		</>
	);
}
