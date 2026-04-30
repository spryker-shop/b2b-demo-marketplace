export default { title: 'Basic/Colors' };

const sectionStyle = 'margin: 0 0 32px; padding: 20px; background: #fafbfc; border: 1px solid #e8e8e8; border-radius: 8px;';
const headStyle = 'margin: 0 0 16px; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 0.05em;';
const gridStyle = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;';
const swatchStyle = () =>
    `display: flex; flex-direction: column; gap: 4px; padding: 8px; border: 1px solid #e8e8e8; border-radius: 6px; background: #fff;`;
const blockStyle = (varName) =>
    `width: 100%; height: 60px; border-radius: 4px; background: var(${varName}); border: 1px solid rgba(0,0,0,0.05);`;

const swatch = (varName) => `
    <div style="${swatchStyle(varName)}">
        <div style="${blockStyle(varName)}"></div>
        <code style="font-family: ui-monospace, monospace; font-size: 11px; color: #555;">${varName}</code>
    </div>`;

const palette = (label, prefix, steps) => `
    <h3 style="${headStyle}">${label}</h3>
    <div style="${gridStyle}">
        ${steps.map((s) => swatch(`--${prefix}-${s}`)).join('')}
    </div>`;

const semantic = (label, varNames) => `
    <h3 style="${headStyle}">${label}</h3>
    <div style="${gridStyle}">
        ${varNames.map(swatch).join('')}
    </div>`;

const greySteps = ['25', '50', '100', '150', '200', '300', '350', '400', '450', '500', '600', '700', '750', '800', '900', '950'];
const colorSteps = ['25', '50', '100', '200', '300', '400', '500', '600', '700', '800', '900'];

export const Overview = {
    render: () => `
        <div style="font-family: 'Inter', sans-serif;">
            <div style="${sectionStyle}">
                ${semantic('Surfaces &amp; backgrounds', [
        '--background-page',
        '--background-surface',
        '--background-subtle',
        '--background-muted',
        '--background-inverse',
        '--background-brand-primary',
        '--background-brand-hover',
        '--background-brand-subtle',
        '--background-accent-primary',
        '--background-accent-subtle',
    ])}
            </div>

            <div style="${sectionStyle}">
                ${semantic('State colors', [
        '--background-state-success-subtle',
        '--background-state-success-bold',
        '--background-state-info-subtle',
        '--background-state-info-bold',
        '--background-state-warning-subtle',
        '--background-state-warning-bold',
        '--background-state-error-subtle',
        '--background-state-error-bold',
    ])}
            </div>

            <div style="${sectionStyle}">
                ${semantic('Borders', [
        '--border-default',
        '--border-subtle',
        '--border-strong',
        '--border-brand',
        '--border-state-success',
    ])}
            </div>

            <div style="${sectionStyle}">
                ${palette('Grey scale', 'grey', greySteps)}
            </div>

            <div style="${sectionStyle}">
                ${palette('Teal (brand)', 'teal', colorSteps)}
            </div>

            <div style="${sectionStyle}">
                ${palette('Forest (accent)', 'forest', colorSteps)}
            </div>

            <div style="${sectionStyle}">
                ${palette('Blue', 'blue', colorSteps)}
            </div>

            <div style="${sectionStyle}">
                ${palette('Green', 'green', colorSteps)}
            </div>

            <div style="${sectionStyle}">
                ${palette('Yellow', 'yellow', colorSteps)}
            </div>
        </div>
    `,
};
