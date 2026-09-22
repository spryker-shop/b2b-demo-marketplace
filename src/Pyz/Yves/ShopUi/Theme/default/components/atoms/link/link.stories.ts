import { componentDocs, figmaLink, section, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=382-1030&p=f&m=dev';

const docs = componentDocs({
    name: 'link',
    tag: 'a',
    modifiers: ['no-decoration', 'subtle', 'danger', 'footer', 'sm', 'lg'],
    notes: 'CSS-only component — no Twig template. Apply classes directly to HTML.',
});

const meta: Meta = { title: 'Atoms/Link' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            section('Default', '<a class="link" href="#">Default link</a>') +
            section('No decoration', '<a class="link link--no-decoration" href="#">No decoration</a>') +
            section('Subtle', '<a class="link link--subtle" href="#">Subtle link</a>') +
            section('Danger', '<a class="link link--danger" href="#">Danger link</a>') +
            section(
                'Sizes',
                '<a class="link link--sm" href="#">Small</a>' +
                    '<a class="link" href="#">Default</a>' +
                    '<a class="link link--lg" href="#">Large</a>',
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
