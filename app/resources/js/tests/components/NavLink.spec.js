import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import NavLink from '@/Components/NavLink.vue';

describe('NavLink', () => {
    it('renders a nav link with the correct href and label', () => {
        const wrapper = mount(NavLink, { props: { path: '/dashboard', name: 'Dashboard' } });
        const link = wrapper.find('a.nav-link');
        expect(link.attributes('href')).toBe('/dashboard');
        expect(link.text()).toContain('Dashboard');
    });

    it('adds the active class when active prop is true', () => {
        const wrapper = mount(NavLink, { props: { path: '/home', name: 'Home', active: true } });
        expect(wrapper.find('a').classes()).toContain('active');
    });

    it('does not add the active class when active prop is false', () => {
        const wrapper = mount(NavLink, { props: { path: '/home', name: 'Home', active: false } });
        expect(wrapper.find('a').classes()).not.toContain('active');
    });

    it('renders an icon element when the icon prop is given', () => {
        const wrapper = mount(NavLink, { props: { path: '/settings', name: 'Settings', icon: 'bi-gear' } });
        expect(wrapper.find('i.bi.bi-gear').exists()).toBe(true);
    });

    it('does not render an icon element when the icon prop is omitted', () => {
        const wrapper = mount(NavLink, { props: { path: '/home', name: 'Home' } });
        expect(wrapper.find('i.bi').exists()).toBe(false);
    });

    it('renders a dropdown toggle and menu when dropdowns are provided', () => {
        const wrapper = mount(NavLink, {
            props: {
                path: '/admin',
                name: 'Admin',
                dropdowns: [
                    { path: '/admin/users', name: 'Users' },
                    { path: '/admin/roles', name: 'Roles' },
                ],
            },
        });
        expect(wrapper.find('.dropdown-toggle').exists()).toBe(true);
        expect(wrapper.find('.dropdown-menu').exists()).toBe(true);
        const items = wrapper.findAll('.dropdown-item');
        expect(items).toHaveLength(2);
        expect(items[0].attributes('href')).toBe('/admin/users');
        expect(items[1].text()).toContain('Roles');
    });

    it('marks a dropdown item as active when its active flag is set', () => {
        const wrapper = mount(NavLink, {
            props: {
                path: '/reports',
                name: 'Reports',
                dropdowns: [
                    { path: '/reports/monthly', name: 'Monthly', active: true },
                    { path: '/reports/yearly', name: 'Yearly', active: false },
                ],
            },
        });
        const items = wrapper.findAll('.dropdown-item');
        expect(items[0].classes()).toContain('active');
        expect(items[1].classes()).not.toContain('active');
    });

    it('renders the parent item with dropdown nav-item class when dropdowns are provided', () => {
        const wrapper = mount(NavLink, {
            props: {
                path: '/tools',
                name: 'Tools',
                dropdowns: [{ path: '/tools/lint', name: 'Lint' }],
            },
        });
        expect(wrapper.find('li.nav-item').classes()).toContain('dropdown');
    });

    it('renders an icon inside a dropdown toggle', () => {
        const wrapper = mount(NavLink, {
            props: {
                path: '/admin',
                name: 'Admin',
                icon: 'bi-shield',
                dropdowns: [{ path: '/admin/users', name: 'Users' }],
            },
        });
        expect(wrapper.find('.dropdown-toggle i.bi.bi-shield').exists()).toBe(true);
    });
});
