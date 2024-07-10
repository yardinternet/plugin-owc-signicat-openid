/**
 * External dependencies.
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	TextControl,
	ToggleControl,
	PanelBody,
	PanelRow,
	Disabled,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './editor.css';

export default function Edit({ attributes, setAttributes }) {
	const { redirectUrl, buttonText } = attributes;

	const handleRedirectUrlChange = (url) => {
		setAttributes({ redirectUrl: url });
	};

	const handleButtonTextChange = (value) => {
		setAttributes({ buttonText: value });
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={__('Settings', 'owc-signicat-openid')}
					initialOpen={true}
				>
					<PanelRow>
						<TextControl
							label={__('Redirect URL', 'owc-signicat-openid')}
							value={redirectUrl}
							onChange={handleRedirectUrlChange}
						/>
					</PanelRow>
					<PanelRow>
							<TextControl
								label={__('Button text', 'owc-signicat-openid')}
								value={buttonText}
								onChange={handleButtonTextChange}
							/>
						</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div {...useBlockProps()}>
				<Disabled>
					<ServerSideRender
						block={metadata.name}
						attributes={attributes}
					/>
				</Disabled>
			</div>
		</>
	);
}
