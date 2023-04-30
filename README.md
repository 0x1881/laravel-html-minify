# Laravel HTML Minifier
This package helps to minify your project`s html (blade file) output.

## Installation
You can install the package via composer:

```bash
composer require 0x1881/laravel-html-minify
```

## Usage
Publish the config file if the defaults doesn't suite your needs:
```bash
php artisan vendor:publish --tag=LaravelHtmlMinify
```

The following config file will be published in config/htmlminify.php
```php
return [
    'enable' => env('HTML_MINIFY', true),
];
```

You should add middleware to your web middleware group within your app/Http/Kernel.php file:
```php
\C4N\LaravelHtmlMinify\Middleware\LaravelMinifyHtml::class
```

Add in ENV
```conf
HTML_MINIFY = true
```

### Testing
``` bash
composer test
```

### Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security
If you discover any security related issues, please email 1881x0@gmail.com instead of using the issue tracker.

## Credits
- [Dipesh Sukhia](https://github.com/dipeshsukhia)
- [All Contributors](../../contributors)

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

