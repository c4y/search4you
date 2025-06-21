<?php

namespace C4Y\SearchLiteBundle\Twig\Loader;

use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Loader\FilesystemLoader;

/**
 * Custom template loader for SearchLite bundle templates.
 */
class SearchLiteTemplateLoader extends FilesystemLoader
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
        
        $this->addSearchLitePaths();
    }

    private function addSearchLitePaths(): void
    {
        $projectDir = $this->kernel->getProjectDir();
        $bundleDevPath = $projectDir . '/bundles/SearchLite/templates';
        $bundleProdPath = $projectDir . '/vendor/c4y/search-lite/templates';
        
        // Try to register dev path if it exists
        if (is_dir($bundleDevPath)) {
            $this->addPath($bundleDevPath, 'SearchLite');
        }
        
        // Try to register prod path if it exists
        if (is_dir($bundleProdPath)) {
            $this->addPath($bundleProdPath, 'SearchLite');
        }
    }
}
