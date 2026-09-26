<?php

namespace Store\Dependency\Illuminate\Database\Eloquent\Casts;

use Store\Dependency\Illuminate\Contracts\Database\Eloquent\Castable;
use Store\Dependency\Illuminate\Contracts\Database\Eloquent\CastsAttributes;
class AsArrayObject implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return CastsAttributes<ArrayObject<array-key, mixed>, iterable>
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                if (!isset($attributes[$key])) {
                    return;
                }
                $data = json_decode($attributes[$key], \true);
                return is_array($data) ? new ArrayObject($data) : null;
            }
            public function set($model, $key, $value, $attributes)
            {
                return [$key => json_encode($value)];
            }
            public function serialize($model, string $key, $value, array $attributes)
            {
                return $value->getArrayCopy();
            }
        };
    }
}
