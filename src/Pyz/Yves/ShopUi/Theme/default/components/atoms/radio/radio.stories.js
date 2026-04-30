import { renderAtom } from 'storybook-helpers/render-twig';
import { componentDocs, section } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'radio',
    tag: 'span',
    extends: "atom('checkbox')",
    data: [
        { prop: 'label', type: 'string', default: "''", desc: 'Label text next to the radio' },
        { prop: 'isChecked', type: 'boolean', default: 'false', desc: 'Whether the radio is selected' },
    ],
    attributes: [
        { attr: 'type', default: "'radio'", desc: 'Input type (overridden from checkbox)' },
        { attr: 'name', default: "''", desc: 'Radio group name' },
    ],
});

export default { title: 'Atoms/Radio' };

export const Overview = {
    render: () => {
        return (
            section('Default',
                renderAtom('radio', { data: { label: 'Unselected option' }, attributes: { name: 'demo1' } })
            ) +
            section('Checked',
                renderAtom('radio', { data: { label: 'Selected option', isChecked: true }, attributes: { name: 'demo2' } })
            ) +
            section('Disabled',
                renderAtom('radio', { data: { label: 'Disabled unselected' }, attributes: { name: 'demo3', disabled: 'disabled' } }) +
                renderAtom('radio', { data: { label: 'Disabled selected', isChecked: true }, attributes: { name: 'demo4', disabled: 'disabled' } })
            ) +
            section('Group',
                renderAtom('radio', { data: { label: 'Option A' }, attributes: { name: 'group1' } }) +
                renderAtom('radio', { data: { label: 'Option B', isChecked: true }, attributes: { name: 'group1' } }) +
                renderAtom('radio', { data: { label: 'Option C' }, attributes: { name: 'group1' } })
            )
        );
    },
};

export const API = {
    render: () => docs,
};
