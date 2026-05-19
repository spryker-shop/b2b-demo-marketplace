import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, figmaLink, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=372-10613&p=f&m=dev';

const docs = componentDocs({
    name: 'spr-tabs',
    tag: 'spr-tabs',
    extends: "model('component')",
    data: [
        {
            prop: 'items',
            type: 'array',
            default: '[]',
            desc: 'Tab objects: {id, label:{text, icon?}, content, disabled?}',
        },
        {
            prop: 'activeTab',
            type: 'string',
            default: "''",
            desc: 'ID of the initially active tab (defaults to first non-disabled)',
        },
    ],
    attributes: [
        {
            attr: 'active-class',
            type: 'string',
            default: "'spr-tabs__trigger--active'",
            desc: 'CSS class for the active trigger',
        },
        {
            attr: 'panel-hidden-class',
            type: 'string',
            default: "'spr-tabs__panel--hidden'",
            desc: 'CSS class for hidden panels',
        },
    ],
    modifiers: ['with-bar', 'sm'],
});

const meta: Meta = { title: 'Molecules/Tabs' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            sectionFull(
                'Text tabs',
                renderMolecule('spr-tabs', {
                    data: {
                        items: [
                            {
                                id: 'details',
                                label: { text: 'Details' },
                                content:
                                    '<p>Product details content here. This is a detailed description of the product including specifications and features.</p>',
                            },
                            {
                                id: 'specs',
                                label: { text: 'Specifications' },
                                content:
                                    '<p>Technical specifications: Weight 1.2kg, Dimensions 30x20x10cm, Material: Aluminum.</p>',
                            },
                            {
                                id: 'reviews',
                                label: { text: 'Reviews' },
                                content: '<p>Customer reviews will appear here. Average rating: 4.5/5 stars.</p>',
                            },
                        ],
                        activeTab: 'details',
                    },
                }),
            ) +
            sectionFull(
                'With icons',
                renderMolecule('spr-tabs', {
                    data: {
                        items: [
                            {
                                id: 'user-account',
                                label: { text: 'User Account', icon: 'person' },
                                content: '<p>User account settings and preferences.</p>',
                            },
                            {
                                id: 'company',
                                label: { text: 'Company', icon: 'apartment' },
                                content: '<p>Company profile and business details.</p>',
                            },
                        ],
                        activeTab: 'user-account',
                    },
                }),
            ) +
            sectionFull(
                'With disabled tab',
                renderMolecule('spr-tabs', {
                    data: {
                        items: [
                            { id: 'active', label: { text: 'Active Tab' }, content: '<p>This tab is active.</p>' },
                            {
                                id: 'disabled',
                                label: { text: 'Disabled Tab' },
                                content: '<p>This content is hidden.</p>',
                                disabled: true,
                            },
                            { id: 'another', label: { text: 'Another Tab' }, content: '<p>Another tab content.</p>' },
                        ],
                        activeTab: 'active',
                    },
                }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
