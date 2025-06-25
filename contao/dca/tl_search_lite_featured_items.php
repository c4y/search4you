<?php

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
			'mode'                    => DataContainer::MODE_SORTED,
			'fields'                  => array('title'),
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
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
		'default'                     => '{title_legend},title,text,suchtext,url'
	),


	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
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
			'eval'                    => array('mandatory'=>true, 'tl_class'=>'clr'),
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
	)
);

class tl_search_lite_featured_items extends Backend
{


}
