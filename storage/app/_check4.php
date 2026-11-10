<?php

foreach (App\Models\Team::all() as $t) {
    echo $t->id.' | '.$t->name.' | code:'.$t->code.' | dept:'.$t->department_id.PHP_EOL;
}
