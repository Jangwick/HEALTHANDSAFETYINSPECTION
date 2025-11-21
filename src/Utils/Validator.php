<?php

declare(strict_types=1);

namespace HealthSafety\Utils;

/**
 * Input Validator Utility
 * Health & Safety Inspections System
 */
class Validator
{
    private array $errors = [];
    
    /**
     * Validate required field
     */
    public function required(string $field, mixed $value, string $label = null): self
    {
        if (empty($value) && $value !== '0' && $value !== 0) {
            $this->errors[$field][] = ($label ?? $field) . ' is required';
        }
        
        return $this;
    }
    
    /**
     * Validate email format
     */
    public function email(string $field, string $value, string $label = null): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = ($label ?? $field) . ' must be a valid email address';
        }
        
        return $this;
    }
    
    /**
     * Validate minimum length
     */
    public function minLength(string $field, string $value, int $min, string $label = null): self
    {
        if (!empty($value) && mb_strlen($value) < $min) {
            $this->errors[$field][] = ($label ?? $field) . " must be at least $min characters";
        }
        
        return $this;
    }
    
    /**
     * Validate maximum length
     */
    public function maxLength(string $field, string $value, int $max, string $label = null): self
    {
        if (!empty($value) && mb_strlen($value) > $max) {
            $this->errors[$field][] = ($label ?? $field) . " must not exceed $max characters";
        }
        
        return $this;
    }
    
    /**
     * Validate numeric value
     */
    public function numeric(string $field, mixed $value, string $label = null): self
    {
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field][] = ($label ?? $field) . ' must be a number';
        }
        
        return $this;
    }
    
    /**
     * Validate integer value
     */
    public function integer(string $field, mixed $value, string $label = null): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = ($label ?? $field) . ' must be an integer';
        }
        
        return $this;
    }
    
    /**
     * Validate minimum value
     */
    public function min(string $field, mixed $value, float $min, string $label = null): self
    {
        if (!empty($value) && is_numeric($value) && $value < $min) {
            $this->errors[$field][] = ($label ?? $field) . " must be at least $min";
        }
        
        return $this;
    }
    
    /**
     * Validate maximum value
     */
    public function max(string $field, mixed $value, float $max, string $label = null): self
    {
        if (!empty($value) && is_numeric($value) && $value > $max) {
            $this->errors[$field][] = ($label ?? $field) . " must not exceed $max";
        }
        
        return $this;
    }
    
    /**
     * Validate value is in allowed list
     */
    public function in(string $field, mixed $value, array $allowed, string $label = null): self
    {
        if (!empty($value) && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = ($label ?? $field) . ' has an invalid value';
        }
        
        return $this;
    }
    
    /**
     * Validate date format
     */
    public function date(string $field, string $value, string $format = 'Y-m-d', string $label = null): self
    {
        if (!empty($value)) {
            $d = \DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $this->errors[$field][] = ($label ?? $field) . ' must be a valid date';
            }
        }
        
        return $this;
    }
    
    /**
     * Validate phone number (Philippine format)
     */
    public function phone(string $field, string $value, string $label = null): self
    {
        if (!empty($value)) {
            $pattern = '/^(09|\+639)\d{9}$/';
            if (!preg_match($pattern, str_replace([' ', '-', '(', ')'], '', $value))) {
                $this->errors[$field][] = ($label ?? $field) . ' must be a valid Philippine phone number';
            }
        }
        
        return $this;
    }
    
    /**
     * Validate URL format
     */
    public function url(string $field, string $value, string $label = null): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = ($label ?? $field) . ' must be a valid URL';
        }
        
        return $this;
    }
    
    /**
     * Validate password strength
     */
    public function password(string $field, string $value, string $label = null): self
    {
        if (!empty($value)) {
            $errors = [];
            
            if (mb_strlen($value) < PASSWORD_MIN_LENGTH) {
                $errors[] = "at least " . PASSWORD_MIN_LENGTH . " characters";
            }
            
            if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $value)) {
                $errors[] = "one uppercase letter";
            }
            
            if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $value)) {
                $errors[] = "one lowercase letter";
            }
            
            if (PASSWORD_REQUIRE_NUMBER && !preg_match('/\d/', $value)) {
                $errors[] = "one number";
            }
            
            if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $value)) {
                $errors[] = "one special character";
            }
            
            if (!empty($errors)) {
                $this->errors[$field][] = ($label ?? $field) . ' must contain ' . implode(', ', $errors);
            }
        }
        
        return $this;
    }
    
    /**
     * Validate file upload
     */
    public function file(string $field, array $file, array $allowedTypes = [], int $maxSize = MAX_FILE_SIZE, string $label = null): self
    {
        if (!empty($file['tmp_name'])) {
            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->errors[$field][] = ($label ?? $field) . ' upload failed';
                return $this;
            }
            
            // Check file size
            if ($file['size'] > $maxSize) {
                $this->errors[$field][] = ($label ?? $field) . ' must not exceed ' . ($maxSize / 1048576) . 'MB';
            }
            
            // Check MIME type
            if (!empty($allowedTypes)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedTypes, true)) {
                    $this->errors[$field][] = ($label ?? $field) . ' has invalid file type';
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Custom validation rule
     */
    public function custom(string $field, callable $callback, string $message): self
    {
        if (!$callback()) {
            $this->errors[$field][] = $message;
        }
        
        return $this;
    }
    
    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !$this->passes();
    }
    
    /**
     * Get all errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get first error message
     */
    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }
        
        $firstField = array_key_first($this->errors);
        return $this->errors[$firstField][0] ?? null;
    }
    
    /**
     * Clear all errors
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }
}
