<?php
/**
 * @package    contao-search-lite
 * @author     Oliver Lohoff <info@contao4you.de>
 * @copyright  Contao4you 2025
 * @license    LGPL-3.0-or-later
 */

 
use Contao\Backend;
use Contao\BackendUser;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_search_lite_featured_items'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'ptable'                      => 'tl_search_lite_featured_categories',
		'markAsCopy'                  => 'title',
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_PARENT,
			'fields'                  => array('sorting'),
			'headerFields'            => array('title', 'url'),
			'panelLayout'             => 'filter;search,limit',
			'defaultSearchField'      => 'title'
		),
		'label' => array
		(
			'fields'                  => array('title', 'url'),
			'format'                  => '%s'
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},title,text,suchtext,url;{invisible_legend:hide},invisible'
	),


	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'sorting' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'title' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'text' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array(
				'mandatory'=>true,
				'tl_class'=>'clr',
				'rte' => 'tinyMCE',
				'allowHtml' => true,
				'preserveTags' => true
			),
			'sql'                     => "text NOT NULL default ''"
		),
		'suchtext' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NOT NULL default ''"
		),
		'url' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('dcaPicker' => true, 'rgxp'=>'url', 'tl_class'=>'clr'),
			'sql'                     => "text NULL",
		),
		'invisible' => array
		(
			'reverseToggle'           => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
	)
);

class tl_search_lite_featured_items extends Backend
{


}
