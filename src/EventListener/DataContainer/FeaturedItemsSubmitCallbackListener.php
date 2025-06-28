<?php
// src/EventListener/DataContainer/NewsSubmitCallbackListener.php
namespace C4Y\SearchLiteBundle\EventListener\DataContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use C4Y\SearchLiteBundle\Service\LoupeEngineFactory;
use Loupe\Loupe\Loupe;

#[AsCallback(table: 'tl_search_lite_featured_items', target: 'config.onsubmit')]
class FeaturedItemsSubmitCallbackListener
{
    private Connection $db;
    private LoupeEngineFactory $loupeEngineFactory;

    /**
     * @var string
     */
    public function __construct(Connection $db, LoupeEngineFactory $loupeEngineFactory)
    {
        $this->db = $db;
        $this->loupeEngineFactory = $loupeEngineFactory;
    }

    public function __invoke(DataContainer $dc): void
    {
        $this->addWebpageToLoupe($dc);
    }

    private function addWebpageToLoupe(DataContainer $dc): bool
    {
        $engine = $this->loupeEngineFactory->getLoupeEngine();

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