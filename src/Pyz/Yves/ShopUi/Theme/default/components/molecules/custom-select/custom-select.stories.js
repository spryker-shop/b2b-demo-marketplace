import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, section, sectionFull } from 'storybook-helpers/docs';

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

export default { title: 'Molecules/Custom Select' };

export const Overview = {
    render: () => {
        const options = [
            { value: '1', label: 'Option one' },
            { value: '2', label: 'Option two' },
            { value: '3', label: 'Option three' },
        ];

        return (
            sectionFull('With options',
                '' +
                renderMolecule('custom-select', {
                    data: { options },
                }) +
                '</div>'
            )
        );
    },
};

export const API = {
    render: () => docs,
};
