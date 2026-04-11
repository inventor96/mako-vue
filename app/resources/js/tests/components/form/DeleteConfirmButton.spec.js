import { mount } from '@vue/test-utils';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import DeleteConfirmButton from '@/Components/Form/DeleteConfirmButton.vue';

describe('DeleteConfirmButton', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders a trigger button that targets the correct modal id', () => {
        const wrapper = mount(DeleteConfirmButton, { props: { id: 'user-42' } });
        const trigger = wrapper.find('button[data-bs-toggle="modal"]');
        expect(trigger.exists()).toBe(true);
        expect(trigger.attributes('data-bs-target')).toBe('#delete-modal-user-42');
    });

    it('applies btn-danger class to the trigger button by default', () => {
        const wrapper = mount(DeleteConfirmButton, { props: { id: 'item-1' } });
        expect(wrapper.find('button[data-bs-toggle="modal"]').classes()).toContain('btn-danger');
    });

    it('applies a custom buttonClass to the trigger button', () => {
        const wrapper = mount(DeleteConfirmButton, {
            props: { id: 'item-1', buttonClass: 'btn-warning' },
        });
        expect(wrapper.find('button[data-bs-toggle="modal"]').classes()).toContain('btn-warning');
    });

    it('shows the itemText confirmation message in the modal body', () => {
        const wrapper = mount(DeleteConfirmButton, {
            props: { id: 'doc-1', itemText: 'this document' },
        });
        expect(wrapper.text()).toContain('Are you sure you want to delete this document?');
    });

    it('renders custom buttonText in the trigger button', () => {
        const wrapper = mount(DeleteConfirmButton, { props: { id: 'row-1', buttonText: 'Remove' } });
        expect(wrapper.find('button[data-bs-toggle="modal"]').text()).toContain('Remove');
    });

    it('disables the submit button when processing is true', () => {
        const wrapper = mount(DeleteConfirmButton, { props: { id: 'entry-1', processing: true } });
        expect(wrapper.find('button[type="submit"]').element.disabled).toBe(true);
    });

    it('does not disable the submit button when processing is false', () => {
        const wrapper = mount(DeleteConfirmButton, { props: { id: 'entry-1', processing: false } });
        expect(wrapper.find('button[type="submit"]').element.disabled).toBe(false);
    });

    it('shows the spinner when processing is true', () => {
        const wrapper = mount(DeleteConfirmButton, { props: { id: 'entry-1', processing: true } });
        expect(wrapper.find('.spinner-border').exists()).toBe(true);
    });

    it('hides the spinner when processing is false', () => {
        const wrapper = mount(DeleteConfirmButton, { props: { id: 'entry-1', processing: false } });
        expect(wrapper.find('.spinner-border').exists()).toBe(false);
    });
});
