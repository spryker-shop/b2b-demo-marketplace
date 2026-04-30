import { componentDocs, section, Meta, StoryObj } from 'storybook-helpers/docs';


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

export const API: StoryObj = {
    render: () => docs,
};
