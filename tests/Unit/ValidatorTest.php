<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Utils\Validator;

class ValidatorTest extends TestCase
{
    public function testRequiredValidation(): void
    {
        $data = ['name' => ''];
        $rules = ['name' => 'required'];

        $result = Validator::validate($data, $rules);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('name', $result['errors']);
    }

    public function testEmailValidation(): void
    {
        $validData = ['email' => 'test@example.com'];
        $invalidData = ['email' => 'invalid-email'];
        $rules = ['email' => 'email'];

        $validResult = Validator::validate($validData, $rules);
        $invalidResult = Validator::validate($invalidData, $rules);

        $this->assertTrue($validResult['valid']);
        $this->assertFalse($invalidResult['valid']);
    }

    public function testMinLengthValidation(): void
    {
        $data = ['password' => 'short'];
        $rules = ['password' => 'min:8'];

        $result = Validator::validate($data, $rules);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('password', $result['errors']);
    }

    public function testMaxLengthValidation(): void
    {
        $data = ['name' => str_repeat('a', 300)];
        $rules = ['name' => 'max:255'];

        $result = Validator::validate($data, $rules);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('name', $result['errors']);
    }

    public function testIntegerValidation(): void
    {
        $validData = ['age' => 25];
        $invalidData = ['age' => 'not a number'];
        $rules = ['age' => 'integer'];

        $validResult = Validator::validate($validData, $rules);
        $invalidResult = Validator::validate($invalidData, $rules);

        $this->assertTrue($validResult['valid']);
        $this->assertFalse($invalidResult['valid']);
    }

    public function testDateValidation(): void
    {
        $validData = ['date' => '2024-01-15'];
        $invalidData = ['date' => 'not a date'];
        $rules = ['date' => 'date'];

        $validResult = Validator::validate($validData, $rules);
        $invalidResult = Validator::validate($invalidData, $rules);

        $this->assertTrue($validResult['valid']);
        $this->assertFalse($invalidResult['valid']);
    }

    public function testMultipleRules(): void
    {
        $data = ['email' => 'test@example.com', 'name' => 'John Doe'];
        $rules = [
            'email' => 'required|email',
            'name' => 'required|string|min:3|max:100'
        ];

        $result = Validator::validate($data, $rules);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testOptionalField(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'required', 'nickname' => 'string|min:2'];

        $result = Validator::validate($data, $rules);

        $this->assertTrue($result['valid']);
    }
}
