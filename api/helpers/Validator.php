<?php
/**
 * Input Validation Helper
 */
class Validator {
    private array $errors = [];
    private array $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public static function make(array $data): self {
        return new self($data);
    }

    public function required(string $field, string $label = ''): self {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (!isset($this->data[$field]) || trim((string)$this->data[$field]) === '') {
            $this->errors[$field] = "{$label} is required.";
        }
        return $this;
    }

    public function email(string $field, string $label = 'Email'): self {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} must be a valid email address.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label = ''): self {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = "{$label} must be at least {$min} characters.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label = ''): self {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = "{$label} must be no more than {$max} characters.";
        }
        return $this;
    }

    public function matches(string $field, string $matchField, string $label = '', string $matchLabel = ''): self {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $matchLabel = $matchLabel ?: ucfirst(str_replace('_', ' ', $matchField));
        if (isset($this->data[$field], $this->data[$matchField]) && $this->data[$field] !== $this->data[$matchField]) {
            $this->errors[$field] = "{$label} must match {$matchLabel}.";
        }
        return $this;
    }

    public function phone(string $field, string $label = 'Phone'): self {
        if (isset($this->data[$field]) && !preg_match('/^\+?[\d\s\-\(\)]{7,20}$/', $this->data[$field])) {
            $this->errors[$field] = "{$label} must be a valid phone number.";
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $label = ''): self {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowed, true)) {
            $this->errors[$field] = "{$label} must be one of: " . implode(', ', $allowed) . ".";
        }
        return $this;
    }

    public function numeric(string $field, string $label = ''): self {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = "{$label} must be a number.";
        }
        return $this;
    }

    public function fails(): bool {
        return !empty($this->errors);
    }

    public function passes(): bool {
        return empty($this->errors);
    }

    public function errors(): array {
        return $this->errors;
    }

    public function get(string $field, mixed $default = null): mixed {
        return $this->data[$field] ?? $default;
    }

    public function only(array $fields): array {
        return array_intersect_key($this->data, array_flip($fields));
    }

    public function sanitized(string $field): string {
        return htmlspecialchars(trim((string)($this->data[$field] ?? '')), ENT_QUOTES, 'UTF-8');
    }
}
