<?php

class NewSite extends Site
{
	public $blogs;
	public $users;

	public function __construct( $db, $name, $dbname, $dbprefix, $domain )
	{
		parent::__construct( $db, $name, $dbname, $dbprefix, $domain );
		$this->blogs = array();
		$this->users = array();
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
	public function add_users( $site_name, $username, $user )
	{
		$this->users[ $username ] = array( $site_name => $user );
		
		foreach( $this->blogs as $blog )
		{
			$users = $blog->old_site->get_users( $blog->old_id );
			
			foreach( $users as $user_data )
			{
				$username = $user_data['user_login'];
				$user = new User( $blog->old_site, $user_data );
				
				if( array_key_exists( $username, $this->users ) )
				{
					if( ! array_key_exists( $blog->old_site->name, $this->users[ $username ] ) )
						$this->users[ $username ][ $blog->old_site->name ] = $user;
				}
				else
				{
					$this->users[ $username ] = array(
						$blog->old_site->name => $user,
					);
				}
			}
		}
	}
	public function assign_new_user_ids()
	{
		$count = 1;
		foreach( $this->users as $users )
		{
			foreach( $users as $user ) {
				$user->set_new_id( $count );
			}
			$count++;
		}
	}
	public function add_usermeta()
	{
		foreach( $this->users as $username => $users )
		{
			foreach( $users as $user )
			{
				$usermeta_info = $user->old_site->get_usermeta( $user->old_id );
			
				foreach( $usermeta_info as $meta )
				{
					extract( $meta );
					if( strpos( $key, $user->old_site->dbprefix ) === 0 )
					{
						$k = str_replace( $user->old_site->dbprefix, '', $key );
						$blog_id = intval( substr( $k, 0, strpos( '_', $k ) ) );
						$k = substr( $k, strpos( '_', $k ) + 1 );
						$user->add_blog_metadata( $blog_id, $k, $value );
					}
					else
					{
						$user->add_metadata( $key, $value );
					}
				}
			}
		}
	}
	public function get_new_blog_id( $site_name, $blog_id )
	{
		foreach( $this->blogs as $blog )
		{
			if( $blog->old_id == $blog_id )
				return $blog->new_id;
		}
		
		return FALSE;
	}
	public function create_table_blogs( $old_site )
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
	public function create_table_domain_mapping( $old_site )
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
	public function create_table_users( $old_site )
	{
		global $db;
		
		if( ! $old_site->table_exists( $old_site->add_prefix( 'users' ) ) ) {
			return;
		}
	
		$users_table_name = $this->add_prefix( 'users' );
		$users_create_table_sql = $old_site->get_table_create_sql( 'users', true );
		$users_create_table_sql = str_replace( "`users`", $users_table_name, $users_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$users_table_name}" );
			$this->dbconnection->query( $users_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( 'Unable to create '.$this->name.' users table.', $e->getMessage() );
		}
		
		foreach( $this->users as $username => $users )
		{
			$keys = array_keys( $users );
			$user = $users[ $keys[0] ];
			$db_row = $user->get_new_user_table_row();
			$columns = $this->db->escape_fields( $db_row, $old_site->dbname, $old_site->add_prefix( 'users' ) );

			try
			{
				$this->dbconnection->query( "INSERT INTO {$users_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( 'Unable to insert '.$this->name.' user '. $db_row['user_login'] .' row.', $e->getMessage() );
			}
		}
	}
	public function create_table_usermeta( $old_site )
	{
		global $db;
		
		if( ! $old_site->table_exists( $old_site->add_prefix( 'usermeta' ) ) ) {
			return;
		}
	
		$usermeta_table_name = $this->add_prefix( 'usermeta' );
		$usermeta_create_table_sql = $old_site->get_table_create_sql( 'usermeta', true );
		$usermeta_create_table_sql = str_replace( "`usermeta`", $usermeta_table_name, $usermeta_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$usermeta_table_name}" );
			$this->dbconnection->query( $usermeta_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( 'Unable to create '.$this->name.' usermeta table.', $e->getMessage() );
		}		

		foreach( $this->users as $username => $users )
		{
			$site_names = array_keys( $users );
			$user = $users[ $site_names[0] ];
			$metadata = $user->get_metadata();
			
			foreach( $metadata as $key => $value )
			{
				switch( $key )
				{
					case 'cross_domain':
						continue;
						break;
					case 'primary_blog':
						$i = 0;
						$blog_id = FALSE;
						do
						{
							$u = $users[ $site_names[ $i ] ];
							$blog_id = $this->get_new_blog_id( $u->old_site->name, intval( $u->get_metadata( $key ) ) );
							$i++;
						} while( FALSE === $blog_id && $i < count( $site_names ) );
						
						if( FALSE === $blog_id )
							continue;
						
						$value = $blog_id;
						break;
				}
				
				$db_row = array(
					'user_id' => $user->new_id,
					'meta_key' => $key,
					'meta_value' => $value,
				);
				$columns = $this->db->escape_fields( $db_row, $old_site->dbname, $old_site->add_prefix( 'usermeta' ) );

				try
				{
					$this->dbconnection->query( "INSERT INTO {$usermeta_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
				}
				catch( PDOException $e )
				{
					script_die( 'Unable to insert '.$this->name.' usermeta '. $user_id .' key ' . $key . ' row.', $e->getMessage() );
				}
			}
			
			
			foreach( $users as $user )
			{
				$blog_metadata = $user->get_blog_metadata();
				
				foreach( $blog_metadata as $blog_id => $metadata )
				{
					$new_blog_id = $this->get_new_blog_id( $user->old_site->name, $blog_id );
					if( FALSE === $new_blog_id ) {
						continue;
					}
					
					foreach( $metadata as $key => $value )
					{
						$db_row = array(
							'user_id' => $user->new_id,
							'meta_key' => $this->add_prefix( $blog_id.'_'.$key ),
							'meta_value' => $value,
						);
						echo2( $db_row['meta_key']."\n" );
						$columns = $this->db->escape_fields( $db_row, $old_site->dbname, $old_site->add_prefix( 'usermeta' ) );

						try
						{
							$this->dbconnection->query( "INSERT INTO {$usermeta_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
						}
						catch( PDOException $e )
						{
							script_die( 'Unable to insert '.$this->name.' usermeta '. $user_id .' key ' . $db_row['meta_key'] . ' row.', $e->getMessage() );
						}
					}
				}
			}
		}
	}
}

