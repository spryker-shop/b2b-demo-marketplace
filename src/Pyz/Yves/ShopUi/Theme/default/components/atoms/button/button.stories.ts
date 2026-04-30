import { componentDocs, section, Meta, StoryObj } from 'storybook-helpers/docs';


const docs = componentDocs({
    name: 'button',
    tag: 'button',
    modifiers: ['secondary', 'hollow', 'tertiary', 'alert', 'destructive', 'sm', 'lg', 'expand', 'icon-only'],
    notes: 'CSS-only component — no Twig template. Apply classes directly to HTML.',
});

const meta: Meta = { title: 'Atoms/Button' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            section('Types',
                '<button class="button">Primary</button>' +
                '<button class="button button--secondary">Secondary</button>' +
                '<button class="button button--tertiary">Tertiary</button>' +
                '<button class="button button--destructive">Destructive</button>' +
                '<button class="button" disabled>Disabled</button>'
            ) +
            section('Sizes',
                '<button class="button button--sm">Small</button>' +
                '<button class="button">Default</button>' +
                '<button class="button button--lg">Large</button>'
            ) +
            section('Expand',
                '<button class="button button--expand">Full width</button>'
            ) +
            section('Icon-only',
                '<button class="button button--icon-only"><span class="material-symbols-outlined">add</span></button>' +
                '<button class="button button--secondary button--icon-only"><span class="material-symbols-outlined">edit</span></button>' +
                '<button class="button button--tertiary button--icon-only"><span class="material-symbols-outlined">delete</span></button>'
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
