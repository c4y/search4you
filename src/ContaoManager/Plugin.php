<?php
namespace C4Y\SearchLiteBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\CoreBundle\ContaoCoreBundle;
use C4Y\SearchLiteBundle\ContaoSearchLiteBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Codefog\TagsBundle\CodefogTagsBundle;

class Plugin implements BundlePluginInterface, RoutingPluginInterface, ExtensionPluginInterface
{

    // dies hier ist obligatorisch
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(ContaoSearchLiteBundle::class)
                ->setLoadAfter(
                    [ContaoCoreBundle::class, CodefogTagsBundle::class]
                )
        ];
    }

    // dies hier wird nur benötigt, wenn eigene Routen außerhalb von Contao benötigt
    // werden, z.B. für eine eigene API (ließe sich aber auch mit Modulen oder Inhalts-
    // elementen lösen) oder Ajax-Requests
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        return $resolver
            ->resolve(__DIR__ . '/../../config/routing.yml')
            ->load(__DIR__ . '/../../config/routing.yml');
    }

    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container)
    {
        if ('codefog_tags' === $extensionName && !isset($extensionConfigs[0]['managers']['terminal42_node'])) {
            $extensionConfigs[0]['managers']['search_lite_manager'] = [
                'source' => 'tl_page.tags',
            ];
        }

        return $extensionConfigs;
    }
}
