import { renderMolecule } from 'storybook-helpers/render-twig';
import { componentDocs, sectionFull, Meta, StoryObj } from 'storybook-helpers/docs';


const docs = componentDocs({
    name: 'pagination',
    tag: 'nav',
    extends: "model('component')",
    data: [
        { prop: 'parameters', type: 'array', default: '[]', desc: 'Associative array with current route arguments' },
        { prop: 'currentPage', type: 'int', default: '1', desc: 'Currently active page number' },
        { prop: 'paginationPath', type: 'string', default: "''", desc: 'Route name for page links' },
        { prop: 'showAlwaysFirstAndLast', type: 'boolean', default: 'false', desc: 'Always show first/last nav buttons (disabled at boundary)' },
        { prop: 'maxPage', type: 'int', default: '10', desc: 'Total number of pages' },
        { prop: 'extremePagesLimit', type: 'int', default: '1', desc: 'Number of first/last pages to display' },
        { prop: 'nearbyPagesLimit', type: 'int', default: '2', desc: 'Number of pages around the active page' },
        { prop: 'anchor', type: 'string', default: "'#'", desc: 'URL anchor appended after each page link' },
    ],
    modifiers: ['search-cms-results'],
});

const meta: Meta = { title: 'Molecules/Pagination' };
export default meta;

export const Overview: StoryObj = {
    translations: {
        'pagination.previous': 'Previous',
        'pagination.next': 'Next'
    },
    render: () => {

        return (
            sectionFull('Middle page (3 of 10)',
                renderMolecule('pagination', {
                    data: {
                        currentPage: 3,
                        maxPage: 10,
                        paginationPath: '#',
                        showAlwaysFirstAndLast: true,
                    },
                }),
            ) +
            sectionFull('First page',
                renderMolecule('pagination', {
                    data: {
                        currentPage: 1,
                        maxPage: 10,
                        paginationPath: '#',
                        showAlwaysFirstAndLast: true,
                    },
                }),
            ) +
            sectionFull('Last page',
                renderMolecule('pagination', {
                    data: {
                        currentPage: 10,
                        maxPage: 10,
                        paginationPath: '#',
                        showAlwaysFirstAndLast: true,
                    },
                }),
            ) +
            sectionFull('Few pages (no first/last buttons)',
                renderMolecule('pagination', {
                    data: {
                        currentPage: 1,
                        maxPage: 3,
                        paginationPath: '#',
                        showAlwaysFirstAndLast: false,
                    },
                }),
            )
        );
    },
};

export const API: StoryObj = {
    render: () => docs,
};
