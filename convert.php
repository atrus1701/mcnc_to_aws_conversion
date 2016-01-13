<?php
/**
 * Copies the dump files from a remote server and imports the contents into a local
 * database.
 * 
 * @package    wordpress-migrate
 * @author     Crystal Barton <atrus1701@gmail.com>
 */


require_once( __DIR__.'/config.php' );
require_once( __DIR__.'/classes/class.database.php' );
require_once( __DIR__.'/classes/class.site.php' );
require_once( __DIR__.'/classes/class.old-site.php' );
require_once( __DIR__.'/classes/class.new-site.php' );
require_once( __DIR__.'/classes/class.blog.php' );


/**
 * The main function of the script.
 */
function main()
{
	global $db, $claspages, $pages, $awssites, $awspages, 
		$claspages_to_sites_slugs, $pages_to_sites_slugs;
	
	clear_log();
	
	
	create_sites();
	
	add_blogs_to_new_sites( $claspages, $claspages_to_sites_slugs );
	add_blogs_to_new_sites( $pages, $pages_to_sites_slugs );
	$awssites->assign_new_blog_ids();
	$awspages->assign_new_blog_ids();
	assign_blog_tables( $claspages );
	assign_blog_tables( $pages );
	$awssites->create_blogs_table( $claspages );
	$awspages->create_blogs_table( $claspages );
	$awssites->create_domain_mapping_table( $claspages );
	$awssites->create_domain_mapping_table( $pages );
	
	disconnect_sites();
}


/**
 * Clear the log file, if one is specified.
 */
if( !function_exists('clear_log') ):
function clear_log()
{
	global $log;
	if( $log ) file_put_contents( $log, '' );
}
endif;


/**
 * Echo text to the screen and a log file, if one is specified.
 * @param   string  $text  The text to display.
 */
if( !function_exists('echo2') ):
function echo2( $text )
{
	global $log;
	echo $text;
	if( $log ) file_put_contents( $log, $text, FILE_APPEND );
}
endif;


/**
 * Prints the header and footer for the script output.
 * @param  string  $text  The action text, for example: Copying files started.
 */
function print_header( $text )
{
	echo2( "\n\n" );
	echo2( "==========================================================================================\n" );
	echo2( $text.' on '.date( 'F j, Y h:i:s A' )."\n" );
	echo2( "==========================================================================================\n" );
	echo2( "\n\n" );
}


/**
 * 
 */
function create_sites()
{
	global $dbhost, $dbusername, $dbpassword, $claspages_dbname, $pages_dbname, $awssites_dbname, $awspages_dbname;
	global $claspages_prefix, $pages_prefix, $awssites_prefix, $awspages_prefix;
	global $db, $claspages, $pages, $awssites, $awspages;
	
	$db        = new Database();
	$claspages = new OldSite( $db, 'clas-pages', $claspages_dbname, $claspages_prefix, 'clas-pages.uncc.edu' );
	$pages     = new OldSite( $db, 'pages', $pages_dbname, $pages_prefix, 'pages.uncc.edu' );
	$awssites  = new NewSite( $db, 'sites', $awssites_dbname, $awssites_prefix, 'sites.uncc.edu' );
	$awspages  = new NewSite( $db, 'pages', $awspages_dbname, $awspages_prefix, 'pages.uncc.edu' );
	
	$db->connect( $dbhost, $dbusername, $dbpassword );
	$claspages->connect( $dbhost, $dbusername, $dbpassword, $claspages_dbname );
	$pages->connect( $dbhost, $dbusername, $dbpassword, $pages_dbname );
	$awssites->connect( $dbhost, $dbusername, $dbpassword, $awssites_dbname );
	$awspages->connect( $dbhost, $dbusername, $dbpassword, $awspages_dbname );
}


/**
 *
 */
function disconnect_sites()
{
	global $db, $claspages, $pages, $awssites, $awspages;
	
	$db->disconnect();
	$claspages->disconnect();
	$pages->disconnect();
	$awssites->disconnect();
	$awspages->disconnect();
}


/**
 *
 */
function add_blogs_to_new_sites( $site, $to_sites_slugs )
{
	global $awssites, $awspages;
	
	$blogs = $site->get_blogs();
	foreach( $blogs as $blog )
	{
		$slug = trim( $blog['path'], '/' );
		if( $slug == '' ) {
			$site->add_base_blog( $blog );
			continue;
		}
	
		if( in_array( $slug, $to_sites_slugs ) ) {
			$awssites->add_blog( $site, $blog );
		} else {
			$awspages->add_blog( $site, $blog );
		}
	}
	$blogs = null;
}


/**
 *
 */
function assign_blog_tables( $site )
{
	global $awssites, $awspages;
	
	$table_list = $site->get_table_list( true );
	foreach( $table_list as $table_name )
	{
		if( strpos( $table_name, 'clas_uncc_' ) === 0 ) continue;
	
		$blog_id = intval( substr( $table_name, 0, strpos( $table_name, '_' ) + 1 ) );
	
		if( $blog_id < 1 )
		{
			$site->add_base_table( $table_name );
			continue;
		}
	
		$awssites->add_blog_table( $site->name, $blog_id, $table_name );
		$awspages->add_blog_table( $site->name, $blog_id, $table_name );
	}
}


//========================================================================================
//============================================================================= MAIN =====

print_header( 'Convert database started' );

extract($config);
main();

print_header( 'Convert database ended' );

