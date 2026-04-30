import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';


const docs = componentDocs({
    name: 'logo',
    tag: 'div',
    extends: "molecule('logo', '@SprykerShop:ShopUi')",
    data: [
        { prop: 'externalUrl', type: 'boolean', default: 'false', desc: 'Open link in new tab (target=_blank)' },
        { prop: 'configurable', type: 'boolean', default: 'true', desc: 'Use configurable logo URL from theme config' },
    ],
    modifiers: ['checkout'],
});

const meta: Meta = { title: 'Molecules/Logo' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            sectionFull('Default (internal link)',
                renderMolecule('logo', {
                    data: { externalUrl: false },
                }),
            ) +
            sectionFull('External link (target=_blank)',
                renderMolecule('logo', {
                    data: { externalUrl: true },
                }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
