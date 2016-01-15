<?php

class OldSite extends Site
{
	public $base_blog;
	public $base_tables;
	
	public function __construct( $db, $name, $dbname, $dbprefix, $domain, $ipaddress, $path )
	{
		parent::__construct( $db, $name, $dbname, $dbprefix, $domain, $ipaddress, $path );
		$this->base_blog = NULL;
		$this->base_tables = array();
	}
	public function add_base_blog( $blog )
	{
		$this->base_blog = $blog;
	}
	public function get_base_blog()
	{
		return $this->base_blog;
	}
	public function add_base_table( $table_name )
	{
		$this->base_tables[] = $table_name;
	}
	public function get_sitemeta_data()
	{
		$sitemeta_table_name = $this->add_prefix( 'sitemeta' );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$sitemeta_table_name}`" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' sitemeta data.", $e->getMessage() );
		}
		
		return $data->fetchAll( PDO::FETCH_ASSOC );
	}
	public function get_sitemeta_value( $key )
	{
		$sitemeta_table_name = $this->add_prefix( 'sitemeta' );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$sitemeta_table_name}` WHERE `meta_key`='{$key}'" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' sitemeta data.", $e->getMessage() );
		}
		
		if( $data->rowCount() > 0 ) {
			$row = $data->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT );
			return $row['meta_value'];
		}
		
		return NULL;
	}
	public function get_blog_versions()
	{
		$blog_versions_table_name = $this->add_prefix( 'blog_versions' );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$blog_versions_table_name}`" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' blog_versions data.", $e->getMessage() );
		}
		
		return $data->fetchAll( PDO::FETCH_ASSOC );
	}
	public function get_registration_log()
	{
		$registration_log_table_name = $this->add_prefix( 'registration_log' );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$registration_log_table_name}`" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' registration_log data.", $e->getMessage() );
		}
		
		return $data->fetchAll( PDO::FETCH_ASSOC );
	}
	public function get_options( $blog_id )
	{
		$p = '';
		if( $blog_id > 1 ) {
			$p = "{$blog_id}_";
		}
		
		$options_table_name = $this->add_prefix( $p.'options' );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$options_table_name}`" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' '{$blog_id}_options' data.", $e->getMessage() );
		}
		
		return $data->fetchAll( PDO::FETCH_ASSOC );
	}
	public function get_posts( $blog_id )
	{
		$p = '';
		if( $blog_id > 1 ) {
			$p = "{$blog_id}_";
		}
		
		$posts_table_name = $this->add_prefix( $p.'posts' );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$posts_table_name}`" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' '{$blog_id}_posts' data.", $e->getMessage() );
		}
		
		return $data->fetchAll( PDO::FETCH_ASSOC );
	}
	public function get_postmeta( $blog_id )
	{
		$p = '';
		if( $blog_id > 1 ) {
			$p = "{$blog_id}_";
		}
		
		$postmeta_table_name = $this->add_prefix( $p.'postmeta' );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$postmeta_table_name}`" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' '{$blog_id}_postmeta' data.", $e->getMessage() );
		}
		
		return $data->fetchAll( PDO::FETCH_ASSOC );
	}
	public function get_comments( $blog_id )
	{
		$p = '';
		if( $blog_id > 1 ) {
			$p = "{$blog_id}_";
		}
		
		$comments_table_name = $this->add_prefix( $p.'comments' );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$comments_table_name}`" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' '{$blog_id}_comments' data.", $e->getMessage() );
		}
		
		return $data->fetchAll( PDO::FETCH_ASSOC );
	}
	public function get_commentmeta( $blog_id )
	{
		$p = '';
		if( $blog_id > 1 ) {
			$p = "{$blog_id}_";
		}
		
		$comments_table_name = $this->add_prefix( $p.'commentmeta' );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$commentmeta_table_name}`" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' '{$blog_id}_commentmeta' data.", $e->getMessage() );
		}
		
		return $data->fetchAll( PDO::FETCH_ASSOC );
	}
	public function get_links( $blog_id )
	{
	}
	public function get_terms( $blog_id )
	{
	}
	public function get_term_taxonomy( $blog_id )
	{
	}
	public function get_term_relationships( $blog_id )
	{
	}
	public function get_termmeta( $blog_id )
	{
	}
}

