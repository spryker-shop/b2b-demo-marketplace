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

const styles = 'style="margin: 0 0 32px; padding: 20px; background: #fafbfc; border: 1px solid #e8e8e8; border-radius: 8px; font-family: Inter, sans-serif;"';
const thStyle = 'style="text-align: left; padding: 6px 12px 6px 0; font-size: 13px; color: #666; border-bottom: 1px solid #eee;"';
const tdStyle = 'style="padding: 6px 12px 6px 0; font-size: 13px; border-bottom: 1px solid #f0f0f0; vertical-align: top;"';
const codeStyle = 'style="font-size: 12px; background: #eef1f5; padding: 2px 6px; border-radius: 3px; color: #333;"';
const sectionHeadStyle = 'style="margin: 0 0 12px; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 0.05em;"';
const sectionWrapStyle = 'style="margin-bottom: 12px; padding-bottom: 24px; border-bottom: 1px solid #eee;"';

export function componentDocs({ name, tag, extends: ext, data, modifiers, attributes, notes }: ComponentDocsInput): string {
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
