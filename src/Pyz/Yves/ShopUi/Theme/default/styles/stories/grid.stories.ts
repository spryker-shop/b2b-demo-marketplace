import type { Meta, StoryObj } from 'storybook-helpers/docs';
import {
    cellStyle,
    headStyle,
    mutedCell,
    renderColumnsSection,
    renderDirectionSection,
    renderGapSection,
    renderHorizontalAlignmentSection,
    renderSizingSection,
    renderVerticalAlignmentSection,
    renderWrappingSection,
    sectionStyle,
    subHeadStyle,
    tallCellStyle,
} from './grid-story-sections';

const meta: Meta = { title: 'Basic/Grid' };
export default meta;

export const Overview: StoryObj = {
    render: () => `
        <div style="font-family: 'Inter', sans-serif;">
            ${renderColumnsSection()}

            ${renderGapSection()}

            ${renderHorizontalAlignmentSection()}

            ${renderVerticalAlignmentSection()}

            ${renderDirectionSection()}

            ${renderWrappingSection()}

            ${renderSizingSection()}

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
