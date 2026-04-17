<?php

namespace App\Service;

class PasswordValidator
{
    public const MIN_LENGTH = 8;
    
    public function validate(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = 'password.error.min_length';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'password.error.uppercase';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'password.error.number';
        }
        
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'password.error.special';
        }
        
        return $errors;
    }
    
    public function isValid(string $password): bool
    {
        return empty($this->validate($password));
    }
    
    public function getRequirements(): array
    {
        return [
            'password.requirement.min_length' => self::MIN_LENGTH,
        ];
    }
}
