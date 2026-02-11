<?php
/**
 * @package    contao-search-lite
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
namespace C4Y\SearchLiteBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use C4Y\SearchLiteBundle\Service\LoupeEngineFactory;

#[AsCallback(table: 'tl_search_lite_featured_items', target: 'config.onsubmit')]
class FeaturedItemsSubmitCallbackListener
{
    private Connection $db;
    private LoupeEngineFactory $loupeEngineFactory;
    private InsertTagParser $insertTagParser;

    /**
     * @var string
     */
    public function __construct(Connection $db, LoupeEngineFactory $loupeEngineFactory, InsertTagParser $insertTagParser)
    {
        $this->db = $db;
        $this->loupeEngineFactory = $loupeEngineFactory;
        $this->insertTagParser = $insertTagParser;
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

        $content = $this->insertTagParser->replaceInline($row['text']);
        $searchText = strip_tags($this->insertTagParser->replaceInline($row['suchtext']));
        $url = $this->insertTagParser->replaceInline($row['url']);

        $document = [
            'id' => "featured-" . $dc->id,
            'is_featured'=> true,
            'origin' => 'featured',
            'root' => $row['pid'],
            'sorting' => $row['sorting'],
            'url' => $url,
            'title' => $row['title'],
            'category' => null,
            'tags' => null,
            'content' => $content,
            'search' => $searchText
        ];
        
        // Dokument neu hinzufügen (Upsert)
        $result = $engine->addDocument($document);

        return true;
    }
}