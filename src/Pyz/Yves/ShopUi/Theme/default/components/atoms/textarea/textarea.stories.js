import { componentDocs, section } from 'storybook-helpers/docs';

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

export default { title: 'Atoms/Textarea' };

export const Overview = {
    render: () => {
        return (
            section('Default',
                '<textarea class="textarea" placeholder="Default textarea"></textarea>'
            ) +
            section('Error',
                '<textarea class="textarea textarea--error" placeholder="Error state"></textarea>'
            ) +
            section('Disabled',
                '<textarea class="textarea" placeholder="Disabled" disabled></textarea>'
            ) +
            section('Readonly',
                '<textarea class="textarea" readonly>Read-only content</textarea>'
            ) +
            section('Medium',
                '<textarea class="textarea textarea--md" placeholder="Medium textarea"></textarea>'
            )
        );
    },
};

export const API = {
    render: () => docs,
};
