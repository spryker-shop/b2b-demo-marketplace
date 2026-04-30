import type { Meta, StoryObj } from 'storybook-helpers/docs';

const meta: Meta = { title: 'Basic/Typography' };
export default meta;

const sectionStyle =
    'margin: 0 0 32px; padding: 20px; background: #fafbfc; border: 1px solid #e8e8e8; border-radius: 8px;';
const headStyle = 'margin: 0 0 16px; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 0.05em;';
const rowStyle =
    'display: grid; grid-template-columns: 160px 1fr 200px; gap: 16px; align-items: baseline; padding: 8px 0; border-bottom: 1px solid #f0f0f0;';
const tokenStyle = 'font-size: 12px; color: #888; font-family: ui-monospace, monospace;';
const metaStyle = 'font-size: 12px; color: #888; font-family: ui-monospace, monospace; text-align: right;';

const fontSizeRow = (label, sizeVar, lineVar) => `
    <div style="${rowStyle}">
        <code style="${tokenStyle}">${sizeVar}</code>
        <div style="font-size: var(${sizeVar}); line-height: var(${lineVar}); color: var(--text-primary);">${label} — The quick brown fox jumps</div>
        <code style="${metaStyle}">${lineVar}</code>
    </div>`;

const headingRow = (tag, sample) => `
    <div style="${rowStyle}">
        <code style="${tokenStyle}">&lt;${tag}&gt;</code>
        <${tag} style="margin: 0;">${sample}</${tag}>
        <span style="${metaStyle}">native style</span>
    </div>`;

export const Overview: StoryObj = {
    render: () => `
        <div style="font-family: 'Inter', sans-serif;">
            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Font sizes &amp; line heights</h3>
                ${fontSizeRow('12 / 16', '--font-size-12', '--font-line-height-16')}
                ${fontSizeRow('14 / 20', '--font-size-14', '--font-line-height-20')}
                ${fontSizeRow('16 / 24', '--font-size-16', '--font-line-height-24')}
                ${fontSizeRow('18 / 24', '--font-size-18', '--font-line-height-24')}
                ${fontSizeRow('20 / 28', '--font-size-20', '--font-line-height-28')}
                ${fontSizeRow('24 / 32', '--font-size-24', '--font-line-height-32')}
                ${fontSizeRow('28 / 32', '--font-size-28', '--font-line-height-32')}
                ${fontSizeRow('32 / 40', '--font-size-32', '--font-line-height-40')}
            </div>

            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Native heading tags</h3>
                ${headingRow('h1', 'Heading 1 — Page title')}
                ${headingRow('h2', 'Heading 2 — Major section')}
                ${headingRow('h3', 'Heading 3 — Sub-section')}
                ${headingRow('h4', 'Heading 4 — Card title')}
                ${headingRow('h5', 'Heading 5 — Group label')}
                ${headingRow('h6', 'Heading 6 — Caption')}
            </div>

            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Body &amp; semantic colors</h3>
                <p style="color: var(--text-primary); margin: 0 0 8px;">Primary text — used for body copy and headings.</p>
                <p style="color: var(--text-secondary); margin: 0 0 8px;">Secondary text — supporting information.</p>
                <p style="color: var(--text-tertiary); margin: 0 0 8px;">Tertiary text — labels and meta information.</p>
                <p style="color: var(--text-placeholder); margin: 0 0 8px;">Placeholder text — input hints.</p>
                <p style="color: var(--text-disabled); margin: 0 0 8px;">Disabled text — inactive elements.</p>
                <p style="color: var(--text-brand); margin: 0;">Brand text — links, primary actions.</p>
            </div>
        </div>
    `,
};
