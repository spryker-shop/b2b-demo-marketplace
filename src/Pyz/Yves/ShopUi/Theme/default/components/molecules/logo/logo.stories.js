import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, sectionFull } from 'storybook-helpers/docs';

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

export default { title: 'Molecules/Logo' };

export const Overview = {
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

export const API = {
    render: () => docs,
};
