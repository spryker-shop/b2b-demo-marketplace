import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, section, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'breadcrumb',
    tag: 'ul',
    extends: "model('component')",
    data: [
        {
            prop: 'steps',
            type: 'array',
            default: '[]',
            desc: 'Breadcrumb steps: { label, url?, iconName?, itemProp?, formName?, tokenFieldName? }',
        },
        { prop: 'startWithHome', type: 'boolean', default: 'true', desc: 'Show the home icon as first step' },
        { prop: 'size', type: 'string', default: "'md'", desc: "Size modifier applied automatically: 'sm' or 'md'" },
        { prop: 'separator', type: 'string', default: "'slash'", desc: "Separator style: 'slash' or 'chevron'" },
    ],
    modifiers: ['sm', 'md', 'progress'],
    notes: 'Size modifier is auto-merged from data.size. Each step renders a breadcrumb-step molecule.',
});

const meta: Meta = { title: 'Molecules/Breadcrumb' };
export default meta;

export const Overview: StoryObj = {
    translations: {
        'global.home': 'Home',
    },
    render: () => {
        const steps = [{ label: 'Category', url: '#' }, { label: 'Subcategory', url: '#' }, { label: 'Current Page' }];

        return (
            sectionFull(
                'With home and steps',
                renderMolecule('breadcrumb', {
                    data: {
                        startWithHome: true,
                        steps,
                    },
                }),
            ) +
            sectionFull(
                'Chevron separator',
                renderMolecule('breadcrumb', {
                    data: {
                        startWithHome: true,
                        steps,
                        separator: 'chevron',
                    },
                }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
