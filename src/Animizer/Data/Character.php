<?php

namespace Animizer\Data;

class Character extends Base
{
    /**
     * @var int|string
     */
    public $id;

    /**
     * @var string [main|support]
     */
    public $type;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $gender;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $picture;

    /**
     * @var Person
     */
    public $actor;

    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->guessCharacterType();
    }

    private function guessCharacterType()
    {
        if (!empty($this->type)) {
            $this->type = strtolower($this->type);
            if (str_contains($this->type, ['main'])) {
                $this->type = 'main';
            }
            if (str_contains($this->type, ['appear', 'support', 'secondary'])) {
                $this->type = 'support';
            }
        }
    }
}