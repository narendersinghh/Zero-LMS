<?php
namespace App\Core;

/**
 * Rule based validation. Rules: required, email, min:n, max:n, int, numeric, date, datetime, in:a,b,
 * url, color, confirmed, slug, unique:table,column[,ignoreId], exists:table,column
 */
class Validator {
    public static function make(array $data, array $rules, array $labels = []): array {
        $errors = [];
        foreach ($rules as $field => $ruleStr) {
            $value = $data[$field] ?? null;
            $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $list = is_array($ruleStr) ? $ruleStr : explode('|', $ruleStr);
            $str = is_string($value) ? trim($value) : $value;
            $empty = $str === null || $str === '' || $str === [];
            if (!in_array('required', $list, true) && $empty) {
                continue;}
            foreach ($list as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $err = self::check($name, $arg, $str, $data, $field, $label);
                if ($err) {
                    $errors[$field] = $err;
                    break;}}}
        return $errors;}

    private static function check(string $name, ?string $arg, $v, array $data, string $field, string $label): ?string {
        switch ($name) {
            case 'required':
                return ($v === null || $v === '' || $v === []) ? "$label is required." : null;
            case 'email':
                return filter_var($v, FILTER_VALIDATE_EMAIL) && strlen((string)$v) <= 190 ? null : "$label must be a valid email address.";
            case 'min':
                return (is_numeric($v) && !is_string($v)) || (is_string($v) && mb_strlen($v) >= (int)$arg) ? null : "$label must be at least $arg characters.";
            case 'max':
                return is_string($v) && mb_strlen($v) > (int)$arg ? "$label may not be longer than $arg characters." : null;
            case 'int':
                return filter_var($v, FILTER_VALIDATE_INT) !== false ? null : "$label must be a whole number.";
            case 'numeric':
                return is_numeric($v) ? null : "$label must be a number.";
            case 'gte':
                return is_numeric($v) && $v >= (float)$arg ? null : "$label must be at least $arg.";
            case 'date':
                return \DateTime::createFromFormat('Y-m-d', (string)$v) ? null : "$label must be a valid date.";
            case 'datetime':
                return strtotime((string)$v) !== false ? null : "$label must be a valid date/time.";
            case 'in':
                return in_array((string)$v, explode(',', (string)$arg), true) ? null : "$label is invalid.";
            case 'url':
                return filter_var($v, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', (string)$v) ? null : "$label must be a valid URL.";
            case 'color':
                return preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', (string)$v) ? null : "$label must be a hex color.";
            case 'slug':
                return preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', (string)$v) ? null : "$label may contain lowercase letters, numbers and dashes only.";
            case 'confirmed':
                return ($data[$field . '_confirmation'] ?? null) === $v ? null : "$label confirmation does not match.";
            case 'password':
                return mb_strlen((string)$v) >= 8 && preg_match('/[A-Za-z]/', (string)$v) && preg_match('/\d/', (string)$v) ? null : "$label must be at least 8 characters and include a letter and a number.";
            case 'unique':
                $a = explode(',', (string)$arg);
                $sql = 'SELECT COUNT(*) FROM ' . DB::table($a[0]) . ' WHERE `' . $a[1] . '` = ?' . (!empty($a[2]) ? ' AND id <> ?' : '');
                $p = !empty($a[2]) ? [$v, (int)$a[2]] : [$v];
                return (int)DB::val($sql, $p) > 0 ? "$label is already taken." : null;
            case 'exists':
                $a = explode(',', (string)$arg);
                return (int)DB::val('SELECT COUNT(*) FROM ' . DB::table($a[0]) . ' WHERE `' . $a[1] . '` = ?', [$v]) ? null : "$label does not exist.";}
        return null;}}
