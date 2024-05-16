/**
 * External dependencies.
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	TextControl,
	PanelBody,
	PanelRow,
	Disabled,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
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
