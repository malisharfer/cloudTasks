<?php

namespace App\Traits;

trait TrimsAttributes
{
    public static function bootTrimsAttributes()
    {
        static::saving(function ($model) {
            foreach ($model->getAttributes() as $key => $value) {
                $casts = $model->getCasts();
                $castType = $casts[$key] ?? null;

                if (in_array($castType, ['array', 'json', 'object'], true)) {
                    continue;
                }

                if (is_string($value)) {
                    $model->{$key} = trim($value);
                }
            }
        });
    }
}
