<?php
/**
 * @package    contao-search4you
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
namespace C4Y\Search4you\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\KernelInterface;
use Codefog\TagsBundle\Manager\DefaultManager;
use C4Y\Search4you\Service\LoupeEngineFactory;
use Contao\System;

#[AsCallback(table: 'tl_search4you_featured_items', target: 'config.onsubmit')]
class FeaturedItemsSubmitCallbackListener
{
    private Connection $db;
    private LoupeEngineFactory $loupeEngineFactory;

    /**
     * @var DefaultManager
     */
    private $tagsManager;

    /**
     * @var string
     */
    public function __construct(KernelInterface $kernel, Connection $db, LoupeEngineFactory $loupeEngineFactory)
    {
        $this->db = $db;
        $this->loupeEngineFactory = $loupeEngineFactory;
        $this->tagsManager = $kernel->getContainer()->get('codefog_tags.manager.search4you_featured_items_tags_manager');
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
        $insertTagParser = System::getContainer()->get('contao.insert_tag.parser');
        
        $row = $dc->getCurrentRecord();
        
        $tagsCriteria = $this->tagsManager->createTagCriteria()->setSourceIds([$row['id']]);
        $tags = $this->tagsManager->getTagFinder()->findMultiple($tagsCriteria);

        $tagNames = array_map(function ($tag) {
            return $tag->getName();
        }, $tags);

        $url = $insertTagParser->replace($row['url']);

        $document = [
            'id' => "featured-" . $dc->id,
            'is_featured'=> true,
            'origin' => 'featured',
            'root' => $row['pid'],
            'sorting' => $row['sorting'],
            'url' => $url,
            'title' => $row['title'],
            'category' => null,
            'tags' => $tagNames,
            'content' => $row['text'],
            'search' => $row['suchtext'],
            'css' => $row['css']
        ];
        
        // Dokument neu hinzufügen (Upsert)
        $result = $engine->addDocument($document);

        return true;
    }
}