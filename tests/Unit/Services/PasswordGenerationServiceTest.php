<?php

namespace Tests\Unit\Services;

use App\Services\PasswordGenerationService;
use PHPUnit\Framework\TestCase;

class PasswordGenerationServiceTest extends TestCase
{
    /**
     * Test password generation creates valid password
     */
    public function test_generate_creates_valid_password(): void
    {
        $password = PasswordGenerationService::generate();

        // Check length
        $this->assertGreaterThanOrEqual(16, strlen($password));

        // Check for uppercase
        $this->assertMatchesRegularExpression('/[A-Z]/', $password, 'Password should contain uppercase');

        // Check for lowercase
        $this->assertMatchesRegularExpression('/[a-z]/', $password, 'Password should contain lowercase');

        // Check for numbers
        $this->assertMatchesRegularExpression('/[0-9]/', $password, 'Password should contain numbers');

        // Check for special characters
        $this->assertMatchesRegularExpression('/[!@#$%^&*\-_+=]/', $password, 'Password should contain special chars');
    }

    /**
     * Test no ambiguous characters
     */
    public function test_no_ambiguous_characters(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $password = PasswordGenerationService::generate();

            // Should not contain ambiguous characters
            $this->assertStringNotContainsString('0', $password); // Zero
            $this->assertStringNotContainsString('O', $password); // Capital O
            $this->assertStringNotContainsString('l', $password); // Lowercase L
            $this->assertStringNotContainsString('1', $password); // One
            $this->assertStringNotContainsString('i', $password); // Lowercase i
            $this->assertStringNotContainsString('o', $password); // Lowercase o
        }
    }

    /**
     * Test passwords are unique
     */
    public function test_generated_passwords_are_unique(): void
    {
        $passwords = [];
        for ($i = 0; $i < 20; $i++) {
            $passwords[] = PasswordGenerationService::generate();
        }

        // All should be unique
        $this->assertEquals(count($passwords), count(array_unique($passwords)), 'All passwords should be unique');
    }

    /**
     * Test generate and hash returns both plaintext and hash
     */
    public function test_generate_and_hash_returns_both_values(): void
    {
        $result = PasswordGenerationService::generateAndHash();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('plaintext', $result);
        $this->assertArrayHasKey('hash', $result);
        $this->assertNotEmpty($result['plaintext']);
        $this->assertNotEmpty($result['hash']);
    }

    /**
     * Test plaintext and hash are different
     */
    public function test_plaintext_and_hash_are_different(): void
    {
        $result = PasswordGenerationService::generateAndHash();

        $this->assertNotEquals($result['plaintext'], $result['hash']);
    }

    /**
     * Test hash is valid BCrypt
     */
    public function test_hash_is_valid_bcrypt(): void
    {
        $result = PasswordGenerationService::generateAndHash();

        // BCrypt hashes start with $2
        $this->assertStringStartsWith('$2', $result['hash']);

        // Verify password matches hash
        $this->assertTrue(password_verify($result['plaintext'], $result['hash']));
    }

    /**
     * Test custom length
     */
    public function test_custom_length(): void
    {
        $password = PasswordGenerationService::generate(20);
        $this->assertGreaterThanOrEqual(20, strlen($password));
    }
}
