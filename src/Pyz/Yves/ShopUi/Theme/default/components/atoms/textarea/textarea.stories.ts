import { componentDocs, figmaLink, section, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=144-1671&p=f&m=dev';

const docs = componentDocs({
    name: 'textarea',
    tag: 'textarea',
    modifiers: ['error', 'md'],
    attributes: [
        { attr: 'disabled', desc: 'Disables the textarea' },
        { attr: 'readonly', desc: 'Makes the textarea read-only' },
    ],
    notes: 'CSS-only component — no Twig template. Extends input styles.',
});

const meta: Meta = { title: 'Atoms/Textarea' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            section('Default', '<textarea class="textarea" placeholder="Default textarea"></textarea>') +
            section('Error', '<textarea class="textarea textarea--error" placeholder="Error state"></textarea>') +
            section('Disabled', '<textarea class="textarea" placeholder="Disabled" disabled></textarea>') +
            section('Readonly', '<textarea class="textarea" readonly>Read-only content</textarea>') +
            section('Medium', '<textarea class="textarea textarea--md" placeholder="Medium textarea"></textarea>')
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
