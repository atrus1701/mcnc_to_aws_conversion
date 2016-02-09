<?php

class Blog
{
	public $old_site;
	public $db_row;
	public $old_id;
	public $new_id;
	public $old_domain;
	public $new_domain;
	public $domain_type;
	public $associated_tables;
	public $old_file_upload_path;
	public $new_file_upload_path;
	
	public function __construct( $site, $row )
	{
		$this->old_site = $site;
		$this->db_row = $row;
		$this->old_id = $row['blog_id'];
		$this->new_id = -1;
		$this->old_domain = $row['domain'];
		$this->new_domain = $row['domain'];
		$this->domain_type = false;
		$this->associated_tables = array();
		$this->old_file_upload_path = NULL;
		$this->new_file_upload_path = NULL;
	}
	public function set_new_id( $id )
	{
		$this->new_id = $id;
	}
	public function set_domain_type( $domain_type )
	{
		$this->domain_type = $domain_type;
	}
	public function set_new_domain( $domain )
	{
		$this->new_domain = $domain;
	}
	public function add_table( $table )
	{
		$this->associated_tables[] = $table;
	}
	public function get_new_blog_table_row()
	{
		$row = array_merge( array(), $this->db_row );
		$row['blog_id'] = $this->new_id;
		$row['domain'] = $this->new_domain;
		return $row;
	}
	public function set_file_upload_paths( $old_path, $new_path )
	{
		$this->old_file_upload_path = $old_path;
		$this->new_file_upload_path = $new_path;
	}
}

