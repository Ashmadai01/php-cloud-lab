<?php
declare(strict_types=1);
namespace SdutSwap\Core;

final class Validator
{
    private array $errors = [];

    public function required(string $field, ?string $value, int $min = 1, int $max = 10000): self
    {
        $len = mb_strlen(trim((string) $value));
        if ($len < $min) $this->errors[$field][] = "This field is required (min {$min} characters).";
        if ($len > $max) $this->errors[$field][] = "Must be at most {$max} characters.";
        return $this;
    }

    public function email(string $field, ?string $value): self
    {
        if (!filter_var(trim((string) $value), FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = 'Please enter a valid email address.';
        }
        return $this;
    }

    public function password(string $field, ?string $value): self
    {
        $v = (string) $value;
        if (mb_strlen($v) < 10) $this->errors[$field][] = 'Password must be at least 10 characters.';
        if (!preg_match('/[A-Z]/', $v) || !preg_match('/[a-z]/', $v) || !preg_match('/[0-9]/', $v)) {
            $this->errors[$field][] = 'Password must include uppercase, lowercase, and a number.';
        }
        $common = ['password123', 'qwerty12345', 'letmein1234', 'admin12345', 'changeme123', 'campusx1234'];
        if (in_array(strtolower($v), $common, true)) {
            $this->errors[$field][] = 'This password is too common. Please choose a stronger one.';
        }
        return $this;
    }

    public function inList(string $field, ?string $value, array $allowed): self
    {
        if (!in_array((string) $value, $allowed, true)) {
            $this->errors[$field][] = 'Invalid selection.';
        }
        return $this;
    }

    public function numericRange(string $field, ?string $value, float $min, float $max): self
    {
        if (!is_numeric($value) || (float) $value < $min || (float) $value > $max) {
            $this->errors[$field][] = "Must be between {$min} and {$max}.";
        }
        return $this;
    }

    public function regex(string $field, ?string $value, string $pattern, string $msg): self
    {
        if (!preg_match($pattern, (string) $value)) $this->errors[$field][] = $msg;
        return $this;
    }

    public function add(string $field, string $msg): self
    {
        $this->errors[$field][] = $msg;
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(string $field): string
    {
        return $this->errors[$field][0] ?? '';
    }
}
