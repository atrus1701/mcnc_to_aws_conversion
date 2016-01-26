<?php

// Configuration settings.
$config = array(
	
	// Database settings for local database.
	'dbhost'              => '__common_db_host__',
	'dbusername'          => '__db_username__',
	'dbpassword'          => '__db_password__',
	
	// Database names.
	'claspages_dbname'    => 'mcnc_clas_pages',
	'pages_dbname'        => 'mcnc_pages',
	'this_dbname'         => '__new_db_name__',
	
	// WordPress table prefix.
	'claspages_prefix'    => '__claspages_prefix__',
	'pages_prefix'        => '__pages_prefix__',
	'this_prefix'         => '__new_prefix__',
	
	// IP addresses.
	'claspages_ipaddress' => '152.46.254.20',
	'pages_ipaddress'     => '152.46.254.20',
	'this_ipaddress'      => '__new_ip_address__',
	
	// Paths.
	'claspages_path'      => '/var/www/backup/claspages/public_html',
	'pages_path'          => '/var/www/backup/pages/public_html',
	'this_path'           => '/var/www/public_html',

	// Names.
	'claspages_name'      => 'claspages',
	'pages_name'          => 'pages',
	'this_name'           => '__new_name__',
	
	// Domains.
	'claspages_domain'    => 'clas-pages.uncc.edu',
	'pages_domain'        => 'pages.uncc.edu',
	'this_domain'         => '__new_domain__',
	
	// Users.
	'claspages_user'      => '__claspages_username__',
	'pages_user'          => '__pages_username__',
	'this_user'           => '__this_username__',
	
	// The relative or full path to the log file.
	'log'                 => 'convert_log_' . date( 'Ymd_His' ) . '.txt',
	
	// sites from CLAS-PAGES to move to sites.uncc.edu
	'claspages_to_sites_slugs' => array(
		'seeds',
		'homelessness',
		'rgpa',
		'projectmosaic',
		'jbp',
		'editorethics',
		'weather',
		'ossi',
		'filmfest',
		'connections',
		'exchange',
		'observatory',
		'inside-clas',
		'lrc',
		'ptgi',
		'rachel',
		'vpa',
		'gardens',
		'thinkingmatters',
		'digmountzion',
		'nano-dynamics',
	),
	
	// sites from PAGES to move to sites.uncc.edu
	'pages_to_sites_slugs' => array(
	),
);

