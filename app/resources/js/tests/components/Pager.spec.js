import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Pager from '@/Components/Pager.vue';

function buildPagination(overrides = {}) {
    return {
        items: 35,
        items_per_page: 10,
        current_page: 2,
        number_of_pages: 4,
        previous: '?page=1',
        next: '?page=3',
        ...overrides,
    };
}

describe('Pager', () => {
    it('renders first and last record numbers for the current page', () => {
        const wrapper = mount(Pager, {
            props: {
                pagination: buildPagination(),
            },
        });

        expect(wrapper.text()).toContain('Showing 11 - 20 of 35');
    });

    it('shows computed page range around the current page', () => {
        const wrapper = mount(Pager, {
            props: {
                pagination: buildPagination({
                    current_page: 5,
                    number_of_pages: 10,
                }),
                maxSidePages: 2,
            },
        });

        const pageLinks = wrapper
            .findAll('li.page-item a.page-link')
            .map((node) => node.text().trim())
            .filter((text) => /^\d+$/.test(text));

        expect(pageLinks).toEqual(['3', '4', '5', '6', '7']);
    });

    it('handles boundaries for the first page', () => {
        const wrapper = mount(Pager, {
            props: {
                pagination: buildPagination({
                    current_page: 1,
                    number_of_pages: 4,
                    previous: null,
                }),
            },
        });

        const pageLinks = wrapper
            .findAll('li.page-item a.page-link')
            .map((node) => node.text().trim())
            .filter((text) => /^\d+$/.test(text));

        expect(pageLinks).toEqual(['1', '2', '3', '4']);
        expect(wrapper.find('a[aria-label="Previous"]').classes()).toContain('disabled');
    });

    it('renders 0 bounds when there are no items', () => {
        const wrapper = mount(Pager, {
            props: {
                pagination: buildPagination({
                    items: 0,
                    current_page: 1,
                    number_of_pages: 0,
                    previous: null,
                    next: null,
                }),
            },
        });

        expect(wrapper.text()).toContain('Showing 0 - 0 of 0');
        const pageLinks = wrapper
            .findAll('li.page-item a.page-link')
            .map((node) => node.text().trim())
            .filter((text) => /^\d+$/.test(text));

        expect(pageLinks).toEqual([]);
    });
});
