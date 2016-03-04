<?php

class Database
{
	protected $dbconnection;
	
	public function __construct()
	{
		$this->dbconnection = null;
	}
	public function connect( $dbhost, $dbusername, $dbpassword )
	{
		try
		{
			$this->dbconnection = new PDO( "mysql:host={$dbhost};dbname=information_schema;charset=utf8", $dbusername, $dbpassword );
			$this->dbconnection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		}
		catch( PDOException $e )
		{
			$this->dbconnection = null;
			script_die( 'Unable to connect to the database.', "mysql:host={$dbhost};dbname=information_schema;charset=utf8", $e->getMessage() );
		}
	}
	public function disconnect()
	{
		$this->dbconnection = null;
	}
	public function escape_fields( $fields, $dbname, $table_name )
	{
		$columns = array();
		foreach( $fields as $column_name => &$value )
		{
			$v = $value;
			if( is_null($value) )
				$v = 'NULL';
			elseif( TRUE === $value )
				$v = 'true';
			elseif( FALSE === $value )
				$v = 'false';
			elseif( $this->is_numeric_column( $dbname, $table_name, $column_name ) )
				$v = $value;
			else
				$v = $this->dbconnection->quote( $value );
	
			$columns[ $column_name ] = $v;
		}
		return $columns;
	}
	public function is_numeric_column( $dbname, $table_name, $column_name )
	{
		$select_sql = "SELECT 1 FROM `columns` WHERE TABLE_SCHEMA = '{$dbname}' AND TABLE_NAME = '{$table_name}' AND COLUMN_NAME = '{$column_name}' AND NUMERIC_PRECISION IS NOT NULL;";
	
		try
		{
			$data = $this->dbconnection->query( $select_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to determine if table `{$dbname}`.`{$table_name}` column '{$column_name}'.", "SELECT 1 FROM `columns` WHERE TABLE_SCHEMA = '{$dbname}' AND TABLE_NAME = '{$table_name}' AND COLUMN_NAME = '{$column_name}' AND NUMERIC_PRECISION IS NOT NULL;", $e->getMessage() );
		}

		$is_numeric_column = ( $data->rowCount() > 0 );
		$data = null;

		return $is_numeric_column;
	}
	public function table_exists( $dbname, $table_name )
	{
		$select_sql = "SELECT 1 FROM `tables` WHERE TABLE_SCHEMA = '{$dbname}' AND TABLE_NAME = '{$table_name}';";
	
		try
		{
			$data = $this->dbconnection->query( $select_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to determine if table `{$dbname}`.`{$table_name}`.", "SELECT 1 FROM `tables` WHERE TABLE_SCHEMA = '{$dbname}' AND TABLE_NAME = '{$table_name}';", $e->getMessage() );
		}

		$table_exists = ( $data->rowCount() > 0 );
		$data = null;

		return $table_exists;
	}
	public function get_table_list( $dbname )
	{
		$select_sql = "SELECT TABLE_NAME FROM `tables` WHERE TABLE_SCHEMA = '{$dbname}';";
	
		try
		{
			$data = $this->dbconnection->query( $select_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to determine if table `{$dbname}`.`{$table_name}`.", "SELECT 1 FROM `tables` WHERE TABLE_SCHEMA = '{$dbname}' AND TABLE_NAME = '{$table_name}';", $e->getMessage() );
		}
		
		$table_names = array();
		while( $row = $data->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$table_names[] = $row['TABLE_NAME'];
		}
		$data = null;

		return $table_names;
	}
	public function get_table_list_by_prefix( $dbname, $prefix )
	{
		$prefix = str_replace( '%', '\%', $prefix );
		$prefix = str_replace( '_', '\_', $prefix );
		$select_sql = "SELECT TABLE_NAME FROM `tables` WHERE TABLE_SCHEMA = '{$dbname}' AND TABLE_NAME LIKE '{$prefix}%';";
	
		try
		{
			$data = $this->dbconnection->query( $select_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to determine if table `{$dbname}`.`{$table_name}`.", "SELECT TABLE_NAME FROM `tables` WHERE TABLE_SCHEMA = '{$dbname}' AND TABLE_NAME LIKE '{$prefix}%';", $e->getMessage() );
		}
		
		$table_names = array();
		while( $row = $data->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT ) )
		{
			$table_names[] = $row['TABLE_NAME'];
		}
		$data = null;

		return $table_names;
	}
	public function get_table_primary_key( $dbname, $table_name )
	{
		$column_name = NULL;
		$select_sql = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE (`TABLE_SCHEMA`='$dbname') AND (`TABLE_NAME`='$table_name') AND (`COLUMN_KEY`='PRI')";
		
		try
		{
			$primary_key = $this->dbconnection->query( $select_sql );
			$column_name = $primary_key->fetchColumn( 0 );
		}
		catch( PDOException $e )
		{
			script_die( 'Unable to retrieve the primary key for table "'.$table_name.'".', "SELECT `COLUMN_NAME` FROM `COLUMNS` WHERE (`TABLE_SCHEMA`='$dbname') AND (`TABLE_NAME`='$table_name') AND (`COLUMN_KEY`='PRI')", $e->getMessage() );
		}
	
		return $column_name;
	}
}

