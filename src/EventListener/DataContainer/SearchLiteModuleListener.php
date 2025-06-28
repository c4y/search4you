<?php

namespace C4Y\SearchLiteBundle\EventListener\DataContainer;

use Contao\PageModel;
use Contao\DataContainer;

class SearchLiteModuleListener {

    public function __construct()
    {
    }

    public function getRootPages(DataContainer $dc = null) {
        $pages = [];
        $rootPages = PageModel::findPublishedRootPages();
        
        if ($rootPages !== null) {
            while ($rootPages->next()) {
                $pages[$rootPages->id] = $rootPages->title;
            }
        }
        
        return $pages;
    }
}