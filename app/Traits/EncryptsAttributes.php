<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

/**
 * Trait for encrypting/decrypting sensitive model attributes at rest.
 *
 * Usage: Add `use EncryptsAttributes;` to your model and define
 * `protected array $encryptable = ['field1', 'field2'];`
 *
 * Values are automatically encrypted when setting and decrypted when getting.
 * The encrypted values are stored with a `enc:` prefix so we know they're encrypted.
 */
trait EncryptsAttributes
{
    /**
     * Override: Get an attribute value, decrypting if needed.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if ($this->isEncryptable($key) && is_string($value) && str_starts_with($value, 'enc:')) {
            try {
                return Crypt::decryptString(substr($value, 4));
            } catch (\Throwable) {
                // If decryption fails, return raw value (data may not be encrypted yet)
                return $value;
            }
        }

        return $value;
    }

    /**
     * Override: Set an attribute value, encrypting if needed.
     */
    public function setAttribute($key, $value)
    {
        if ($this->isEncryptable($key) && $value !== null && !str_starts_with((string) $value, 'enc:')) {
            $value = 'enc:' . Crypt::encryptString((string) $value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Check if a given attribute should be encrypted.
     */
    protected function isEncryptable(string $key): bool
    {
        return property_exists($this, 'encryptable') && in_array($key, $this->encryptable);
    }

    /**
     * Get the raw (encrypted) value without decryption.
     */
    public function getRawAttribute(string $key): mixed
    {
        return parent::getAttribute($key);
    }

    /**
     * Get all encryptable field names.
     */
    public function getEncryptableFields(): array
    {
        return property_exists($this, 'encryptable') ? $this->encryptable : [];
    }
}
