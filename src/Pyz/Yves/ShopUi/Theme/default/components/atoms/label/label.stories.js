import { componentDocs, section } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'label',
    tag: 'label',
    modifiers: ['required', 'sm'],
    notes: 'CSS-only component — no Twig template. Apply classes directly to HTML.',
});

export default { title: 'Atoms/Label' };

export const Overview = {
    render: () => {
        return (
            section('Default',
                '<label class="label">Default label</label>'
            ) +
            section('Required',
                '<label class="label label--required">Required label</label>'
            ) +
            section('Small',
                '<label class="label label--sm">Small label</label>'
            )
        );
    },
};

export const API = {
    render: () => docs,
};
