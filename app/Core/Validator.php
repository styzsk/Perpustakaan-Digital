<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validator sederhana untuk validasi input di sisi server.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    private function value(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    public function required(string $field, string $label): self
    {
        $value = $this->value($field);
        if (!is_string($value) && !is_numeric($value)) {
            $this->errors[$field] = $label . ' wajib diisi.';
            return $this;
        }
        if (trim((string) $value) === '') {
            $this->errors[$field] = $label . ' wajib diisi.';
        }
        return $this;
    }

    public function maxLength(string $field, string $label, int $max): self
    {
        $value = $this->value($field);
        if (is_string($value) && mb_strlen(trim($value)) > $max) {
            $this->errors[$field] = $label . ' maksimal ' . $max . ' karakter.';
        }
        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = trim((string) $this->value($field));
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field] = $label . ' harus berupa alamat email yang valid.';
        }
        return $this;
    }

    public function integer(string $field, string $label, int $min, int $max): self
    {
        $value = $this->value($field);
        if (!is_numeric($value) || (string) (int) $value !== trim((string) $value)) {
            $this->errors[$field] = $label . ' harus berupa angka bulat.';
            return $this;
        }
        $number = (int) $value;
        if ($number < $min || $number > $max) {
            $this->errors[$field] = sprintf('%s harus di antara %d dan %d.', $label, $min, $max);
        }
        return $this;
    }

    public function date(string $field, string $label): self
    {
        $value = trim((string) $this->value($field));
        if ($value === '') {
            return $this;
        }
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (!$parsed || $parsed->format('Y-m-d') !== $value) {
            $this->errors[$field] = $label . ' harus berupa tanggal yang valid.';
        }
        return $this;
    }

    public function inList(string $field, string $label, array $allowed): self
    {
        $value = (string) $this->value($field);
        if (!in_array($value, $allowed, true)) {
            $this->errors[$field] = $label . ' tidak valid.';
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return $this->errors === [] ? '' : (string) reset($this->errors);
    }
}
