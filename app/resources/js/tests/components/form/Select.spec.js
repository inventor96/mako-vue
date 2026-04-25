import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Select from '@/Components/Form/Select.vue';

describe('Select', () => {
    const options = { apple: 'Apple', banana: 'Banana', cherry: 'Cherry' };

    it('renders options from the options prop', () => {
        const wrapper = mount(Select, { props: { id: 'fruit', options } });
        const opts = wrapper.findAll('option:not([disabled])');
        expect(opts).toHaveLength(3);
        expect(opts[0].text()).toBe('Apple');
        expect(opts[2].text()).toBe('Cherry');
    });

    it('emits update:modelValue when the selection changes', async () => {
        const wrapper = mount(Select, { props: { id: 'fruit', options } });
        await wrapper.find('select').setValue('banana');
        expect(wrapper.emitted('update:modelValue')).toEqual([['banana']]);
    });

    it('shows is-invalid class and error message when error prop is set', () => {
        const wrapper = mount(Select, { props: { id: 'fruit', options, error: 'Selection required' } });
        expect(wrapper.find('select').classes()).toContain('is-invalid');
        expect(wrapper.find('.invalid-feedback').text()).toBe('Selection required');
    });

    it('renders a disabled placeholder option when placeholder prop is set', () => {
        const wrapper = mount(Select, {
            props: { id: 'fruit', options, placeholder: 'Pick one...' },
        });
        const placeholder = wrapper.find('option[disabled]');
        expect(placeholder.exists()).toBe(true);
        expect(placeholder.text()).toBe('Pick one...');
    });

    it('does not render a placeholder option when placeholder prop is omitted', () => {
        const wrapper = mount(Select, { props: { id: 'fruit', options } });
        expect(wrapper.find('option[disabled]').exists()).toBe(false);
    });

    it('renders before and after slots', () => {
        const wrapper = mount(Select, {
            props: { id: 'color', options: {} },
            slots: {
                before: '<p class="before-slot">Before</p>',
                after: '<p class="after-slot">After</p>',
            },
        });
        expect(wrapper.find('.before-slot').exists()).toBe(true);
        expect(wrapper.find('.after-slot').exists()).toBe(true);
    });

    it('renders a label element linked to the select', () => {
        const wrapper = mount(Select, { props: { id: 'fruit', options, label: 'Choose fruit' } });
        const label = wrapper.find('label');
        expect(label.attributes('for')).toBe('fruit');
        expect(label.text()).toBe('Choose fruit');
    });
});
