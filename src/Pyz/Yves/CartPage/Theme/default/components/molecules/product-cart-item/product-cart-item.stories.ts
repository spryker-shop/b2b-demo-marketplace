import './product-cart-item.scss';
import { renderMolecule } from 'storybook-helpers/render-twig';
import { sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const ROOT = 'product-cart-item';

const PRODUCT = {
    title: 'BREMBO Prime P50 035 Brake Pads',
    sku: 'EAN-0012349',
    merchant: 'Spryker',
    image: '/images/image-placeholder.png',
};

const STOCK = {
    'in-stock': { mod: 'success', label: 'In stock' },
    'out-of-stock': { mod: 'error', label: 'Out of stock' },
    backorder: { mod: 'warning', label: 'Backorder' },
    limited: { mod: 'warning', label: 'Limited stock' },
};

const icon = (name: string, cls = '') =>
    `<svg class="icon ${cls}" aria-hidden="true"><use xlink:href="#:${name}"></use></svg>`;

interface CartItemOptions {
    stock?: keyof typeof STOCK;
    commentsCount?: number;
    assignedAssetName?: string;
    expanded?: boolean;
    comment?: string;
    asset?: { name: string; serial: string; compatibility: 'compatible' | 'incompatible' | 'unchecked' };
}

function chip(filled: boolean, iconName: string, label: string): string {
    const modifier = filled ? `${ROOT}__context-item--filled` : '';
    return `<button type="button" class="${ROOT}__context-item ${modifier}">
        ${icon(iconName, `${ROOT}__context-glyph`)}<span class="${ROOT}__context-text">${label}</span>
    </button>`;
}

function contextStrip(o: CartItemOptions): string {
    const assetLabel = o.assignedAssetName || 'Add asset';
    const commentLabel = o.commentsCount ? `${o.commentsCount} ${o.commentsCount === 1 ? 'Comment' : 'Comments'}` : 'Add comment';
    return `<div class="${ROOT}__context">
        ${chip(!!o.assignedAssetName, 'assets', assetLabel)}
        ${chip(!!o.commentsCount, 'message', commentLabel)}
    </div>`;
}

function commentsSection(o: CartItemOptions): string {
    const body = o.comment
        ? `<div class="${ROOT}__comment" data-qa="cart-item-comment-filled">
               <p style="margin:0">${o.comment}</p>
               <div class="${ROOT}__comment-menu">
                   <button type="button" class="${ROOT}__comment-menu-trigger" aria-label="Comment actions"><span></span></button>
                   <div class="${ROOT}__comment-menu-list"><button type="button">Edit</button><button type="button">Delete</button></div>
               </div>
           </div>`
        : `<div class="${ROOT}__comments"><form class="form"><textarea placeholder="Add a comment for this item…"></textarea></form></div>`;

    return `<section class="${ROOT}__section ${ROOT}__section--comments">
        <h4 class="${ROOT}__section-label">Comments</h4>
        ${body}
    </section>`;
}

function assetSection(o: CartItemOptions): string {
    if (!o.asset) {
        return `<section class="${ROOT}__section ${ROOT}__section--asset">
            <h4 class="${ROOT}__section-label">Assigned asset</h4>
            <div class="${ROOT}__asset"><a class="button button--hollow" href="#">${icon('assets')} Assign asset</a></div>
        </section>`;
    }

    const pill = {
        compatible: '<span class="badge badge--success">Compatible</span>',
        incompatible: '<span class="badge badge--error">Not compatible</span>',
        unchecked: '<span class="badge badge--warning">Not checked</span>',
    }[o.asset.compatibility];

    return `<section class="${ROOT}__section ${ROOT}__section--asset">
        <h4 class="${ROOT}__section-label">Assigned asset</h4>
        <div class="${ROOT}__asset" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span>${o.asset.name}</span><span class="${ROOT}__meta-sep">&middot;</span>
            <span>${o.asset.serial}</span> ${pill}
            <button type="button" class="${ROOT}__remove" style="width:auto;height:auto">${icon('edit')}</button>
            <button type="button" class="${ROOT}__remove" style="width:auto;height:auto">${icon('cross')}</button>
        </div>
    </section>`;
}

function cartItem(o: CartItemOptions = {}): string {
    const stock = STOCK[o.stock || 'in-stock'];
    const quantity = renderMolecule('quantity-counter', {
        modifiers: ['cart'],
        data: { isDisabled: false },
        attributes: { value: 2, min: 1, step: 1, name: 'quantity' },
    });

    return `<article class="${ROOT}" style="max-width:880px">
        <div class="grid">
            <div class="${ROOT}__col ${ROOT}__col--image col">
                <img src="${PRODUCT.image}" alt="${PRODUCT.title}" style="width:100%;border-radius:4px" />
            </div>
            <div class="${ROOT}__col ${ROOT}__col--description col">
                <div class="${ROOT}__details${o.expanded ? ` ${ROOT}__details--expanded` : ''}">
                    <div class="${ROOT}__header grid grid--gap">
                        <div class="${ROOT}__col ${ROOT}__col--content col col--expand">
                            <a class="${ROOT}__title" href="#">${PRODUCT.title}</a>
                            <div class="${ROOT}__meta">
                                <span class="${ROOT}__meta-item">SKU: ${PRODUCT.sku}</span>
                                <span class="${ROOT}__meta-sep" aria-hidden="true">&middot;</span>
                                <span class="${ROOT}__meta-item ${ROOT}__meta-item--merchant"><p>By: <a href="#">${PRODUCT.merchant}</a></p></span>
                                <span class="badge badge--${stock.mod} ${ROOT}__stock">${stock.label}</span>
                            </div>
                            ${contextStrip(o)}
                            <button type="button" class="${ROOT}__toggle">
                                <span class="${ROOT}__toggle-label ${ROOT}__toggle-label--show">Show details</span>
                                <span class="${ROOT}__toggle-label ${ROOT}__toggle-label--hide">Hide details</span>
                                ${icon('caret-down', `${ROOT}__toggle-chevron`)}
                            </button>
                            <div class="${ROOT}__panel">
                                <div class="${ROOT}__panel-inner">
                                    ${commentsSection(o)}
                                    ${assetSection(o)}
                                </div>
                            </div>
                        </div>
                        <div class="${ROOT}__col ${ROOT}__col--rail col">
                            <div class="${ROOT}__quantity-holder">${quantity}</div>
                            <div class="${ROOT}__price-zone"><span>$60.00</span><span>Incl. VAT</span></div>
                            <button type="button" class="${ROOT}__remove">${icon('delete')}<span class="${ROOT}__remove-tooltip" role="tooltip">Remove item from cart</span></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>`;
}

const meta: Meta = { title: 'Molecules/Product Cart Item' };
export default meta;

export const Overview: StoryObj = {
    render: () =>
        sectionFull('Default · in stock', cartItem()) +
        sectionFull('With comment', cartItem({ commentsCount: 1 })) +
        sectionFull('With assigned asset', cartItem({ assignedAssetName: 'Mercedes S Class' })) +
        sectionFull('Out of stock', cartItem({ stock: 'out-of-stock' })) +
        sectionFull('Backorder', cartItem({ stock: 'backorder' })) +
        sectionFull('Limited stock', cartItem({ stock: 'limited' })) +
        sectionFull(
            'Expanded · comment + asset',
            cartItem({
                expanded: true,
                assignedAssetName: 'Mercedes S Class',
                commentsCount: 1,
                comment: 'Replacement for unit damaged on 05/02. Match serial to existing inventory if possible.',
                asset: { name: 'Mercedes S Class', serial: 'ZRH-ETH-75L-23-000148', compatibility: 'incompatible' },
            }),
        ),
};

export const Mobile: StoryObj = {
    parameters: { viewport: { defaultViewport: 'mobile1' } },
    render: () =>
        `<div style="max-width:360px">` +
        cartItem({ expanded: true, commentsCount: 1, comment: 'Deliver to dock B.' }) +
        `</div>`,
};
