<?php

namespace Animizer\Data;

use Illuminate\Support\Collection;

class Title extends Base
{
    /**
     * @var string [main|alt]
     */
    public $type;

    /**
     * @var string [i18n ISO 3166-1 language code]
     */
    public $language;

    /**
     * @var string
     */
    public $title;

    public function __construct(Collection $data)
    {
        parent::__construct($data);
        $this->guessTitleType();
    }

    private function guessTitleType()
    {
        if (!empty($this->type)) {
            $this->type = strtolower($this->type);
            if (str_contains($this->type, ['main'])) {
                $this->type = 'main';
            }
            if (str_contains($this->type, ['alt'])) {
                $this->type = 'alt';
            }
        }
    }
}