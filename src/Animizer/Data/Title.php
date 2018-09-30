<?php

namespace Animizer\Data;

class Title extends Base
{
    /**
     * @var string [main|alt]
     */
    public $type;

    /**
     * @var Language
     */
    public $language;

    /**
     * @var string
     */
    public $title;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->guessTitleType();

        $this->language = new Language([$this->language]);
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