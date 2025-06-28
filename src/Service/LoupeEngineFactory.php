<?php

declare(strict_types=1);

namespace C4Y\SearchLiteBundle\Service;

use Loupe\Loupe\Configuration;
use Loupe\Loupe\Loupe;
use Loupe\Loupe\LoupeFactory;

class LoupeEngineFactory
{
    private ?Loupe $loupe = null;
    private string $projectDir;
    private string $environment;

    public function __construct(string $projectDir, string $environment)
    {
        $this->projectDir = $projectDir;
        $this->environment = $environment;
    }

    public function getLoupeEngine(): Loupe
    {
        if (null === $this->loupe) {
            $config = Configuration::create()
                ->withPrimaryKey('id')
                ->withSearchableAttributes(['title', 'search'])
                ->withLanguages(['de', 'en'])
                ->withSortableAttributes(['is_featured'])
                ->withFilterableAttributes(['tags', 'category']);

            $this->loupe = (new LoupeFactory())->create($this->projectDir . '/var/search_lite', $config);
        }

        return $this->loupe;
    }

    public function reset(): void
    {
        $this->loupe = null;
    }
}
