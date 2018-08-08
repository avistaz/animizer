<?php

require __DIR__.'/../vendor/autoload.php';

$client = new \Animizer\Clients\AnidbClient('animetorrents');

dump($client->get(123));
