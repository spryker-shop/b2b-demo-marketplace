import { renderAtom } from 'storybook-helpers/render-twig';
import { componentDocs, figmaLink, section, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=193-275&p=f&m=dev';

const docs = componentDocs({
    name: 'select',
    tag: 'label',
    extends: "model('component')",
    data: [
        {
            prop: 'options',
            type: 'array',
            default: '[]',
            desc: 'Options list — each: {value, label, selected?, disabled?}',
        },
        { prop: 'isGrouped', type: 'boolean', default: 'false', desc: 'Render options inside optgroup elements' },
    ],
    modifiers: ['expand', 'error', 'naked'],
});

const meta: Meta = { title: 'Atoms/Select' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        const options = [
            { value: '1', label: 'Option one' },
            { value: '2', label: 'Option two' },
            { value: '3', label: 'Option three' },
        ];

        const selectedOptions = [
            { value: '1', label: 'Option one' },
            { value: '2', label: 'Option two', selected: true },
            { value: '3', label: 'Option three' },
        ];

        const groupedOptions = {
            'Group A': [
                { value: 'a1', label: 'Alpha' },
                { value: 'a2', label: 'Beta' },
            ],
            'Group B': [
                { value: 'b1', label: 'Gamma' },
                { value: 'b2', label: 'Delta' },
            ],
        };

        return (
            figmaLink(FIGMA_URL) +
            section('Default options', renderAtom('select', { data: { options } })) +
            section('Selected', renderAtom('select', { data: { options: selectedOptions } })) +
            section('Grouped', renderAtom('select', { data: { options: groupedOptions, isGrouped: true } }))
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
