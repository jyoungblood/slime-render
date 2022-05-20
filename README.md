# SLIME Render

### PHP abstraction functions to help more easily render views for [Slim Framework](https://www.slimframework.com/) with [LightnCandy](https://github.com/zordius/lightncandy) (handlebars) or [Twig](https://github.com/slimphp/Twig-View).

Included with the [Slime boilerplate](https://github.com/hxgf/slime) for Slim applications.

# Installation
Easy install with composer:
```
composer require hxgf/slime-render
```
```php
use Slime\render;
require __DIR__ . '/vendor/autoload.php';
```

## Requirements
- [Slim Framework](https://www.slimframework.com/) 4
- [LightnCandy](https://github.com/zordius/lightncandy) >= 1.2.6
- (or) [Twig-View for Slim](https://github.com/slimphp/Twig-View) >= 3.3.0
- PHP >= 7.4


# Usage
## render::hbs($request, $response, $parameters)
Renders a specific Handlebars template with an array of data, including any partials and global `locals` variables array. Returns a standard Slim (PSR-7) response object with optional HTTP status code.
```php
$app->get('/', function ($req, $res, $args) {

  return render::hbs($req, $res, [
    'template' => 'index',
    'layout' => '_layouts/base', // optional "wrapper" layout template
    'title' => 'Page title', // for HTML <title> tag
    'status' => 200, // optional, 200 by default
    'data' => [
      'name' => 'Ringo',
      'friends' => [
        'Paul', 'George', 'John'
      ]
    ],
  ]);

});
```

The parser function expects a template path and default file extension to be defined in a global `settings` array, in addition to any `locals` variables you'd like to make available for all templates, like this:
```php
$GLOBALS['settings']['templates']['path'] = __DIR__ . '/templates';
$GLOBALS['settings']['templates']['extension'] = '.html';

$GLOBALS['locals'] = [
  'year' => date('Y'),
  'site_title' => 'Web Site Title',
  'site_code' => 'WST',
  'site_domain' => 'example.com',
];
```


## render::json($request, $response, $parameters)
Renders an array or data as standard Slim (PSR-7) response object with `application/json` content type and optional HTTP status code.
```php
$app->get('/json/', function ($req, $res, $args) {

  $data = [
    'name' => 'Ringo',
    'friends' => [
      'Paul', 'George', 'John'
    ]
  ];

  return render::json($req, $res, [
    'status' => 200, // optional, 200 by default
    'data' => $data
  ]);

});
```

## render::lightncandy_html($parameters)($data)
Prepares and compiles a specific Handlebars template with an array of data, including any partials and global `locals` variables array.<br />
This is automatically called by `rebder::hbs()` but can be used as a standalone function if desired.
```php
$args = [
  'template' => 'index',
  'layout' => '_layouts/base',
  'title' => 'Page title',
];

$data = [
  'name' => 'Ringo',
  'friends' => [
    'Paul', 'George', 'John'
  ]
];

echo render::lightncandy_html($args)($data);
```

## render::twig($request, $response, $parameters)
Similar to `render::hbs()` except with Twig templates.
```php
$app->get('/', function ($req, $res, $args) {

  return render::twig($req, $res, [
    'template' => 'index',
    'title' => 'Page title', // for HTML <title> tag
    'data' => [
      'name' => 'Ringo',
      'friends' => [
        'Paul', 'George', 'John'
      ]
    ],
  ]);

});
```
To use Twig templates, remember to add the middleware declarations after initializing your Slim app (as outlined in the [Twig-View](https://github.com/slimphp/Twig-View) documentation). 
```php
$app = AppFactory::create();

use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

$twig = Twig::create(__DIR__ . '/templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));
```
<sub>\* NOTE: Although this function renders the global `locals` variable array, it doesn't read any of the `settings` template variables mentioned above, and all templates are expected to be `.html`.</sub>