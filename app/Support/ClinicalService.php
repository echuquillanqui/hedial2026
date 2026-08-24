<?php

namespace App\Support;

use InvalidArgumentException;

final class ClinicalService
{
    public const HEMODIALYSIS = 'HEMODIALYSIS';
    public const NEPHROLOGY = 'NEPHROLOGY';
    public const NUTRITION = 'NUTRITION';
    public const PSYCHOLOGY = 'PSYCHOLOGY';
    public const SOCIAL_WORK = 'SOCIAL_WORK';
    public const CORRECTION = 'CORRECTION';

    public const ORDINARY_TYPES = [
        self::HEMODIALYSIS,
        self::NEPHROLOGY,
        self::NUTRITION,
        self::PSYCHOLOGY,
        self::SOCIAL_WORK,
    ];

    public const MULTISECTORIAL_TYPES = [
        self::NUTRITION,
        self::PSYCHOLOGY,
        self::SOCIAL_WORK,
    ];

    public static function isMultisectorial(string $type): bool
    {
        return in_array($type, self::MULTISECTORIAL_TYPES, true);
    }

    public static function label(string $type): string
    {
        return match ($type) {
            self::NUTRITION => 'NUTRICIÓN',
            self::PSYCHOLOGY => 'PSICOLOGÍA',
            self::SOCIAL_WORK => 'TRABAJO SOCIAL',
            self::HEMODIALYSIS => 'HEMODIÁLISIS',
            self::NEPHROLOGY => 'CONSULTA NEFROLÓGICA',
            default => throw new InvalidArgumentException('Tipo de atención no válido.'),
        };
    }

    public static function permissionPrefix(string $type): string
    {
        return match ($type) {
            self::NUTRITION => 'nutrition',
            self::PSYCHOLOGY => 'psychology',
            self::SOCIAL_WORK => 'social_work',
            default => throw new InvalidArgumentException('Tipo multisectorial no válido.'),
        };
    }

    public static function cpms(string $type): string
    {
        return match ($type) {
            self::HEMODIALYSIS => '90937',
            self::NEPHROLOGY => '99215',
            self::PSYCHOLOGY => '99207',
            self::NUTRITION => '99209',
            self::SOCIAL_WORK => '99210',
            default => throw new InvalidArgumentException('Tipo de atención sin código CPMS configurado.'),
        };
    }
}
