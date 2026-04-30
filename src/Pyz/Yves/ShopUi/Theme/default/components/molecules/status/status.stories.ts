import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, section, Meta, StoryObj } from 'storybook-helpers/docs';


const docs = componentDocs({
    name: 'status',
    tag: 'span',
    extends: "molecule('status', '@SprykerShop:ShopUi')",
    data: [
        { prop: 'label', type: 'string', default: 'required', desc: 'Text label for the status badge' },
        { prop: 'status', type: 'string', default: 'required', desc: 'Status key (used as CSS modifier, e.g. approved, cancelled)' },
    ],
    modifiers: [
        'shipped', 'in-stock', 'available', 'approved', 'ready', 'paid', 'delivered',
        'cancelled', 'ready-for-return', 'returned', 'waiting-for-return',
        'out-of-stock', 'rejected', 'not-configured', 'info', 'availability',
        'solid',
    ],
    notes: 'Pyz overrides extraClass block and SCSS. Uses helper-badge-chip and semantic color mixins.',
});

const meta: Meta = { title: 'Molecules/Status' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        const statuses = [
            { label: 'Info', status: 'info' },
            { label: 'Availability', status: 'availability' },
            { label: 'Shipped', status: 'shipped' },
            { label: 'Available', status: 'available' },
            { label: 'Cancelled', status: 'cancelled' },
            { label: 'Returned', status: 'returned' },
            { label: 'Rejected', status: 'rejected' },
            { label: 'Not Configured', status: 'not-configured' },
        ];

        const solidStatuses = [
            { label: 'Info', status: 'info' },
            { label: 'Available', status: 'available' },
            { label: 'Returned', status: 'returned' },
            { label: 'Rejected', status: 'rejected' },
        ];

        return (
            section('Subtle (default)',
                statuses
                    .map((s) =>
                        renderMolecule('status', {
                            data: { label: s.label, status: s.status },
                        }),
                    )
                    .join(''),
            ) +
            section('Solid',
                solidStatuses
                    .map((s) =>
                        renderMolecule('status', {
                            data: { label: s.label, status: s.status },
                            modifiers: ['solid'],
                        }),
                    )
                    .join(''),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
