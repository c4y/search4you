<?php
/**
 * @package    contao-search4you
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_page']['fields']['search_category'] = [
    'exclude' => true,
    'inputType' => 'cfgTags',
    'eval' => [
        'tagsManager' => 'search4you_category_manager', 
        'tagsCreate' => true, 
        'maxItems' => 1,
        'hideList' => true,
        'tl_class' => 'clr w50'
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['search_tags'] = [
    'exclude' => true,
    'inputType' => 'cfgTags',
    'eval' => [
        'tagsManager' => 'search4you_tags_manager', 
        'tagsCreate' => true, 
        'maxItems' => 5, 
        'hideList' => true,
        'tl_class' => 'w50'
    ],
];

PaletteManipulator::create()
    ->addField('search_category', 'type', PaletteManipulator::POSITION_AFTER)
    ->addField('search_tags', 'search_category', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('regular', 'tl_page') 
;