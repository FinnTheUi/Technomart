<?php

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

trait EnumHelpers
{
    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return Str::headline($this->value);
    }
}
