const GRID_COLUMN_COUNT = 12;
const GRID_COLUMN_NUMBERS = Array.from({ length: GRID_COLUMN_COUNT }, (_, index) => index + 1);

export const sectionStyle =
    'margin: 0 0 32px; padding: 20px; background: #fafbfc; border: 1px solid #e8e8e8; border-radius: 8px;';
export const headStyle =
    'margin: 0 0 16px; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 0.05em;';
export const subHeadStyle = 'font-family: ui-monospace, monospace; font-size: 12px; color: #666; margin: 16px 0 8px;';
export const cellStyle =
    'background: var(--background-brand-subtle); color: var(--text-brand); padding: 16px; border: 1px solid var(--border-brand); border-radius: 4px; text-align: center; font-family: ui-monospace, monospace; font-size: 12px;';
export const tallCellStyle = `${cellStyle} min-height: 60px; display: flex; align-items: center; justify-content: center;`;
export const mutedCell =
    'background: var(--background-accent-subtle); color: var(--text-secondary); padding: 12px; border: 1px solid var(--border-default); border-radius: 4px; text-align: center; font-family: ui-monospace, monospace; font-size: 12px;';

const cell = (label, opts = '') => `<div class="col col--sm-3"><div style="${cellStyle} ${opts}">${label}</div></div>`;
const tallCell = (label, h, opts = '') =>
    `<div class="col col--sm-3" style="${opts}"><div style="${tallCellStyle} height: ${h};">${label}</div></div>`;

// Each section keeps the indentation it has inside the Overview story so the rendered HTML stays unchanged.

export const renderColumnsSection = (): string => `<div style="${sectionStyle}">
                <h3 style="${headStyle}">12-column grid (.grid + .col)</h3>
                <div class="grid grid--gap" style="margin-bottom: 16px;">
                    ${GRID_COLUMN_NUMBERS.map(
                        (columnNumber) => `
                        <div class="col col--sm-1">
                            <div style="${mutedCell}">${columnNumber}</div>
                        </div>
                    `,
                    ).join('')}
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
            </div>`;

export const renderGapSection = (): string => `<div style="${sectionStyle}">
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
            </div>`;

export const renderHorizontalAlignmentSection = (): string => `<div style="${sectionStyle}">
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
            </div>`;

export const renderVerticalAlignmentSection = (): string => `<div style="${sectionStyle}">
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
            </div>`;

export const renderDirectionSection = (): string => `<div style="${sectionStyle}">
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
            </div>`;

export const renderWrappingSection = (): string => `<div style="${sectionStyle}">
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
            </div>`;

export const renderSizingSection = (): string => `<div style="${sectionStyle}">
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
            </div>`;
