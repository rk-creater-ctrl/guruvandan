<?php

namespace App\Enums;

enum TributeType: string
{
    case ThankYouMessage = 'thank_you_message';
    case Letter = 'letter';
    case Poem = 'poem';
    case Drawing = 'drawing';
    case PhotoMemory = 'photo_memory';
    case AudioMessage = 'audio_message';
    case VideoWish = 'video_wish';
    case GreetingCard = 'greeting_card';

    public function label(): string
    {
        return match ($this) {
            self::ThankYouMessage => 'Thank-you message',
            self::Letter => 'Letter',
            self::Poem => 'Poem',
            self::Drawing => 'Drawing',
            self::PhotoMemory => 'Photo memory',
            self::AudioMessage => 'Audio message',
            self::VideoWish => 'Video wish',
            self::GreetingCard => 'Greeting card',
        };
    }

    public function mediaKind(): ?string
    {
        return match ($this) {
            self::Drawing, self::PhotoMemory, self::GreetingCard => 'image',
            self::AudioMessage => 'audio',
            self::VideoWish => 'video',
            default => null,
        };
    }
}
