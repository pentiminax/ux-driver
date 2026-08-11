# UX Driver

[![Latest Stable Version](https://img.shields.io/packagist/v/pentiminax/ux-driver.svg?style=flat-square)](https://packagist.org/packages/pentiminax/ux-driver)
[![PHP Version](https://img.shields.io/packagist/php-v/pentiminax/ux-driver?style=flat-square)](https://packagist.org/packages/pentiminax/ux-driver)
[![Downloads total](https://img.shields.io/packagist/dt/pentiminax/ux-driver.svg?style=flat-square)](https://packagist.org/packages/pentiminax/ux-driver/stats)

UX Driver is a Symfony bundle that integrates [driver.js](https://driverjs.com/) into your Symfony applications. It provides a PHP builder, Twig components and a Stimulus controller to create product tours, highlights and contextual help without writing JavaScript.

## Requirements

- PHP 8.2 or higher
- Symfony StimulusBundle
- Symfony UX TwigComponent
- Composer

## Installation

Install the bundle via Composer:

```bash
composer require pentiminax/ux-driver
```

The bundle registers the AssetMapper path automatically. With Webpack Encore, import the controller from `@pentiminax/ux-driver/dist/controller.js` and ensure `driver.js` is installed.

## Compatibility

| Dependency              | Supported range      |
|-------------------------|----------------------|
| PHP                     | >= 8.2               |
| Symfony                 | 7.x / 8.x            |
| Symfony StimulusBundle  | ^2.0 \| ^3.0         |
| Symfony UX TwigComponent| ^2.0 \| ^3.0         |
| driver.js               | >= 1.8.0 < 2 (`^1.8.0`) |

The same `^1.8.0` range is declared in `peerDependencies` and in the `symfony.importmap`
metadata of `assets/package.json`, so AssetMapper and npm/Webpack Encore installs cannot
resolve to a different baseline.

### Why 1.8.0?

The v0.1 option surface relies on `advanceOnClick` and `waitForElement`, both introduced in
driver.js 1.8.0. Anything older would install successfully while silently ignoring options
the bundle advertises.

| Option / capability                                | Minimum driver.js |
|----------------------------------------------------|-------------------|
| `showProgress`, `animate`, `smoothScroll`, `allowClose` | 1.3.0         |
| `overlayColor`, `overlayOpacity`, `stagePadding`   | 1.3.0             |
| `duration`                                         | 1.6.0             |
| `skipMissingElement`                               | 1.7.0             |
| `advanceOnClick`, `waitForElement`                 | 1.8.0             |

`once()` is implemented by this bundle via `localStorage` and does not depend on driver.js.

**Hints are not supported.** There is no Hints API in any published driver.js release — the
1.8.0 type definitions export only `driver`, `Driver`, `Config`, `DriveStep`, `Popover`,
`Side`, `Alignment`, `AllowedButtons`, `DriverHook`, `PopoverDOM`, `State` and
`StageDefinition`. Hints will stay out of scope until driver.js ships them upstream.

## Mode builder (PHP / Twig)

Build a tour fluently in Twig and attach it to any trigger element:

```twig
{% set tour = create_tour('onboarding')
    .addStep('.page-header', 'Bienvenue', 'Voici l\'en-tête')
    .addStep('.sidebar', 'Navigation', 'Tout est ici', 'right')
    .showProgress()
    .once() %}

<button {{ ux_tour(tour) }}
        data-action="pentiminax--ux-driver--tour#start">
    Démarrer la visite
</button>
```

You can also inject `TourBuilder` in PHP:

```php
use Pentiminax\UX\Driver\Builder\TourBuilder;

public function __construct(private readonly TourBuilder $tourBuilder) {}

public function index(): Response
{
    $tour = $this->tourBuilder->create('dashboard')
        ->addStep('#stats', 'Statistiques', 'Suivez vos KPIs')
        ->smoothScroll()
        ->once();

    return $this->render('dashboard/index.html.twig', [
        'tour' => $tour,
    ]);
}
```

## Mode déclaratif (Twig Components)

Declare steps next to the markup they describe:

```twig
<twig:Driver:Tour id="onboarding" once="true">
    <button data-action="pentiminax--ux-driver--tour#start">Visite guidée</button>

    <twig:Driver:Step :order="1" title="En-tête" tag="header" class="page-header">
        Mon en-tête
    </twig:Driver:Step>

    <twig:Driver:Step :order="2" title="Navigation" side="right" tag="aside" class="sidebar">
        Ma sidebar
    </twig:Driver:Step>
</twig:Driver:Tour>
```

## Highlight simple

Highlight a single element without a multi-step tour:

```twig
<button {{ ux_highlight('.help-button', 'Aide', 'Cliquez ici pour commencer') }}
        data-action="pentiminax--ux-driver--tour#highlight">
    ?
</button>
```

## Options disponibles (V1)

- `showProgress`, `animate`, `smoothScroll`, `allowClose`
- `overlayColor`, `overlayOpacity`, `stagePadding`
- `once()` — persistance via `localStorage`

## Events Stimulus

The controller dispatches:

- `ux-driver:pre-connect` — before driver.js is initialized
- `ux-driver:connect` — after the driver instance is created

## Development

```bash
composer install
vendor/bin/phpunit

cd assets
npm install
npm run build
npm test
```

## License

This bundle is released under the [MIT license](LICENSE).
