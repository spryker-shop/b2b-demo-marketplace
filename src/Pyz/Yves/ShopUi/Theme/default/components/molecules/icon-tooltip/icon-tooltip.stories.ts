import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, section, Meta, StoryObj } from 'storybook-helpers/docs';


const docs = componentDocs({
    name: 'icon-tooltip',
    tag: 'span',
    extends: "model('component')",
    data: [
        { prop: 'icon', type: 'string', default: 'required', desc: 'Icon name (passed to atom icon)' },
        { prop: 'text', type: 'string', default: 'required', desc: 'Tooltip text shown on hover' },
        { prop: 'classIcon', type: 'string', default: "''", desc: 'Extra CSS class for the icon element' },
    ],
    modifiers: ['big'],
});

const meta: Meta = { title: 'Molecules/Icon Tooltip' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            section('Info / Edit / Delete',
                renderMolecule('icon-tooltip', {
                    data: { icon: 'flash-message-info', text: 'Helpful information' },
                }) +
                renderMolecule('icon-tooltip', {
                    data: { icon: 'edit', text: 'Edit this item' },
                }) +
                renderMolecule('icon-tooltip', {
                    data: { icon: 'delete', text: 'Delete this item' },
                }),
            ) +
            section('Big modifier',
                renderMolecule('icon-tooltip', {
                    modifiers: ['big'],
                    data: { icon: 'eye', text: 'View details' },
                }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
