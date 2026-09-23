import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, figmaLink, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=545-2635&p=f&m=dev';

const docs = componentDocs({
    name: 'header-dropdown',
    tag: 'header-dropdown',
    extends: "model('component')",
    data: [
        {
            prop: 'preset',
            type: 'string',
            default: 'required',
            desc: "Dropdown preset: 'user-account' or 'self-service'",
        },
        { prop: 'userName', type: 'string', default: "''", desc: 'Display name of the user' },
        { prop: 'companyName', type: 'string', default: "''", desc: 'Company name shown beneath user name' },
        {
            prop: 'isDrawer',
            type: 'boolean',
            default: 'false',
            desc: 'Drawer (mobile) mode — changes chevron and layout',
        },
        {
            prop: 'drillDownTarget',
            type: 'string',
            default: "''",
            desc: 'Target panel id for drawer drill-down navigation',
        },
        {
            prop: 'panelOnly',
            type: 'boolean',
            default: 'false',
            desc: 'Show only panel content without trigger button',
        },
    ],
});

const meta: Meta = { title: 'Molecules/Header Dropdown' };
export default meta;

export const Overview: StoryObj = {
    translations: {
        'header.account.menu': 'Account menu',
        'self_service_portal.customer_navigation.title': 'Self-Service Portal',
        'customer.account.user_account': 'User Account',
        'customer.account.overview': 'Overview',
        'customer.account.profile_data': 'Profile Data',
        'customer.account.address': 'Addresses',
        'customer.account.order_history': 'Order History',
        'return_page.default_title': 'Returns',
        'customer.account.newsletter': 'Newsletter',
        'quote_request_page.quote_request': 'Quote Requests',
        'customer.logout': 'Logout',
        'company.account.company_account': 'Company Account',
        'company.account.overview': 'Company Overview',
        'company.account.company_role.users': 'Users',
        'company.account.business_unit': 'Business Units',
        'company.account.company_user.role': 'Roles',
        'company.account.merchant_relations': 'Merchant Relations',
        'merchant_relation_request_page.merchant_relation_request': 'Merchant Relation Requests',
    },
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            sectionFull(
                'User Account preset',
                renderMolecule('header-dropdown', {
                    data: {
                        preset: 'user-account',
                        userName: 'John Doe',
                        companyName: 'Spryker Systems GmbH',
                    },
                }),
            ) +
            sectionFull(
                'User Account — panel only',
                renderMolecule('header-dropdown', {
                    data: {
                        preset: 'user-account',
                        userName: 'John Doe',
                        companyName: 'Spryker Systems GmbH',
                        panelOnly: true,
                    },
                }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
