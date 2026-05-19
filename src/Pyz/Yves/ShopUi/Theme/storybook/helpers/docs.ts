import type { Meta as SbMeta, StoryObj as SbStoryObj } from '@storybook/html';

// `translations` is a Pyz-specific field: the storybook decorator reads it off
// the story export and feeds it into the twig-engine's i18n stub before render
// so `{{ 'key' | trans }}` resolves. It's not part of Storybook's contract,
// so we widen `StoryObj` here once and re-export — saves every story file
// from declaring its own union.
export type Translations = Record<string, string>;
export type Meta = SbMeta;
export type StoryObj = SbStoryObj & { translations?: Translations };

interface DocsDataProp {
    prop: string;
    type?: string;
    default?: string;
    desc?: string;
}

interface DocsAttribute {
    attr: string;
    default?: string;
    desc?: string;
}

export interface ComponentDocsInput {
    name: string;
    tag?: string;
    extends?: string;
    data?: DocsDataProp[];
    modifiers?: string[];
    attributes?: DocsAttribute[];
    notes?: string;
}

const styles =
    'style="margin: 0 0 32px; padding: 20px; background: #fafbfc; border: 1px solid #e8e8e8; border-radius: 8px; font-family: Inter, sans-serif;"';
const thStyle =
    'style="text-align: left; padding: 6px 12px 6px 0; font-size: 13px; color: #666; border-bottom: 1px solid #eee;"';
const tdStyle =
    'style="padding: 6px 12px 6px 0; font-size: 13px; border-bottom: 1px solid #f0f0f0; vertical-align: top;"';
const codeStyle = 'style="font-size: 12px; background: #eef1f5; padding: 2px 6px; border-radius: 3px; color: #333;"';
const sectionHeadStyle =
    'style="margin: 0 0 12px; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 0.05em;"';
const sectionWrapStyle = 'style="margin-bottom: 12px; padding-bottom: 24px; border-bottom: 1px solid #eee;"';

export function componentDocs({
    name,
    tag,
    extends: ext,
    data,
    modifiers,
    attributes,
    notes,
}: ComponentDocsInput): string {
    let html = `<div ${styles}>`;
    html += `<div style="display: flex; gap: 12px; align-items: baseline; margin-bottom: 12px;">`;
    html += `<h2 style="margin: 0; font-size: 18px;">${name}</h2>`;
    html += `<code ${codeStyle}>&lt;${tag || 'div'}&gt;</code>`;
    if (ext) html += `<span style="font-size: 12px; color: #999;">extends ${ext}</span>`;
    html += `</div>`;

    if (data?.length) {
        html += `<h4 style="margin: 12px 0 6px; font-size: 13px; color: #666; text-transform: uppercase; letter-spacing: 0.05em;">Data</h4>`;
        html += `<table style="border-collapse: collapse; width: 100%;"><tr><th ${thStyle}>Prop</th><th ${thStyle}>Type</th><th ${thStyle}>Default</th><th ${thStyle}>Description</th></tr>`;
        for (const d of data) {
            const def = d.default !== undefined ? `<code ${codeStyle}>${d.default}</code>` : '';
            html += `<tr><td ${tdStyle}><code ${codeStyle}>${d.prop}</code></td><td ${tdStyle}>${d.type || ''}</td><td ${tdStyle}>${def}</td><td ${tdStyle}>${d.desc || ''}</td></tr>`;
        }
        html += `</table>`;
    }

    if (modifiers?.length) {
        html += `<h4 style="margin: 12px 0 6px; font-size: 13px; color: #666; text-transform: uppercase; letter-spacing: 0.05em;">Modifiers</h4>`;
        html += `<div style="display: flex; flex-wrap: wrap; gap: 6px;">${modifiers.map((m) => `<code ${codeStyle}>${m}</code>`).join('')}</div>`;
    }

    if (attributes?.length) {
        html += `<h4 style="margin: 12px 0 6px; font-size: 13px; color: #666; text-transform: uppercase; letter-spacing: 0.05em;">Attributes</h4>`;
        html += `<table style="border-collapse: collapse; width: 100%;"><tr><th ${thStyle}>Attr</th><th ${thStyle}>Default</th><th ${thStyle}>Description</th></tr>`;
        for (const a of attributes) {
            const def = a.default !== undefined ? `<code ${codeStyle}>${a.default}</code>` : '';
            html += `<tr><td ${tdStyle}><code ${codeStyle}>${a.attr}</code></td><td ${tdStyle}>${def}</td><td ${tdStyle}>${a.desc || ''}</td></tr>`;
        }
        html += `</table>`;
    }

    if (notes) html += `<p style="margin: 12px 0 0; font-size: 12px; color: #888;">${notes}</p>`;
    html += `</div>`;
    return html;
}

export function section(title: string, html: string): string {
    return `<div ${sectionWrapStyle}><h3 ${sectionHeadStyle}>${title}</h3><div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start;">${html}</div></div>`;
}

export function sectionFull(title: string, html: string): string {
    return `<div ${sectionWrapStyle}><h3 ${sectionHeadStyle}>${title}</h3>${html}</div>`;
}

export function figmaLink(url: string | string[], label: string | string[] = 'View in Figma'): string {
    const urls = Array.isArray(url) ? url : [url];
    const labels = Array.isArray(label) ? label : urls.map(() => label);
    const icon = `<svg width="14" height="14" viewBox="0 0 38 57" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M19 28.5a9.5 9.5 0 1 1 19 0 9.5 9.5 0 0 1-19 0Z" fill="#1ABCFE"/><path d="M0 47.5A9.5 9.5 0 0 1 9.5 38H19v9.5a9.5 9.5 0 1 1-19 0Z" fill="#0ACF83"/><path d="M19 0v19h9.5a9.5 9.5 0 1 0 0-19H19Z" fill="#FF7262"/><path d="M0 9.5A9.5 9.5 0 0 0 9.5 19H19V0H9.5A9.5 9.5 0 0 0 0 9.5Z" fill="#F24E1E"/><path d="M0 28.5A9.5 9.5 0 0 0 9.5 38H19V19H9.5A9.5 9.5 0 0 0 0 28.5Z" fill="#A259FF"/></svg>`;
    const linkStyle =
        'display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; background: #fff; border: 1px solid #e8e8e8; border-radius: 6px; color: #1a1a1a; text-decoration: none; font-family: Inter, sans-serif; font-size: 12px; line-height: 1;';
    const links = urls
        .map((u, i) => {
            const text = labels[i] ?? labels[0] ?? 'View in Figma';
            return `<a href="${u}" target="_blank" rel="noopener noreferrer" style="${linkStyle}">${icon}<span>${text} →</span></a>`;
        })
        .join('');
    return `<div style="display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 16px;">${links}</div>`;
}
