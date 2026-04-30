import { renderAtom } from 'storybook-helpers/render-twig';
import { componentDocs, section } from 'storybook-helpers/docs';

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

export default { title: 'Atoms/Switch' };

export const Overview = {
    render: () => {
        return (
            section('Off',
                renderAtom('switch', { data: { label: 'Inactive toggle' } })
            ) +
            section('On (active)',
                renderAtom('switch', { modifiers: ['active'], data: { label: 'Active toggle' } })
            ) +
            section('Disabled',
                renderAtom('switch', { modifiers: ['disabled'], data: { label: 'Disabled off' } }) +
                renderAtom('switch', { modifiers: ['active', 'disabled'], data: { label: 'Disabled on' } })
            )
        );
    },
};

export const API = {
    render: () => docs,
};
