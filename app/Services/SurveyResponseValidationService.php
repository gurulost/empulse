<?php

namespace App\Services;

use App\Models\SurveyAssignment;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class SurveyResponseValidationService
{
    protected array $requiredByDefaultTypes = [
        'slider',
        'single_select',
        'single_select_text',
        'dropdown',
        'multi_select',
        'number_integer',
    ];

    public function __construct(
        protected SurveyDefinitionService $definitionService
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function validateAndSanitize(SurveyAssignment $assignment, array $responses): array
    {
        $definition = $this->definitionService->definitionForAssignment($assignment);
        $items = $this->flattenItems($definition['pages'] ?? []);
        $validQids = collect($items)
            ->pluck('qid')
            ->filter(fn ($qid) => is_string($qid) && $qid !== '')
            ->values()
            ->all();

        $errors = [];
        $sanitized = [];

        foreach (array_keys($responses) as $qid) {
            $qid = (string) $qid;
            if (!in_array($qid, $validQids, true)) {
                $errors["responses.{$qid}"][] = 'Unknown question key.';
            }
        }

        foreach ($items as $item) {
            $qid = $item['qid'] ?? null;
            if (!$qid) {
                continue;
            }

            if (!$this->isItemVisible($item, $responses)) {
                continue;
            }

            $rawValue = $responses[$qid] ?? null;
            [$value, $error] = $this->validateItemValue($item, $rawValue);

            if ($error) {
                $errors["responses.{$qid}"][] = $error;
                continue;
            }

            if ($this->isEmptyValue($value)) {
                continue;
            }

            $sanitized[$qid] = $value;
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $sanitized;
    }

    protected function flattenItems(array $pages): array
    {
        $items = [];

        foreach ($pages as $page) {
            foreach (($page['items'] ?? []) as $item) {
                $items[] = $item;
            }

            foreach (($page['sections'] ?? []) as $section) {
                foreach (($section['items'] ?? []) as $item) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    protected function isItemVisible(array $item, array $responses): bool
    {
        $logic = $item['display_logic'] ?? null;
        if (!$logic || (is_array($logic) && empty($logic))) {
            return true;
        }

        $conditions = is_array($logic) && array_is_list($logic)
            ? $logic
            : ($logic['when'] ?? []);

        if (empty($conditions)) {
            return true;
        }

        $operator = strtolower((string) ($logic['operator'] ?? $logic['combinator'] ?? 'and'));
        $evaluator = fn (array $condition): bool => $this->conditionMatches($condition, $responses);

        if (in_array($operator, ['or', 'any'], true)) {
            foreach ($conditions as $condition) {
                if ($evaluator($condition)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($conditions as $condition) {
            if (!$evaluator($condition)) {
                return false;
            }
        }

        return true;
    }

    protected function conditionMatches(array $condition, array $responses): bool
    {
        $qid = $condition['qid'] ?? null;
        if (!$qid) {
            return true;
        }

        $currentValue = $responses[$qid] ?? null;
        if ($this->isEmptyValue($currentValue)) {
            return false;
        }

        $equalsAny = $condition['equals_any'] ?? null;
        if (is_array($equalsAny) && !empty($equalsAny)) {
            return $this->valueMatchesAny($currentValue, $equalsAny);
        }

        if (array_key_exists('equals', $condition)) {
            return $this->valueMatchesAny($currentValue, [(string) $condition['equals']]);
        }

        return true;
    }

    protected function valueMatchesAny($value, array $candidates): bool
    {
        $candidateSet = array_map('strval', $candidates);

        if (is_array($value)) {
            if (array_is_list($value)) {
                foreach ($value as $entry) {
                    if (in_array((string) $entry, $candidateSet, true)) {
                        return true;
                    }
                }

                return false;
            }

            $selected = $value['selected'] ?? $value['value'] ?? $value['text'] ?? null;
            return $selected !== null && in_array((string) $selected, $candidateSet, true);
        }

        return in_array((string) $value, $candidateSet, true);
    }

    protected function validateItemValue(array $item, $rawValue): array
    {
        $type = (string) ($item['type'] ?? '');
        $required = $this->isRequired($item);

        if ($required && $this->isEmptyValue($rawValue)) {
            return [null, 'Please provide an answer.'];
        }

        if ($this->isEmptyValue($rawValue)) {
            return [null, null];
        }

        return match ($type) {
            'slider' => $this->validateSlider($item, $rawValue),
            'single_select', 'dropdown' => $this->validateSingleSelect($item, $rawValue),
            'single_select_text' => $this->validateSingleSelectText($item, $rawValue),
            'multi_select' => $this->validateMultiSelect($item, $rawValue),
            'number_integer' => $this->validateInteger($item, $rawValue),
            'text_short', 'text', 'text_long' => $this->validateText($item, $rawValue),
            default => [$rawValue, null],
        };
    }

    protected function validateSlider(array $item, $rawValue): array
    {
        if (!is_numeric($rawValue)) {
            return [null, 'Value must be a whole number within range.'];
        }

        $scale = $item['scale'] ?? [];
        $min = (int) ($scale['min'] ?? 1);
        $max = (int) ($scale['max'] ?? 5);
        $step = (float) ($scale['step'] ?? 1);

        $value = (float) $rawValue;
        if (floor($value) !== $value) {
            return [null, 'Value must be a whole number within range.'];
        }

        if ($value < $min || $value > $max) {
            return [null, "Value must be between {$min} and {$max}."];
        }

        if ($step > 0) {
            $offset = ($value - $min) / $step;
            if (abs($offset - round($offset)) > 1e-9) {
                return [null, 'Value is not on a valid step.'];
            }
        }

        return [(int) $value, null];
    }

    protected function validateSingleSelect(array $item, $rawValue): array
    {
        $allowed = $this->allowedOptionValues($item);
        $value = (string) $rawValue;

        if (!in_array($value, $allowed, true)) {
            return [null, 'Selected option is invalid.'];
        }

        return [$value, null];
    }

    protected function validateSingleSelectText(array $item, $rawValue): array
    {
        $optionsByValue = collect($item['options'] ?? [])->keyBy(fn ($option) => (string) ($option['value'] ?? ''));

        $selected = is_array($rawValue) ? ($rawValue['selected'] ?? null) : $rawValue;
        if ($selected === null || $selected === '') {
            return [null, 'Please provide an answer.'];
        }

        $selected = (string) $selected;
        if (!$optionsByValue->has($selected)) {
            return [null, 'Selected option is invalid.'];
        }

        $selectedOption = $optionsByValue->get($selected);
        $meta = Arr::get($selectedOption, 'meta', []);
        $expectsText = is_array($meta) && array_key_exists('freetext_placeholder', $meta);

        if (!$expectsText) {
            return [$selected, null];
        }

        $text = is_array($rawValue) ? trim((string) ($rawValue['text'] ?? '')) : '';
        if ($text === '') {
            return [null, 'Please provide the additional text.'];
        }

        return [[
            'selected' => $selected,
            'text' => $text,
        ], null];
    }

    protected function validateMultiSelect(array $item, $rawValue): array
    {
        if (!is_array($rawValue) || !array_is_list($rawValue)) {
            return [null, 'Please select one or more valid options.'];
        }

        $selected = array_values(array_unique(array_map('strval', $rawValue)));
        $allowed = $this->allowedOptionValues($item);
        foreach ($selected as $value) {
            if (!in_array($value, $allowed, true)) {
                return [null, 'Please select one or more valid options.'];
            }
        }

        $exclusiveValues = collect($item['options'] ?? [])
            ->filter(fn ($option) => (bool) ($option['exclusive'] ?? false))
            ->map(fn ($option) => (string) ($option['value'] ?? ''))
            ->filter()
            ->values()
            ->all();

        $selectedExclusive = array_values(array_intersect($selected, $exclusiveValues));
        if (count($selectedExclusive) > 1 || (count($selectedExclusive) === 1 && count($selected) > 1)) {
            return [null, 'Exclusive option cannot be combined with other selections.'];
        }

        return [$selected, null];
    }

    protected function validateInteger(array $item, $rawValue): array
    {
        if (!is_numeric($rawValue)) {
            return [null, 'Value must be a whole number.'];
        }

        $value = (float) $rawValue;
        if (floor($value) !== $value) {
            return [null, 'Value must be a whole number.'];
        }

        $min = Arr::get($item, 'response.min');
        if (is_numeric($min) && $value < (int) $min) {
            return [null, 'Value is below the allowed minimum.'];
        }

        return [(int) $value, null];
    }

    protected function validateText(array $item, $rawValue): array
    {
        $value = trim((string) $rawValue);
        $formatHint = Arr::get($item, 'response.format_hint');

        if ($formatHint === 'email' && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return [null, 'Please enter a valid email address.'];
        }

        $maxLength = Arr::get($item, 'response.max_length');
        if (is_numeric($maxLength) && mb_strlen($value) > (int) $maxLength) {
            return [null, "Answer exceeds max length of {$maxLength} characters."];
        }

        return [$value, null];
    }

    protected function allowedOptionValues(array $item): array
    {
        return collect($item['options'] ?? [])
            ->map(fn ($option) => (string) ($option['value'] ?? ''))
            ->filter()
            ->values()
            ->all();
    }

    protected function isRequired(array $item): bool
    {
        $required = Arr::get($item, 'response.required');
        if ($required === true) {
            return true;
        }

        if ($required === false) {
            return false;
        }

        return in_array((string) ($item['type'] ?? ''), $this->requiredByDefaultTypes, true);
    }

    protected function isEmptyValue($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return count($value) === 0;
            }

            foreach ($value as $entry) {
                if ($entry !== null && $entry !== '') {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
