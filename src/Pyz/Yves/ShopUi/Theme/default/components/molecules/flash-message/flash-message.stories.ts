import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, figmaLink, section, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=218-11344&p=f&m=dev';

const docs = componentDocs({
    name: 'flash-message',
    tag: 'flash-message',
    extends: "model('component')",
    data: [
        {
            prop: 'action',
            type: 'string',
            default: "''",
            desc: "Message type: 'success', 'info', 'warning', or 'alert'",
        },
        { prop: 'title', type: 'string', default: "''", desc: 'Title text of the notification' },
        { prop: 'text', type: 'string', default: "''", desc: 'Body text of the notification' },
        { prop: 'icon', type: 'string', default: 'null', desc: 'Override icon name (defaults to action-based icon)' },
    ],
    attributes: [
        { attr: 'default-duration', default: '4000', desc: 'Auto-dismiss duration in ms' },
        { attr: 'max-visible-messages', default: '3', desc: 'Maximum visible messages at once' },
    ],
    modifiers: ['success', 'info', 'warning', 'alert', 'show'],
    notes: 'Action is auto-merged as a modifier. The --show modifier controls visibility (added by JS).',
});

const meta: Meta = { title: 'Molecules/Flash Message' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            sectionFull(
                'Success',
                renderMolecule('flash-message', {
                    modifiers: ['show'],
                    data: { action: 'success', title: 'Success', text: 'Your changes have been saved.' },
                }),
            ) +
            sectionFull(
                'Info',
                renderMolecule('flash-message', {
                    modifiers: ['show'],
                    data: { action: 'info', title: 'Information', text: 'Your session will expire in 5 minutes.' },
                }),
            ) +
            sectionFull(
                'Warning',
                renderMolecule('flash-message', {
                    modifiers: ['show'],
                    data: { action: 'warning', title: 'Warning', text: 'Some items in your cart are low in stock.' },
                }),
            ) +
            sectionFull(
                'Alert',
                renderMolecule('flash-message', {
                    modifiers: ['show'],
                    data: { action: 'alert', title: 'Error', text: 'Something went wrong. Please try again.' },
                }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
