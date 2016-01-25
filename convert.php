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
	global $db, $claspages, $pages, $awssites, $awspages, 
		$claspages_to_sites_slugs, $pages_to_sites_slugs;
	
	clear_log();
	create_sites();
	
//----------------------------------------
// Store blogs information
//----------------------------------------

	echo2( "\n" );
	add_blogs_to_new_sites( $claspages, $claspages_to_sites_slugs );
	add_blogs_to_new_sites( $pages, $pages_to_sites_slugs );
	assign_new_blog_ids( $awssites );
	assign_new_blog_ids( $awspages );
	assign_blog_tables( $claspages );
	assign_blog_tables( $pages );
	assign_base_blog();
	
//----------------------------------------
// Store users information
//----------------------------------------
	
	echo2( "\n" );
	assign_site_users( $awssites );
	assign_site_users( $awspages );
	assign_new_user_ids( $awssites );
	assign_new_user_ids( $awspages );

//----------------------------------------
// Main Site tables
//----------------------------------------
	
	echo2( "\n" );
	create_table_site( $awssites );
	create_table_site( $awspages );
	create_table_sitemeta( $awssites );
	create_table_sitemeta( $awspages );
	create_table_blogs( $awssites );
	create_table_blogs( $awspages );
	create_table_domain_mapping( $awssites );
	create_table_users( $awssites );
	create_table_users( $awspages );
	create_table_usermeta( $awssites );
	create_table_usermeta( $awspages );
	create_table_blog_versions( $awssites );
	create_table_blog_versions( $awspages );
	create_table_registration_log( $awssites );
	create_table_registration_log( $awspages );
	create_table_signups( $awssites );
	create_table_signups( $awspages );

//----------------------------------------
// Main Blog tables
//----------------------------------------

	echo2( "\n" );
	create_table_options( $awssites );
	create_table_options( $awspages );
	create_table_posts( $awssites );
	create_table_posts( $awspages );
	create_table_postmeta( $awssites );
	create_table_postmeta( $awspages );
	create_table_comments( $awssites );
	create_table_comments( $awspages );
	create_table_commentmeta( $awssites );
	create_table_commentmeta( $awspages );
	create_table_links( $awssites );
	create_table_links( $awspages );
	create_table_terms( $awssites );
	create_table_terms( $awspages );
	create_table_term_taxonomy( $awssites );
	create_table_term_taxonomy( $awspages );
	create_table_term_relationships( $awssites );
	create_table_term_relationships( $awspages );
	create_table_termmeta( $awssites );
	create_table_termmeta( $awspages );

//----------------------------------------
// Plugin Site tables
//----------------------------------------

	echo2( "\n" );
	create_table_batch_create_table_queue( $awssites );
	create_table_batch_create_table_queue( $awspages );
	create_table_batch_create_table_queuemeta( $awssites );
	create_table_batch_create_table_queuemeta( $awspages );

	echo2( "\n" );
	create_table_frmpro_copies( $awssites );
	create_table_frmpro_copies( $awspages );

	echo2( "\n" );
	create_table_gaplus_login( $awssites );
	create_table_gaplus_login( $awspages );

	echo2( "\n" );
	create_table_itsec_lockouts( $awssites );
	create_table_itsec_lockouts( $awspages );
	create_table_itsec_log( $awssites );
	create_table_itsec_log( $awspages );
	create_table_itsec_temp( $awssites );
	create_table_itsec_temp( $awspages );

	echo2( "\n" );
	create_table_nbt_categories_relationships_table( $awssites );
	create_table_nbt_categories_relationships_table( $awspages );
	create_table_nbt_templates( $awssites );
	create_table_nbt_templates( $awspages );
	create_table_nbt_templates_categories( $awssites );
	create_table_nbt_templates_categories( $awspages );

	echo2( "\n" );
	create_table_wiki_subscriptions( $awspages );

	echo2( "\n" );
	create_table_orghub_category( $awssites );
	create_table_orghub_category( $awspages );
	create_table_orghub_connections( $awssites );
	create_table_orghub_connections( $awspages );
	create_table_orghub_site( $awssites );
	create_table_orghub_site( $awspages );
	create_table_orghub_type( $awssites );
	create_table_orghub_type( $awspages );
	create_table_orghub_upload( $awssites );
	create_table_orghub_upload( $awspages );
	create_table_orghub_user( $awssites );
	create_table_orghub_user( $awspages );

	echo2( "\n" );
//  Don't copy...
//	create_table_smackcsv_line_log( $awssites );
//	create_table_smackcsv_pie_log( $awspages );

//----------------------------------------
// Plugin Blog tables
//----------------------------------------
	
	echo2( "\n" );
 	create_table_frm_forms( $awssites );
 	create_table_frm_forms( $awspages );
	create_table_frm_fields( $awssites );
	create_table_frm_fields( $awspages );
	create_table_frm_items( $awssites );
	create_table_frm_items( $awspages );
	create_table_frm_item_metas( $awssites );
	create_table_frm_item_metas( $awspages );

	echo2( "\n" );
	create_table_wpmm_subscribers( $awssites );
	create_table_wpmm_subscribers( $awspages );

	echo2( "\n" );
	create_table_ngg_album( $awssites );
	create_table_ngg_album( $awspages );
	create_table_ngg_gallery( $awssites );
	create_table_ngg_gallery( $awspages );
	create_table_ngg_pictures( $awssites );
	create_table_ngg_pictures( $awspages );

	echo2( "\n" );
	create_table_redirection_404( $awssites );
	create_table_redirection_404( $awspages );
	create_table_redirection_groups( $awssites );
	create_table_redirection_groups( $awspages );
	create_table_redirection_items( $awssites );
	create_table_redirection_items( $awspages );
	create_table_redirection_logs( $awssites );
	create_table_redirection_logs( $awspages );
	create_table_redirection_modules( $awssites );
	create_table_redirection_modules( $awspages );

//----------------------------------------
// Copy files
//----------------------------------------
	
	echo2( "\n" );
	copy_wp_files( $awssites );
	copy_wp_files( $awspages );
	
	echo2( "\n" );
	copy_uploads_files( $awssites );
	copy_uploads_files( $awspages );

	echo2( "\n" );
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
function create_sites()
{
	echo2( "Creating sites..." );
	
	global $dbhost, $dbusername, $dbpassword, $claspages_dbname, $pages_dbname, $awssites_dbname, $awspages_dbname;
	global $claspages_prefix, $pages_prefix, $awssites_prefix, $awspages_prefix;
	global $claspages_ipaddress, $pages_ipaddress, $awssites_ipaddress, $awspages_ipaddress;
	global $claspages_path, $pages_path, $awssites_path, $awspages_path;
	global $db, $claspages, $pages, $awssites, $awspages;
	
	$db        = new Database();
	$claspages = new OldSite( $db, 'clas-pages', $claspages_dbname, $claspages_prefix, 'clas-pages.uncc.edu', $claspages_ipaddress, $claspages_path, 'pages' );
	$pages     = new OldSite( $db, 'pages', $pages_dbname, $pages_prefix, 'pages.uncc.edu', $pages_ipaddress, $pages_path, 'pages2' );
	$awssites  = new NewSite( $db, 'sites', $awssites_dbname, $awssites_prefix, 'sites.uncc.edu', $awssites_ipaddress, $awssites_path, 'ec2-user' );
	$awspages  = new NewSite( $db, 'pages', $awspages_dbname, $awspages_prefix, 'pages.uncc.edu', $awspages_ipaddress, $awspages_path, 'ec2-user' );
	
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
			$site->add_base_table( $table_name );
			continue;
		}
	
		$awssites->add_blog_table( $site->name, $blog_id, $table_name );
		$awspages->add_blog_table( $site->name, $blog_id, $table_name );
	}
	
	echo2( "done.\n" );
}
function assign_base_blog()
{
	echo2( "Assigning base blogs..." );
	global $claspages, $pages, $awssites, $awspages;
	$awssites->assign_base_blog( $claspages );
	$awspages->assign_base_blog( $pages );
	echo2( "done.\n" );
}
function assign_site_users( $site )
{
	echo2( "Assign users to new site '{$site->name}'..." );
	$site->store_users();
	$site->store_usermeta();
	echo2( "done.\n" );
}
function assign_new_user_ids( $site )
{
	echo2( "Assign new user ids for new site '{$site->name}'..." );
	$site->assign_new_user_ids();
	echo2( "done.\n" );
}
function create_table_site( $site )
{
	echo2( "Creating the site table for new site '{$site->name}'..." );
	$site->create_table_site();
	echo2( "done.\n" );
}
function create_table_sitemeta( $site )
{
	echo2( "Creating the sitemeta table for new site '{$site->name}'..." );
	$site->create_table_sitemeta();
	echo2( "done.\n" );
}
function create_table_blogs( $site )
{
	echo2( "Creating the blogs table for new site '{$site->name}'..." );
	$site->create_table_blogs();
	echo2( "done.\n" );
}
function create_table_domain_mapping( $site )
{
	echo2( "Creating the domain_mapping table for new site '{$site->name}'..." );
	$site->create_table_domain_mapping();
	echo2( "done.\n" );
}
function create_table_users( $site )
{
	echo2( "Creating the users table for new site '{$site->name}'..." );
	$site->create_table_users();
	echo2( "done.\n" );
}
function create_table_usermeta( $site )
{
	echo2( "Creating the usermeta table for new site '{$site->name}'..." );
	$site->create_table_usermeta();
	echo2( "done.\n" );
}
function create_table_blog_versions( $site )
{
	echo2( "Creating the blog_versions table for new site '{$site->name}'..." );
	$site->create_table_blog_versions();
	echo2( "done.\n" );
}
function create_table_registration_log( $site )
{
	echo2( "Creating the registration_log table for new site '{$site->name}'..." );
	$site->create_table_registration_log();
	echo2( "done.\n" );
}
function create_table_signups( $site )
{
	echo2( "Creating the signups table for new site '{$site->name}'..." );
	$site->create_table_signups();
	echo2( "done.\n" );
}
function create_table_options( $site )
{
	echo2( "Creating the options table(s) for new site '{$site->name}'..." );
	$site->create_table_options();
	echo2( "done.\n" );
}
function create_table_posts( $site )
{
	echo2( "Creating the posts table(s) for new site '{$site->name}'..." );
	$site->create_table_posts();
	echo2( "done.\n" );
}
function create_table_postmeta( $site )
{
	echo2( "Creating the postmeta table(s) for new site '{$site->name}'..." );
	$site->create_table_postmeta();
	echo2( "done.\n" );
}
function create_table_comments( $site )
{
	echo2( "Creating the comments table(s) for new site '{$site->name}'..." );
	$site->create_table_comments();
	echo2( "done.\n" );
}
function create_table_commentmeta( $site )
{
	echo2( "Creating the commentmeta table(s) for new site '{$site->name}'..." );
	$site->create_table_commentmeta();
	echo2( "done.\n" );
}
function create_table_links( $site )
{
	echo2( "Creating the links table(s) for new site '{$site->name}'..." );
	$site->create_table_links();
	echo2( "done.\n" );
}
function create_table_terms( $site )
{
	echo2( "Creating the terms table(s) for new site '{$site->name}'..." );
	$site->create_table_terms();
	echo2( "done.\n" );
}
function create_table_term_taxonomy( $site )
{
	echo2( "Creating the term_taxonomy table(s) for new site '{$site->name}'..." );
	$site->create_table_term_taxonomy();
	echo2( "done.\n" );
}
function create_table_term_relationships( $site )
{
	echo2( "Creating the term_relationships table(s) for new site '{$site->name}'..." );
	$site->create_table_term_relationships();
	echo2( "done.\n" );
}
function create_table_termmeta( $site )
{
	echo2( "Creating the termmeta table(s) for new site '{$site->name}'..." );
	$site->create_table_termmeta();
	echo2( "done.\n" );
}
function create_table_batch_create_table_queue( $site )
{
	echo2( "Creating the batch_create_table_queue table for new site '{$site->name}'..." );
	$site->create_table_batch_create_table_queue();
	echo2( "done.\n" );
}
function create_table_batch_create_table_queuemeta( $site )
{
	echo2( "Creating the batch_create_table_queuemeta table for new site '{$site->name}'..." );
	$site->create_table_batch_create_table_queuemeta();
	echo2( "done.\n" );
}
function create_table_frmpro_copies( $site )
{
	echo2( "Creating the frmpro_copies table for new site '{$site->name}'..." );
	$site->create_table_frmpro_copies();
	echo2( "done.\n" );
}
function create_table_gaplus_login( $site )
{
	echo2( "Creating the gaplus_login table for new site '{$site->name}'..." );
	$site->create_table_gaplus_login();
	echo2( "done.\n" );
}
function create_table_itsec_lockouts( $site )
{
	echo2( "Creating the itsec_lockouts table for new site '{$site->name}'..." );
	$site->create_table_itsec_lockouts();
	echo2( "done.\n" );
}
function create_table_itsec_log( $site )
{
	echo2( "Creating the itsec_log table for new site '{$site->name}'..." );
	$site->create_table_itsec_log();
	echo2( "done.\n" );
}
function create_table_itsec_temp( $site )
{
	echo2( "Creating the itsec_temp table for new site '{$site->name}'..." );
	$site->create_table_itsec_temp();
	echo2( "done.\n" );
}
function create_table_nbt_categories_relationships_table( $site )
{
	echo2( "Creating the nbt_categories_relationships table for new site '{$site->name}'..." );
	$site->create_table_nbt_categories_relationships_table();
	echo2( "done.\n" );
}
function create_table_nbt_templates( $site )
{
	echo2( "Creating the nbt_templates table for new site '{$site->name}'..." );
	$site->create_table_nbt_templates();
	echo2( "done.\n" );
}
function create_table_nbt_templates_categories( $site )
{
	echo2( "Creating the nbt_templates table for new site '{$site->name}'..." );
	$site->create_table_nbt_templates_categories();
	echo2( "done.\n" );
}
function create_table_wiki_subscriptions( $site )
{
	echo2( "Creating the wiki_subscriptions table for new site '{$site->name}'..." );
	$site->create_table_wiki_subscriptions();
	echo2( "done.\n" );
}
function create_table_orghub_category( $site )
{
	echo2( "Creating the orghub_category table for new site '{$site->name}'..." );
	$site->create_table_orghub_category();
	echo2( "done.\n" );
}
function create_table_orghub_connections( $site )
{
	echo2( "Creating the orghub_connections table for new site '{$site->name}'..." );
	$site->create_table_orghub_connections();
	echo2( "done.\n" );
}
function create_table_orghub_site( $site )
{
	echo2( "Creating the orghub_site table for new site '{$site->name}'..." );
	$site->create_table_orghub_site();
	echo2( "done.\n" );
}
function create_table_orghub_type( $site )
{
	echo2( "Creating the orghub_type table for new site '{$site->name}'..." );
	$site->create_table_orghub_type();
	echo2( "done.\n" );
}
function create_table_orghub_upload( $site )
{
	echo2( "Creating the orghub_upload table for new site '{$site->name}'..." );
	$site->create_table_orghub_upload();
	echo2( "done.\n" );
}
function create_table_orghub_user( $site )
{
	echo2( "Creating the orghub_user table for new site '{$site->name}'..." );
	$site->create_table_orghub_user();
	echo2( "done.\n" );
}
function create_table_smackcsv_line_log( $site )
{
	echo2( "Creating the smackcsv_line_log table for new site '{$site->name}'..." );
	$site->create_table_smackcsv_line_log();
	echo2( "done.\n" );
}
function create_table_frm_forms( $site )
{
	echo2( "Creating the frm_forms table(s) for new site '{$site->name}'..." );
	$site->create_table_frm_forms();
	echo2( "done.\n" );
}
function create_table_frm_fields( $site )
{
	echo2( "Creating the frm_fields table(s) for new site '{$site->name}'..." );
	$site->create_table_frm_fields();
	echo2( "done.\n" );
}
function create_table_frm_items( $site )
{
	echo2( "Creating the frm_items table(s) for new site '{$site->name}'..." );
	$site->create_table_frm_items();
	echo2( "done.\n" );
}
function create_table_frm_item_metas( $site )
{
	echo2( "Creating the frm_item_metas table(s) for new site '{$site->name}'..." );
	$site->create_table_frm_item_metas();
	echo2( "done.\n" );
}
function create_table_wpmm_subscribers( $site )
{
	echo2( "Creating the wpmm_subscribers table(s) for new site '{$site->name}'..." );
	$site->create_table_wpmm_subscribers();
	echo2( "done.\n" );
}
function create_table_ngg_album( $site )
{
	echo2( "Creating the ngg_album table(s) for new site '{$site->name}'..." );
	$site->create_table_ngg_album();
	echo2( "done.\n" );
}
function create_table_ngg_gallery( $site )
{
	echo2( "Creating the ngg_gallery table(s) for new site '{$site->name}'..." );
	$site->create_table_ngg_gallery();
	echo2( "done.\n" );
}
function create_table_ngg_pictures( $site )
{
	echo2( "Creating the ngg_pictures table(s) for new site '{$site->name}'..." );
	$site->create_table_ngg_pictures();
	echo2( "done.\n" );
}
function create_table_redirection_404( $site )
{
	echo2( "Creating the redirection_404 table(s) for new site '{$site->name}'..." );
	$site->create_table_redirection_404();
	echo2( "done.\n" );
}
function create_table_redirection_groups( $site )
{
	echo2( "Creating the redirection_groups table(s) for new site '{$site->name}'..." );
	$site->create_table_redirection_groups();
	echo2( "done.\n" );
}
function create_table_redirection_items( $site )
{
	echo2( "Creating the redirection_items table(s) for new site '{$site->name}'..." );
	$site->create_table_redirection_items();
	echo2( "done.\n" );
}
function create_table_redirection_logs( $site )
{
	echo2( "Creating the redirection_logs table(s) for new site '{$site->name}'..." );
	$site->create_table_redirection_logs();
	echo2( "done.\n" );
}
function create_table_redirection_modules( $site )
{
	echo2( "Creating the redirection_modules table(s) for new site '{$site->name}'..." );
	$site->create_table_redirection_modules();
	echo2( "done.\n" );
}
function copy_wp_files( $site )
{
	echo2( "Copy WordPress files for new site '{$site->name}'..." );
	$site->copy_wp_folder();
	echo2( "done.\n" );
}
function copy_uploads_files( $site )
{
	echo2( "Copy WordPress files for new site '{$site->name}'..." );
	$site->copy_uploads_folder();
	echo2( "done.\n" );
}


//========================================================================================
//============================================================================= MAIN =====

print_header( 'Convert database started' );

extract($config);
main();

print_header( 'Convert database ended' );

