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
require_once( __DIR__.'/classes/class.user.php' );


/**
 * The main function of the script.
 */
function main()
{
	global $db, $claspages, $pages, $awssites, $awspages, 
		$claspages_to_sites_slugs, $pages_to_sites_slugs;
	
	clear_log();
	create_table_sites();
	
//----------------------------------------
// Store blogs information
//----------------------------------------

	add_blogs_to_new_sites( $claspages, $claspages_to_sites_slugs );
	add_blogs_to_new_sites( $pages, $pages_to_sites_slugs );
	assign_new_blog_ids( $awssites );
	assign_new_blog_ids( $awspages );
	assign_blog_tables( $claspages );
	assign_blog_tables( $pages );

//----------------------------------------
// Store users information
//----------------------------------------
	
	assign_site_users( $awssites );
	assign_site_users( $awspages );
	assign_new_user_ids( $awssites );
	assign_new_user_ids( $awspages );

//----------------------------------------
// Site setup tables
//----------------------------------------
	
//	create_table_site( $awssites );
//	create_table_site( $awspages );
//	create_table_sitemeta( $awssites );
//	create_table_sitemeta( $awspages );
	create_table_blogs( $awssites );
	create_table_blogs( $awspages );
	create_table_domain_mapping( $awssites );
	create_table_users( $awssites );
	create_table_users( $awspages );
	create_table_usermeta( $awssites );
	create_table_usermeta( $awspages );

//	create_table_blog_versions( $awssites );
//	create_table_blog_versions( $awspages );
//	create_table_registration_log( $awssites );
//	create_table_registration_log( $awspages );
//	create_table_signups( $awssites );
//	create_table_signups( $awspages );

//----------------------------------------
// Blog-specific tables
//----------------------------------------

//	create_table_options( $awssites );
//	create_table_options( $awspages );
//  create_table_posts( $awssites );
//  create_table_posts( $awspages );
//  create_table_postmeta( $awssites );
//	create_table_postmeta( $awspages );
//	create_table_comments( $awssites );
//	create_table_comments( $awspages );
//	create_table_commentmeta( $awssites );
//	create_table_commentmeta( $awspages );
//	create_table_links( $awssites );
//	create_table_links( $awspages );
//	create_table_terms( $awssites );
//	create_table_terms( $awspages );
//	create_table_term_taxonomy( $awssites );
//	create_table_term_taxonomy( $awspages );
//	create_table_term_relationships( $awssites );
//	create_table_term_relationships( $awspages );
//	create_table_termmeta( $awssites );
//	create_table_termmeta( $awspages );

//	create_table_frm_forms( $awssites );
//	create_table_frm_forms( $awspages );
//	create_table_frm_fields( $awssites );
//	create_table_frm_fields( $awspages );
//	create_table_frm_items( $awssites );
//	create_table_frm_items( $awspages );
//	create_table_frm_item_metas( $awssites );
//	create_table_frm_item_metas( $awspages );

//	create_table_wpmm_subscribers( $awssites );
//	create_table_wpmm_subscribers( $awspages );

//	create_table_ngg_album( $awssites );
//	create_table_ngg_album( $awspages );
//	create_table_ngg_gallery( $awssites );
//	create_table_ngg_gallery( $awspages );
//	create_table_ngg_pictures( $awssites );
//	create_table_ngg_pictures( $awspages );

//	create_table_redirection_404( $awssites );
//	create_table_redirection_404( $awspages );
//	create_table_redirection_groups( $awssites );
//	create_table_redirection_groups( $awspages );
//	create_table_redirection_items( $awssites );
//	create_table_redirection_items( $awspages );
//	create_table_redirection_logs( $awssites );
//	create_table_redirection_logs( $awspages );
//	create_table_redirection_modules( $awssites );
//	create_table_redirection_modules( $awspages );

//----------------------------------------
// One-off tables
//----------------------------------------

//	create_table_batch_create_table_queue( $awssites );
//	create_table_batch_create_table_queue( $awspages );
//	create_table_batch_create_table_queuemeta( $awssites );
//	create_table_batch_create_table_queuemeta( $awspages );

//	create_table_frmpro_copies( $awssites );
//	create_table_frmpro_copies( $awspages );

//	create_table_gaplus_login( $awssites );
//	create_table_gaplus_login( $awspages );

//	create_table_itsec_lockouts( $awssites );
//	create_table_itsec_lockouts( $awspages );
//	create_table_itsec_log( $awssites );
//	create_table_itsec_log( $awspages );
//	create_table_itsec_temp( $awssites );
//	create_table_itsec_temp( $awspages );

//	create_table_nbt_categories_relationships_table( $awssites );
//	create_table_nbt_categories_relationships_table( $awspages );
//	create_table_nbt_templates( $awssites );
//	create_table_nbt_templates( $awspages );
//	create_table_nbt_templates_categories( $awssites );
//	create_table_nbt_templates_categories( $awspages );

//	create_table_wiki_subscriptions( $awssites );
//	create_table_wiki_subscriptions( $awspages );

//	create_table_orghub_category( $awssites );
//	create_table_orghub_category( $awspages );
//	create_table_orghub_connections( $awssites );
//	create_table_orghub_connections( $awspages );
//	create_table_orghub_site( $awssites );
//	create_table_orghub_site( $awspages );
//	create_table_orghub_type( $awssites );
//	create_table_orghub_type( $awspages );
//	create_table_orghub_upload( $awssites );
//	create_table_orghub_upload( $awspages );
//	create_table_orghub_user( $awssites );
//	create_table_orghub_user( $awspages );

//	create_table_tt_site( $awssites );
//	create_table_tt_site( $awspages );

//	create_table_smackcsv_line_log( $awssites );
//	create_table_smackcsv_pie_log( $awspages );

	disconnect_sites();
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
function create_table_sites()
{
	echo2( "Creating sites..." );
	
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
	
	echo2( "done.\n" );
}
function disconnect_sites()
{
	echo2( "Disconnecting sites..." );
	
	global $db, $claspages, $pages, $awssites, $awspages;
	
	$db->disconnect();
	$claspages->disconnect();
	$pages->disconnect();
	$awssites->disconnect();
	$awspages->disconnect();
	
	echo2( "done.\n" );
}
function add_blogs_to_new_sites( $site, $to_sites_slugs )
{
	echo2( "Add blogs from old site '{$site->name}'..." );
	
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
	
	echo2( "done.\n" );
}
function assign_new_blog_ids( $site )
{
	echo2( "Assigning new blog ids for new site '{$site->name}'..." );
	
	$site->assign_new_blog_ids();
	
	echo2( "done.\n" );
}
function assign_blog_tables( $site )
{
	echo2( "Assign blog tables from old site '{$site->name}'..." );
	
	global $awssites, $awspages;
	
	$table_list = $site->get_table_list( true );
	foreach( $table_list as $table_name )
	{
		if( strpos( $table_name, 'clas_uncc_' ) === 0 ) continue;
	
		$blog_id = intval( substr( $table_name, 0, strpos( $table_name, '_' ) + 1 ) );
	
		if( $blog_id < 1 )
		{
			$site->add_base( $table_name );
			continue;
		}
	
		$awssites->add_blog( $site->name, $blog_id, $table_name );
		$awspages->add_blog( $site->name, $blog_id, $table_name );
	}
	
	echo2( "done.\n" );
}
function create_table_blogs( $site )
{
	echo2( "Creating the blogs table for new site '{$site->name}'..." );
	
	global $claspages;
	$site->create_table_blogs( $claspages );
	
	echo2( "done.\n" );
}
function create_table_domain_mapping( $site )
{
	echo2( "Creating the domain mapping table for new site '{$site->name}'..." );
	
	global $claspages, $pages;	
	$site->create_table_domain_mapping( $claspages );
	$site->create_table_domain_mapping( $pages );
	
	echo2( "done.\n" );
}
function assign_site_users( $site )
{
	echo2( "Assign users to new site '{$site->name}'..." );
	
	global $claspages, $pages;
	
	$user_data = $claspages->get_admin_user();
	$username = $user_data['user_login'];
	$user = new User( $claspages, $user_data );
	
	$site->add_users( $claspages->name, $username, $user );
	$site->add_usermeta();
	
	echo2( "done.\n" );
}
function assign_new_user_ids( $site )
{
	echo2( "Assign new user ids for new site '{$site->name}'..." );
	
	$site->assign_new_user_ids();
	
	echo2( "done.\n" );
}
function create_table_users( $site )
{
	echo2( "Creating the users table for new site '{$site->name}'..." );
	
	global $claspages;
	$site->create_table_users( $claspages );
	
	echo2( "done.\n" );
}
function create_table_usermeta( $site )
{
	echo2( "Creating the usermeta table for new site '{$site->name}'..." );
	
	global $claspages;
	$site->create_table_usermeta( $claspages );
	
	echo2( "done.\n" );
}


//========================================================================================
//============================================================================= MAIN =====

print_header( 'Convert database started' );

extract($config);
main();

print_header( 'Convert database ended' );

