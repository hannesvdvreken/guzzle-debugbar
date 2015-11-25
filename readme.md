# [Guzzle](http://docs.guzzlephp.org/en/latest/) middleware to log requests to [PHP DebugBar](https://github.com/maximebf/php-debugbar)'s timeline

Guzzle middleware to log requests to DebugBar's timeline.

![Debugbar timeline](https://www.dropbox.com/s/cabwqycckbu681b/debugbar-timeline.png?dl=1 "Debugbar timeline")
![Debugbar logs](https://www.dropbox.com/s/7rez2q1mbrl76yq/debugbar-logs.png?dl=1 "Debugbar logs")

## Usage

Just six lines of code are needed to log your requests to DebugBar's timeline.

```php
$debugBar = new StandardDebugBar();

// Get data collector.
$timeline = $debugBar->getCollector('time');

// Wrap the timeline.
$profiler = new GuzzleHttp\Profiling\Debugbar\Profiler($timeline);

// Add the middleware to the stack
$stack = GuzzleHttp\HandlerStack::create();
$stack->unshift(new GuzzleHttp\Profiling\Middleware($profiler));

// New up the client with this handler stack.
$client = new GuzzleHttp\Client(['handler' => $stack]);
```

Now `$client` is ready to make requests. Every request is now logged to the timeline.

### Recommended: use Guzzle's Log middleware

```php
$debugBar = new StandardDebugBar();

// PSR-3 logger:
$logger = $debugBar->getCollector('messages');

// Create a new Log middleware.
$stack->push(GuzzleHttp\Middleware::log($logger));

// New up the client with this handler stack.
$client = new GuzzleHttp\Client(['handler' => $stack]);
```

## Support

### Laravel

It is recommended to have my friend Barry's [laravel-debugbar](https://github.com/barryvdh/laravel-debugbar) installed and configured. Make sure to include his and our service providers your app's `providers` array:

```php
'providers' => [
    ...
    Barryvdh\Debugbar\ServiceProvider::class,
    GuzzleHttp\Profiling\Debugbar\Support\Laravel\ServiceProvider::class,
],
```

If you want to use a different `DebugBar\DebugBar` instance, create a ServiceProvider that binds an
instance with the key `debugbar`. For example with this register method:

```php
public function register()
{
    $this->app->singleton('debugbar', function () {
        return new \DebugBar\StandardDebugBar();
    });
}
```

Be sure to create every client (type hint with `GuzzleHttp\ClientInterface` or `GuzzleHttp\Client`) via the IoC container.

### FAQ:

I get one of these errors:

`'time' is not a registered collector`
`'exceptions' is not a registered collector`

It means you disabled the `time` collector and/or the `exceptions` collector in your `packages/barryvdh/laravel-debugbar/config.php`. These are enabled by default. This package depends on it, so please enable them both.

## Contributing

Feel free to make a pull request. Please try to be as
[PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
compliant as possible. Fix Code Style quickly by running `vendor/bin/php-cs-fixer fix`. Give a good description of what is supposed to be added/changed/removed/fixed.

### Testing

To test your code before pushing, run the unit test suite.

```bash
vendor/bin/phpunit
```

## License

[MIT](LICENSE)
