<?php

class OldSite extends Site
{
	public $base_blog;
	public $base_tables;
	
	public function __construct( $db, $name, $dbname, $dbprefix, $domain, $ipaddress, $path, $username )
	{
		parent::__construct( $db, $name, $dbname, $dbprefix, $domain, $ipaddress, $path, $username );
		$this->base_blog = NULL;
		$this->base_tables = array();
	}
	public function add_base_blog( $blog_info )
	{
		$this->base_blog = new Blog( $this, $blog_info );
	}
	public function get_base_blog()
	{
		return $this->base_blog;
	}
	public function add_base_table( $table_name )
	{
		$this->base_tables[] = $table_name;
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
			script_die( "Unable to get '{$this->name}' sitemeta data.", "SELECT * FROM `{$sitemeta_table_name}` WHERE `meta_key`='{$key}'", $e->getMessage() );
		}
		
		if( $data->rowCount() > 0 ) {
			$row = $data->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT );
			return $row['meta_value'];
		}
		
		return NULL;
	}
	public function get_mapped_domain( $blog_id )
	{
		$domain_mapping_table_name = $this->add_prefix( 'domain_mapping' );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$domain_mapping_table_name}` WHERE `blog_id`={$blog_id}" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' domain mapping data.", "SELECT * FROM `{$domain_mapping_table_name}` WHERE `blog_id`={$blog_id}", $e->getMessage() );
		}
		
		if( $data->rowCount() > 0 ) {
			$row = $data->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT );
			return $row['domain'];
		}
		
		return NULL;
	}
}

