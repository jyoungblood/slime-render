# SLIME Render

### PHP abstraction functions to help more easily render views for [Slim Framework](https://www.slimframework.com/) (v4) with plain text, HTML, JSON, and Handlebars (using [LightnCandy](https://github.com/zordius/lightncandy))

These functions aim to provide a simplified and standardized interface for rendering various types of data-driven responses as PSR-7 objects for use with Slim.

Included with the [Slime boilerplate](https://github.com/jyoungblood/slime) for Slim applications.

# Installation
Easy install with composer:
```
composer require jyoungblood/slime-render
```
```php
use Slime\render;
require __DIR__ . '/vendor/autoload.php';
```

## Requirements
- [Slim Framework](https://www.slimframework.com/) 4
- [LightnCandy](https://github.com/zordius/lightncandy) >= 1.2.6
- PHP >= 7.4


# Usage
## render::html($request, $response, $string, $status = 200)
Renders a string as HTML. Returns a standard Slim (PSR-7) response object with optional HTTP status code (200 by default).
```php
$app->get('/', function ($req, $res, $args) {

  return render::html($req, $res, '<h2>Hey whats up</h2>');

});
```

Additionally, a path to an HTML file can be specified to load and render instead of a string:
```php
$app->get('/', function ($req, $res, $args) {

  return render::html($req, $res, '/hey/whats-up.html');

});
```




## render::text($request, $response, $string, $status = 200)
Renders a string as plain text. Returns a standard Slim (PSR-7) response object with optional HTTP status code (200 by default).
```php
$app->get('/', function ($req, $res, $args) {

  return render::text($req, $res, 'Hey whats up');

});
```


## render::hbs($request, $response, $parameters, $status = 200)
Renders a specific Handlebars template with an array of data, including any partials and global `locals` variables array. Returns a standard Slim (PSR-7) response object with optional HTTP status code (200 by default).
```php
$app->get('/', function ($req, $res, $args) {

  return render::hbs($req, $res, [
    'template' => 'index',
    'layout' => '_layouts/base', // optional "wrapper" layout template
    'title' => 'Page title', // for HTML <title> tag
    'data' => [
      'name' => 'Ringo',
      'friends' => [
        'Paul', 'George', 'John'
      ]
    ],
  ], 200); // optional status code, 200 by default

});
```

The parser function expects templates to be in a `templates` directory with `html` file extension. This can be customized by defining these variables in a global `settings` array:
```php
$GLOBALS['settings']['templates']['path'] = 'pages';
$GLOBALS['settings']['templates']['extension'] = 'hbs';
```

Additionally, an array of `locals` can be added to make variables available across all templates:
```php
$GLOBALS['locals'] = [
  'year' => date('Y'),
  'site_title' => 'Web Site Title',
  'site_code' => 'WST',
  'site_domain' => 'example.com',
];
```
```handlebars
Welcome to {{locals.site_title}}, the year is {{locals.year}}!
```

Parameters from PHP $_GET and $_POST variables are automatically made available to templates rendered with this function, using the variables `{{GET}}` and `{{POST}}`:
```handlebars
<!-- assuming a url like /hello/?name=Delilah&location=New%20York%20City -->
Hey there, {{GET.name}}, what's it like in {{GET.location}}?
```

Check out the [Handlebars Cookbook](https://zordius.github.io/HandlebarsCookbook/) to see everything you can do with LightnCandy and Handlebars.

Additionally, we've included a few helper functions.

The `date` helper applies the PHP `date()` function to a given variable or string (or `now` keyword for the current time)
```handlebars
Date from unix timestamp: {{date unix_ts_var "d/m/Y"}}
Current date: {{date "now" "d/m/Y"}} <!-- use the "now" keyword instead of a variable to use the current time -->
Date from non-unix timestamp: {{date non_unix_ts_var "d/m/Y" "convert"}} <!-- add the "convert" parameter to convert the variable to unix time using strtotime() -->
```
The `#is` block helper allows for basic conditional logic:
```handlebars
Is it 1981? {{#is locals.year "==" "1981"}} Yes! {{else}} No! {{/is}}
```

Custom helpers are easy to create. Take a look at how these helpers are defined in [initialize_handlebars_helpers()](https://github.com/jyoungblood/slime-render/blob/74e6e4a89a90a2490196a4d50d7466855820dd3a/src/render.php#L46). The Handlebars cookbook also has a reference for creating [custom helpers](https://zordius.github.io/HandlebarsCookbook/0021-customhelper.html) and [custom block helpers](https://zordius.github.io/HandlebarsCookbook/0022-blockhelper.html).

## render::handlebars($parameters)
Renders a specicific Handlebars template with data array the same as `render::hbs()`, but returns raw html instead of a PSR-7 response.
```php
$app->get('/', function ($req, $res, $args) {

  echo render::handlebars([
    'template' => 'email/test',
    'data' => [
      'link' => 'https://jy.hxgf.io',
    ]
  ]);

  return $res;
});
```

## render::redirect($request, $response, $string, $status = 302)
Renders a redirect as standard Slim (PSR-7) response object with optional HTTP status code.
```php
  return render::redirect($req, $res, 'https://google.com/');
```

## render::json($request, $response, $data, $status = 200)
Renders an array or data as standard Slim (PSR-7) response object with `application/json` content type and optional HTTP status code.
```php
$app->get('/json/', function ($req, $res, $args) {

  $data = [
    'name' => 'Ringo',
    'friends' => [
      'Paul', 'George', 'John'
    ]
  ];

  return render::json($req, $res, $data);

});
```

## render::lightncandy_html($parameters)($data)
Prepares and compiles a specific Handlebars template with an array of data, including any partials and global `locals` variables array.<br />
This is automatically called by `render::hbs()` but can be used as a standalone function if desired.
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

## render::initialize_handlebars_helpers()
For internal use by `lightncandy_html()`. Defines a couple custom Handlebars helper functions to be used by the LightnCandy compiler.







# Components

We've also created a `component` helper, which allows you to define components that accept props and handle asset management. Components are stored in the `templates/_components` directory and can include both a template file (`[name].html`) and an optional assets file (`[name].assets.html`).

Here are some examples of how to use components:

### Basic Card Component Example
```handlebars
{{{component "card" title="My Card Title"}}}
  <p>This is the slot content that will appear inside the card.</p>
  <p>You can put any HTML content here.</p>
{{{/component}}}
```

### Input Component with All Attributes
```handlebars
{{{component "input" 
  label="Username" 
  type="text" 
  width="w-80" 
  padding="pa3"
  name="username" 
  value="john_doe"
}}}
```

### Nested Components (Card containing Inputs)
```handlebars
{{{component "card" title="User Profile"}}}
  {{{component "input" 
    label="Full Name" 
    type="text" 
    width="w-100" 
    name="fullname" 
    value=user.fullname
  }}}
  
  {{{component "input" 
    label="Email" 
    type="email" 
    width="w-100" 
    name="email" 
    value=user.email
  }}}
{{{/component}}}
```

### Dynamic Values from Context
```handlebars
{{{component "input" 
  label="Search" 
  type="search" 
  width="w-100" 
  name="query" 
  value=search.query
}}}
```

### Multiple Components with Different Styles
```handlebars
{{{component "input" 
  label="Small Input" 
  type="text" 
  width="w-30" 
  padding="pa2"
  name="small" 
  value="small value"
}}}

{{{component "input" 
  label="Large Input" 
  type="text" 
  width="w-100" 
  padding="pa4"
  name="large" 
  value="large value"
}}}
```

### Form with Multiple Components
```handlebars
<form action="/submit" method="post">
  {{{component "input" 
    label="First Name" 
    type="text" 
    width="w-50" 
    name="firstName" 
    value=form.firstName
  }}}
  
  {{{component "input" 
    label="Last Name" 
    type="text" 
    width="w-50" 
    name="lastName" 
    value=form.lastName
  }}}
  
  {{{component "input" 
    label="Password" 
    type="password" 
    width="w-100" 
    name="password" 
    value=""
  }}}
</form>
```

Key features of the component helper:
- Use triple curly braces `{{{component}}}` to ensure HTML is not escaped
- The first argument is the component name (e.g., "card" or "input")
- All other attributes are passed as hash parameters
- For components that support slots (like the card), content between the opening and closing tags will be placed where `{{slot}}` appears in the template
- Assets (like CSS and JS) are automatically loaded if they exist in the component's assets file
- The component helper caches templates and assets for better performance

The component helper will automatically:
- Load the template from `templates/_components/[name].html`
- Load assets from `templates/_components/[name].assets.html` if they exist
- Replace variables in the template with provided values
- Handle slot content if present
- Cache templates and assets for better performance