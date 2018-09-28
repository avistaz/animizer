<?php

namespace Animizer\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

abstract class Base implements Arrayable, Jsonable
{
    public function __construct(array $data)
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
        return json_decode(json_encode($this), true);
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