<?php

class Site
{
	public $db;
	public $name;
	public $dbconnection;
	public $dbname;
	public $dbprefix;
	public $domain;
	
	public function __construct( $db, $name, $dbname, $dbprefix, $domain )
	{
		$this->db = $db;
		$this->name = $name;
		$this->dbconnection = NULL;
		$this->dbname = $dbname;
		$this->dbprefix = $dbprefix;
		$this->domain = $domain;
	}
	public function connect( $dbhost, $dbusername, $dbpassword, $dbname )
	{
		try
		{
			$this->dbconnection = new PDO( "mysql:host={$dbhost};dbname={$dbname};charset=utf8", $dbusername, $dbpassword );
			$this->dbconnection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		}
		catch( PDOException $e )
		{
			$this->dbconnection = null;
			script_die( 'Unable to connect to the database.', $e->getMessage() );
		}
	}
	public function disconnect()
	{
		$this->dbconnection = null;
	}
	public function get_table_create_sql( $table_name, $remove_prefix = false )
	{
		try
		{
			$create_table = $this->dbconnection->query( "SHOW CREATE TABLE `{$this->dbprefix}{$table_name}`" );
		}
		catch( PDOException $e )
		{
			die( "Unable to retrieve create table data for `{$this->dbprefix}{$table_name}`.\n".$e->getMessage()."\n\n" );
		}
	
		if( count($create_table) == 0 )
		{
			die( "Unable to retrieve create table data for `{$this->dbprefix}{$table_name}`.\n\n" );
		}
	
		$create_table_sql = $create_table->fetchColumn( 1 );
		$create_table_sql = str_replace( array( "\r\n", "\r", "\n" ), '', $create_table_sql );
		
		if( $remove_prefix ) {
			$create_table_sql = str_replace( "`{$this->dbprefix}", "`", $create_table_sql );
		}
	
		return $create_table_sql;
	}
	public function remove_prefix( $statement )
	{
		return str_replace( $this->dbprefix, '', $statement );
	}
	public function replace_prefix( $statement, $old_prefix )
	{
		return str_replace( "`{$old_prefix}", "`{$this->dbprefix}", $statement );
	}
	public function add_prefix( $statement )
	{
		return $this->dbprefix.$statement;
	}
	public function get_blogs()
	{
		try
		{
			$blogs = $this->dbconnection->query( "SELECT * FROM `{$this->dbprefix}blogs`" );
		}
		catch( PDOException $e )
		{
			script_die( 'Unable to retrieve '.$this->name.' blogs list.', $e->getMessage() );
		}
		
		return $blogs->fetchAll( PDO::FETCH_ASSOC );
	}
	public function get_table_list( $remove_prefix = false )
	{
		$tables = array();
		
		try
		{
			$table_list = $this->dbconnection->query( 'SHOW TABLES FROM '.$this->dbname );
		}
		catch( PDOException $e )
		{
			script_die( 'Unable to retrieve '.$this->name.' table list.', $e->getMessage() );
		}
		
		$key = 'Tables_in_'.$this->dbname;
		while( $table = $table_list->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT) )
		{
			if( $remove_prefix === true && strpos( $table[ $key ], $this->dbprefix ) === 0 ) {
				$tables[] = substr( $table[ $key ], strlen( $this->dbprefix ) );
			} else {
				$tables[] = $table[ $key ];
			}
		}
		
		return $tables;
	}
	public function is_domain_mapped_blog( $blog_id )
	{
		$domain_mapped_table_name = $this->add_prefix( 'domain_mapping' );
		
		if( ! $this->table_exists( $domain_mapped_table_name ) ) {
			return false;
		}
		
		$select_sql = "SELECT 1 FROM `{$domain_mapped_table_name}` WHERE `blog_id`=$blog_id;";
	
		try
		{
			$data = $this->dbconnection->query( $select_sql );
		}
		catch( PDOException $e )
		{
			echo2( $e->getMessage() );
			add_error(
				$table_name,
				$e->getMessage(),
				"\n".print_r($select_sql, true)
			);
			return false;
		}

		$is_domain_mapped = ( $data->rowCount() > 0 );
		$data = null;

		return $is_domain_mapped;
	}
	public function get_domain_mapped_row( $blog_id )
	{
		$domain_mapped_table_name = $this->add_prefix( 'domain_mapping' );
		
		if( ! $this->table_exists( $domain_mapped_table_name ) ) {
			return false;
		}
		
		$select_sql = "SELECT * FROM `{$domain_mapped_table_name}` WHERE `blog_id`=$blog_id;";

		try
		{
			$data = $this->dbconnection->query( $select_sql );
		}
		catch( PDOException $e )
		{
			echo2( $e->getMessage() );
			add_error(
				$table_name,
				$e->getMessage(),
				"\n".print_r($select_sql, true)
			);
			return false;
		}
		
		if( $data->rowCount() > 0 ) {
			return $data->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT );
		}
		
		return false;
	}
	public function is_multidomain_blog( $blog_id )
	{
		return false;
	}
	public function table_exists( $table_name )
	{
		return $this->db->table_exists( $this->dbname, $table_name );
	}
}

