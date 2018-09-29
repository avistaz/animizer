<?php

namespace Animizer\Data;

class Franchise extends Base
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string [sequel|prequel]
     */
    public $type;

    /**
     * @var string
     */
    public $title;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->guessFranchiseType();
    }

    private function guessFranchiseType()
    {
        $types = [
            'prequel',
            'sequel',
            'adaptation',
            'alternative-setting',
            'alternative-version',
            'full-story',
            'side-story',
            'same-setting',
            'spin-off',
            'summary',
            'other',
        ];

        $this->type = str_slug($this->type);
        if (!in_array($this->type, $types)) {
            $this->type = 'other';
        }
    }
}