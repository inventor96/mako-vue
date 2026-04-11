import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Switch from '@/Components/Form/Switch.vue';

describe('Switch', () => {
    it('emits boolean updates in default mode', async () => {
        const wrapper = mount(Switch, {
            props: {
                id: 'remember',
                label: 'Remember me',
                modelValue: false,
            },
        });

        const checkbox = wrapper.find('input[type="checkbox"]');
        await checkbox.setValue(true);

        expect(wrapper.emitted('update:modelValue')[0]).toEqual([true]);

        await checkbox.setValue(false);
        expect(wrapper.emitted('update:modelValue')[1]).toEqual([false]);
    });

    it('renders hidden input for boolean mode', () => {
        const wrapper = mount(Switch, {
            props: {
                id: 'tos',
                label: 'Terms',
                modelValue: true,
            },
        });

        expect(wrapper.find('input[type="hidden"]').exists()).toBe(true);
    });

    it('uses array membership mode when modelValue is an array', async () => {
        const wrapper = mount(Switch, {
            props: {
                id: 'roles_admin',
                label: 'Admin',
                value: 'admin',
                modelValue: ['editor'],
            },
        });

        const checkbox = wrapper.find('input[type="checkbox"]');
        expect(checkbox.element.checked).toBe(false);

        await checkbox.setValue(true);
        expect(wrapper.emitted('update:modelValue')[0]).toEqual([['editor', 'admin']]);

        await wrapper.setProps({ modelValue: ['editor', 'admin'] });
        await checkbox.setValue(false);
        expect(wrapper.emitted('update:modelValue')[1]).toEqual([['editor']]);
    });
});
