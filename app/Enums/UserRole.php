<?php

namespace App\Enums;

enum UserRole: int
{
    case SUPER_ADMIN = 0;
    case MANAGER = 1;
    case CHIEF = 2;
    case TEAM_LEAD = 3;
    case EMPLOYEE = 4;

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::MANAGER => 'Manager',
            self::CHIEF => 'Chief',
            self::TEAM_LEAD => 'Team Lead',
            self::EMPLOYEE => 'Employee',
        };
    }

    public static function fromStatus(string $status): self
    {
        $normalized = strtolower($status);
        
        return match (true) {
            str_contains($normalized, 'admin') => self::SUPER_ADMIN,
            str_contains($normalized, 'manager') => self::MANAGER,
            str_contains($normalized, 'chief') => self::CHIEF,
            str_contains($normalized, 'teamlead') => self::TEAM_LEAD,
            str_contains($normalized, 'employee') => self::EMPLOYEE,
            default => self::EMPLOYEE,
        };
    }

    public function canManage(self $targetRole): bool
    {
        return $this->value < $targetRole->value;
    }
}
