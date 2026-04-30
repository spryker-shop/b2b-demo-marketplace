import { renderAtom } from 'storybook-helpers/render-twig';
import { componentDocs, section, Meta, StoryObj } from 'storybook-helpers/docs';


const docs = componentDocs({
    name: 'thumbnail',
    tag: 'span',
    extends: "model('component')",
    attributes: [
        { attr: 'src', desc: 'Image source URL' },
        { attr: 'alt', desc: 'Alternative text for the image' },
    ],
    modifiers: ['smaller', 'small', 'big', 'bigger', 'expand', 'item', 'catalog', 'category', 'cart', 'summary', 'shopping-list', 'category-list', 'reset-indents'],
});

const meta: Meta = { title: 'Atoms/Thumbnail' };
export default meta;

const frame = (size, html) => `<div style="position: relative; width: ${size}; height: ${size};">${html}</div>`;

export const Overview: StoryObj = {
    render: () => {
        const img = 'https://placehold.co/200x200/e8e8e8/666?text=Product';

        return (
            section('Default',
                frame('10rem', renderAtom('thumbnail', { attributes: { src: img, alt: 'Default' } })),
            ) +
            section('Smaller',
                frame('3rem', renderAtom('thumbnail', { modifiers: ['smaller'], attributes: { src: img, alt: 'Smaller' } })),
            ) +
            section('Small',
                frame('5rem', renderAtom('thumbnail', { modifiers: ['small'], attributes: { src: img, alt: 'Small' } })),
            ) +
            section('Expand',
                `<div style="width: 200px;">${renderAtom('thumbnail', { modifiers: ['expand'], attributes: { src: img, alt: 'Expand' } })}</div>`,
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
