import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Head from '@/Components/Head.vue';

describe('Head', () => {
    it('uses the app name alone when no title prop is given', () => {
        const wrapper = mount(Head);
        expect(wrapper.find('[data-testid="head-title"]').text()).toBe('Mako Vue');
    });

    it('prepends the page title when the title prop is provided', () => {
        const wrapper = mount(Head, { props: { title: 'Dashboard' } });
        expect(wrapper.find('[data-testid="head-title"]').text()).toBe('Dashboard | Mako Vue');
    });
});
