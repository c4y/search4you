<?php
/**
 * @package    contao-search-lite
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
namespace C4Y\SearchLiteBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use C4Y\SearchLiteBundle\Service\LoupeEngineFactory;

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
        $row = $dc->getCurrentRecord();

        if($row['invisible']) {
            $this->removeWebpageFromLoupe($dc);
        } else {
            $this->addWebpageToLoupe($dc);
        }
    }

    protected function removeWebpageFromLoupe(DataContainer $dc): bool
    {
        $engine = $this->loupeEngineFactory->getLoupeEngine();

        $row = $dc->getCurrentRecord();

        $engine->deleteDocument("featured-" . $dc->id);

        return true;
    }

    protected function addWebpageToLoupe(DataContainer $dc): bool
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

        return true;
    }
}