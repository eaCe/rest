# REST API für REDAXO 5

Eine einfache Möglichkeit eine REST API in REDAXO zu erstellen.

## Route registrieren

```php
// https://domain.de/api/my-call
Rest::registerRoute([
    'route' => 'my-call',
    'methods' => array('POST', 'GET'),
    'callback' => 'myCallbackFn',
]);

function myCallbackFn($route) {
    /** @var RestRoute $route */
    $data = [
        'lorem' => 'ipsum',
        'dolor' => [
            'sit' => 'amet',
        ]
    ];

    $route->sendContent($data);
}

// https://domain.de/api/my-call/12
Rest::registerRoute([
    'route' => 'my-call/{articleID}',
    'methods' => array('POST', 'GET'),
    'callback' => function ($route) {
        $articleID = $route->getParam('articleID', 'int');
        // ...
    },
]);

// Full configuration
[
    'route' => '/my-call/{articleID}',
    'permission' => 'admin', // addon[]
    'methods' => array('POST', 'GET'),
    'validations' => [
        'articleID' => 'int', // number, bool/boolean, 
    ],
    'callback' => static function($route) {
        /** @var RestRoute $route */
        $data = [
            'lorem' => 'ipsum',
            'dolor' => [
                'sit' => 'amet',
            ]
        ];

        $route->sendContent($data);
    },
]
```
