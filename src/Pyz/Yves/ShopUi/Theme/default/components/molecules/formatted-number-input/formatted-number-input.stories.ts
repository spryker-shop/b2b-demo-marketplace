import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, section, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';


const docs = componentDocs({
    name: 'formatted-number-input',
    tag: 'formatted-number-input',
    extends: "molecule('formatted-number-input', '@SprykerShop:ShopUi')",
    data: [
        { prop: 'inputName', type: 'string', default: 'required', desc: 'Name attribute for the hidden input' },
        { prop: 'inputValue', type: 'string', default: 'required', desc: 'Initial numeric value' },
        { prop: 'inputExtraClasses', type: 'string', default: "''", desc: 'Extra CSS classes for the visible input' },
        { prop: 'inputAttributes', type: 'object', default: '{}', desc: 'Additional HTML attributes for the visible input' },
        { prop: 'hiddenInputExtraClasses', type: 'string', default: "''", desc: 'Extra CSS classes for the hidden input' },
        { prop: 'hiddenInputAttributes', type: 'object', default: '{}', desc: 'Additional HTML attributes for the hidden input' },
    ],
    attributes: [
        { attr: 'grouping-separator', default: "'' (locale-based)", desc: 'Thousands grouping separator' },
        { attr: 'decimal-separator', default: "'.' (locale-based)", desc: 'Decimal separator' },
        { attr: 'decimal-rounding', default: '3 (locale-based)', desc: 'Number of decimal places' },
        { attr: 'decimal-filling', default: 'false', desc: 'Fill with trailing zeros' },
        { attr: 'watch-external-changes', default: 'false', desc: 'Watch for external value changes' },
        { attr: 'min', default: 'false', desc: 'Minimum allowed value' },
        { attr: 'max', default: 'false', desc: 'Maximum allowed value' },
    ],
    notes: 'Pyz override configures locale-based separators from getNumberFormatConfig(). Renders a visible formatted input + hidden raw-value input.',
});

const meta: Meta = { title: 'Molecules/Formatted Number Input' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            sectionFull('Basic',
                '' +
                renderMolecule('formatted-number-input', {
                    data: {
                        inputName: 'quantity',
                        inputValue: '1234.56',
                    },
                    attributes: {
                        'decimal-separator': '.',
                        'grouping-separator': ',',
                        'decimal-rounding': 2,
                    },
                }) +
                '</div>'
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
