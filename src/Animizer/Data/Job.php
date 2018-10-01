<?php

namespace Animizer\Data;

class Job extends Base
{

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $job;

    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->matchJob($data);
    }

    private function matchJob(array $data)
    {
        if (isset($data[0])) {
            $this->job = $data[0];
        }
    }
}