<?php
/**
 * @package    search4you
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
$GLOBALS['BE_MOD']['system']['search_lite_featured_items'] = array
(
	'tables' => array('tl_search_lite_featured_categories', 'tl_search_lite_featured_items')
);

$GLOBALS['TL_MODELS']['tl_search_lite_featured_items'] = \C4Y\SearchLiteBundle\Model\FeaturedItemModel::class;