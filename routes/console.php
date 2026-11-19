<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Делаю сайты с вниманием к деталям.');
})->purpose('Вывести короткое сообщение');
