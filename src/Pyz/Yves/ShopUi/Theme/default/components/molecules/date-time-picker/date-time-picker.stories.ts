import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, section, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'date-time-picker',
    tag: 'date-time-picker',
    extends: "model('component')",
    data: [
        { prop: 'field', type: 'FormView', default: 'null', desc: 'Symfony form field (used with form_row)' },
        {
            prop: 'input',
            type: 'object',
            default: '{}',
            desc: 'Manual input config: { label, attrs: { id, name, placeholder } }',
        },
        { prop: 'fieldClass', type: 'string', default: "''", desc: 'Extra CSS class for the input field' },
        { prop: 'iconName', type: 'string', default: "'calendar'", desc: 'Calendar button icon name (empty to hide)' },
    ],
    attributes: [
        { attr: 'language', default: 'required', desc: 'Locale for the flatpickr date picker' },
        { attr: 'parent-id', default: "'.date-time-picker'", desc: 'Parent selector for positioning' },
        { attr: 'config', default: "'{}'", desc: 'JSON config passed to flatpickr' },
        { attr: 'formatted-date-time', default: "''", desc: 'Pre-formatted date/time string' },
        { attr: 'date-from-id', default: 'false', desc: 'ID of the date-from input for range mode' },
        { attr: 'date-to-id', default: 'false', desc: 'ID of the date-to input for range mode' },
    ],
    modifiers: ['with-icon'],
    notes: 'JS-enhanced component using flatpickr. Supports both Symfony form fields and manual input config.',
});

const meta: Meta = { title: 'Molecules/Date Time Picker' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return sectionFull(
            'Basic',
            '' +
                renderMolecule('date-time-picker', {
                    data: {
                        input: {
                            label: 'Select date',
                            attrs: { id: 'demo-date', name: 'date', placeholder: 'YYYY-MM-DD' },
                        },
                    },
                    attributes: { language: 'en' },
                }) +
                '</div>',
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
