<?php
/**
 * @package    contao-search4you
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
namespace C4Y\Search4you\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class ContaoSearch4youExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );
        $loader->load('services.yaml');
        
        // Register the templates directory with Twig
        $bundlePath = realpath(__DIR__.'/../../');
        $templatesDir = $bundlePath.'/templates';
        
        if (!$container->hasParameter('twig.form.resources')) {
            $container->setParameter('twig.form.resources', []);
        }
        
        // Register the template path
        $container->prependExtensionConfig('twig', [
            'paths' => [$templatesDir => 'Search4you']
        ]);
        
        // Create the directory if it doesn't exist yet
        if (!is_dir($templatesDir)) {
            mkdir($templatesDir, 0777, true);
        }
    }
}