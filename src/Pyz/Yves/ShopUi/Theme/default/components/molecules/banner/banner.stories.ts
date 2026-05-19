import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, figmaLink, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=437-804&p=f&m=dev';

const docs = componentDocs({
    name: 'banner',
    tag: 'article',
    extends: "molecule('banner', '@SprykerShop:ShopUi')",
    data: [
        { prop: 'imageUrl', type: 'string', default: "''", desc: 'URL for the banner background image' },
        { prop: 'imageAlt', type: 'string', default: "''", desc: 'Alt text for the image' },
        { prop: 'content', type: 'string', default: "''", desc: 'HTML content for the banner body' },
        { prop: 'clickUrl', type: 'string', default: "''", desc: 'URL for the CTA button' },
        {
            prop: 'buttonTitle',
            type: 'string',
            default: "''",
            desc: 'CTA button text (defaults to "Show more" translation)',
        },
    ],
    modifiers: ['small', 'medium'],
    notes: 'Horizontal layout on lg+, vertical on mobile. Uses lazy-image for the image area.',
});

const meta: Meta = { title: 'Molecules/Banner' };
export default meta;

export const Overview: StoryObj = {
    translations: {
        'show_more.btn.title': 'Show more',
    },
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            sectionFull(
                'With image and CTA',
                renderMolecule('banner', {
                    data: {
                        imageUrl: 'https://placehold.co/1200x400/0a1f44/ffffff?text=Spring+Sale+—+Up+to+30%25+Off',
                        imageAlt: 'Spring Sale Banner',
                        content:
                            '<div class="banner__title-group"><h2 class="banner__title">Spring Sale</h2><p class="banner__text">Up to 30% off on office supplies and electronics</p></div>',
                        clickUrl: '#',
                        buttonTitle: 'Shop Now',
                    },
                }),
            ) +
            sectionFull(
                'Small banner',
                renderMolecule('banner', {
                    modifiers: ['small'],
                    data: {
                        imageUrl: 'https://placehold.co/1200x400/1a365d/ffffff?text=New+B2B+Marketplace',
                        imageAlt: 'B2B Marketplace',
                        content:
                            '<div class="banner__title-group"><h2 class="banner__title">B2B Marketplace</h2><p class="banner__text">Everything your business needs in one place</p></div>',
                        clickUrl: '#',
                        buttonTitle: 'Explore',
                    },
                }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
