<?php

class User
{
	public $old_site;
	public $db_row;
	public $old_id;
	public $new_id;
	public $meta;
	public $blog_meta;
	
	public function __construct( $site, $row )
	{
		$this->old_site = $site;
		$this->db_row = $row;
		$this->old_id = $row['ID'];
		$this->new_id = -1;
		$this->meta = array();
		$this->blog_meta = array();
	}
	public function set_new_id( $id )
	{
		$this->new_id = $id;
	}
	public function get_new_user_table_row()
	{
		$row = array_merge( array(), $this->db_row );
		$row['ID'] = $this->new_id;
		return $row;
	}
	public function add_metadata( $key, $value )
	{
		$this->meta[ $key ] = $value;
	}
	public function add_blog_metadata( $blog_id, $key, $value )
	{
		if( ! array_key_exists( $blog_id, $this->blog_meta ) )
			$this->blog_meta[ $blog_id ] = array();
		$this->blog_meta[ $blog_id ][ $key ] = $value;
	}
	public function get_metadata( $key = NULL )
	{
		if( NULL === $key ) return $this->meta;
		
		if( array_key_exists( $key, $this->meta ) )
			return $this->meta[ $key ];
		
		return NULL;
	}
	public function get_blog_metadata( $blog_id = NULL, $key = NULL )
	{
		if( NULL === $blog_id ) return $this->blog_meta;
		
		if( array_key_exists( $blog_id, $this->blog_meta ) ) {
			
			if( NULL === $key ) {
				return $this->blog_meta[ $blog_id ];
			}
			
			if( array_key_exists( $key, $this->blog_meta[ $blog_id ] ) ) {
				return $this->blog_meta[ $blog_id ][ $key ];
			}
		}
		
		return NULL;
	}
}

