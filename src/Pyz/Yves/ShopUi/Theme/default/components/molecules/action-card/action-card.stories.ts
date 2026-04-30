import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, section, Meta, StoryObj } from 'storybook-helpers/docs';


const docs = componentDocs({
    name: 'action-card',
    tag: 'div',
    extends: "model('component')",
    data: [
        { prop: 'title', type: 'string', default: "''", desc: 'Card heading text' },
        { prop: 'actions', type: 'array', default: '[]', desc: 'Action objects: { url, title, icon? }' },
    ],
    notes: 'Card with a header (title + icon actions) and a content block. Content is provided via Twig block override.',
});

const meta: Meta = { title: 'Molecules/Action Card' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            section('With title and icon actions',
                renderMolecule('action-card', {
                    data: {
                        title: 'Company Details',
                        actions: [
                            { url: '#', title: 'Edit', icon: 'edit' },
                        ],
                    },
                })
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
