<?php

$fetched = \IPS\Db::i()->query('SELECT typed, image FROM core_emoticons;')->fetch_all();


$emoticons = [];

foreach ($fetched as $emoticon) {
  $emoticons[] = [
    'typed' => $emoticon[0],
    'image' => $emoticon[1]
  ];
}

echo json_encode($emoticons);


