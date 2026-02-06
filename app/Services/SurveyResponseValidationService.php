<?php

namespace App\Services;

use App\Models\SurveyAssignment;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class SurveyResponseValidationService
{
    /**
     * @throws ValidationException
     */
    public function validateAndSanitize(SurveyAssignment $assignment, array $responses): array
    {
        $definition = $this->definitionService->definitionForAssignment($assignment);
        $items = $this->flattenItems($definition['pages'] ?? []);
        $itemsByQid = $this->itemsByQid($items);
        $visibilityCache = [];

        $errors = [];
        $sanitized = [];

        foreach ($responses as $rawQid => $_) {
            $qid = (string) $rawQid;
            if (!array_key_exists($qid, $itemsByQid)) {
                $errors["responses.{$qid}"][] = 'Unknown question key.';
            }
        }

        foreach ($items as $item) {
            $qid = $item['qid'] ?? null;
            if (!$qid) {
                continue;
            }

            if (!$this->isVisible($item, $responses, $itemsByQid, $visibilityCache)) {
                continue;
            }

            $value = $responses[$qid] ?? null;
            if ($this->isEmptyValue($value)) {
                if ($this->isRequired($item)) {
                    $errors["responses.{$qid}"][] = 'Please provide an answer.';
                }

                continue;
            }

            [$cleanValue, $error] = $this->sanitizeValue($item, $value);
            if ($error !== null) {
                $errors["responses.{$qid}"][] = $error;
                continue;
            }

            $sanitized[$qid] = $cleanValue;
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $sanitized;
    }

    public function __construct(
        protected SurveyDefinitionService $definitionService
    ) {
    }

    protected function itemsByQid(array $items): array
    {
        $map = [];
        foreach ($items as $item) {
            $qid = $item['qid'] ?? null;
            if (!$qid) {
                continue;
            }

            $map[$qid] = $item;
        }

        return $map;
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

    protected function isVisible(
        array $item,
        array $responses,
        array $itemsByQid = [],
        array &$visibilityCache = [],
        array $stack = []
    ): bool
    {
        $itemQid = $item['qid'] ?? null;
        if ($itemQid && array_key_exists($itemQid, $visibilityCache)) {
            return $visibilityCache[$itemQid];
        }

        if ($itemQid && in_array($itemQid, $stack, true)) {
            return false;
        }

        if ($itemQid) {
            $stack[] = $itemQid;
        }

        $logic = $item['display_logic'] ?? null;
        if (!$logic || (is_array($logic) && empty($logic))) {
            if ($itemQid) {
                $visibilityCache[$itemQid] = true;
            }

            return true;
        }

        if (is_array($logic) && array_is_list($logic)) {
            $conditions = $logic;
            $operator = 'and';
        } else {
            $conditions = Arr::get($logic, 'when', []);
            $operator = strtolower((string) Arr::get($logic, 'operator', Arr::get($logic, 'mode', Arr::get($logic, 'combinator', 'and'))));
        }

        if (empty($conditions)) {
            if ($itemQid) {
                $visibilityCache[$itemQid] = true;
            }

            return true;
        }

        $matches = array_map(
            fn ($condition) => $this->conditionMatches($condition, $responses, $itemsByQid, $visibilityCache, $stack),
            $conditions
        );

        if (in_array($operator, ['or', 'any'], true)) {
            $visible = in_array(true, $matches, true);
            if ($itemQid) {
                $visibilityCache[$itemQid] = $visible;
            }

            return $visible;
        }

        $visible = !in_array(false, $matches, true);
        if ($itemQid) {
            $visibilityCache[$itemQid] = $visible;
        }

        return $visible;
    }

    protected function conditionMatches(
        array $condition,
        array $responses,
        array $itemsByQid,
        array &$visibilityCache,
        array $stack
    ): bool
    {
        $qid = Arr::get($condition, 'qid');
        if (!$qid) {
            return false;
        }

        if (!array_key_exists($qid, $itemsByQid)) {
            return false;
        }

        if (!$this->isVisible($itemsByQid[$qid], $responses, $itemsByQid, $visibilityCache, $stack)) {
            return false;
        }

        if (!array_key_exists($qid, $responses)) {
            return false;
        }

        $allowed = Arr::get($condition, 'equals_any', []);
        if (!is_array($allowed) || empty($allowed)) {
            return true;
        }

        $allowedValues = array_map([$this, 'normalizeComparable'], $allowed);
        $actualValues = $this->extractComparableValues($responses[$qid]);

        foreach ($actualValues as $actual) {
            if (in_array($this->normalizeComparable($actual), $allowedValues, true)) {
                return true;
            }
        }

        return false;
    }

    protected function extractComparableValues(mixed $value): array
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                return array_values($value);
            }

            $candidates = [];
            foreach (['selected', 'value', 'text'] as $key) {
                if (array_key_exists($key, $value)) {
                    $candidates[] = $value[$key];
                }
            }

            return !empty($candidates) ? $candidates : array_values($value);
        }

        return [$value];
    }

    protected function normalizeComparable(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    protected function isRequired(array $item): bool
    {
        $response = $this->responseConfig($item);
        if (array_key_exists('required', $response)) {
            return (bool) $response['required'];
        }

        if (($response['optional'] ?? false) || ($response['nullable'] ?? false)) {
            return false;
        }

        $requiredTypes = config('survey.validation.default_required_types', []);
        return in_array($item['type'] ?? '', $requiredTypes, true);
    }

    protected function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return count($value) === 0;
            }

            foreach ($value as $nestedValue) {
                if (!$this->isEmptyValue($nestedValue)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    protected function sanitizeValue(array $item, mixed $value): array
    {
        return match ($item['type'] ?? null) {
            'slider' => $this->sanitizeSlider($item, $value),
            'number_integer' => $this->sanitizeInteger($item, $value),
            'dropdown', 'single_select' => $this->sanitizeSingleSelect($item, $value),
            'single_select_text' => $this->sanitizeSingleSelectText($item, $value),
            'multi_select' => $this->sanitizeMultiSelect($item, $value),
            'text', 'text_short', 'text_long' => $this->sanitizeText($item, $value),
            default => [is_scalar($value) ? (string) $value : null, is_scalar($value) ? null : 'Invalid answer format.'],
        };
    }

    protected function sanitizeSlider(array $item, mixed $value): array
    {
        if (!is_numeric($value)) {
            return [null, 'Please provide a valid number.'];
        }

        $numeric = (float) $value;
        if (abs($numeric - round($numeric)) > 0.000001) {
            return [null, 'Please provide a whole number.'];
        }

        $scale = is_array($item['scale'] ?? null) ? $item['scale'] : [];
        $min = (float) ($scale['min'] ?? 1);
        $max = (float) ($scale['max'] ?? 5);
        $step = (float) ($scale['step'] ?? 1);

        if ($numeric < $min || $numeric > $max) {
            return [null, sprintf('Please select a value between %s and %s.', $min, $max)];
        }

        if ($step > 0) {
            $delta = ($numeric - $min) / $step;
            if (abs($delta - round($delta)) > 0.000001) {
                return [null, sprintf('Please select a value in increments of %s.', $step)];
            }
        }

        return [(int) round($numeric), null];
    }

    protected function sanitizeInteger(array $item, mixed $value): array
    {
        if (!is_numeric($value)) {
            return [null, 'Please provide a valid number.'];
        }

        $numeric = (float) $value;
        if (abs($numeric - round($numeric)) > 0.000001) {
            return [null, 'Please provide a whole number.'];
        }

        $response = $this->responseConfig($item);
        $min = array_key_exists('min', $response) ? (int) $response['min'] : null;
        $max = array_key_exists('max', $response) ? (int) $response['max'] : null;
        $intValue = (int) round($numeric);

        if ($min !== null && $intValue < $min) {
            return [null, sprintf('Please enter a value of at least %d.', $min)];
        }

        if ($max !== null && $intValue > $max) {
            return [null, sprintf('Please enter a value no greater than %d.', $max)];
        }

        return [$intValue, null];
    }

    protected function sanitizeSingleSelect(array $item, mixed $value): array
    {
        if (!is_scalar($value)) {
            return [null, 'Please select a valid option.'];
        }

        $selected = (string) $value;
        $options = $this->optionMap($item);
        if (!array_key_exists($selected, $options)) {
            return [null, 'Please select a valid option.'];
        }

        return [$selected, null];
    }

    protected function sanitizeSingleSelectText(array $item, mixed $value): array
    {
        $selected = null;
        $text = null;

        if (is_array($value)) {
            $selected = array_key_exists('selected', $value) ? $value['selected'] : ($value['value'] ?? null);
            $text = $value['text'] ?? null;
        } elseif (is_scalar($value)) {
            $selected = $value;
        }

        if (!is_scalar($selected)) {
            return [null, 'Please select a valid option.'];
        }

        $selectedValue = (string) $selected;
        $options = $this->optionMap($item);
        if (!array_key_exists($selectedValue, $options)) {
            return [null, 'Please select a valid option.'];
        }

        $selectedOption = $options[$selectedValue];
        $meta = is_array($selectedOption['meta'] ?? null) ? $selectedOption['meta'] : [];
        $requiresFreeText = array_key_exists('freetext_placeholder', $meta);

        if ($requiresFreeText) {
            if (!is_scalar($text) || trim((string) $text) === '') {
                return [null, 'Please provide details for the selected option.'];
            }

            [$cleanText, $error] = $this->sanitizeText($item, $text);
            if ($error !== null) {
                return [null, $error];
            }

            return [[
                'selected' => $selectedValue,
                'text' => $cleanText,
            ], null];
        }

        return [$selectedValue, null];
    }

    protected function sanitizeMultiSelect(array $item, mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            return [null, 'Please select valid options.'];
        }

        $options = $this->optionMap($item);
        $selected = [];

        foreach ($value as $candidate) {
            if (!is_scalar($candidate)) {
                return [null, 'Please select valid options.'];
            }

            $candidateValue = (string) $candidate;
            if (!array_key_exists($candidateValue, $options)) {
                return [null, 'Please select valid options.'];
            }

            if (!in_array($candidateValue, $selected, true)) {
                $selected[] = $candidateValue;
            }
        }

        $exclusive = array_keys(array_filter($options, fn ($option) => (bool) ($option['exclusive'] ?? false)));
        $selectedExclusive = array_values(array_intersect($selected, $exclusive));

        if (!empty($selectedExclusive)) {
            $selected = [$selectedExclusive[0]];
        }

        return [$selected, null];
    }

    protected function sanitizeText(array $item, mixed $value): array
    {
        if (!is_scalar($value)) {
            return [null, 'Please provide a valid text response.'];
        }

        $text = (string) $value;
        $response = $this->responseConfig($item);
        $formatHint = $response['format_hint'] ?? null;

        if ($formatHint === 'email') {
            $text = trim($text);
        }

        $maxLength = $response['max_length'] ?? config('survey.validation.default_max_text_length');
        if (is_numeric($maxLength) && $maxLength > 0 && mb_strlen($text) > (int) $maxLength) {
            return [null, sprintf('Please limit your answer to %d characters.', (int) $maxLength)];
        }

        if ($formatHint === 'email' && !filter_var($text, FILTER_VALIDATE_EMAIL)) {
            return [null, 'Please provide a valid email address.'];
        }

        return [$text, null];
    }

    protected function optionMap(array $item): array
    {
        $map = [];
        foreach (($item['options'] ?? []) as $option) {
            if (!array_key_exists('value', $option)) {
                continue;
            }

            $value = (string) $option['value'];
            $map[$value] = [
                'exclusive' => (bool) ($option['exclusive'] ?? false),
                'meta' => is_array($option['meta'] ?? null) ? $option['meta'] : [],
            ];
        }

        return $map;
    }

    protected function responseConfig(array $item): array
    {
        $response = $item['response'] ?? [];
        return is_array($response) ? $response : [];
    }
}
