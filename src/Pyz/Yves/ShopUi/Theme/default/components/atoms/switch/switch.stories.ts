import { renderAtom } from 'storybook-helpers/render-twig';
import { componentDocs, figmaLink, section, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=218-11229&p=f&m=dev';

const docs = componentDocs({
    name: 'switch',
    tag: 'div',
    extends: "model('component')",
    data: [
        { prop: 'id', type: 'string | null', default: 'null', desc: 'ID attribute for the input' },
        { prop: 'name', type: 'string | null', default: 'null', desc: 'Name attribute for the input' },
        { prop: 'label', type: 'string | null', default: 'null', desc: 'Label text next to the toggle' },
    ],
    modifiers: ['active', 'disabled'],
    notes: 'Modifier "active" sets the checkbox to checked. Modifier "disabled" disables the input.',
});

const meta: Meta = { title: 'Atoms/Switch' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            section('Off', renderAtom('switch', { data: { label: 'Inactive toggle' } })) +
            section('On (active)', renderAtom('switch', { modifiers: ['active'], data: { label: 'Active toggle' } })) +
            section(
                'Disabled',
                renderAtom('switch', { modifiers: ['disabled'], data: { label: 'Disabled off' } }) +
                    renderAtom('switch', { modifiers: ['active', 'disabled'], data: { label: 'Disabled on' } }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
