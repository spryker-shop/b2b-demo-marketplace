import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, figmaLink, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=895-15987&p=f&m=dev';

const docs = componentDocs({
    name: 'jumbotron',
    tag: 'section',
    extends: "model('component')",
    data: [
        { prop: 'imageUrl', type: 'string', default: "''", desc: 'Background image URL' },
        { prop: 'headline', type: 'string', default: "''", desc: 'Main headline' },
        { prop: 'subHeadline', type: 'string', default: "''", desc: 'Sub-headline' },
        { prop: 'btnTitle', type: 'string', default: "''", desc: 'CTA button text' },
        { prop: 'link', type: 'string', default: "''", desc: 'CTA / banner link' },
        { prop: 'label', type: 'string', default: "''", desc: 'Label above headline' },
        { prop: 'containerMode', type: 'boolean', default: 'false', desc: 'Wrap content in container div' },
        { prop: 'isBanner', type: 'boolean', default: 'false', desc: 'Wrap in <a> link' },
        { prop: 'content', type: 'string', default: "''", desc: 'Raw HTML (overrides headline/subHeadline)' },
    ],
    modifiers: ['banner'],
});

const meta: Meta = { title: 'Molecules/Jumbotron' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            sectionFull(
                'Container mode with all props',
                renderMolecule('jumbotron', {
                data: {
                    headline: 'B2B Marketplace',
                    subHeadline: 'Everything for your business in one place',
                    imageUrl: 'https://placehold.co/1200x500/0a1f44/ffffff?text=Hero+Banner',
                    btnTitle: 'Explore',
                    link: '#',
                    label: 'Featured',
                    containerMode: true,
                },
            }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
