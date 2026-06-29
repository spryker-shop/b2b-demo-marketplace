import { componentDocs, section, Meta, StoryObj } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'table',
    tag: 'table',
    modifiers: ['expand', 'responsive'],
    notes: 'CSS-only component — no Twig template. Has __row, __row--inactive, __row--spaceless, __cell, and __actions elements.',
});

const meta: Meta = { title: 'Atoms/Table' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return section(
            'Default',
            '<table class="table table--expand">' +
                '<thead><tr><th>Name</th><th>Status</th><th>Date</th></tr></thead>' +
                '<tbody>' +
                '<tr><td>Item A</td><td>Active</td><td>2024-01-15</td></tr>' +
                '<tr><td>Item B</td><td>Pending</td><td>2024-02-20</td></tr>' +
                '<tr><td>Item C</td><td>Active</td><td>2024-03-10</td></tr>' +
                '</tbody>' +
                '</table>',
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
