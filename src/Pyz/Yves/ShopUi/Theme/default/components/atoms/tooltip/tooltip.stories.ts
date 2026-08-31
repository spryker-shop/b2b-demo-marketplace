import { renderAtom } from 'storybook-helpers/render-twig';
import { componentDocs, figmaLink, section, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=457-2736&m=dev';

const docs = componentDocs({
    name: 'tooltip',
    tag: 'span',
    extends: "model('component')",
    data: [{ prop: 'text', type: 'string', default: 'required', desc: 'Tooltip text shown on hover' }],
    modifiers: ['bottom', 'left', 'right'],
});

const meta: Meta = { title: 'Atoms/Tooltip' };
export default meta;

const shown = { attributes: { style: 'transform: var(--tooltip-translate) scale(1)' } };
const frame = (html) =>
    `<span style="position: relative; display: inline-flex; width: 24px; height: 24px; margin: 70px; background: #e0e3e8; border-radius: 4px;">${html}</span>`;
const variant = (pos, modifiers) =>
    section(`position: ${pos}`, frame(renderAtom('tooltip', { ...shown, modifiers, data: { text: 'Tooltip text' } })));

export const Overview: StoryObj = {
    render: () =>
        figmaLink(FIGMA_URL) +
        variant('top', []) +
        variant('bottom', ['bottom']) +
        variant('left', ['left']) +
        variant('right', ['right']),
};

export const API: StoryObj = {
    render: () => docs,
};
