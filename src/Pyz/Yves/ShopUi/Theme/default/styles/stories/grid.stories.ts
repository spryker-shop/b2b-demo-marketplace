import type { Meta, StoryObj } from 'storybook-helpers/docs';

const meta: Meta = { title: 'Basic/Grid' };
export default meta;

const sectionStyle =
    'margin: 0 0 32px; padding: 20px; background: #fafbfc; border: 1px solid #e8e8e8; border-radius: 8px;';
const headStyle = 'margin: 0 0 16px; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 0.05em;';
const subHeadStyle = 'font-family: ui-monospace, monospace; font-size: 12px; color: #666; margin: 16px 0 8px;';
const cellStyle =
    'background: var(--background-brand-subtle); color: var(--text-brand); padding: 16px; border: 1px solid var(--border-brand); border-radius: 4px; text-align: center; font-family: ui-monospace, monospace; font-size: 12px;';
const tallCellStyle = `${cellStyle} min-height: 60px; display: flex; align-items: center; justify-content: center;`;
const mutedCell =
    'background: var(--background-accent-subtle); color: var(--text-secondary); padding: 12px; border: 1px solid var(--border-default); border-radius: 4px; text-align: center; font-family: ui-monospace, monospace; font-size: 12px;';

const cell = (label, opts = '') => `<div class="col col--sm-3"><div style="${cellStyle} ${opts}">${label}</div></div>`;
const tallCell = (label, h, opts = '') =>
    `<div class="col col--sm-3" style="${opts}"><div style="${tallCellStyle} height: ${h};">${label}</div></div>`;

export const Overview: StoryObj = {
    render: () => `
        <div style="font-family: 'Inter', sans-serif;">
            <div style="${sectionStyle}">
                <h3 style="${headStyle}">12-column grid (.grid + .col)</h3>
                <div class="grid grid--gap" style="margin-bottom: 16px;">
                    ${[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]
                        .map(
                            (i) => `
                        <div class="col col--sm-1">
                            <div style="${mutedCell}">${i}</div>
                        </div>
                    `,
                        )
                        .join('')}
                </div>

                <h3 style="${headStyle}">Column spans (col--sm-N)</h3>
                <div class="grid grid--gap" style="margin-bottom: 8px;">
                    <div class="col col--sm-12"><div style="${cellStyle}">col--sm-12</div></div>
                </div>
                <div class="grid grid--gap" style="margin-bottom: 8px;">
                    <div class="col col--sm-6"><div style="${cellStyle}">col--sm-6</div></div>
                    <div class="col col--sm-6"><div style="${cellStyle}">col--sm-6</div></div>
                </div>
                <div class="grid grid--gap" style="margin-bottom: 8px;">
                    <div class="col col--sm-4"><div style="${cellStyle}">col--sm-4</div></div>
                    <div class="col col--sm-4"><div style="${cellStyle}">col--sm-4</div></div>
                    <div class="col col--sm-4"><div style="${cellStyle}">col--sm-4</div></div>
                </div>
                <div class="grid grid--gap" style="margin-bottom: 8px;">
                    ${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}
                </div>

                <h3 style="${headStyle}">Push offset (col--push-left-sm-N)</h3>
                <div class="grid grid--gap">
                    <div class="col col--sm-3 col--push-left-sm-3"><div style="${cellStyle}">col--sm-3 + push-left-sm-3</div></div>
                    <div class="col col--sm-3 col--push-left-sm-3"><div style="${cellStyle}">col--sm-3 + push-left-sm-3</div></div>
                </div>
            </div>

            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Gap / gutter modifiers</h3>

                <p style="${subHeadStyle}">no modifier — 0 gap (columns butt against each other)</p>
                <div class="grid" style="margin-bottom: 16px;">
                    ${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}
                </div>

                <p style="${subHeadStyle}">grid--gap-smaller — 10px</p>
                <div class="grid grid--gap-smaller" style="margin-bottom: 16px;">
                    ${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}
                </div>

                <p style="${subHeadStyle}">grid--gap-small — 20px</p>
                <div class="grid grid--gap-small" style="margin-bottom: 16px;">
                    ${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}
                </div>

                <p style="${subHeadStyle}">grid--gap / grid--with-gutter — responsive (mobile/lg)</p>
                <div class="grid grid--gap" style="margin-bottom: 16px;">
                    ${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}
                </div>
            </div>

            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Horizontal alignment (justify-content)</h3>

                <p style="${subHeadStyle}">grid--left (default) — flex-start</p>
                <div class="grid grid--gap grid--left" style="margin-bottom: 16px; background: var(--background-subtle);">
                    ${cell('col--sm-3')}${cell('col--sm-3')}
                </div>

                <p style="${subHeadStyle}">grid--center</p>
                <div class="grid grid--gap grid--center" style="margin-bottom: 16px; background: var(--background-subtle);">
                    ${cell('col--sm-3')}${cell('col--sm-3')}
                </div>

                <p style="${subHeadStyle}">grid--right</p>
                <div class="grid grid--gap grid--right" style="margin-bottom: 16px; background: var(--background-subtle);">
                    ${cell('col--sm-3')}${cell('col--sm-3')}
                </div>

                <p style="${subHeadStyle}">grid--justify / grid--justify-column — space-between</p>
                <div class="grid grid--gap grid--justify" style="margin-bottom: 16px; background: var(--background-subtle);">
                    ${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}
                </div>
            </div>

            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Vertical alignment (align-items)</h3>

                <p style="${subHeadStyle}">grid--top (flex-start)</p>
                <div class="grid grid--gap grid--top" style="margin-bottom: 16px; background: var(--background-subtle); min-height: 120px;">
                    ${tallCell('40px', '40px')}${tallCell('80px', '80px')}${tallCell('60px', '60px')}
                </div>

                <p style="${subHeadStyle}">grid--middle (center)</p>
                <div class="grid grid--gap grid--middle" style="margin-bottom: 16px; background: var(--background-subtle); min-height: 120px;">
                    ${tallCell('40px', '40px')}${tallCell('80px', '80px')}${tallCell('60px', '60px')}
                </div>

                <p style="${subHeadStyle}">grid--bottom (flex-end)</p>
                <div class="grid grid--gap grid--bottom" style="margin-bottom: 16px; background: var(--background-subtle); min-height: 120px;">
                    ${tallCell('40px', '40px')}${tallCell('80px', '80px')}${tallCell('60px', '60px')}
                </div>

                <p style="${subHeadStyle}">grid--baseline</p>
                <div class="grid grid--gap grid--baseline" style="margin-bottom: 16px; background: var(--background-subtle); min-height: 100px;">
                    <div class="col col--sm-3"><div style="${cellStyle} font-size: 12px;">12px</div></div>
                    <div class="col col--sm-3"><div style="${cellStyle} font-size: 24px;">24px</div></div>
                    <div class="col col--sm-3"><div style="${cellStyle} font-size: 36px;">36px</div></div>
                </div>

                <p style="${subHeadStyle}">grid--stretch — children stretch to row height</p>
                <div class="grid grid--gap grid--stretch" style="margin-bottom: 16px; background: var(--background-subtle); min-height: 120px;">
                    <div class="col col--sm-3"><div style="${cellStyle}">short</div></div>
                    <div class="col col--sm-3"><div style="${cellStyle}">A bit longer content that wraps onto multiple lines so the row gets taller</div></div>
                    <div class="col col--sm-3"><div style="${cellStyle}">short</div></div>
                </div>
            </div>

            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Layout direction</h3>

                <p style="${subHeadStyle}">grid--column — flex-direction: column</p>
                <div class="grid grid--gap grid--column" style="margin-bottom: 16px;">
                    <div class="col"><div style="${cellStyle}">row 1</div></div>
                    <div class="col"><div style="${cellStyle}">row 2</div></div>
                    <div class="col"><div style="${cellStyle}">row 3</div></div>
                </div>

                <p style="${subHeadStyle}">grid--column-mob-reverse — column-reverse on mobile, column on lg+</p>
                <div class="grid grid--gap grid--column-mob-reverse" style="margin-bottom: 16px;">
                    <div class="col"><div style="${cellStyle}">first in source — last on mobile</div></div>
                    <div class="col"><div style="${cellStyle}">middle</div></div>
                    <div class="col"><div style="${cellStyle}">last in source — first on mobile</div></div>
                </div>

                <p style="${subHeadStyle}">grid--row-tablet — flex-direction: row from md+</p>
                <div class="grid grid--gap grid--column grid--row-tablet" style="margin-bottom: 16px;">
                    ${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}
                </div>
            </div>

            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Wrapping</h3>

                <p style="${subHeadStyle}">grid--nowrap — never wraps (note: cells get squished if they exceed 100%)</p>
                <div class="grid grid--gap grid--nowrap" style="margin-bottom: 16px; overflow: auto;">
                    ${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}
                </div>

                <p style="${subHeadStyle}">grid--nowrap-lg-only — wraps on small, nowrap on lg+</p>
                <div class="grid grid--gap grid--nowrap-lg-only" style="margin-bottom: 16px;">
                    ${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}${cell('col--sm-3')}
                </div>

                <p style="${subHeadStyle}">grid--sm-scroll — horizontal scroll snap on small, regular grid on lg+</p>
                <div class="grid grid--gap grid--sm-scroll" style="margin-bottom: 16px;">
                    ${cell('Card 1', 'min-height: 80px;')}${cell('Card 2', 'min-height: 80px;')}${cell('Card 3', 'min-height: 80px;')}${cell('Card 4', 'min-height: 80px;')}
                </div>
            </div>

            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Sizing modifiers</h3>

                <p style="${subHeadStyle}">grid--wide / grid--expand — width: 100%</p>
                <div class="grid grid--gap grid--wide" style="margin-bottom: 16px; background: var(--background-subtle);">
                    ${cell('col--sm-3')}${cell('col--sm-3')}
                </div>

                <p style="${subHeadStyle}">grid--inline — display: inline-flex</p>
                <div class="grid grid--gap grid--inline" style="background: var(--background-subtle); padding: 8px;">
                    <div class="col"><div style="${cellStyle}">Inline grid 1</div></div>
                    <div class="col"><div style="${cellStyle}">Inline grid 2</div></div>
                </div>
            </div>

            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Column-specific modifiers</h3>

                <p style="${subHeadStyle}">col--expand — flex-grow: 1, fills remaining space</p>
                <div class="grid grid--gap" style="margin-bottom: 16px;">
                    <div class="col col--sm-3"><div style="${cellStyle}">col--sm-3</div></div>
                    <div class="col col--expand"><div style="${cellStyle}">col--expand</div></div>
                    <div class="col col--sm-3"><div style="${cellStyle}">col--sm-3</div></div>
                </div>

                <p style="${subHeadStyle}">col--equal — equal flex 1 + flex-basis 0</p>
                <div class="grid grid--gap" style="margin-bottom: 16px;">
                    <div class="col col--equal"><div style="${cellStyle}">equal</div></div>
                    <div class="col col--equal"><div style="${cellStyle}">equal (longer content)</div></div>
                    <div class="col col--equal"><div style="${cellStyle}">equal</div></div>
                </div>

                <p style="${subHeadStyle}">col--top / col--middle / col--bottom — align-self override</p>
                <div class="grid grid--gap grid--top" style="margin-bottom: 16px; background: var(--background-subtle); min-height: 140px;">
                    <div class="col col--sm-3 col--top"><div style="${tallCellStyle}">col--top</div></div>
                    <div class="col col--sm-3 col--middle"><div style="${tallCellStyle}">col--middle</div></div>
                    <div class="col col--sm-3 col--bottom"><div style="${tallCellStyle}">col--bottom</div></div>
                </div>

                <p style="${subHeadStyle}">col--center — margin auto on both sides</p>
                <div class="grid grid--gap" style="margin-bottom: 16px; background: var(--background-subtle);">
                    <div class="col col--sm-3 col--center"><div style="${cellStyle}">col--center</div></div>
                </div>

                <p style="${subHeadStyle}">col--left / col--right — push to edges via auto margins</p>
                <div class="grid grid--gap" style="margin-bottom: 16px; background: var(--background-subtle);">
                    <div class="col col--sm-3 col--left"><div style="${cellStyle}">col--left</div></div>
                    <div class="col col--sm-3 col--right"><div style="${cellStyle}">col--right</div></div>
                </div>

                <p style="${subHeadStyle}">col--auto — width: auto, fits content</p>
                <div class="grid grid--gap" style="margin-bottom: 16px;">
                    <div class="col col--sm-auto"><div style="${cellStyle}">auto</div></div>
                    <div class="col col--sm-auto"><div style="${cellStyle}">A wider auto column</div></div>
                </div>

                <p style="${subHeadStyle}">col--mobile-expand — expand on mobile, fixed width on lg+</p>
                <div class="grid grid--gap" style="margin-bottom: 16px;">
                    <div class="col col--sm-3 col--mobile-expand"><div style="${cellStyle}">col--mobile-expand</div></div>
                </div>

                <p style="${subHeadStyle}">col--bottom-indent — adds responsive bottom padding</p>
                <div class="grid grid--gap" style="margin-bottom: 16px; background: var(--background-subtle);">
                    <div class="col col--sm-3 col--bottom-indent"><div style="${cellStyle}">col--bottom-indent</div></div>
                </div>

                <p style="${subHeadStyle}">col--reset-min-width — min-width: 0 (helps text-overflow inside flex children)</p>
                <div class="grid grid--gap" style="margin-bottom: 16px;">
                    <div class="col col--sm-3 col--reset-min-width" style="overflow: hidden;">
                        <div style="${cellStyle} white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">very-very-very-very-very-long-string-that-needs-to-clip</div>
                    </div>
                </div>
            </div>

            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Container modifiers</h3>
                <p style="${subHeadStyle}">.container — page-width wrapper with horizontal padding</p>
                <div class="container" style="background: var(--background-subtle); padding-top: 16px; padding-bottom: 16px; margin-bottom: 12px;">
                    <div style="${mutedCell}">.container (default max-width)</div>
                </div>

                <p style="${subHeadStyle}">.container--medium — max-width 1000px</p>
                <div class="container container--medium" style="background: var(--background-subtle); padding-top: 16px; padding-bottom: 16px; margin-bottom: 12px;">
                    <div style="${mutedCell}">.container--medium</div>
                </div>

                <p style="${subHeadStyle}">.container--small — max-width 800px</p>
                <div class="container container--small" style="background: var(--background-subtle); padding-top: 16px; padding-bottom: 16px; margin-bottom: 12px;">
                    <div style="${mutedCell}">.container--small</div>
                </div>

                <p style="${subHeadStyle}">.container--expand — max-width 100%</p>
                <div class="container container--expand" style="background: var(--background-subtle); padding-top: 16px; padding-bottom: 16px;">
                    <div style="${mutedCell}">.container--expand</div>
                </div>
            </div>

            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Responsive grid (resize viewport)</h3>
                <p style="font-size: 13px; color: #666; margin: 0 0 12px;">col--sm-12 col--md-6 col--lg-3 — full width on mobile → 2 cols on md → 4 cols on lg+</p>
                <div class="grid grid--gap">
                    <div class="col col--sm-12 col--md-6 col--lg-3"><div style="${cellStyle}">Column 1</div></div>
                    <div class="col col--sm-12 col--md-6 col--lg-3"><div style="${cellStyle}">Column 2</div></div>
                    <div class="col col--sm-12 col--md-6 col--lg-3"><div style="${cellStyle}">Column 3</div></div>
                    <div class="col col--sm-12 col--md-6 col--lg-3"><div style="${cellStyle}">Column 4</div></div>
                </div>
            </div>

            <div style="${sectionStyle}">
                <h3 style="${headStyle}">Breakpoints</h3>
                <table style="width: 100%; border-collapse: collapse; font-family: ui-monospace, monospace; font-size: 12px;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e0e0e0; color: #555;">Token</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e0e0e0; color: #555;">Min width</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e0e0e0; color: #555;">Class suffix</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td style="padding: 8px;">$xs</td><td style="padding: 8px;">0</td><td style="padding: 8px;">--xs-*</td></tr>
                        <tr><td style="padding: 8px;">$sm</td><td style="padding: 8px;">576px</td><td style="padding: 8px;">--sm-*</td></tr>
                        <tr><td style="padding: 8px;">$md</td><td style="padding: 8px;">768px</td><td style="padding: 8px;">--md-*</td></tr>
                        <tr><td style="padding: 8px;">$lg</td><td style="padding: 8px;">992px</td><td style="padding: 8px;">--lg-*</td></tr>
                        <tr><td style="padding: 8px;">$xl</td><td style="padding: 8px;">1200px</td><td style="padding: 8px;">--xl-*</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    `,
};
