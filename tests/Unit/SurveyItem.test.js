import { afterEach, describe, expect, it, vi } from 'vitest';
import { createApp, nextTick } from 'vue';
import SurveyItem from '../../resources/js/components/survey/SurveyItem.vue';

const mounted = [];

const mountItem = (item, modelValue = null, onUpdate = vi.fn(), error = '') => {
    const root = document.createElement('div');
    document.body.appendChild(root);
    const app = createApp(SurveyItem, {
        item,
        modelValue,
        error,
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
        expect(input.getAttribute('aria-valuetext')).toContain('Not answered');
        expect(input.getAttribute('aria-describedby')).toContain('scale-description');
        expect(input.getAttribute('aria-describedby')).toContain('hint');
        expect(root.textContent).toContain('Not answered');
        expect(root.textContent).not.toContain('Selected:');
        expect(onUpdate).not.toHaveBeenCalled();

        input.value = '7';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        await nextTick();

        expect(onUpdate).toHaveBeenCalledWith(7);
        expect(root.textContent).toContain('Selected: 7');
        expect(input.getAttribute('aria-valuetext')).toBe('Selected 7');
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

    it('associates validation errors with the control that can resolve them', async () => {
        const { root } = mountItem({
            qid: 'OTHER_REASON',
            type: 'single_select_text',
            question: 'Choose a reason',
            options: [
                { value: 'standard', label: 'Standard' },
                {
                    value: 'other',
                    label: 'Other',
                    meta: { freetext_placeholder: 'Describe the reason' },
                },
            ],
        }, { selected: 'other', text: '' }, vi.fn(), 'Please provide the additional text.');

        await nextTick();

        const select = root.querySelector('select');
        const freeText = root.querySelector('input[type="text"]');
        const error = root.querySelector('[role="alert"]');

        expect(select.getAttribute('data-error-focus')).toBe('false');
        expect(freeText.getAttribute('data-error-focus')).toBe('true');
        expect(freeText.getAttribute('aria-invalid')).toBe('true');
        expect(freeText.getAttribute('aria-errormessage')).toBe(error.id);
        expect(freeText.getAttribute('aria-describedby')).toContain(error.id);
    });

    it('marks the first grouped choice as the keyboard error-recovery target', () => {
        const { root } = mountItem({
            qid: 'MULTI',
            type: 'multi_select',
            question: 'Choose one or more',
            options: [
                { value: 'one', label: 'One' },
                { value: 'two', label: 'Two' },
            ],
        }, [], vi.fn(), 'Please provide an answer.');

        const group = root.querySelector('[role="group"]');
        const choices = root.querySelectorAll('input[type="checkbox"]');
        const error = root.querySelector('[role="alert"]');

        expect(group.getAttribute('aria-invalid')).toBe('true');
        expect(group.getAttribute('aria-errormessage')).toBe(error.id);
        expect(choices[0].getAttribute('data-error-focus')).toBe('true');
        expect(choices[1].getAttribute('data-error-focus')).toBe('false');
    });
});
