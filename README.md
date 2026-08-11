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

### Stylesheet

The controller never imports `driver.css` at runtime: a native importmap browser module cannot
execute a CSS file as JavaScript.

- **AssetMapper**: nothing to do. `driver.js/dist/driver.css` is declared as a StimulusBundle
  `autoimport` (and as an `symfony.importmap` entry, which StimulusBundle requires to resolve it),
  so the stylesheet is injected for you.
- **Webpack Encore**: import it yourself, once, in your entrypoint:

  ```js
  import 'driver.js/dist/driver.css';
  ```

## Compatibility

| Dependency               | Supported range         | Verified in CI                                     |
|--------------------------|-------------------------|----------------------------------------------------|
| PHP                      | >= 8.2                  | 8.2, 8.3, 8.4                                      |
| Symfony                  | 7.x / 8.x               | highest stable allowed by `composer.json`          |
| Symfony StimulusBundle   | ^2.0 \| ^3.0            | highest stable allowed by `composer.json`          |
| Symfony UX TwigComponent | ^2.0 \| ^3.0            | highest stable allowed by `composer.json`          |
| driver.js                | >= 1.8.0 < 2 (`^1.8.0`) | 1.8.0 (floor) and `latest`                         |
| Node.js (build only)     | >= 22                   | 22                                                 |

CI does not run a Symfony version matrix yet: each PHP job installs the highest stable
dependency set the constraints allow. The driver.js floor, by contrast, is pinned and tested
explicitly, so a 1.8.0-only regression cannot pass unnoticed.

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

## Restart policy

Destroy-and-restart: every `start` or `highlight` destroys the active driver.js instance
before creating a new one. Re-triggering an action restarts the tour from the first step,
and switching between `start` and `highlight` cleans up the previous instance, so
overlapping overlays and listeners cannot pile up. `disconnect()` is idempotent.

## Events Stimulus

The controller dispatches:

- `ux-driver:pre-connect` — before driver.js is initialized
- `ux-driver:connect` — after the driver instance is created
- `ux-driver:empty` — when `start` or `highlight` resolves no step; detail is `{id}`. No
  driver.js instance is created and the `once` flag is not persisted, so a tour that never
  played can still run later.

## Development

```bash
composer install
vendor/bin/phpunit
composer analyse

cd assets
npm install
npm run typecheck
npm test
npm run build
git diff --exit-code dist
```

`assets/dist` is committed, so CI fails if a fresh `npm run build` diverges from it: rebuild and
commit `dist` with any change to `assets/src`.

## License

This bundle is released under the [MIT license](LICENSE).
