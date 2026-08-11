# UX Driver

[![Latest Stable Version](https://img.shields.io/packagist/v/pentiminax/ux-driver.svg?style=flat-square)](https://packagist.org/packages/pentiminax/ux-driver)
[![PHP Version](https://img.shields.io/packagist/php-v/pentiminax/ux-driver?style=flat-square)](https://packagist.org/packages/pentiminax/ux-driver)
[![Downloads total](https://img.shields.io/packagist/dt/pentiminax/ux-driver.svg?style=flat-square)](https://packagist.org/packages/pentiminax/ux-driver/stats)

UX Driver integrates [Driver.js](https://driverjs.com/) with Symfony UX so you can build product Tours, Highlights, and Hints from Twig Components or PHP builders.

**Documentation:** <https://pentiminax.github.io/ux-driver/>

## Requirements

- PHP 8.2 or higher
- Symfony 7 or 8
- Symfony StimulusBundle 2 or 3
- Symfony UX TwigComponent 2 or 3
- Twig 3.8 or higher

## Installation

```bash
composer require pentiminax/ux-driver
```

With AssetMapper, the bundle autoimports `driver.js/dist/driver.css` and `driver.js/dist/hints.css` through StimulusBundle.

With Webpack Encore, import both styles once in your app entry:

```js
import 'driver.js/dist/driver.css'
import 'driver.js/dist/hints.css'
```

## Minimal Tour

```twig
<twig:Driver:Tour id="welcome">
    <button type="button" data-action="pentiminax--ux-driver--tour#start">
        Start tour
    </button>

    <twig:Driver:Step :order="1" title="Dashboard" description="This is your daily overview">
        <h1>Dashboard</h1>
    </twig:Driver:Step>
</twig:Driver:Tour>
```

## Minimal Hints

```twig
<twig:Driver:Hints id="dashboard-help" buttonText="Done">
    <twig:Driver:Hint hintId="filters" title="Filters" description="Narrow the list before export">
        <button type="button">Filters</button>
    </twig:Driver:Hint>
</twig:Driver:Hints>
```

## Compatibility

| Dependency | Supported range |
| --- | --- |
| PHP | `>=8.2` |
| Symfony components | `^7.0` or `^8.0` |
| StimulusBundle | `^2.0` or `^3.0` |
| TwigComponent | `^2.0` or `^3.0` |
| Twig | `^3.8` |
| Driver.js | `^1.8.0` |
| Stimulus | `^3.0.0` |
| Node.js for development | CI uses `22`; no package engine is declared |

Driver.js 1.8 is the floor because UX Driver exposes `advanceOnClick`, `waitForElement`, and the `driver.js/hints` entrypoint.

## Development

```bash
php vendor/bin/phpunit

cd assets
npm test

cd ../docs
npm run check
npm run build
npm run test:build
```

## License

MIT
