/**
 * External dependencies.
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { TextControl, PanelBody, PanelRow } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import image from '../../../resources/img/logo-eherkenning.svg';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.css';

export default function Edit({ attributes, setAttributes }) {
	const { redirectUrl } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={__('Settings', 'owc-signicat-openid')}
					initialOpen={false}
				>
					<PanelRow>
						<TextControl
							label={__('Redirect URL', 'owc-signicat-openid')}
							onChange={(url) =>
								setAttributes({ redirectUrl: url })
							}
							value={redirectUrl}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				<a className="sopenid-button-container">
					<img src={image} width="160" height="28" />
				</a>
			</div>
		</>
	);
}
