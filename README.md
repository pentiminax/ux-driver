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

## Sécurité — contenu des popovers

driver.js writes `title` and `description` with `innerHTML`. Escaping the value in the Twig
attribute is not enough: the controller reads it back through `dataset`, which decodes it
once, and hands the result straight to `innerHTML`.

The same holds for `progressText` and the three button labels, which driver.js also writes
with `innerHTML`.

The bundle therefore escapes on the PHP side, at the single point where both modes converge
— `Step::toArray()` for the builder and `ux_highlight()`, `titleHtml()`/`descriptionHtml()`
for the components. Any plain string is escaped with
`htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` and rendered as inert text:

```twig
{# renders the literal text <b>Attention</b>, no markup #}
<twig:Driver:Step title="<b>Attention</b>" />
```

Opt in explicitly with `ux_driver_html()` (or a `Twig\Markup` instance from PHP) when the
markup is trusted:

```twig
<twig:Driver:Step :title="ux_driver_html('<b>Attention</b>')" />
```

`ux_driver_html()` disables escaping for that value — never pass user input to it. Escaping
is the bundle's default; **sanitising rich text authored by a user remains the
application's responsibility** (`symfony/html-sanitizer` or an equivalent) before it is
marked as trusted.

## Options globales

Every option below exists as a fluent method on the builder and as a prop of
`<twig:Driver:Tour>`, under the exact Driver.js key. Both modes produce the same
Driver.js config.

| Option / méthode           | Type                    | Défaut driver.js |
|----------------------------|-------------------------|------------------|
| `showProgress`             | `bool`                  | `false`          |
| `animate`                  | `bool`                  | `true`           |
| `smoothScroll`             | `bool`                  | `false`          |
| `allowClose`               | `bool`                  | `true`           |
| `allowScroll`              | `bool`                  | `false`          |
| `allowKeyboardControl`     | `bool`                  | `true`           |
| `disableActiveInteraction` | `bool`                  | `false`          |
| `duration`                 | `int` (ms)              | `500`            |
| `overlayColor`             | `string`                | `#000`           |
| `overlayOpacity`           | `float`                 | `0.7`            |
| `overlayClickBehavior`     | `close` \| `nextStep`   | `close`          |
| `stagePadding`             | `int`                   | `10`             |
| `stageRadius`              | `int`                   | `5`              |
| `popoverClass`             | `string`                | —                |
| `popoverOffset`            | `int`                   | `10`             |
| `showButtons`              | `next\|previous\|close` | all              |
| `disableButtons`           | `next\|previous\|close` | none             |
| `progressText`             | `string`                | `{{current}} of {{total}}` |
| `nextBtnText`              | `string`                | `Next &rarr;`    |
| `prevBtnText`              | `string`                | `&larr; Previous`|
| `doneBtnText`              | `string`                | `Done`           |

`once()` is not a Driver.js option — it is this bundle's `localStorage` persistence.

Callback-valued options (`onPopoverRender`, and `overlayClickBehavior` as a function) are
deliberately out of the PHP surface: they cannot be serialized. Use the
[Stimulus events](#cycle-de-vie) instead.

`progressText`, `nextBtnText`, `prevBtnText` and `doneBtnText` are written by driver.js with
`innerHTML`, exactly like the popover title and description, so they go through the same
escaping (see [Sécurité](#sécurité--contenu-des-popovers)). Wrap them in `ux_driver_html()`
when you need markup — an arrow entity, for instance.

### Localisation et thème

```php
$tour = $this->tourBuilder->create('onboarding')
    ->addStep('#stats', 'Statistiques', 'Suivez vos KPIs')
    ->progressText('Étape {{current}} sur {{total}}')
    ->nextBtnText('Suivant')
    ->prevBtnText('Précédent')
    ->doneBtnText('Terminer')
    ->popoverClass('tour-popover')
    ->stageRadius(12)
    ->overlayColor('#0f172a')
    ->overlayOpacity(0.6);
```

The same tour declared with the component:

```twig
<twig:Driver:Tour
    id="onboarding"
    progressText="Étape {{ '{{current}}' }} sur {{ '{{total}}' }}"
    nextBtnText="Suivant"
    prevBtnText="Précédent"
    doneBtnText="Terminer"
    popoverClass="tour-popover"
    :stageRadius="12"
    overlayColor="#0f172a"
    :overlayOpacity="0.6"
>
    …
</twig:Driver:Tour>
```

Hide or disable buttons — pass the Driver.js names, or the `Button` enum from PHP:

```twig
<twig:Driver:Tour id="onboarding" :showButtons="['next', 'previous']" :disableButtons="['close']" />
```

```php
use Pentiminax\UX\Driver\Enum\Button;

$tour->showButtons(Button::Next, Button::Previous)->disableButtons(Button::Close);
```

`popoverClass` targets the popover wrapper, so theming stays plain CSS:

```css
.tour-popover { --driver-popover-bg: #0f172a; color: #f8fafc; }
```

### Valeurs invalides

Options with a finite value set are backed by PHP enums
(`Pentiminax\UX\Driver\Enum\Button`, `Pentiminax\UX\Driver\Enum\OverlayClickBehavior`).
An unknown value raises a `\ValueError` while the page renders, in both modes — never a
silently ignored Driver.js option:

```
"finish" is not a valid backing value for enum Pentiminax\UX\Driver\Enum\Button
```

## Options par étape

Every option below overrides its global counterpart for a single step, again under the exact
Driver.js key. `Step::toArray()` and `<twig:Driver:Step>` build the same payload — the
component instantiates the very same model, so both modes validate identically.

| Option                     | Type                    | Rôle                                              |
|----------------------------|-------------------------|---------------------------------------------------|
| `popoverClass`             | `string`                | classe CSS du popover de cette étape              |
| `showButtons`              | `next\|previous\|close` | boutons affichés                                  |
| `disableButtons`           | `next\|previous\|close` | boutons désactivés                                |
| `showProgress`             | `bool`                  | compteur de progression                           |
| `progressText`             | `string`                | gabarit du compteur                               |
| `nextBtnText`              | `string`                | libellé du bouton suivant                         |
| `prevBtnText`              | `string`                | libellé du bouton précédent                       |
| `doneBtnText`              | `string`                | libellé du bouton final                           |
| `disableActiveInteraction` | `bool`                  | rend la cible non cliquable                       |
| `advanceOnClick`           | `bool`                  | cliquer la cible passe à l'étape suivante         |
| `skipMissingElement`       | `bool`                  | ignore l'étape si la cible est absente            |
| `waitForElement`           | `int` (ms)              | attend l'apparition de la cible avant d'abandonner |
| `data`                     | `array`                 | données arbitraires relayées dans les événements  |

From the builder, they go in the `options` array; `side` and `align` keep their dedicated
arguments:

```php
$tour->addStep('#cart', 'Panier', 'Vos articles', side: 'top', options: [
    'popoverClass'   => 'promo',
    'showButtons'    => [Button::Next],
    'nextBtnText'    => 'Suivant',
    'advanceOnClick' => true,
    'data'           => ['tracking' => 'cart'],
]);
```

An unknown key raises an `\InvalidArgumentException` listing the accepted options — the
callback-valued Driver.js options (`onNextClick`, `onPopoverRender`, …) are not serializable
and belong to the [Stimulus events](#cycle-de-vie).

The same step declared with the component:

```twig
<twig:Driver:Step
    :order="1"
    title="Panier"
    description="Vos articles"
    side="top"
    popoverClass="promo"
    :showButtons="['next']"
    nextBtnText="Suivant"
    :advanceOnClick="true"
    :data="{tracking: 'cart'}"
/>
```

### Étape centrée

A step with no target centers its popover on the screen — the usual opener for a tour. Omit
`element` from the builder, or pass `centered` to the component:

```php
$tour->addStep(title: 'Bienvenue', description: 'Découvrons l\'application');
```

```twig
<twig:Driver:Step :order="1" :centered="true" title="Bienvenue" description="Découvrons l'application" />
```

The component then renders an inert `<template>` instead of an empty `<div>`: the attributes
still travel to the controller, but nothing is added to the page layout.

### Cibles asynchrones (Turbo, modales)

A target rendered by a Turbo Frame or opened in a modal does not exist when the tour starts.
`waitForElement` polls for it, `skipMissingElement` moves on if it never shows up:

```twig
<twig:Driver:Step :order="2" title="Détail" tag="turbo-frame" id="detail"
                  :waitForElement="2000" :skipMissingElement="true" />
```

```php
$tour->addStep('#detail', 'Détail', options: [
    'waitForElement'     => 2000,
    'skipMissingElement' => true,
]);
```

Without `skipMissingElement`, a target that never appears leaves the tour stuck on that step.

## Persistance — `once()`

### Sémantique

`once()` means **do not replay after completion**, not "do not replay after the first
display". The flag is persisted when the tour reaches its end — the `ux-driver:done` event,
which driver.js also raises on the right arrow of the last step, on `advanceOnClick` and on
`overlayClickBehavior: 'nextStep'`.

Abandoning a tour — the close button, Escape, or anything else that destroys the instance
early — persists nothing, so the tour plays again on the next visit. Preventing
`ux-driver:done` also prevents the flag from being written.

Use `forgetSeen(id)` (below) to re-arm a completed tour.

### Stockage indisponible

`once()` stores its flag under `ux-driver:seen:<id>`. Every access to that store is guarded:
a sandboxed iframe, blocked cookies, some private-browsing modes or a full quota make
`window.localStorage` — or `setItem` — throw, and an unguarded read would break the whole
controller.

Fallback when the store is unreachable: the tour is treated as **never seen**, so it plays
again on every page load rather than never playing at all. Nothing is logged and no
exception escapes.

The bundle also exports `forgetSeen(id)` from `@pentiminax/ux-driver/tour-utils` to clear the
flag and re-arm a `once()` tour.

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

### Cycle de vie

driver.js only accepts callbacks, which PHP and Twig cannot serialize. Every driver.js hook
is therefore bridged to a Stimulus event, so a tour declared server-side stays observable
from your own JavaScript.

| Event                          | driver.js hook       | Cancelable | Default action        |
|--------------------------------|----------------------|------------|-----------------------|
| `ux-driver:highlight-started`  | `onHighlightStarted` | no         | —                     |
| `ux-driver:highlighted`        | `onHighlighted`      | no         | —                     |
| `ux-driver:deselected`         | `onDeselected`       | no         | —                     |
| `ux-driver:destroyed`          | `onDestroyed`        | no         | —                     |
| `ux-driver:next`               | `onNextClick`        | yes        | `moveNext()`          |
| `ux-driver:previous`           | `onPrevClick`        | yes        | `movePrevious()`      |
| `ux-driver:close`              | `onCloseClick`       | yes        | `destroy()`           |
| `ux-driver:done`               | `onDoneClick`        | yes        | `destroy()`           |
| `ux-driver:destroy-started`    | `onDestroyStarted`   | yes        | `destroy()`           |

Every detail carries the same shape: `{tourId, index, step, element, driver}`.

Observing changes nothing — the default behaviour still runs. Call `preventDefault()` on a
cancelable event to take over: the tour then stays where it is until you drive it yourself.

```js
document.addEventListener('ux-driver:next', (event) => {
    if (!formIsValid()) {
        event.preventDefault(); // hold the tour on the current step
    }
});
```

### Actions Stimulus

Drive a running tour from your markup. All of them no-op when no tour is running.

```twig
<button data-action="pentiminax--ux-driver--tour#next">Suivant</button>
<button data-action="pentiminax--ux-driver--tour#previous">Précédent</button>
<button data-action="pentiminax--ux-driver--tour#refresh">Repositionner</button>
<button data-action="pentiminax--ux-driver--tour#destroy">Fermer</button>

<button data-action="pentiminax--ux-driver--tour#moveTo"
        data-pentiminax--ux-driver--tour-index-param="2">Aller à l'étape 3</button>

<button data-action="pentiminax--ux-driver--tour#start"
        data-pentiminax--ux-driver--tour-index-param="1">Reprendre à l'étape 2</button>
```

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
