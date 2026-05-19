import { renderAtom } from 'storybook-helpers/render-twig';
import { componentDocs, figmaLink, section, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=218-10700&p=f&m=dev';

const docs = componentDocs({
    name: 'checkbox',
    tag: 'span',
    extends: "model('component')",
    data: [
        { prop: 'label', type: 'string', default: "''", desc: 'Label text next to the checkbox' },
        { prop: 'isChecked', type: 'boolean', default: 'false', desc: 'Whether the checkbox is checked' },
        { prop: 'inputClass', type: 'string', default: "''", desc: 'Additional CSS class for the input element' },
    ],
    attributes: [
        { attr: 'type', default: "'checkbox'", desc: 'Input type' },
        { attr: 'required', default: 'false', desc: 'Mark as required field' },
    ],
    modifiers: ['intermediate', 'expand'],
});

const meta: Meta = { title: 'Atoms/Checkbox' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            section('Unchecked', renderAtom('checkbox', { data: { label: 'Unchecked option' } })) +
            section('Checked', renderAtom('checkbox', { data: { label: 'Checked option', isChecked: true } })) +
            section(
                'Required',
                renderAtom('checkbox', { data: { label: 'Required field' }, attributes: { required: true } }),
            ) +
            section(
                'Disabled',
                renderAtom('checkbox', {
                    data: { label: 'Disabled unchecked' },
                    attributes: { disabled: 'disabled' },
                }) +
                    renderAtom('checkbox', {
                        data: { label: 'Disabled checked', isChecked: true },
                        attributes: { disabled: 'disabled' },
                    }),
            ) +
            section(
                'Group',
                renderAtom('checkbox', { data: { label: 'Option A' }, attributes: { name: 'group1' } }) +
                    renderAtom('checkbox', {
                        data: { label: 'Option B', isChecked: true },
                        attributes: { name: 'group1' },
                    }) +
                    renderAtom('checkbox', { data: { label: 'Option C' }, attributes: { name: 'group1' } }),
            ) +
            section(
                'Intermediate',
                renderAtom('checkbox', {
                    modifiers: ['intermediate'],
                    data: { label: 'Intermediate', isChecked: true },
                }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
