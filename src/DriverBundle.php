<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class DriverBundle extends AbstractBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('twig_component', [
            'anonymous_template_directory' => 'components/',
            'defaults'                     => [
                'Pentiminax\UX\Driver\Twig\Components\\' => [
                    'template_directory' => '@Driver/components/',
                    'name_prefix'        => 'Driver',
                ],
            ],
        ]);

        if ($this->isAssetMapperAvailable($builder)) {
            $builder->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        __DIR__.'/../assets/dist' => '@pentiminax/ux-driver',
                    ],
                ],
            ]);
        }
    }

    private function isAssetMapperAvailable(ContainerBuilder $builder): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        $bundlesMetadata = $builder->getParameter('kernel.bundles_metadata');
        if (!\is_array($bundlesMetadata) || !isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        $frameworkBundle = $bundlesMetadata['FrameworkBundle'];
        if (!\is_array($frameworkBundle) || !isset($frameworkBundle['path'])) {
            return false;
        }

        return is_file($frameworkBundle['path'].'/Resources/config/asset_mapper.php');
    }
}
