import { renderAtom } from 'storybook-helpers/render-twig';
import { componentDocs, figmaLink, section, Meta, StoryObj } from 'storybook-helpers/docs';

const FIGMA_URL = 'https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=388-6308&p=f&m=dev';

const docs = componentDocs({
    name: 'avatar',
    tag: 'span',
    extends: "model('component')",
    data: [
        { prop: 'placeholder', type: 'string | null', default: 'null', desc: 'Initials text shown inside the avatar' },
        { prop: 'image', type: 'string | null', default: 'null', desc: 'Background image URL' },
        { prop: 'status', type: 'boolean | null', default: 'null', desc: 'Show online-status dot' },
    ],
    modifiers: ['sm', 'lg', 'xl'],
});

const meta: Meta = { title: 'Atoms/Avatar' };
export default meta;

export const Overview: StoryObj = {
    render: () => {
        return (
            figmaLink(FIGMA_URL) +
            section(
                'Initials',
                renderAtom('avatar', { data: { placeholder: 'AB' } }) +
                    renderAtom('avatar', { data: { placeholder: 'JD' } }) +
                    renderAtom('avatar', { data: { placeholder: 'KS' } }),
            ) +
            section('Image', renderAtom('avatar', { data: { image: 'https://i.pravatar.cc/48?img=1' } })) +
            section('Icon fallback', renderAtom('avatar', {})) +
            section(
                'Status dot',
                renderAtom('avatar', { data: { placeholder: 'AB', status: true } }) +
                    renderAtom('avatar', { data: { status: true } }),
            ) +
            section(
                'Sizes',
                renderAtom('avatar', { modifiers: ['sm'], data: { placeholder: 'SM' } }) +
                    renderAtom('avatar', { data: { placeholder: 'MD' } }) +
                    renderAtom('avatar', { modifiers: ['lg'], data: { placeholder: 'LG' } }) +
                    renderAtom('avatar', { modifiers: ['xl'], data: { placeholder: 'XL' } }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
