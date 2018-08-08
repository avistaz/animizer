<?php

require __DIR__.'/../vendor/autoload.php';

$client = new \Animizer\Clients\AnidbClient('YOURAPIKEY');

dump($client->get(123));
