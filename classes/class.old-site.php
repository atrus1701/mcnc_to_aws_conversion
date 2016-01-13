<?php

class OldSite extends Site
{
	public $base_blog;
	public $base_tables;
	
	public function __construct( $db, $name, $dbname, $dbprefix, $domain )
	{
		parent::__construct( $db, $name, $dbname, $dbprefix, $domain );
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
}

