import { afterEach, describe, expect, it, vi } from 'vitest';
import { createApp, nextTick } from 'vue';
import SurveyItem from '../../resources/js/components/survey/SurveyItem.vue';

const mounted = [];

const mountItem = (item, modelValue = null, onUpdate = vi.fn()) => {
    const root = document.createElement('div');
    document.body.appendChild(root);
    const app = createApp(SurveyItem, {
        item,
        modelValue,
        'onUpdate:modelValue': onUpdate,
    });
    app.mount(root);
    mounted.push({ app, root });

    return { root, onUpdate };
};

afterEach(() => {
    while (mounted.length) {
        const { app, root } = mounted.pop();
        app.unmount();
        root.remove();
    }
});

describe('SurveyItem respondent contracts', () => {
    it('does not present the visual slider midpoint as an answer until the respondent acts', async () => {
        const { root, onUpdate } = mountItem({
            qid: 'WCA_REL_A',
            type: 'slider',
            question: 'How much relationship-building do you experience?',
            scale: { min: 0, max: 10, step: 1 },
        });

        const input = root.querySelector('input[type="range"]');
        expect(input.value).toBe('5');
        expect(root.textContent).toContain('Not answered');
        expect(root.textContent).not.toContain('Selected:');
        expect(onUpdate).not.toHaveBeenCalled();

        input.value = '7';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        await nextTick();

        expect(onUpdate).toHaveBeenCalledWith(7);
        expect(root.textContent).toContain('Selected: 7');
    });

    it('enforces exclusive multi-select choices in the emitted answer', async () => {
        const onUpdate = vi.fn();
        const { root } = mountItem({
            qid: 'CHOICE',
            type: 'multi_select',
            question: 'Choose applicable options',
            options: [
                { value: 'one', label: 'One' },
                { value: 'none', label: 'None', exclusive: true },
            ],
        }, ['one'], onUpdate);

        root.querySelector('#CHOICE-none').dispatchEvent(new Event('change', { bubbles: true }));
        await nextTick();

        expect(onUpdate).toHaveBeenCalledWith(['none']);
    });
});
