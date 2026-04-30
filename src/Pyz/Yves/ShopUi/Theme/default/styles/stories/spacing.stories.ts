import type { Meta, StoryObj } from 'storybook-helpers/docs';

const meta: Meta = { title: 'Basic/Spacing' };
export default meta;

const sectionStyle =
    'margin: 0 0 32px; padding: 20px; background: #fafbfc; border: 1px solid #e8e8e8; border-radius: 8px;';
const headStyle = 'margin: 0 0 16px; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 0.05em;';
const rowStyle =
    'display: grid; grid-template-columns: 100px 80px 1fr; gap: 16px; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f0f0;';
const tokenStyle = 'font-family: ui-monospace, monospace; font-size: 12px; color: #555;';
const valStyle = 'font-family: ui-monospace, monospace; font-size: 11px; color: #888;';
const barStyle = (varName) =>
    `width: var(${varName}); height: 16px; background: var(--background-brand-primary); border-radius: 2px;`;

const scaleRow = (token, px) => `
    <div style="${rowStyle}">
        <code style="${tokenStyle}">${token}</code>
        <code style="${valStyle}">${px}</code>
        <div style="${barStyle(token)}"></div>
    </div>`;

const radiusSwatch = (token, px) => `
    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 12px; border: 1px solid #e8e8e8; border-radius: 6px; background: #fff;">
        <div style="width: 80px; height: 80px; background: var(--background-brand-primary); border-radius: var(${token});"></div>
        <code style="${tokenStyle}">${token}</code>
        <code style="${valStyle}">${px}</code>
    </div>`;

export const Overview: StoryObj = {
    render: () => `
        <div style="font-family: 'Inter', sans-serif;">
            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Scale tokens (--scale-*)</h3>
                ${scaleRow('--scale-2', '2px')}
                ${scaleRow('--scale-4', '4px')}
                ${scaleRow('--scale-6', '6px')}
                ${scaleRow('--scale-8', '8px')}
                ${scaleRow('--scale-12', '12px')}
                ${scaleRow('--scale-16', '16px')}
                ${scaleRow('--scale-20', '20px')}
                ${scaleRow('--scale-24', '24px')}
                ${scaleRow('--scale-32', '32px')}
                ${scaleRow('--scale-40', '40px')}
                ${scaleRow('--scale-48', '48px')}
                ${scaleRow('--scale-64', '64px')}
            </div>

            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Border radius (--radius-*)</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 16px;">
                    ${radiusSwatch('--radius-none', '0px')}
                    ${radiusSwatch('--radius-xs', '2px')}
                    ${radiusSwatch('--radius-sm', '4px')}
                    ${radiusSwatch('--radius-md', '8px')}
                    ${radiusSwatch('--radius-lg', '12px')}
                    ${radiusSwatch('--radius-full', '999px')}
                </div>
            </div>
        </div>
    `,
};
