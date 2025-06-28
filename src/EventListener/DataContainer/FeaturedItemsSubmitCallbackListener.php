<?php
namespace C4Y\SearchLiteBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use C4Y\SearchLiteBundle\Service\LoupeEngineFactory;
use C4Y\SearchLiteBundle\Model\FeaturedItemModel;

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

        $row = $dc->getCurrentRecord();

        $document = [
            'id' => "featured-" . $dc->id,
            'is_featured'=> true,
            'origin' => 'featured',
            'root' => $row['pid'],
            'sorting' => $row['sorting'],
            'url' => $row['url'],
            'title' => $row['title'],
            'category' => null,
            'tags' => null,
            'content' => $row['text'],
            'search' => $row['suchtext']
        ];
        
        // Dokument neu hinzufügen (Upsert)
        $result = $engine->addDocument($document);

        $this->checkAndUpdateSortingOfItems($row['pid']);

        return true;
    }

    protected function checkAndUpdateSortingOfItems(int $pid) {
        $featuredItems = FeaturedItemModel::findByPid($pid);
        $engine = $this->loupeEngineFactory->getLoupeEngine();
        
        foreach($featuredItems as $item) {
            $document = $engine->getDocument("featured-" . $item->id);
            if($item->sorting != $document['sorting']) {
                $document['sorting'] = $item->sorting;
                $engine->addDocument($document);
            }
        }
        
    }
}