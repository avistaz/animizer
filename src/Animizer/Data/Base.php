<?php

namespace Animizer\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;

abstract class Base implements Arrayable, Jsonable
{
    public function __construct(Collection $data)
    {
        foreach ($this as $key => $value) {
            $this->$key = isset($data[$key]) ? $data[$key] : null;
        }

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $data = [];
        foreach ($this as $key => $value) {
            if ($value instanceof Collection) {
                $data[$key] = $value->toArray();
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }
}