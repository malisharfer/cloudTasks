<?php

namespace App\Enums;

class Options
{
    public static function getOptions($array) {
        return collect($array)
            ->flatMap(fn($option) => [$option->value => __($option->value)])
            ->all();
    }
}