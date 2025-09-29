<?php

namespace C4Y\Search4you\Twig\Loader;

use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Loader\FilesystemLoader;

/**
 * Custom template loader for Search4you bundle templates.
 */
class Search4youTemplateLoader extends FilesystemLoader
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        
        // Initialize parent with empty paths array, we'll add them ourselves
        parent::__construct([]);
        
        $this->addSearch4youPaths();
    }

    private function addSearch4youPaths(): void
    {
        $projectDir = $this->kernel->getProjectDir();
        $bundleDevPath = $projectDir . '/bundles/Search4you/templates';
        $bundleProdPath = $projectDir . '/vendor/c4y/search4you/templates';
        
        // Try to register dev path if it exists
        if (is_dir($bundleDevPath)) {
            $this->addPath($bundleDevPath, 'Search4you');
        }
        
        // Try to register prod path if it exists
        if (is_dir($bundleProdPath)) {
            $this->addPath($bundleProdPath, 'Search4you');
        }
    }
}
