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
	public function get_table_rows( $table_name )
	{
		$table_name = $this->add_prefix( $table_name );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$table_name}`" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' '{$table_name}' data.", "SELECT * FROM `{$table_name}`", $e->getMessage() );
		}
		
		return $data->fetchAll( PDO::FETCH_ASSOC );
	}
	public function get_blog_table_rows( $blog_id, $table_name )
	{
		$p = '';
		if( $blog_id > 1 ) {
			$p = "{$blog_id}_";
		}
		
		$table_name = $this->add_prefix( $p.$table_name );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$table_name}`" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' '{$table_name}' data.", "SELECT * FROM `{$table_name}`", $e->getMessage() );
		}
		
		return $data->fetchAll( PDO::FETCH_ASSOC );
	}
	public function get_blog_table_row( $blog_id, $table_name, $row_count )
	{
		$p = '';
		if( $blog_id > 1 ) {
			$p = "{$blog_id}_";
		}
		
		$table_name = $this->add_prefix( $p.$table_name );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$table_name}` LIMIT 1 OFFSET {$row_count}" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' '{$table_name}' data.", "SELECT * FROM `{$table_name}` LIMIT 1 OFFSET {$row_count}", $e->getMessage() );
		}
		
		if( $data->rowCount() > 0 ) {
			return $data->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT );
		}
		
		return NULL;
	}
	public function get_table_row( $table_name, $row_count )
	{
		$table_name = $this->add_prefix( $table_name );
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$table_name}` LIMIT 1 OFFSET {$row_count}" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' '{$table_name}' data.", "SELECT * FROM `{$table_name}` LIMIT 1 OFFSET {$row_count}", $e->getMessage() );
		}
		
		if( $data->rowCount() > 0 ) {
			return $data->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT );
		}
		
		return NULL;
	}
	public function get_blog_table_row_list( $blog_id, $table_name, $row_count, $limit = 1000 )
	{
		$p = '';
		if( $blog_id > 1 ) {
			$p = "{$blog_id}_";
		}
		
		$table_name = $this->add_prefix( $p.$table_name );
		$offset = $row_count * $limit;
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$table_name}` LIMIT {$limit} OFFSET {$offset}" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' '{$table_name}' data.", "SELECT * FROM `{$table_name}` LIMIT {$limit} OFFSET {$offset}", $e->getMessage() );
		}
		
		if( $data->rowCount() > 0 ) {
			return $data->fetchAll( PDO::FETCH_ASSOC );
		}
		
		return NULL;
	}
	public function get_table_row_list( $table_name, $row_count, $limit = 1000 )
	{
		$table_name = $this->add_prefix( $table_name );
		$offset = $row_count * $limit;
		
		try
		{
			$data = $this->dbconnection->query( "SELECT * FROM `{$table_name}` LIMIT {$limit} OFFSET {$offset}" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to get '{$this->name}' '{$table_name}' data.", "SELECT * FROM `{$table_name}` LIMIT {$limit} OFFSET {$offset}", $e->getMessage() );
		}
		
		if( $data->rowCount() > 0 ) {
			return $data->fetchAll( PDO::FETCH_ASSOC );
		}
		
		return NULL;
	}
}

