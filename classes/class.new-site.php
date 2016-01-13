<?php

class NewSite extends Site
{
	public $blogs;

	public function __construct( $db, $name, $dbname, $dbprefix, $domain )
	{
		parent::__construct( $db, $name, $dbname, $dbprefix, $domain );
		$this->blogs = array();
	}
	public function add_blog( $old_site, $blog_info )
	{
		$blog = new Blog( $old_site, $blog_info );
		
		$domain_type = false;
		if( $old_site->is_domain_mapped_blog( $blog_info['blog_id'] ) ) {
			$blog->set_domain_type( 'mapped' );
			$blog->set_new_domain( $this->domain );
		}
		elseif( $old_site->is_multidomain_blog( $blog_info['blog_id'] ) ) {
			$blog->set_domain_type( 'multi' );
		}
		
		$this->blogs[] = $blog;
	}
	public function assign_new_blog_ids()
	{	
		$count = 2;
		foreach( $this->blogs as &$blog )
		{
			$blog->set_new_id( $count );
			$count++;
		}
	}
	public function add_blog_table( $old_site, $old_blog_id, $table_name )
	{
		foreach( $this->blogs as &$blog )
		{
			if( $blog->old_site == $old_site && 
			    $blog->db_row['blog_id'] == $old_blog_id )
			{
				$blog->add_table();
			}
		}
	}
	public function create_blogs_table( $old_site )
	{
		global $db;
	
		$blogs_table_name = $this->add_prefix( 'blogs' );
		$wp_blogs_create_table_sql = $old_site->get_table_create_sql( 'blogs', true );
		$wp_blogs_create_table_sql = str_replace( "`blogs`", $blogs_table_name, $wp_blogs_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$blogs_table_name}" );
			$this->dbconnection->query( $wp_blogs_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( 'Unable to create '.$this->name.' blogs table.', $e->getMessage() );
		}
	
		$base_blog_info = $old_site->get_base_blog();
		$base_blog_info['domain'] = $this->domain;
		
		$columns = $this->db->escape_fields( $base_blog_info, $old_site->dbname, $old_site->add_prefix( 'blogs' ) );
		try
		{
			$this->dbconnection->query( "INSERT INTO {$blogs_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
		}
		catch( PDOException $e )
		{
			script_die( 'Unable to insert '.$this->name.' base blog row.', $e->getMessage() );
		}	
		
		foreach( $this->blogs as $blog )
		{
			$db_row = $blog->get_new_blog_table_row();
			$columns = $this->db->escape_fields( $db_row, $old_site->dbname, $old_site->add_prefix( 'blogs' ) );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$blogs_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( 'Unable to insert '.$this->name.' blog '. $db_row['path'] .' blog row.', $e->getMessage() );
			}			
		}
	}
	public function create_domain_mapping_table( $old_site )
	{
		global $db;
		
		if( ! $old_site->table_exists( $old_site->add_prefix( 'domain_mapping' ) ) ) {
			return;
		}
	
		$domain_mapping_table_name = $this->add_prefix( 'domain_mapping' );
		$wp_dm_create_table_sql = $old_site->get_table_create_sql( 'domain_mapping', true );
		$wp_dm_create_table_sql = str_replace( "`domain_mapping`", $domain_mapping_table_name, $wp_dm_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$domain_mapping_table_name}" );
			$this->dbconnection->query( $wp_dm_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( 'Unable to create '.$this->name.' domain mapping table.', $e->getMessage() );
		}
		
		foreach( $this->blogs as $blog )
		{
			if( $blog->domain_type != 'mapped' || $blog->old_site->name != $old_site->name ) {
				continue;
			}
			
			$dm_row = $blog->old_site->get_domain_mapped_row( $blog->db_row['blog_id'] );
			$dm_row['blog_id'] = $blog->new_id;
			unset( $dm_row['id'] );
			
			$columns = $this->db->escape_fields( $dm_row, $old_site->dbname, $old_site->add_prefix( 'domain_mapping' ) );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$domain_mapping_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( 'Unable to insert '.$this->name.' domain_mapping row.', $e->getMessage() );
			}
		}
		
		$domain_mapping_table_name = $this->add_prefix( 'domain_mapping_reseller_log' );
		$wp_dm_create_table_sql = $old_site->get_table_create_sql( 'domain_mapping_reseller_log', true );
		$wp_dm_create_table_sql = str_replace( "`domain_mapping_reseller_log`", $domain_mapping_table_name, $wp_dm_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$domain_mapping_table_name}" );
			$this->dbconnection->query( $wp_dm_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( 'Unable to create '.$this->name.' domain_mapping_reseller_log table.', $e->getMessage() );
		}
	}
}

