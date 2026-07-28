<?php

namespace App\Enums;

enum TributeLanguage: string
{
    case English = 'english';
    case Hindi = 'hindi';
    case Hinglish = 'hinglish';
    case SanskritQuote = 'sanskrit_quote';

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Hindi => 'Hindi',
            self::Hinglish => 'Hinglish',
            self::SanskritQuote => 'Sanskrit Quote',
        };
    }
}
