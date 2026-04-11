import { mount } from '@vue/test-utils';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import Modal from '@/Components/Modal.vue';

describe('Modal', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('applies the given id to the modal root element', () => {
        const wrapper = mount(Modal, { props: { id: 'my-modal' } });
        expect(wrapper.find('#my-modal').exists()).toBe(true);
    });

    it('renders the title in h5 when the title prop is provided', () => {
        const wrapper = mount(Modal, { props: { id: 'test-modal', title: 'Confirm Action' } });
        expect(wrapper.find('.modal-title').text()).toBe('Confirm Action');
    });

    it('omits the h5 title element when no title prop is given', () => {
        const wrapper = mount(Modal, { props: { id: 'test-modal' } });
        expect(wrapper.find('.modal-title').exists()).toBe(false);
    });

    it('renders the confirm button with custom confirmText', () => {
        const wrapper = mount(Modal, { props: { id: 'test-modal', confirmText: 'Yes, proceed' } });
        const footerButtons = wrapper.findAll('.modal-footer button');
        expect(footerButtons.some(b => b.text() === 'Yes, proceed')).toBe(true);
    });

    it('omits the confirm button when confirmText is empty', () => {
        const wrapper = mount(Modal, { props: { id: 'test-modal', confirmText: '' } });
        // Only the close button should remain
        const primaryBtns = wrapper.findAll('.modal-footer .btn-primary');
        expect(primaryBtns).toHaveLength(0);
    });

    it('renders the close button with custom closeText', () => {
        const wrapper = mount(Modal, { props: { id: 'test-modal', closeText: 'Cancel' } });
        const footerButtons = wrapper.findAll('.modal-footer button');
        expect(footerButtons.some(b => b.text() === 'Cancel')).toBe(true);
    });

    it('omits the close button when closeText is empty', () => {
        const wrapper = mount(Modal, { props: { id: 'test-modal', closeText: '' } });
        const secondaryBtns = wrapper.findAll('.modal-footer .btn-secondary');
        expect(secondaryBtns).toHaveLength(0);
    });

    it('renders default slot content inside the modal body', () => {
        const wrapper = mount(Modal, {
            props: { id: 'test-modal' },
            slots: { default: '<p class="body-content">Modal body text</p>' },
        });
        expect(wrapper.find('.modal-body .body-content').exists()).toBe(true);
    });

    it('renders the header slot inside the modal header', () => {
        const wrapper = mount(Modal, {
            props: { id: 'test-modal' },
            slots: { header: '<span class="custom-header">Custom Header</span>' },
        });
        expect(wrapper.find('.modal-header .custom-header').exists()).toBe(true);
    });

    it('renders the footer slot inside the modal footer', () => {
        const wrapper = mount(Modal, {
            props: { id: 'test-modal' },
            slots: { footer: '<button class="custom-action">Save</button>' },
        });
        expect(wrapper.find('.modal-footer .custom-action').exists()).toBe(true);
    });

    it('exposes show, hide, and toggle methods', () => {
        const wrapper = mount(Modal, { props: { id: 'test-modal' } });
        expect(typeof wrapper.vm.show).toBe('function');
        expect(typeof wrapper.vm.hide).toBe('function');
        expect(typeof wrapper.vm.toggle).toBe('function');
    });

    it('calling exposed show, hide, and toggle does not throw', () => {
        const wrapper = mount(Modal, { props: { id: 'test-modal' } });
        expect(() => wrapper.vm.show()).not.toThrow();
        expect(() => wrapper.vm.hide()).not.toThrow();
        expect(() => wrapper.vm.toggle()).not.toThrow();
    });
});
