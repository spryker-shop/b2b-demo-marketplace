import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, section, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'action-bar',
    tag: 'nav',
    extends: "model('component')",
    data: [
        { prop: 'title', type: 'string', default: "''", desc: 'Heading text displayed on the left' },
        {
            prop: 'actions',
            type: 'array',
            default: '[]',
            desc: 'Action objects: { url, title, icon?, primary?, modifiers?, block?, qa? }',
        },
    ],
    modifiers: ['first', 'big', 'small', 'spaceless', 'narrow'],
    notes: 'Non-primary actions default to hollow modifier. Use action.block for raw HTML actions.',
});

const meta: Meta = { title: 'Molecules/Action Bar' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return sectionFull(
            'With title and actions',
            renderMolecule('action-bar', {
                data: {
                    title: 'Shopping Lists',
                    actions: [
                        { url: '#', title: 'Create new list', primary: true },
                        { url: '#', title: 'Import' },
                    ],
                },
            }),
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
