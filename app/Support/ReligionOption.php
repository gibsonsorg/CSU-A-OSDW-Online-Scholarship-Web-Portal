<?php

namespace App\Support;

final class ReligionOption
{
    public const OPTIONS = [
        'Methodist',
        'Iglesia ni Cristo',
        'Catholic',
        'Born Again',
    ];

    public static function normalize(?string $selected, ?string $otherText): string
    {
        $trimmedSelected = trim((string) $selected);
        $trimmedOtherText = trim((string) $otherText);

        if ($trimmedSelected === 'Others') {
            return $trimmedOtherText;
        }

        foreach (self::OPTIONS as $option) {
            if (strcasecmp($trimmedSelected, $option) === 0) {
                return $option;
            }
        }

        if ($trimmedSelected !== '') {
            return $trimmedSelected;
        }

        return $trimmedOtherText;
    }

    public static function displaySelection(?string $storedValue): array
    {
        $trimmed = trim((string) $storedValue);

        foreach (self::OPTIONS as $option) {
            if (strcasecmp($trimmed, $option) === 0) {
                return ['selected' => $option, 'otherText' => ''];
            }
        }

        if ($trimmed === '') {
            return ['selected' => '', 'otherText' => ''];
        }

        return ['selected' => 'Others', 'otherText' => $trimmed];
    }
}
