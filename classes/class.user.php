<?php

class User
{
	public $db_row;
	public $old_site;
	public $old_id;
	public $new_id;
	
	public function __construct( $site, $row )
	{
		$this->old_site = $site;
		$this->db_row = $row;
		$this->old_id = $row['ID'];
		$this->new_id = -1;
	}
	public function set_new_id( $id )
	{
		$this->new_id = $id;
	}
}

