<?php
$components = [

  /*
   * Framework components
   */

  // identity class
  'user' => [
    'class' => 'yii\web\User',
    'identityClass' => 'app\models\User',
  ],
  // logging
  'log' => [
    'targets' => [
      [
        'class' => 'yii\log\FileTarget',
        //'levels' => ['info', 'error', 'warning'],
        'levels' => ['trace','info', 'error', 'warning'],
        'except' => ['yii\*'],
        'logVars' => [],
        //'exportInterval' => 1
      ]
    ] 
  ],
  // Override http response component
  'response' => [
    'class' => \lib\components\EventTransportResponse::class
  ], 
  
  /*
   * Custom applications components
   */  

  // The application configuration
  'config' => [
    'class' => \lib\components\Configuration::class
  ],    
  'ldap' => require('ldap.php'),
  'ldapAuth'  => [
    'class' => \lib\components\LdapAuth::class
  ],  
  // a queue of Events to be transported to the browser
  'eventQueue' => [
    'class' => \lib\components\EventQueue::class
  ],
  // various utility methods
  'utils' => [ 
    'class' => \lib\components\Utils::class
  ],
  //server-side events, not used
  'sse' => [
    'class' => \odannyc\Yii2SSE\LibSSE::class
  ],
  //message channels, not working yet
  'channel' => [
    'class' => \lib\channel\Component::class
  ]
];
return array_merge(
  // merge db components
  require('db.php'),
  $components
);