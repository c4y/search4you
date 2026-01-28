<?php
/**
 * @package    search4you
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
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
                ->withSortableAttributes(['is_featured', 'sorting'])
                ->withFilterableAttributes(['tags', 'category', 'origin', 'root']);

            $this->loupe = (new LoupeFactory())->create($this->projectDir . '/var/search_lite', $config);
        }

        return $this->loupe;
    }

    public function reset(): void
    {
        $this->loupe = null;
    }
}
