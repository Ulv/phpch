<?php
// usage example

include __DIR__ . '/vendor/autoload.php';

$ch = new \Ulv\Phpch\Client();
$ch->query('select * from currency_exchange_rates ');// ->fetchAll();

foreach ($ch->cursor() as $one) {
    var_dump(convert(memory_get_usage(true)), $one);
}

function convert($size)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}
