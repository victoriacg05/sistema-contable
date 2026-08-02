<?php

return [
    'trusted' => array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', ''))
    )),
];
