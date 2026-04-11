import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Input from '@/Components/Form/Input.vue';

describe('Input', () => {
    it('emits update:modelValue when typing', async () => {
        const wrapper = mount(Input, {
            props: {
                id: 'email',
                label: 'Email',
                modelValue: '',
            },
        });

        await wrapper.find('input').setValue('new@example.com');

        expect(wrapper.emitted('update:modelValue')).toEqual([['new@example.com']]);
    });

    it('shows validation error state and message', () => {
        const wrapper = mount(Input, {
            props: {
                id: 'password',
                label: 'Password',
                error: 'Password is required',
            },
        });

        expect(wrapper.find('input').classes()).toContain('is-invalid');
        expect(wrapper.find('.invalid-feedback').text()).toBe('Password is required');
    });

    it('renders before and after slots', () => {
        const wrapper = mount(Input, {
            props: {
                id: 'username',
                label: 'Username',
            },
            slots: {
                before: '<p class="before-slot">Before content</p>',
                after: '<p class="after-slot">After content</p>',
            },
        });

        expect(wrapper.find('.before-slot').exists()).toBe(true);
        expect(wrapper.find('.after-slot').exists()).toBe(true);
    });
});
