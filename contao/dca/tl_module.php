<?php

use C4Y\SearchLiteBundle\EventListener\DataContainer\SearchLiteModuleListener;

$GLOBALS['TL_DCA']['tl_module']['palettes']['search_module'] =
    '{title_legend},name,type,search_lite_featured_category,search_lite_rootPage'
;

$GLOBALS['TL_DCA']['tl_module']['fields']['search_lite_featured_category'] = array(
    'inputType' => 'select',
    'eval' => array('tl_class' => 'clr'),
    'foreignKey' => 'tl_search_lite_featured_categories.title',
    'sql' => "int(10) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['search_lite_rootPage'] = array(
    'inputType' => 'select',
    'eval' => array('tl_class' => 'clr'),
    'options_callback' => [SearchLiteModuleListener::class, 'getRootPages'],
    'sql' => "int(10) unsigned NOT NULL default 0"
);