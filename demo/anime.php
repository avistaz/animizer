<?php

require __DIR__.'/../vendor/autoload.php';

$client = new \Animizer\Clients\AnidbClient('YOURAPIKEY');
dump($client->get(0, 'anidb.xml'));

$client = new \Animizer\Clients\AnnClient();
dump($client->get(0, 'ann.xml'));
