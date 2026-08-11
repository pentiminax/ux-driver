<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Pentiminax\UX\Driver\Builder\TourBuilder;
use Pentiminax\UX\Driver\Twig\Components\Step;
use Pentiminax\UX\Driver\Twig\Components\Tour;
use Pentiminax\UX\Driver\Twig\UXDriverExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set(TourBuilder::class)
        ->private();

    $services
        ->set(UXDriverExtension::class)
        ->arg('$stimulus', service('stimulus.helper'))
        ->arg('$tourBuilder', service(TourBuilder::class))
        ->tag('twig.extension')
        ->private();

    $services
        ->set(Tour::class)
        ->tag('twig.component', [
            'key'                 => 'Driver:Tour',
            'expose_public_props' => true,
            'attributes_var'      => 'attributes',
        ])
        ->public();

    $services
        ->set(Step::class)
        ->tag('twig.component', [
            'key'                 => 'Driver:Step',
            'expose_public_props' => true,
            'attributes_var'      => 'attributes',
        ])
        ->public();
};
