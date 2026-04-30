import { componentDocs, section } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'link',
    tag: 'a',
    modifiers: ['no-decoration', 'subtle', 'danger', 'footer', 'sm', 'lg'],
    notes: 'CSS-only component — no Twig template. Apply classes directly to HTML.',
});

export default { title: 'Atoms/Link' };

export const Overview = {
    render: () => {
        return (
            section('Default',
                '<a class="link" href="#">Default link</a>'
            ) +
            section('No decoration',
                '<a class="link link--no-decoration" href="#">No decoration</a>'
            ) +
            section('Subtle',
                '<a class="link link--subtle" href="#">Subtle link</a>'
            ) +
            section('Danger',
                '<a class="link link--danger" href="#">Danger link</a>'
            ) +
            section('Sizes',
                '<a class="link link--sm" href="#">Small</a>' +
                '<a class="link" href="#">Default</a>' +
                '<a class="link link--lg" href="#">Large</a>'
            )
        );
    },
};

export const API = {
    render: () => docs,
};
