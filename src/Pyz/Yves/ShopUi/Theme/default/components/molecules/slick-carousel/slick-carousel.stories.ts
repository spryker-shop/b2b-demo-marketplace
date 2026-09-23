import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'slick-carousel',
    tag: 'slick-carousel',
    extends: "model('component')",
    data: [{ prop: 'slides', type: 'array', default: '[]', desc: 'Array of HTML strings, one per slide' }],
    attributes: [
        {
            attr: 'slider-config',
            type: 'string (JSON)',
            default: "'{}'",
            desc: 'Slick slider configuration as JSON string',
        },
    ],
    modifiers: [
        'jumbotron',
        'full-width',
        'equal-height',
        'stretch',
        'product-set',
        'product-set-widget',
        'non-fixed-height',
    ],
});

const meta: Meta = { title: 'Molecules/Slick Carousel' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            sectionFull(
                'Multiple slides',
                renderMolecule('slick-carousel', {
                    data: {
                        slides: [
                            '<div style="background: #e3e8ef; padding: 60px 40px; text-align: center; font-size: 18px;">Slide 1 - Office Supplies</div>',
                            '<div style="background: #d1d9e6; padding: 60px 40px; text-align: center; font-size: 18px;">Slide 2 - Electronics</div>',
                            '<div style="background: #bfcbd9; padding: 60px 40px; text-align: center; font-size: 18px;">Slide 3 - Furniture</div>',
                            '<div style="background: #adbdd1; padding: 60px 40px; text-align: center; font-size: 18px;">Slide 4 - Industrial</div>',
                        ],
                    },
                }),
            ) +
            sectionFull(
                'Single slide',
                renderMolecule('slick-carousel', {
                    data: {
                        slides: [
                            '<div style="background: #e3e8ef; padding: 60px 40px; text-align: center;">Single Slide Content</div>',
                        ],
                    },
                }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
