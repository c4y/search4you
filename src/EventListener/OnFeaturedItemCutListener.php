<?php
/**
 * @package    contao-search4you
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
namespace C4Y\Search4you\EventListener;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Symfony\Component\HttpFoundation\RequestStack;
use C4Y\Search4you\Service\LoupeEngineFactory;
use C4Y\Search4you\Model\FeaturedItemModel;

#[AsCallback(table: 'tl_search4you_featured_items', target: 'config.oncut')]
class OnFeaturedItemCutListener
{
    private $requestStack;

    private LoupeEngineFactory $loupeEngineFactory;

    public function __construct(RequestStack $requestStack, LoupeEngineFactory $loupeEngineFactory)
    {
        $this->requestStack = $requestStack;
        $this->loupeEngineFactory = $loupeEngineFactory;
    }

    public function __invoke(DataContainer $dc): void
    {
        $this->checkAndUpdateSortingOfItems($dc->getCurrentRecord()['pid']);
    }

    protected function checkAndUpdateSortingOfItems($pid) {
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