import { figmaLink, Meta, StoryObj } from 'storybook-helpers/docs';

const meta: Meta = { title: 'Introduction' };
export default meta;

const FIGMA_LIBRARY_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master';
const SPRYKER_DOCS_URL = 'https://docs.spryker.com/docs/dg/dev/frontend-development/latest/design-tokens#how-it-works';

const wrapStyle =
    'font-family: Inter, sans-serif; color: #1a1a1a; max-width: 900px; line-height: 1.6;';
const heroStyle =
    'margin: 0 0 32px; padding: 32px; background: linear-gradient(135deg, var(--background-brand-subtle), var(--background-accent-subtle)); border-radius: 12px; border: 1px solid var(--border-default);';
const heroTitleStyle = 'margin: 0 0 12px; font-size: 32px; font-weight: 700; color: var(--text-primary);';
const heroSubtitleStyle = 'margin: 0; font-size: 16px; color: var(--text-secondary);';
const sectionStyle =
    'margin: 0 0 24px; padding: 24px; background: #fafbfc; border: 1px solid #e8e8e8; border-radius: 8px;';
const h2Style = 'margin: 0 0 12px; font-size: 20px; font-weight: 600; color: var(--text-primary);';
const pStyle = 'margin: 0 0 12px; font-size: 14px; color: var(--text-secondary);';
const codeStyle = 'font-family: ui-monospace, monospace; font-size: 12px; background: #eef1f5; padding: 2px 6px; border-radius: 3px; color: #333;';
const tokenGridStyle = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin: 16px 0 0;';
const tokenCardStyle =
    'padding: 12px; background: #fff; border: 1px solid #e8e8e8; border-radius: 6px; text-align: center;';
const navGridStyle = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin: 16px 0 0;';
const navCardStyle =
    'display: block; padding: 16px; background: #fff; border: 1px solid #e8e8e8; border-radius: 8px; text-decoration: none; color: inherit; transition: border-color 0.15s;';
const navTitleStyle = 'margin: 0 0 4px; font-size: 14px; font-weight: 600; color: var(--text-primary);';
const navDescStyle = 'margin: 0; font-size: 12px; color: var(--text-tertiary);';

const tokenSwatch = (varName: string, label: string) => `
    <div style="${tokenCardStyle}">
        <div style="width: 100%; height: 48px; background: var(${varName}); border-radius: 4px; border: 1px solid rgba(0,0,0,0.05); margin: 0 0 8px;"></div>
        <code style="font-family: ui-monospace, monospace; font-size: 11px; color: #555;">${label}</code>
    </div>`;

const navCard = (title: string, desc: string, href: string) => `
    <a href="${href}" style="${navCardStyle}">
        <h4 style="${navTitleStyle}">${title} →</h4>
        <p style="${navDescStyle}">${desc}</p>
    </a>`;

export const Welcome: StoryObj = {
    render: () => `
        <div style="${wrapStyle}">
            <div style="${heroStyle}">
                <h1 style="${heroTitleStyle}">Demo Design System</h1>
                <p style="${heroSubtitleStyle}">Spryker ShopUi component library built on design tokens — single source of truth for colours, typography, spacing, and motion.</p>
            </div>

            ${figmaLink(FIGMA_LIBRARY_URL, 'Open the design library in Figma')}

            <div style="${sectionStyle}">
                <h2 style="${h2Style}">What are design tokens?</h2>
                <p style="${pStyle}">
                    Design tokens are named CSS custom properties that capture every design decision — a colour, a spacing step, a font size, a radius — in one place. Components reference tokens (<code style="${codeStyle}">var(--background-brand-primary)</code>) instead of hard-coded values, so the visual language stays consistent and themable.
                </p>
                <p style="${pStyle}">
                    All tokens are generated into <code style="${codeStyle}">src/Pyz/Yves/ShopUi/Theme/default/styles/design-tokens.css</code> and loaded globally. Every component, every SCSS file, every story in this Storybook reads from the same source.
                </p>
                <p style="${pStyle}">
                    For the full pipeline — how tokens are authored in Figma, exported, and injected into the build — see the Spryker docs: <a href="${SPRYKER_DOCS_URL}" target="_blank" rel="noopener noreferrer" style="color: var(--text-brand); text-decoration: none; border-bottom: 1px solid currentColor;">Design tokens · How it works →</a>
                </p>

                <div style="${tokenGridStyle}">
                    ${tokenSwatch('--background-brand-primary', '--background-brand-primary')}
                    ${tokenSwatch('--background-accent-primary', '--background-accent-primary')}
                    ${tokenSwatch('--background-state-success-bold', '--background-state-success-bold')}
                    ${tokenSwatch('--background-state-error-bold', '--background-state-error-bold')}
                </div>
            </div>

            <div style="${sectionStyle}">
                <h2 style="${h2Style}">How to use tokens</h2>
                <p style="${pStyle}">In any SCSS or inline style, reference the CSS variable:</p>
                <pre style="margin: 0; padding: 16px; background: #1e1e1e; color: #e8e8e8; border-radius: 6px; font-family: ui-monospace, monospace; font-size: 12px; overflow-x: auto;"><span style="color: #569cd6;">.button</span> {
    <span style="color: #9cdcfe;">background</span>: <span style="color: #ce9178;">var(--background-brand-primary)</span>;
    <span style="color: #9cdcfe;">color</span>: <span style="color: #ce9178;">var(--text-on-brand)</span>;
    <span style="color: #9cdcfe;">padding</span>: <span style="color: #ce9178;">var(--scale-8) var(--scale-16)</span>;
    <span style="color: #9cdcfe;">border-radius</span>: <span style="color: #ce9178;">var(--radius-md)</span>;
    <span style="color: #9cdcfe;">font-size</span>: <span style="color: #ce9178;">var(--font-size-14)</span>;
}</pre>
                <p style="${pStyle} margin-top: 12px;">
                    <strong>Never hard-code</strong> hex colours, pixel spacing, or font sizes — always use a token. If a value you need is missing, surface it with the design team rather than working around it.
                </p>
            </div>

            <div style="${sectionStyle}">
                <h2 style="${h2Style}">Foundations</h2>
                <p style="${pStyle}">Browse the token reference pages:</p>
                <div style="${navGridStyle}">
                    ${navCard('Colors', 'Brand, accent, surface, state, and the full grey/teal/forest/blue palettes', './?path=/story/basic-colors--overview')}
                    ${navCard('Typography', 'Font sizes, line heights, and semantic text colours', './?path=/story/basic-typography--overview')}
                    ${navCard('Spacing', 'Scale tokens (--scale-*) and border radius (--radius-*)', './?path=/story/basic-spacing--overview')}
                    ${navCard('Grid', '12-column layout system with responsive breakpoints', './?path=/story/basic-grid--overview')}
                </div>
            </div>

            <div style="${sectionStyle}">
                <h2 style="${h2Style}">Component anatomy</h2>
                <p style="${pStyle}">
                    Components follow the atomic design hierarchy — <strong>Atoms</strong> (Button, Input, Badge) are the smallest building blocks, <strong>Molecules</strong> combine atoms into reusable widgets (Search Form, Pagination, Action Bar), and <strong>Organisms</strong> assemble molecules into page sections.
                </p>
                <p style="${pStyle}">
                    Each component is a Twig template paired with a SCSS file and an optional TypeScript class. The class layer registers a web component and handles interactivity; styles are scoped via BEM-style class names.
                </p>
            </div>

            <div style="${sectionStyle}">
                <h2 style="${h2Style}">Design ↔ code parity</h2>
                <p style="${pStyle}">
                    Most foundation pages and components in this Storybook link out to their counterpart in the Figma design library. Look for the <strong>View in Figma</strong> button at the top of each story.
                </p>
            </div>
        </div>
    `,
};
