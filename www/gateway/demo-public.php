<?php

return array(
    0 => array(
        'enabled' => true,
        'source'  => 'uri',
        'type'    => 'int',
        'key'     => 'is_beta',
        'value'   => '1',
        'start_timestamp' => strtotime(date('Y-m-d')),
        'end_timestamp'   => strtotime(date('Y-m-d', strtotime('+30 days')))
    ),
    1 => array(
        'enabled' => true,
        'source'  => 'cookie',
        'type'    => 'range',
        'key'     => 'user_id',
        'value'   => '1,1000',
        'start_timestamp' => strtotime(date('Y-m-d')),
        'end_timestamp'   => strtotime('+30 days')
    ),
    2 => array(
        'enabled' => false,
        'source'  => 'scale',
        'type'    => 'int',
        'key'     => 'percent',
        'value'   => '20',
        'start_timestamp' => strtotime(date('Y-m-d')),
        'end_timestamp'   => strtotime('+30 days')
    ),
);

?>
