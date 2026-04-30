import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'main-popup',
    tag: 'main-popup',
    extends: "molecule('main-popup', '@SprykerShop:ShopUi')",
    data: [
        { prop: 'content', type: 'string', default: 'required', desc: 'Main content HTML' },
        { prop: 'title', type: 'string', default: "''", desc: 'Popup title' },
        { prop: 'subtitle', type: 'string', default: "''", desc: 'Subtitle below title (Pyz)' },
        { prop: 'footer', type: 'string', default: "''", desc: 'Footer HTML — buttons, links (Pyz)' },
        { prop: 'closeIconName', type: 'string', default: "'cross'", desc: 'Close button icon name' },
    ],
    attributes: [
        { attr: 'content-id', default: 'required', desc: 'Unique ID for the popup content' },
        { attr: 'trigger-class-name', default: "''", desc: 'CSS class of the trigger button' },
        { attr: 'is-open', default: 'false', desc: 'Open popup immediately' },
        { attr: 'should-close-by-overlay-click', default: 'true', desc: 'Close on overlay click' },
    ],
    modifiers: ['wide', 'spaceless', 'open', 'flex-content', 'auto', 'top'],
    notes: 'Hidden by default. Opened via trigger button with matching trigger-class-name, or is-open attribute.',
});

const meta: Meta = { title: 'Molecules/Main Popup' };
export default meta;

const showcaseStyles = `<style>
    .sb-popup-showcase main-popup.main-popup {
        position: relative;
        opacity: 1;
        visibility: visible;
        transform: none;
        pointer-events: auto;
        top: auto;
        left: auto;
        margin: 0 auto;
    }
    .sb-popup-showcase .main-popup__overlay { display: none; }
</style>`;

export const Overview: StoryObj = {
    translations: {
        'global.close': 'Close',
    },
    render: () => {
        const confirmPopup = renderMolecule('main-popup', {
            data: {
                title: 'Delete Item',
                subtitle: 'This action cannot be undone',
                content: '<p>Are you sure you want to remove <strong>"Office Chair Pro"</strong> from your cart?</p>',
                footer: '<button class="button button--alert">Delete</button> <button class="button button--hollow">Cancel</button>',
            },
            attributes: {
                'content-id': 'popup-confirm',
                'trigger-class-name': 'js-popup-confirm-trigger',
            },
        });

        const infoPopup = renderMolecule('main-popup', {
            data: {
                title: 'Shipping Information',
                content: `
                    <p><strong>Standard Delivery</strong> — 3-5 business days, free on orders over €500</p>
                    <p><strong>Express Delivery</strong> — 1-2 business days, €14.90</p>
                    <p><strong>Same Day</strong> — available in Berlin only, €24.90</p>
                `,
            },
            attributes: {
                'content-id': 'popup-info',
                'trigger-class-name': 'js-popup-info-trigger',
            },
        });

        const formPopup = renderMolecule('main-popup', {
            data: {
                title: 'Add Note',
                content: `
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <label class="label">Note for this order</label>
                        <textarea class="textarea" rows="4" placeholder="Enter your note..."></textarea>
                    </div>
                `,
                footer: '<button class="button button--success">Save Note</button> <button class="button button--hollow">Cancel</button>',
            },
            modifiers: ['wide'],
            attributes: {
                'content-id': 'popup-form',
                'trigger-class-name': 'js-popup-form-trigger',
            },
        });

        const livePopups = renderMolecule('main-popup', {
            data: {
                title: 'Hello',
                content: '<p>This live popup opens via a real trigger click.</p>',
                footer: '<button class="button js-main-popup-close">OK</button>',
            },
            attributes: {
                'content-id': 'popup-live',
                'trigger-class-name': 'js-popup-live-trigger',
                'should-close-by-overlay-click': '',
            },
        });

        return (
            showcaseStyles +
            '<main-overlay class="main-overlay" data-qa="component main-overlay"></main-overlay>' +
            sectionFull(
                'Live trigger (click to open the real popup)',
                `<button class="button js-popup-live-trigger">Open popup</button>` + livePopups,
            ) +
            sectionFull('Confirm dialog (preview)', `<div class="sb-popup-showcase">${confirmPopup}</div>`) +
            sectionFull('Info popup, no footer (preview)', `<div class="sb-popup-showcase">${infoPopup}</div>`) +
            sectionFull('Form popup, wide modifier (preview)', `<div class="sb-popup-showcase">${formPopup}</div>`)
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
