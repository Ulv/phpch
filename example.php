<?php
// usage example

ini_set('display_errors', E_ALL);

include_once __DIR__ . '/vendor/autoload.php';

$connection = new \Ulv\Phpch\Configuration([
    'host'            => '172.16.238.2',
    'server.database' => 'devel',
]);

$ch = new \Ulv\Phpch\Client($connection);

$ch->query('select date, user_id, device_id, name from tracker where date between \'2022-04-01\' and \'2022-04-10\' ');

$i = 0;
foreach ($ch->stream() as $one) {
    var_dump($one);
    var_dump($i . ' ' . convert(memory_get_usage(true)));
    $i++;
}

function convert($size)
{
    $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}
