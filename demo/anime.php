<?php

require __DIR__ . '/../vendor/autoload.php';

//$client = new \Animizer\Clients\AnidbClient('YOURAPIKEY');
//$anime = $client->get(0, 'anidb.xml');
//dump($anime);
//
//$client = new \Animizer\Clients\AnnClient();
//$anime = $client->get(0, 'ann.xml');
//dump($anime);

$client = new \Animizer\Clients\AnilistClient();
$anime = $client->get(['mal' => 91941]);
dump($anime);

// https://kitsu.docs.apiary.io
// http://anilist.co