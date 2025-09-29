<?php
/**
 * @package    contao-search4you
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
use C4Y\Search4you\EventListener\DataContainer\Search4youModuleListener;

$GLOBALS['TL_DCA']['tl_module']['palettes']['search_module'] =
    '{title_legend},name,type,search4you_featured_category,search4you_rootPage,search4you_hide_tags'
;

$GLOBALS['TL_DCA']['tl_module']['fields']['search4you_featured_category'] = array(
    'inputType' => 'select',
    'eval' => array('tl_class' => 'clr', 'includeBlankOption' => true),
    'foreignKey' => 'tl_search4you_featured_categories.title',
    'sql' => "int(10) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['search4you_rootPage'] = array(
    'inputType' => 'select',
    'eval' => array('tl_class' => 'clr'),
    'options_callback' => [Search4youModuleListener::class, 'getRootPages'],
    'sql' => "int(10) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['search4you_hide_tags'] = array(
    'inputType' => 'checkbox',
    'eval' => array('tl_class' => 'clr'),
    'sql' => "char(1) NOT NULL default ''"
);