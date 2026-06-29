import { componentDocs, figmaLink, section, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=144-1671&p=f&m=dev';

const docs = componentDocs({
    name: 'input',
    tag: 'input',
    modifiers: ['error', 'sm'],
    attributes: [
        { attr: 'disabled', desc: 'Disables the input' },
        { attr: 'readonly', desc: 'Makes the input read-only' },
    ],
    notes: 'CSS-only component — no Twig template. Apply classes directly to HTML.',
});

const meta: Meta = { title: 'Atoms/Input' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            section('Default', '<input class="input" type="text" placeholder="Default input">') +
            section('Error', '<input class="input input--error" type="text" placeholder="Error state">') +
            section('Disabled', '<input class="input" type="text" placeholder="Disabled" disabled>') +
            section('Readonly', '<input class="input" type="text" value="Read-only value" readonly>') +
            section('Small', '<input class="input input--sm" type="text" placeholder="Small input">')
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
