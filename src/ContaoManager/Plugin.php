<?php
/**
 * @package    contao-search4you
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
namespace C4Y\Search4you\ContaoManager;

use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\CoreBundle\ContaoCoreBundle;
use C4Y\Search4you\ContaoSearch4youBundle;
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
            BundleConfig::create(ContaoSearch4youBundle::class)
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
            ->resolve(__DIR__ . '/../../config/routing.yaml')
            ->load(__DIR__ . '/../../config/routing.yaml');
    }

    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container)
    {
        if ('codefog_tags' === $extensionName && !isset($extensionConfigs[0]['managers']['search4you_tags_manager'])) {
            $extensionConfigs[0]['managers']['search4you_tags_manager'] = [
                'source' => 'tl_page.search_tags',
            ];
        }

        if ('codefog_tags' === $extensionName && !isset($extensionConfigs[0]['managers']['search4you_category_manager'])) {
            $extensionConfigs[0]['managers']['search4you_category_manager'] = [
                'source' => 'tl_page.search_category',
            ];
        }

        if ('codefog_tags' === $extensionName && !isset($extensionConfigs[0]['managers']['search4you_featured_items_tags_manager'])) {
            $extensionConfigs[0]['managers']['search4you_featured_items_tags_manager'] = [
                'source' => 'tl_search4you_featured_items.tags',
            ];
        }

        return $extensionConfigs;
    }
}
