import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';


const docs = componentDocs({
    name: 'quantity-counter',
    tag: 'quantity-counter',
    extends: "model('component')",
    data: [
        { prop: 'isDisabled', type: 'boolean', default: 'false', desc: 'Disable all controls' },
        { prop: 'isReadOnly', type: 'boolean', default: 'false', desc: 'Make input read-only (no keyboard input)' },
        { prop: 'autoUpdate', type: 'boolean', default: 'false', desc: 'Auto-submit on value change' },
        { prop: 'formattedNumberExtraClasses', type: 'string', default: "''", desc: 'Extra classes for formatted-number-input wrapper' },
        { prop: 'inputExtraClasses', type: 'string', default: "''", desc: 'Extra classes for the input element' },
        { prop: 'numberFormatConfig', type: 'object', default: '{}', desc: 'Config for formatted-number-input' },
        { prop: 'ajaxTriggerAttribute', type: 'string', default: "''", desc: 'Ajax trigger data attribute' },
    ],
    attributes: [
        { attr: 'value', type: 'int', default: '1', desc: 'Current quantity value' },
        { attr: 'min', type: 'int', default: '1', desc: 'Minimum allowed value' },
        { attr: 'step', type: 'int', default: '1', desc: 'Increment/decrement step' },
    ],
    modifiers: ['secondary', 'right-space', 'cart', 'shopping-list'],
});

const meta: Meta = { title: 'Molecules/Quantity Counter' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        const wrap = (html) => `<div style="max-width: 300px;">${html}</div>`;

        return (
            sectionFull('Default (value=1)',
                wrap(renderMolecule('quantity-counter', {
                    data: { isDisabled: false, isReadOnly: false },
                    attributes: { value: 1, min: 1, step: 1, name: 'quantity' },
                })),
            ) +
            sectionFull('Higher value (10)',
                wrap(renderMolecule('quantity-counter', {
                    data: { isDisabled: false, isReadOnly: false },
                    attributes: { value: 10, min: 1, step: 1, name: 'quantity' },
                })),
            ) +
            sectionFull('Disabled',
                wrap(renderMolecule('quantity-counter', {
                    data: { isDisabled: true, isReadOnly: false },
                    attributes: { value: 5, min: 1, step: 1, name: 'quantity' },
                })),
            ) +
            sectionFull('Read-only',
                wrap(renderMolecule('quantity-counter', {
                    data: { isDisabled: false, isReadOnly: true },
                    attributes: { value: 3, min: 1, step: 1, name: 'quantity' },
                })),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
