(function (blocks, blockEditor, components, element, i18n, serverSideRender) {
	const registerBlockType = blocks.registerBlockType;
	const InspectorControls = blockEditor.InspectorControls;
	const useBlockProps = blockEditor.useBlockProps;
	const PanelBody = components.PanelBody;
	const SelectControl = components.SelectControl;
	const TextControl = components.TextControl;
	const TextareaControl = components.TextareaControl;
	const ToggleControl = components.ToggleControl;
	const createElement = element.createElement;
	const __ = i18n.__;
	const ServerSideRender = serverSideRender;

	registerBlockType('epdc/conversations', {
		edit: function (props) {
			const attributes = props.attributes;
			const setAttributes = props.setAttributes;

			return createElement(
				element.Fragment,
				null,
				createElement(
					InspectorControls,
					null,
					createElement(
						PanelBody,
						{ title: __('Conversation Settings', 'epdc-conversations'), initialOpen: true },
						createElement(TextControl, {
							label: __('Button Label Override', 'epdc-conversations'),
							value: attributes.label || '',
							onChange: function (value) {
								setAttributes({ label: value });
							}
						}),
						createElement(TextControl, {
							label: __('Phone Number Override', 'epdc-conversations'),
							help: __('Numbers only; punctuation is ignored.', 'epdc-conversations'),
							value: attributes.phoneNumber || '',
							onChange: function (value) {
								setAttributes({ phoneNumber: value });
							}
						}),
						createElement(TextareaControl, {
							label: __('Message Override', 'epdc-conversations'),
							value: attributes.message || '',
							onChange: function (value) {
								setAttributes({ message: value });
							}
						}),
						createElement(SelectControl, {
							label: __('Style Variant', 'epdc-conversations'),
							value: attributes.variant || 'default',
							options: [
								{ label: __('Default', 'epdc-conversations'), value: 'default' },
								{ label: __('Inline', 'epdc-conversations'), value: 'inline' },
								{ label: __('Compact', 'epdc-conversations'), value: 'compact' }
							],
							onChange: function (value) {
								setAttributes({ variant: value });
							}
						}),
						createElement(ToggleControl, {
							label: __('Show Icon', 'epdc-conversations'),
							checked: attributes.showIcon !== false,
							onChange: function (value) {
								setAttributes({ showIcon: value });
							}
						}),
						createElement(ToggleControl, {
							label: __('Open in New Tab', 'epdc-conversations'),
							checked: attributes.newTab === true,
							onChange: function (value) {
								setAttributes({ newTab: value });
							}
						})
					)
				),
				createElement(
					'div',
					useBlockProps(),
					createElement(ServerSideRender, {
						block: 'epdc/conversations',
						attributes: attributes
					})
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.element,
	window.wp.i18n,
	window.wp.serverSideRender
);
