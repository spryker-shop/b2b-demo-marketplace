import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'lazy-image',
    tag: 'lazy-image',
    extends: "model('component')",
    data: [
        { prop: 'imageSrc', type: 'string', default: 'required', desc: 'Image source URL' },
        { prop: 'imagePlaceholder', type: 'string', default: "''", desc: 'Placeholder image (base64 or URL)' },
        {
            prop: 'isBackground',
            type: 'boolean',
            default: 'false',
            desc: 'Render as background-image div instead of img',
        },
        { prop: 'imageTitle', type: 'string', default: "''", desc: 'Alt text / title for the image' },
        { prop: 'extraAttributes', type: 'object', default: '{}', desc: 'Extra HTML attributes for the image element' },
    ],
    modifiers: [
        'smaller',
        'configured-bundle',
        'order-success',
        'pdp-bundle',
        'category-list',
        'catalog',
        'category',
        'shopping-list',
        'cart',
        'summary',
        'full-size',
        'category-list-item',
        'table',
    ],
    notes: 'Pyz overrides vendor lazy-image with additional size modifiers. Twig lives in vendor.',
});

const meta: Meta = { title: 'Molecules/Lazy Image' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            sectionFull(
                'Default',
                renderMolecule('lazy-image', {
                    data: {
                        imageSrc: 'https://placehold.co/300x200/e8e8e8/666?text=Lazy+Image',
                        imageTitle: 'Lazy loaded product image',
                    },
                }),
            ) +
            sectionFull(
                'Smaller modifier',
                renderMolecule('lazy-image', {
                    modifiers: ['smaller'],
                    data: {
                        imageSrc: 'https://placehold.co/300x200/e8e8e8/666?text=Smaller',
                        imageTitle: 'Smaller lazy image',
                    },
                }),
            ) +
            sectionFull(
                'Full-size modifier',
                renderMolecule('lazy-image', {
                    modifiers: ['full-size'],
                    data: {
                        imageSrc: 'https://placehold.co/300x200/e8e8e8/666?text=Full+Size',
                        imageTitle: 'Full size lazy image',
                    },
                }),
            ) +
            sectionFull(
                'Background mode',
                `<style>.sb-lazy-bg-frame .lazy-image--background { display: block; width: 100%; height: 100%; }</style>` +
                    `<div class="sb-lazy-bg-frame" style="width: 600px; height: 300px;">` +
                    renderMolecule('lazy-image', {
                        data: {
                            imageSrc: 'https://placehold.co/600x300/3949ab/ffffff?text=Background',
                            imageTitle: 'Background image',
                            isBackground: true,
                        },
                    }) +
                    `</div>`,
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
