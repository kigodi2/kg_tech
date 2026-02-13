<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordGenerationService
{
    /**
     * Generate a strong, random password
     * 
     * Requirements:
     * - Minimum 16 characters
     * - Mix of uppercase, lowercase, numbers, special characters
     * - No ambiguous characters (0, O, l, 1, etc.)
     */
    public static function generate(int $length = 16): string
    {
        // Character sets excluding ambiguous characters
        $uppercase = 'ABCDEFGHJKMNPQRSTUVWXYZ'; // No I, O
        $lowercase = 'abcdefghjkmnpqrstuvwxyz'; // No i, l, o
        $numbers = '23456789'; // No 0, 1
        $special = '!@#$%^&*-_+='; // Common special chars

        // Build password with at least one from each set
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        // Fill remaining length with random mix
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle to avoid predictable patterns
        return str_shuffle($password);
    }

    /**
     * Generate and hash a password
     */
    public static function generateAndHash(): array
    {
        $plaintext = self::generate();
        return [
            'plaintext' => $plaintext,
            'hash' => Hash::make($plaintext),
        ];
    }
}
