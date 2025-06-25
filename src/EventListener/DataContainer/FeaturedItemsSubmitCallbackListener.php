<?php
// src/EventListener/DataContainer/NewsSubmitCallbackListener.php
namespace C4Y\SearchLiteBundle\EventListener\DataContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\KernelInterface;
use Loupe\Loupe\Configuration;
use Loupe\Loupe\Loupe;
use Loupe\Loupe\LoupeFactory;
use Codefog\TagsBundle\Manager\DefaultManager;

#[AsCallback(table: 'tl_search_lite_featured_items', target: 'config.onsubmit')]
class FeaturedItemsSubmitCallbackListener
{
    private Connection $db;
    private string $projectDir;
    private string $cacheDir;

    /**
     * @var string
     */
    public function __construct(Connection $db, KernelInterface $kernel)
    {
        $this->db = $db;
        $this->projectDir = $kernel->getProjectDir();
        $this->cacheDir = $kernel->getProjectDir() . '/var/search_lite';
    }

    public function __invoke(DataContainer $dc): void
    {
        $this->addWebpageToLoupe($dc);
    }

    private function getLoupeEngine(): Loupe
    {
        $config = Configuration::create()
            ->withPrimaryKey('id')
            ->withSearchableAttributes(['title', 'search', 'is_featured'])
            ->withLanguages(['de', 'en'])
            ->withSortableAttributes(['is_featured'])
            ->withFilterableAttributes(['tags', 'category']);

        $cacheDir = $this->projectDir . '/var/search_lite';
        return (new LoupeFactory())->create($cacheDir, $config);
    }

    private function addWebpageToLoupe(DataContainer $dc): bool
    {
        $engine = $this->getLoupeEngine();

        $record = $dc->getCurrentRecord();

        $document = [
            'id' => "featured-" . $dc->id,
            'is_featured'=> true,
            'url' => $record['url'],
            'title' => $record['title'],
            'category' => null,
            'tags' => null,
            'content' => $record['text'],
            'search' => $record['suchtext']
        ];
        
        // Dokument neu hinzufügen (Upsert)
        $result = $engine->addDocument($document);

        return true;
    }
}