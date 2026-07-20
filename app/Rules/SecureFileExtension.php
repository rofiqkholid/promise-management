<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SecureFileExtension implements ValidationRule
{
    protected array $allowedExtensions;

    // Global Whitelist Presets
    protected static array $presets = [
        'excel' => ['xlsx', 'xls'],
        'attachment' => ['png', 'jpg', 'jpeg', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar']
    ];

    /**
     * Create a new rule instance.
     *
     * @param array|string $extensions Or preset name ('excel', 'attachment')
     */
    public function __construct(array|string $extensions)
    {
        if (is_string($extensions)) {
            $this->allowedExtensions = self::$presets[$extensions] ?? [];
        } else {
            $this->allowedExtensions = array_map('strtolower', $extensions);
        }
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof \Illuminate\Http\UploadedFile) {
            $fail('The :attribute must be a valid uploaded file.');
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (!in_array($extension, $this->allowedExtensions)) {
            $fail("The :attribute extension is not allowed. Only " . implode(', ', $this->allowedExtensions) . " files are permitted.");
        }
    }
}
