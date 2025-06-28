<?php
/**
 * @package    contao-search-lite
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
namespace C4Y\SearchLiteBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use C4Y\SearchLiteBundle\Service\LoupeEngineFactory;


#[AsCallback(table: 'tl_search_lite_featured_items', target: 'config.ondelete')]
class OnFeaturedItemDeleteListener
{
    private $db;

    private LoupeEngineFactory $loupeEngineFactory;

    public function __construct(Connection $db, LoupeEngineFactory $loupeEngineFactory)
    {
        $this->db = $db;
        $this->loupeEngineFactory = $loupeEngineFactory;    
    }

    public function __invoke(DataContainer $dc, int $undoId): void
    {
        if (!$dc->id) {
            return;
        }

        $this->removeWebpageFromLoupe($dc);
    }

    protected function removeWebpageFromLoupe(DataContainer $dc): void
    {
        $engine = $this->loupeEngineFactory->getLoupeEngine();
        $engine->deleteDocument("featured-" . $dc->id);
    }
}