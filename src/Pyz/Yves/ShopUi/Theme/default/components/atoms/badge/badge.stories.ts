import { componentDocs, section, Meta, StoryObj } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'badge',
    tag: 'span',
    modifiers: ['info', 'success', 'warning', 'error', 'solid', 'md'],
    notes: 'CSS-only component — no Twig template. Apply classes directly to HTML.',
});

const meta: Meta = { title: 'Atoms/Badge' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            section(
                'Subtle (default)',
                '<span class="badge">Default</span>' +
                    '<span class="badge badge--info">Info</span>' +
                    '<span class="badge badge--success">Success</span>' +
                    '<span class="badge badge--warning">Warning</span>' +
                    '<span class="badge badge--error">Error</span>',
            ) +
            section(
                'Solid',
                '<span class="badge badge--solid">Default</span>' +
                    '<span class="badge badge--info badge--solid">Info</span>' +
                    '<span class="badge badge--success badge--solid">Success</span>' +
                    '<span class="badge badge--warning badge--solid">Warning</span>' +
                    '<span class="badge badge--error badge--solid">Error</span>',
            ) +
            section(
                'Medium',
                '<span class="badge badge--md">Default MD</span>' +
                    '<span class="badge badge--info badge--md">Info MD</span>' +
                    '<span class="badge badge--success badge--md">Success MD</span>',
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
