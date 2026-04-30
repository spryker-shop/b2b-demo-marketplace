import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, sectionFull } from 'storybook-helpers/docs';

const docs = componentDocs({
    name: 'range-slider',
    tag: 'range-slider',
    extends: "model('component')",
    attributes: [
        { attr: 'wrap-class-name', type: 'string', default: 'required', desc: 'CSS class of the wrapper element where noUiSlider mounts' },
        { attr: 'inputs-class-name', type: 'string', default: 'required', desc: 'CSS class of the min/max number inputs' },
        { attr: 'slider-config', type: 'string (JSON)', default: '{"start":[1,999],...}', desc: 'noUiSlider config as JSON string' },
    ],
    notes: 'Pure JS component — visual track and handles are created by noUiSlider at runtime in a sibling wrap element. The story provides the wrap and inputs in surrounding DOM.',
});

export default { title: 'Molecules/Range Slider' };

let counter = 0;
function rangeSliderBlock(label, config) {
    counter += 1;
    const wrapClass = `js-range-slider-wrap-${counter}`;
    const inputsClass = `js-range-slider-inputs-${counter}`;

    return sectionFull(label,
        `<div style="max-width: 480px; padding: 12px 24px;">
            <div class="${wrapClass} range-slider"></div>
            <div style="display: flex; gap: 16px; margin-top: 16px;">
                <input type="number" class="${inputsClass}" value="${config.start[0]}" min="${config.range.min}" max="${config.range.max}" style="width: 100px; padding: 6px 8px;">
                <input type="number" class="${inputsClass}" value="${config.start[1]}" min="${config.range.min}" max="${config.range.max}" style="width: 100px; padding: 6px 8px;">
            </div>
        </div>` +
        renderMolecule('range-slider', {
            attributes: {
                'wrap-class-name': wrapClass,
                'inputs-class-name': inputsClass,
                'slider-config': JSON.stringify(config),
            },
        }),
    );
}

export const Overview = {
    render: () => {
        counter = 0;
        return (
            rangeSliderBlock('Default range (100 - 900)', {
                start: [100, 900],
                step: 1,
                connect: true,
                margin: 1,
                range: { min: 1, max: 999 },
            }) +
            rangeSliderBlock('Narrow range (200 - 300)', {
                start: [200, 300],
                step: 10,
                connect: true,
                margin: 10,
                range: { min: 0, max: 500 },
            })
        );
    },
};

export const API = {
    render: () => docs,
};
