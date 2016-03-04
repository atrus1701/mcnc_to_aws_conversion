<?php
/**
 * Copies the dump files from a remote server and imports the contents into a local
 * database.
 * 
 * @package    wordpress-migrate
 * @author     Crystal Barton <atrus1701@gmail.com>
 */

ini_set( 'memory_limit', '768M' );
ini_set( 'max_execution_time', 0 );

require_once( __DIR__.'/config.php' );
require_once( __DIR__.'/classes/class.database.php' );
require_once( __DIR__.'/classes/class.site.php' );
require_once( __DIR__.'/classes/class.old-site.php' );
require_once( __DIR__.'/classes/class.new-site.php' );
require_once( __DIR__.'/classes/class.blog.php' );
require_once( __DIR__.'/classes/class.user.php' );


/**
 * The main function of the script.
 */
function main()
{
	global $db, $claspages, $pages, $thissite, 
		$claspages_to_sites_slugs, $pages_to_sites_slugs;
	
	create_sites();
	
	store_blogs_information();
	store_users_information();
	
	find_missing_tables();
	
	disconnect_sites();
}
function store_blogs_information()
{
	global $claspages, $claspages_to_sites_slugs;
	global $pages, $pages_to_sites_slugs;	
	echo2( "\n" );
	add_blogs_to_new_sites( $claspages, $claspages_to_sites_slugs );
	add_blogs_to_new_sites( $pages, $pages_to_sites_slugs );
	assign_new_blog_ids();
	assign_blog_uploads_urls();
	assign_base_blog();
}
function store_users_information()
{
	echo2( "\n" );
	assign_site_users();
	assign_new_user_ids();
}
function add_blogs_to_new_sites( $site, $to_sites_slugs )
{
	global $thissite;
	echo2( "Add blogs from old site '{$site->name}'..." );
	
	$blogs = $site->get_blogs();
	foreach( $blogs as $blog )
	{
		$slug = trim( $blog['path'], '/' );
		if( $slug == '' ) {
			$site->add_base_blog( $blog );
			continue;
		}
	
		if( in_array( $slug, $to_sites_slugs ) ) {
			if( $thissite->name == 'sites' ) {
				$thissite->add_blog( $site, $blog );
			}
		} else {
			if( $thissite->name == 'pages' ) {
				$thissite->add_blog( $site, $blog );
			}
		}
	}
	$blogs = null;
	
	echo2( "done.\n" );
}
function assign_new_blog_ids()
{
	global $thissite;
	echo2( "Assigning new blog ids for new site '{$thissite->name}'..." );
	$thissite->assign_new_blog_ids();
	echo2( "done.\n" );
}
function assign_blog_uploads_urls()
{
	global $thissite;
	echo2( "Assigning uploads urls for new site '{$thissite->name}'..." );
	$thissite->assign_blog_uploads_urls();
	echo2( "done.\n" );
}
function assign_blog_tables()
{
	global $thissite;
	echo2( "Assign blog tables from old site '{$thissite->name}'..." );
	
	$table_list = $thissite->get_table_list( true );
	foreach( $table_list as $table_name )
	{
		if( strpos( $table_name, 'clas_uncc_' ) === 0 ) continue;
	
		$blog_id = intval( substr( $table_name, 0, strpos( $table_name, '_' ) + 1 ) );
	
		if( $blog_id < 1 )
		{
			$thissite->add_base_table( $table_name );
			continue;
		}
	
		$thissite->add_blog_table( $thissite->name, $blog_id, $table_name );
	}
	
	echo2( "done.\n" );
}
function assign_base_blog()
{
	global $claspages, $pages, $thissite;
	echo2( "Assigning base blogs..." );
	
	switch( $thissite->name )
	{
		case 'sites':
			$thissite->assign_base_blog( $claspages );
			break;
		case 'pages':
			$thissite->assign_base_blog( $pages );
			break;
		default:
			echo2( "error.\n" );
			return;
			break;
	}
	
	echo2( "done.\n" );
}
function assign_site_users()
{
	global $thissite;
	echo2( "Assign users to new site '{$thissite->name}'..." );
	$thissite->store_users();
	$thissite->store_usermeta();
	echo2( "done.\n" );
}
function assign_new_user_ids()
{
	global $thissite;
	echo2( "Assign new user ids for new site '{$thissite->name}'..." );
	$thissite->assign_new_user_ids();
	echo2( "done.\n" );
}
function find_missing_tables()
{
	global $db, $claspages, $pages, $thissite;
	echo2( "Finding missing tables..." );
	$thissite_tables = $thissite->find_missing_tables();
	echo2( "done." );
}
function clear_log()
{
	global $log;
	if( $log ) file_put_contents( $log, '' );
}
function echo2( $text )
{
	global $log;
	echo $text;
	if( $log ) file_put_contents( $log, $text, FILE_APPEND );
}
function script_die()
{
	echo2( "\n\n" );
	$args = func_get_args();
	foreach( $args as $text )
	{
		echo2( $text."\n" );
	}
	echo2( "\n\n" );
	die();
}
function print_header( $text )
{
	echo2( "\n\n" );
	echo2( "==========================================================================================\n" );
	echo2( $text.' on '.date( 'F j, Y h:i:s A' )."\n" );
	echo2( "==========================================================================================\n" );
	echo2( "\n\n" );
}
function create_sites()
{
	global $dbhost, $dbusername, $dbpassword;
	global $claspages_name, $pages_name, $this_name;
	global $claspages_dbname, $pages_dbname, $this_dbname;
	global $claspages_prefix, $pages_prefix, $this_prefix;
	global $claspages_domain, $pages_domain, $this_domain;
	global $claspages_ipaddress, $pages_ipaddress, $this_ipaddress;
	global $claspages_path, $pages_path, $this_path;
	global $claspages_user, $pages_user, $this_user;
	global $db, $claspages, $pages, $thissite;
	echo2( "Creating sites..." );
	
	$db        = new Database();
	$claspages = new OldSite( $db, $claspages_name, $claspages_dbname, $claspages_prefix, $claspages_domain, $claspages_ipaddress, $claspages_path, $claspages_user );
	$pages     = new OldSite( $db, $pages_name, $pages_dbname, $pages_prefix, $pages_domain, $pages_ipaddress, $pages_path, $pages_user );
	$thissite  = new NewSite( $db, $this_name, $this_dbname, $this_prefix, $this_domain, $this_ipaddress, $this_path, $this_user );
	
	$db->connect( $dbhost, $dbusername, $dbpassword );
	$claspages->connect( $dbhost, $dbusername, $dbpassword, $claspages_dbname );
	$pages->connect( $dbhost, $dbusername, $dbpassword, $pages_dbname );
	$thissite->connect( $dbhost, $dbusername, $dbpassword, $this_dbname );
	
	echo2( "done.\n" );
}
function disconnect_sites()
{
	global $db, $claspages, $pages, $thissite;
	echo2( "\n" );
	echo2( "Disconnecting sites..." );
	
	$db->disconnect();
	$claspages->disconnect();
	$pages->disconnect();
	$thissite->disconnect();
	
	echo2( "done.\n" );
}



//========================================================================================
//============================================================================= MAIN =====

clear_log();
print_header( 'Find Missing Tables started' );

$config['log'] = 'find_missing_tables_log_' . date( 'Ymd_His' ) . '.txt';
extract($config);
main();

print_header( 'Find Missing Tables ended' );

