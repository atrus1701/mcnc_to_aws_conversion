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
	
	populate_main_site_tables();
	populate_main_blog_tables();
	populate_plugin_site_tables();
	populate_plugin_blog_tables();
	
	copy_files();
	
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
	assign_base_blog();
}
function store_users_information()
{
	echo2( "\n" );
	assign_site_users();
	assign_new_user_ids();
}
function populate_main_site_tables()
{
	echo2( "\n" );
	create_table_site();
	create_table_sitemeta();
	create_table_blogs();
	create_table_domain_mapping();
	create_table_users();
	create_table_usermeta();
	create_table_blog_versions();
	create_table_registration_log();
	create_table_signups();
}
function populate_main_blog_tables()
{
	echo2( "\n" );
	create_table_options();
	create_table_posts();
	create_table_postmeta();
	create_table_comments();
	create_table_commentmeta();
	create_table_links();
	create_table_terms();
	create_table_term_taxonomy();
	create_table_term_relationships();
	create_table_termmeta();
}
function populate_plugin_site_tables()
{
	echo2( "\n" );
	create_table_batch_create_table_queue();
	create_table_batch_create_table_queuemeta();

	echo2( "\n" );
	create_table_frmpro_copies();

	echo2( "\n" );
	create_table_gaplus_login();

	echo2( "\n" );
	create_table_itsec_lockouts();
	create_table_itsec_log();
	create_table_itsec_temp();

	echo2( "\n" );
	create_table_nbt_categories_relationships_table();
	create_table_nbt_templates();
	create_table_nbt_templates_categories();

	echo2( "\n" );
	create_table_wiki_subscriptions();

	echo2( "\n" );
	create_table_orghub_category();
	create_table_orghub_connections();
	create_table_orghub_site();
	create_table_orghub_type();
	create_table_orghub_upload();
	create_table_orghub_user();

	echo2( "\n" );
//	Don't copy...
//	create_table_smackcsv_line_log();
//	create_table_smackcsv_pie_log();
}
function populate_plugin_blog_tables()
{
	echo2( "\n" );
	create_table_frm_forms();
	create_table_frm_fields();
	create_table_frm_items();
	create_table_frm_item_metas();

	echo2( "\n" );
	create_table_wpmm_subscribers();

	echo2( "\n" );
	create_table_ngg_album();
	create_table_ngg_gallery();
	create_table_ngg_pictures();

	echo2( "\n" );
	create_table_redirection_404();
	create_table_redirection_groups();
	create_table_redirection_items();
	create_table_redirection_logs();
	create_table_redirection_modules();
function copy_files()
{
	echo2( "\n" );
	copy_wp_files();
	
	echo2( "\n" );
	copy_uploads_files();
	
	echo2( "\n" );
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
function create_table_site()
{
	global $thissite;
	echo2( "Creating the site table for new site '{$thissite->name}'..." );
	$thissite->create_table_site();
	echo2( "done.\n" );
}
function create_table_sitemeta()
{
	global $thissite;
	echo2( "Creating the sitemeta table for new site '{$thissite->name}'..." );
	$thissite->create_table_sitemeta();
	echo2( "done.\n" );
}
function create_table_blogs()
{
	global $thissite;
	echo2( "Creating the blogs table for new site '{$thissite->name}'..." );
	$thissite->create_table_blogs();
	echo2( "done.\n" );
}
function create_table_domain_mapping()
{
	global $thissite;
	echo2( "Creating the domain_mapping table for new site '{$thissite->name}'..." );
	$thissite->create_table_domain_mapping();
	echo2( "done.\n" );
}
function create_table_users()
{
	global $thissite;
	echo2( "Creating the users table for new site '{$thissite->name}'..." );
	$thissite->create_table_users();
	echo2( "done.\n" );
}
function create_table_usermeta()
{
	global $thissite;
	echo2( "Creating the usermeta table for new site '{$thissite->name}'..." );
	$thissite->create_table_usermeta();
	echo2( "done.\n" );
}
function create_table_blog_versions()
{
	global $thissite;
	echo2( "Creating the blog_versions table for new site '{$thissite->name}'..." );
	$thissite->create_table_blog_versions();
	echo2( "done.\n" );
}
function create_table_registration_log()
{
	global $thissite;
	echo2( "Creating the registration_log table for new site '{$thissite->name}'..." );
	$thissite->create_table_registration_log();
	echo2( "done.\n" );
}
function create_table_signups()
{
	global $thissite;
	echo2( "Creating the signups table for new site '{$thissite->name}'..." );
	$thissite->create_table_signups();
	echo2( "done.\n" );
}
function create_table_options()
{
	global $thissite;
	echo2( "Creating the options table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_options();
	echo2( "done.\n" );
}
function create_table_posts()
{
	global $thissite;
	echo2( "Creating the posts table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_posts();
	echo2( "done.\n" );
}
function create_table_postmeta()
{
	global $thissite;
	echo2( "Creating the postmeta table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_postmeta();
	echo2( "done.\n" );
}
function create_table_comments()
{
	global $thissite;
	echo2( "Creating the comments table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_comments();
	echo2( "done.\n" );
}
function create_table_commentmeta()
{
	global $thissite;
	echo2( "Creating the commentmeta table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_commentmeta();
	echo2( "done.\n" );
}
function create_table_links()
{
	global $thissite;
	echo2( "Creating the links table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_links();
	echo2( "done.\n" );
}
function create_table_terms()
{
	global $thissite;
	echo2( "Creating the terms table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_terms();
	echo2( "done.\n" );
}
function create_table_term_taxonomy()
{
	global $thissite;
	echo2( "Creating the term_taxonomy table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_term_taxonomy();
	echo2( "done.\n" );
}
function create_table_term_relationships()
{
	global $thissite;
	echo2( "Creating the term_relationships table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_term_relationships();
	echo2( "done.\n" );
}
function create_table_termmeta()
{
	global $thissite;
	echo2( "Creating the termmeta table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_termmeta();
	echo2( "done.\n" );
}
function create_table_batch_create_table_queue()
{
	global $thissite;
	echo2( "Creating the batch_create_table_queue table for new site '{$thissite->name}'..." );
	$thissite->create_table_batch_create_table_queue();
	echo2( "done.\n" );
}
function create_table_batch_create_table_queuemeta()
{
	global $thissite;
	echo2( "Creating the batch_create_table_queuemeta table for new site '{$thissite->name}'..." );
	$thissite->create_table_batch_create_table_queuemeta();
	echo2( "done.\n" );
}
function create_table_frmpro_copies()
{
	global $thissite;
	echo2( "Creating the frmpro_copies table for new site '{$thissite->name}'..." );
	$thissite->create_table_frmpro_copies();
	echo2( "done.\n" );
}
function create_table_gaplus_login()
{
	global $thissite;
	echo2( "Creating the gaplus_login table for new site '{$thissite->name}'..." );
	$thissite->create_table_gaplus_login();
	echo2( "done.\n" );
}
function create_table_itsec_lockouts()
{
	global $thissite;
	echo2( "Creating the itsec_lockouts table for new site '{$thissite->name}'..." );
	$thissite->create_table_itsec_lockouts();
	echo2( "done.\n" );
}
function create_table_itsec_log()
{
	global $thissite;
	echo2( "Creating the itsec_log table for new site '{$thissite->name}'..." );
	$thissite->create_table_itsec_log();
	echo2( "done.\n" );
}
function create_table_itsec_temp()
{
	global $thissite;
	echo2( "Creating the itsec_temp table for new site '{$thissite->name}'..." );
	$thissite->create_table_itsec_temp();
	echo2( "done.\n" );
}
function create_table_nbt_categories_relationships_table()
{
	global $thissite;
	echo2( "Creating the nbt_categories_relationships table for new site '{$thissite->name}'..." );
	$thissite->create_table_nbt_categories_relationships_table();
	echo2( "done.\n" );
}
function create_table_nbt_templates()
{
	global $thissite;
	echo2( "Creating the nbt_templates table for new site '{$thissite->name}'..." );
	$thissite->create_table_nbt_templates();
	echo2( "done.\n" );
}
function create_table_nbt_templates_categories()
{
	global $thissite;
	echo2( "Creating the nbt_templates table for new site '{$thissite->name}'..." );
	$thissite->create_table_nbt_templates_categories();
	echo2( "done.\n" );
}
function create_table_wiki_subscriptions()
{
	global $thissite;
	echo2( "Creating the wiki_subscriptions table for new site '{$thissite->name}'..." );
	$thissite->create_table_wiki_subscriptions();
	echo2( "done.\n" );
}
function create_table_orghub_category()
{
	global $thissite;
	echo2( "Creating the orghub_category table for new site '{$thissite->name}'..." );
	$thissite->create_table_orghub_category();
	echo2( "done.\n" );
}
function create_table_orghub_connections()
{
	global $thissite;
	echo2( "Creating the orghub_connections table for new site '{$thissite->name}'..." );
	$thissite->create_table_orghub_connections();
	echo2( "done.\n" );
}
function create_table_orghub_site()
{
	global $thissite;
	echo2( "Creating the orghub_site table for new site '{$thissite->name}'..." );
	$thissite->create_table_orghub_site();
	echo2( "done.\n" );
}
function create_table_orghub_type()
{
	global $thissite;
	echo2( "Creating the orghub_type table for new site '{$thissite->name}'..." );
	$thissite->create_table_orghub_type();
	echo2( "done.\n" );
}
function create_table_orghub_upload()
{
	global $thissite;
	echo2( "Creating the orghub_upload table for new site '{$thissite->name}'..." );
	$thissite->create_table_orghub_upload();
	echo2( "done.\n" );
}
function create_table_orghub_user()
{
	global $thissite;
	echo2( "Creating the orghub_user table for new site '{$thissite->name}'..." );
	$thissite->create_table_orghub_user();
	echo2( "done.\n" );
}
function create_table_smackcsv_line_log()
{
	global $thissite;
	echo2( "Creating the smackcsv_line_log table for new site '{$thissite->name}'..." );
	$thissite->create_table_smackcsv_line_log();
	echo2( "done.\n" );
}
function create_table_frm_forms()
{
	global $thissite;
	echo2( "Creating the frm_forms table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_frm_forms();
	echo2( "done.\n" );
}
function create_table_frm_fields()
{
	global $thissite;
	echo2( "Creating the frm_fields table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_frm_fields();
	echo2( "done.\n" );
}
function create_table_frm_items()
{
	global $thissite;
	echo2( "Creating the frm_items table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_frm_items();
	echo2( "done.\n" );
}
function create_table_frm_item_metas()
{
	global $thissite;
	echo2( "Creating the frm_item_metas table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_frm_item_metas();
	echo2( "done.\n" );
}
function create_table_wpmm_subscribers()
{
	global $thissite;
	echo2( "Creating the wpmm_subscribers table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_wpmm_subscribers();
	echo2( "done.\n" );
}
function create_table_ngg_album()
{
	global $thissite;
	echo2( "Creating the ngg_album table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_ngg_album();
	echo2( "done.\n" );
}
function create_table_ngg_gallery()
{
	global $thissite;
	echo2( "Creating the ngg_gallery table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_ngg_gallery();
	echo2( "done.\n" );
}
function create_table_ngg_pictures()
{
	global $thissite;
	echo2( "Creating the ngg_pictures table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_ngg_pictures();
	echo2( "done.\n" );
}
function create_table_redirection_404()
{
	global $thissite;
	echo2( "Creating the redirection_404 table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_redirection_404();
	echo2( "done.\n" );
}
function create_table_redirection_groups()
{
	global $thissite;
	echo2( "Creating the redirection_groups table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_redirection_groups();
	echo2( "done.\n" );
}
function create_table_redirection_items()
{
	global $thissite;
	echo2( "Creating the redirection_items table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_redirection_items();
	echo2( "done.\n" );
}
function create_table_redirection_logs()
{
	global $thissite;
	echo2( "Creating the redirection_logs table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_redirection_logs();
	echo2( "done.\n" );
}
function create_table_redirection_modules()
{
	global $thissite;
	echo2( "Creating the redirection_modules table(s) for new site '{$thissite->name}'..." );
	$thissite->create_table_redirection_modules();
	echo2( "done.\n" );
}
function copy_wp_files()
{
	global $thissite;
	echo2( "Copy WordPress files for new site '{$thissite->name}'..." );
	$thissite->copy_wp_folder();
	echo2( "done.\n" );
}
function copy_uploads_files()
{
	global $thissite;
	echo2( "Copy WordPress files for new site '{$thissite->name}'..." );
	$thissite->copy_uploads_folder();
	echo2( "done.\n" );
}


//========================================================================================
//============================================================================= MAIN =====

clear_log();
print_header( 'Convert database started' );

extract($config);
main();

print_header( 'Convert database ended' );

