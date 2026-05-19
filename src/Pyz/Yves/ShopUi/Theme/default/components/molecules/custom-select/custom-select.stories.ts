import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, figmaLink, section, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=193-275&p=f&m=dev';

const docs = componentDocs({
    name: 'custom-select',
    tag: 'custom-select',
    extends: "atom('select')",
    data: [
        { prop: 'icon', type: 'string', default: 'null', desc: 'Font-icon name displayed before the select element' },
    ],
    attributes: [
        { attr: 'config-width', default: "'resolve'", desc: 'Select2 width config' },
        { attr: 'config-theme', default: "'default'", desc: 'Select2 theme' },
        { attr: 'config-dropdown-auto-width', default: "''", desc: 'Auto-width for the dropdown' },
        { attr: 'config-dropdown-right', default: "''", desc: 'Align dropdown to the right' },
        { attr: 'auto-init', default: 'true', desc: 'Whether to auto-initialize Select2' },
    ],
    modifiers: ['nav-top', 'expended', 'full-width', 'width-auto'],
    notes: 'Extends atom select with Select2 JS enhancement. Pyz override adds optional icon before the select.',
});

const meta: Meta = { title: 'Molecules/Custom Select' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        const options = [
            { value: '1', label: 'Option one' },
            { value: '2', label: 'Option two' },
            { value: '3', label: 'Option three' },
        ];

        return (
            figmaLink(FIGMA_URL) +
            sectionFull(
                'With options',
                '' +
                    renderMolecule('custom-select', {
                        data: { options },
                    }) +
                    '</div>',
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
