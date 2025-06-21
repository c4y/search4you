<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_page']['fields']['tags'] = [
    'exclude' => true,
    'inputType' => 'cfgTags',
    'eval' => [
        'tagsManager' => 'search_lite_manager', 
        'tagsCreate' => true, 
        'maxItems' => 5, 
        'hideList' => true,
        'tl_class' => 'clr'
    ],
];

PaletteManipulator::create()
    ->addField('tags', 'type', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('regular', 'tl_page') 
;