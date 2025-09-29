<?php
/**
 * @package    contao-search4you
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
namespace C4Y\Search4you;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use C4Y\Search4you\DependencyInjection\ContaoSearch4youExtension;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class ContaoSearch4youBundle extends Bundle implements BundleInterface
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
    
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        
        // Ensure Twig knows about our template directory
        $container->loadFromExtension('twig', [
            'paths' => [
                $this->getPath() . '/templates' => 'Search4you',
            ],
        ]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new ContaoSearch4youExtension();
        }
        
        return $this->extension;
    }
}