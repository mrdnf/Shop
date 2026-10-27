<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Rules: required, nullable, email, numeric, integer, min:n, max:n, in:a,b,
 * confirmed, slug, url, color, date.
 */
final class Validator
{
    public static function check(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $rulesList = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $empty = $value === null || $value === '' || $value === [];
            $label = __(ucfirst(str_replace('_', ' ', $field)));

            if ($empty) {
                if (in_array('required', $rulesList, true)) {
                    $errors[$field] = __(':field is required.', ['field' => $label]);
                }
                continue;
            }

            foreach ($rulesList as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $numeric = in_array('numeric', $rulesList, true) || in_array('integer', $rulesList, true);
                $error = match ($name) {
                    'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : __(':field must be a valid email.', ['field' => $label]),
                    'numeric' => is_numeric($value) ? null : __(':field must be a number.', ['field' => $label]),
                    'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : __(':field must be a whole number.', ['field' => $label]),
                    'min' => ($numeric ? (float) $value >= (float) $arg : mb_strlen((string) $value) >= (int) $arg)
                        ? null : __(':field must be at least :min.', ['field' => $label, 'min' => $arg]),
                    'max' => ($numeric ? (float) $value <= (float) $arg : mb_strlen((string) $value) <= (int) $arg)
                        ? null : __(':field may not be greater than :max.', ['field' => $label, 'max' => $arg]),
                    'in' => in_array((string) $value, explode(',', (string) $arg), true) ? null : __(':field is invalid.', ['field' => $label]),
                    'confirmed' => $value === Request::input($field . '_confirmation') ? null : __(':field confirmation does not match.', ['field' => $label]),
                    'slug' => preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value) ? null : __(':field may only contain lowercase letters, numbers and dashes.', ['field' => $label]),
                    'url' => filter_var($value, FILTER_VALIDATE_URL) ? null : __(':field must be a valid URL.', ['field' => $label]),
                    'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $value) ? null : __(':field must be a hex color.', ['field' => $label]),
                    'date' => strtotime((string) $value) !== false ? null : __(':field is invalid.', ['field' => $label]),
                    default => null,
                };
                if ($error) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }
        return $errors;
    }
}
