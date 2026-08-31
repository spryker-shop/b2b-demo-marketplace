import { renderAtom } from 'storybook-helpers/render-twig';
import { componentDocs, section, Meta, StoryObj } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'font-icon',
    tag: 'span',
    extends: "model('component')",
    data: [{ prop: 'name', type: 'string', default: 'required', desc: 'Material Symbols icon name' }],
    attributes: [
        { attr: 'title', default: 'data.name', desc: 'Tooltip text (defaults to icon name)' },
        { attr: 'aria-hidden', default: "'true'", desc: 'Hidden from screen readers' },
    ],
    modifiers: ['size-inherit', 'big', 'filled'],
    notes: "Uses Google Material Symbols Outlined font. Block 'class' adds 'material-symbols-outlined'.",
});

const meta: Meta = { title: 'Atoms/Font Icon' };
export default meta;

const iconNames = [
    'home',
    'person',
    'search',
    'settings',
    'close',
    'check',
    'add',
    'edit',
    'delete',
    'arrow_forward',
    'shopping_cart',
    'favorite',
    'visibility',
    'info',
    'warning',
];

export const Overview: StoryObj = {
    render: () => {
        return (
            section(
                'Outlined icons',
                '<div style="display: grid; grid-template-columns: repeat(5, auto); gap: 16px; align-items: center;">' +
                    iconNames
                        .map(
                            (name) =>
                                '<div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">' +
                                renderAtom('font-icon', { data: { name } }) +
                                '<span style="font-size: 11px; color: #888;">' +
                                name +
                                '</span></div>',
                        )
                        .join('') +
                    '</div>',
            ) +
            section(
                'Filled',
                renderAtom('font-icon', { modifiers: ['filled'], data: { name: 'home' } }) +
                    renderAtom('font-icon', { modifiers: ['filled'], data: { name: 'favorite' } }) +
                    renderAtom('font-icon', { modifiers: ['filled'], data: { name: 'person' } }),
            ) +
            section(
                'Big',
                renderAtom('font-icon', { modifiers: ['big'], data: { name: 'home' } }) +
                    renderAtom('font-icon', { modifiers: ['big'], data: { name: 'search' } }) +
                    renderAtom('font-icon', { modifiers: ['big'], data: { name: 'settings' } }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
