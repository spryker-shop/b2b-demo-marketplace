import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, figmaLink, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=586-3311&p=f&m=dev';

const docs = componentDocs({
    name: 'mega-menu',
    tag: 'mega-menu',
    extends: "model('component')",
    data: [
        { prop: 'node', type: 'object', default: 'required', desc: 'Navigation tree with title, url, children[]' },
        { prop: 'parentJsName', type: 'string', default: 'required', desc: 'JS class of parent trigger' },
    ],
    notes: 'Hidden by default (visibility:hidden, position:absolute). Shown on navigation hover.',
});

const meta: Meta = { title: 'Molecules/Mega Menu' };
export default meta;

export const Overview: StoryObj = {
    translations: {
        'navigation.browse_by_category': 'Browse by category',
        'navigation.show_all': 'Show all',
        'navigation.all_prefix': 'All',
    },
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            `<style>
                .sb-mega-menu-frame mega-menu.mega-menu {
                    display: flex;
                    visibility: visible;
                    opacity: 1;
                    pointer-events: auto;
                    transform: none;
                    position: relative;
                    top: auto;
                    left: auto;
                    background: var(--header-main, #fff);
                    width: 992px;
                    max-width: 100%;
                    border-radius: 8px;
                    border: 1px solid #eee;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                }
            </style>` +
            '<div class="sb-mega-menu-frame">' +
            sectionFull(
                'Default',
                renderMolecule('mega-menu', {
                    data: {
                        node: {
                            title: 'All Categories',
                            url: '#',
                            children: [
                                {
                                    title: 'Office Supplies',
                                    url: '#',
                                    children: [
                                        { title: 'Paper & Pads', url: '#', children: [] },
                                        { title: 'Pens & Markers', url: '#', children: [] },
                                        { title: 'Binders & Folders', url: '#', children: [] },
                                    ],
                                },
                                {
                                    title: 'Electronics',
                                    url: '#',
                                    children: [
                                        { title: 'Laptops', url: '#', children: [] },
                                        { title: 'Monitors', url: '#', children: [] },
                                    ],
                                },
                                { title: 'Furniture', url: '#', children: [] },
                            ],
                        },
                        parentJsName: 'js-mega-menu',
                    },
                }),
            ) +
            '</div>'
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
