import { componentDocs, figmaLink, section, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=144-1671&p=f&m=dev';

const docs = componentDocs({
    name: 'label',
    tag: 'label',
    modifiers: ['required', 'sm'],
    notes: 'CSS-only component — no Twig template. Apply classes directly to HTML.',
});

const meta: Meta = { title: 'Atoms/Label' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            section('Default', '<label class="label">Default label</label>') +
            section('Required', '<label class="label label--required">Required label</label>') +
            section('Small', '<label class="label label--sm">Small label</label>')
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
