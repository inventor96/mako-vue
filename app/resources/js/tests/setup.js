import { defineComponent, h } from 'vue';
import { vi } from 'vitest';

// Stub Inertia components that require a real Inertia page context
vi.mock('@inertiajs/vue3', () => ({
    Link: defineComponent({
        name: 'InertiaLinkStub',
        props: { href: { type: String, default: '#' } },
        setup(props, { slots, attrs }) {
            return () => h('a', { href: props.href, ...attrs }, slots.default ? slots.default() : []);
        },
    }),
    Head: defineComponent({
        name: 'InertiaHeadStub',
        props: { title: { type: String } },
        setup(props) {
            return () => h('span', { 'data-testid': 'head-title' }, props.title ?? '');
        },
    }),
}));

// Stub Bootstrap JS classes to avoid DOM exceptions in jsdom
vi.mock('bootstrap', () => ({
    Alert: {
        getOrCreateInstance: vi.fn(() => ({ close: vi.fn() })),
    },
    Modal: vi.fn(function () {
        return {
            show: vi.fn(),
            hide: vi.fn(),
            toggle: vi.fn(),
            dispose: vi.fn(),
        };
    }),
}));
