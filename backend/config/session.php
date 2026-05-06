<?php

return [
    'prefix' => 'ed_session:',
    'type'   => 'file',
    'expire' => 86400,
    'path'   => runtime_path('session'),
];
