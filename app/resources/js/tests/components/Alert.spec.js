import { mount } from '@vue/test-utils';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { Alert as BootstrapAlert } from 'bootstrap';
import Alert from '@/Components/Alert.vue';

describe('Alert', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders a string message as inline text', () => {
        const wrapper = mount(Alert, { props: { type: 'success', msgs: 'All done!' } });
        expect(wrapper.text()).toContain('All done!');
        expect(wrapper.find('ul').exists()).toBe(false);
    });

    it('renders a single-item array as inline text, not a list', () => {
        const wrapper = mount(Alert, { props: { type: 'info', msgs: ['Only one'] } });
        expect(wrapper.text()).toContain('Only one');
        expect(wrapper.find('ul').exists()).toBe(false);
    });

    it('renders a multi-item array as an unordered list', () => {
        const wrapper = mount(Alert, { props: { type: 'danger', msgs: ['First', 'Second', 'Third'] } });
        const items = wrapper.findAll('li');
        expect(items).toHaveLength(3);
        expect(items[0].text()).toBe('First');
        expect(items[2].text()).toBe('Third');
    });

    it('renders a single-key object as inline text', () => {
        const wrapper = mount(Alert, { props: { type: 'warning', msgs: { key: 'Single error' } } });
        expect(wrapper.text()).toContain('Single error');
        expect(wrapper.find('ul').exists()).toBe(false);
    });

    it('renders a multi-key object as an unordered list', () => {
        const wrapper = mount(Alert, {
            props: { type: 'danger', msgs: { name: 'Name required', email: 'Email invalid' } },
        });
        expect(wrapper.findAll('li')).toHaveLength(2);
    });

    it('applies the correct Bootstrap alert type class', () => {
        const wrapper = mount(Alert, { props: { type: 'warning', msgs: 'Watch out' } });
        expect(wrapper.find('.alert').classes()).toContain('alert-warning');
    });

    it('emits closed when the closed.bs.alert event fires on the element', async () => {
        const wrapper = mount(Alert, { props: { type: 'success', msgs: 'Done', timeout: 0 } });
        wrapper.find('.alert').element.dispatchEvent(new Event('closed.bs.alert'));
        await wrapper.vm.$nextTick();
        expect(wrapper.emitted('closed')).toHaveLength(1);
    });

    it('auto-closes after 10 s for non-danger/warning types', () => {
        vi.useFakeTimers();
        const closeMock = vi.fn();
        BootstrapAlert.getOrCreateInstance.mockReturnValueOnce({ close: closeMock });

        mount(Alert, { props: { type: 'success', msgs: 'Done' } });
        expect(closeMock).not.toHaveBeenCalled();
        vi.advanceTimersByTime(10000);
        expect(closeMock).toHaveBeenCalledOnce();
        vi.useRealTimers();
    });

    it('does not auto-close for the danger type', () => {
        vi.useFakeTimers();
        const closeMock = vi.fn();
        BootstrapAlert.getOrCreateInstance.mockReturnValueOnce({ close: closeMock });

        mount(Alert, { props: { type: 'danger', msgs: 'Error!' } });
        vi.advanceTimersByTime(60000);
        expect(closeMock).not.toHaveBeenCalled();
        vi.useRealTimers();
    });

    it('does not auto-close for the warning type', () => {
        vi.useFakeTimers();
        const closeMock = vi.fn();
        BootstrapAlert.getOrCreateInstance.mockReturnValueOnce({ close: closeMock });

        mount(Alert, { props: { type: 'warning', msgs: 'Watch out' } });
        vi.advanceTimersByTime(60000);
        expect(closeMock).not.toHaveBeenCalled();
        vi.useRealTimers();
    });

    it('respects an explicit timeout value', () => {
        vi.useFakeTimers();
        const closeMock = vi.fn();
        BootstrapAlert.getOrCreateInstance.mockReturnValueOnce({ close: closeMock });

        mount(Alert, { props: { type: 'danger', msgs: 'Error!', timeout: 3000 } });
        vi.advanceTimersByTime(2999);
        expect(closeMock).not.toHaveBeenCalled();
        vi.advanceTimersByTime(1);
        expect(closeMock).toHaveBeenCalledOnce();
        vi.useRealTimers();
    });
});
