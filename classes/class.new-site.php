<?php

class NewSite extends Site
{
	public $base_blog;
	public $blogs;
	public $users;

	public function __construct( $db, $name, $dbname, $dbprefix, $domain, $ipaddress, $path )
	{
		parent::__construct( $db, $name, $dbname, $dbprefix, $domain, $ipaddress, $path );
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
	public function assign_base_blog( $site )
	{
		$base_blog_info = $site->get_base_blog();
		$this->base_blog = new Blog( $site, $base_blog_info );
		$this->base_blog->set_new_id( 1 );
		$this->base_blog->set_new_domain( $this->domain );
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
	public function store_users()
	{
		global $db, $claspages, $pages;
		
		$user_data = $claspages->get_admin_user();
		$username = $user_data['user_login'];
		$user = new User( $claspages, $user_data );
		
		$this->users[ $username ] = array( $claspages->name => $user );
		
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
	public function store_usermeta()
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
						$blog_id = intval( substr( $k, 0, strpos( $k, '_' ) ) );
						$k = substr( $k, strpos( $k, '_' ) + 1 );
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
	public function get_new_blog_id( $site_name, $blog_id )
	{
		foreach( $this->blogs as $blog )
		{
			if( $site_name == $blog->old_site->name && $blog->old_id == $blog_id )
				return $blog->new_id;
		}
		
		return FALSE;
	}
	public function get_new_user_id( $site_name, $user_id )
	{
		foreach( $this->users as $users )
		{
			if( ! array_key_exists( $site_name, $users ) ) {
				continue;
			}
			if( $users[ $site_name ]->old_id == $user_id ) {
				return $users[ $site_name ]->new_id;
			}
		}
		
		return FALSE;
	}
	public function create_table_site()
	{
		global $db, $claspages, $pages;
	
		$site_table_name = $this->add_prefix( 'site' );
		$site_create_table_sql = $claspages->get_table_create_sql( 'site', true );
		$site_create_table_sql = str_replace( "`site`", $site_table_name, $site_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$site_table_name}" );
			$this->dbconnection->query( $site_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' site table.", $e->getMessage() );
		}
		
		try
		{
			$this->dbconnection->query( "INSERT INTO {$site_table_name} (`id`,`domain`,`path`) VALUES (1,'{$this->domain}','/');" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to insert '{$this->name}' default site row.", $e->getMessage() );
		}	
	}
	public function create_table_sitemeta()
	{
		global $db, $claspages, $pages;
	
		$sitemeta_table_name = $this->add_prefix( 'sitemeta' );
		$sitemeta_create_table_sql = $claspages->get_table_create_sql( 'sitemeta', true );
		$sitemeta_create_table_sql = str_replace( "`sitemeta`", $sitemeta_table_name, $sitemeta_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$sitemeta_table_name}" );
			$this->dbconnection->query( $sitemeta_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' sitemeta table.", $e->getMessage() );
		}
		
		$data = $claspages->get_sitemeta_data();
		foreach( $data as $row )
		{
			unset( $row['meta_id'] );
			
			switch( $row['meta_key'] )
			{
				case 'site_admins':
					$v = $pages->get_sitemeta_value( 'site_admins' );
					
					$admins = array();
					$admins = array_merge( $admins, unserialize( $v ) );
					$admins = array_merge( $admins, unserialize( $row['meta_value'] ) );
					
					$row['meta_value'] = serialize( array_unique( $admins ) );
					break;
				case 'siteurl':
					$row['meta_value'] = 'http://'.$this->domain;
					break;
				case 'active_sitewide_plugins':
					$v = $pages->get_sitemeta_value( 'active_sitewide_plugins' );
					
					$plugins = array();
					$plugins = array_merge( $plugins, unserialize( $v ) );
					$plugins = array_merge( $plugins, unserialize( $row['meta_value'] ) );
					
					$row['meta_value'] = serialize( $plugins );
					break;
				case 'blog_count':
					$row['meta_value'] = count( $this->blogs ) + 1;
					break;
				case 'user_count':
					$row['meta_value'] = count( $this->users );
					break;
				case 'ub_login_image':
					$row['meta_value'] = str_replace( $claspages->domain, $this->domain, $row['meta_value'] );
					break;
				case 'ub_login_image_dir':
					$row['meta_value'] = str_replace( $claspages->path, $this->path, $row['meta_value'] );
					break;
				case 'md_domains':
					$domains = array();
					
					$v = $pages->get_sitemeta_value( 'md_domains' );
					$v = unserialize( $v );
					foreach( $v as $md )
					{
						if( isset( $md['blog_template'] ) ) {
							$md['blog_template'] = $this->get_new_blog_id( $pages->name, $md['blog_template'] );
						}
					}
					$domains = array_merge( $domains, $v );
					
					$v = unserialize( $row['meta_value'] );
					foreach( $v as $md )
					{
						if( isset( $md['blog_template'] ) ) {
							$md['blog_template'] = $this->get_new_blog_id( $claspages->name, $md['blog_template'] );
						}
					}
					$domains = array_merge( $domains, unserialize( $row['meta_value'] ) );
					
					$row['meta_value'] = serialize( $domains );
					break;
				case 'map_ipaddress':
					$row['meta_value'] = $this->ipaddress;
					break;
				case 'domain_mapping':
					$v = unserialize( $row['meta_value'] );
					if( $v )
					{
						$v['map_ipaddress'] = $this->ipaddress;
						$row['meta_value'] = serialize( $v );
					}
					break;
				default:
					if( strpos( $row['meta_key'], 'domainmap-flushed-rules-' ) === 0 )
					{
						
					}
					break;
			}
			
			$columns = $this->db->escape_fields( $row, $this->dbname, $sitemeta_table_name );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$sitemeta_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( "Unable to insert '{$this->name}' sitemeta row.", $e->getMessage() );
			}	
		}
	}
	public function create_table_blogs()
	{
		global $db, $claspages, $pages;
	
		$blogs_table_name = $this->add_prefix( 'blogs' );
		$wp_blogs_create_table_sql = $claspages->get_table_create_sql( 'blogs', true );
		$wp_blogs_create_table_sql = str_replace( "`blogs`", $blogs_table_name, $wp_blogs_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$blogs_table_name}" );
			$this->dbconnection->query( $wp_blogs_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' blogs table.", $e->getMessage() );
		}
	
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog )
		{
			$db_row = $blog->get_new_blog_table_row();
			$columns = $this->db->escape_fields( $db_row, $this->dbname, $blogs_table_name );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$blogs_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( "Unable to insert '{$this->name}' blog '{$db_row['path']}' blog row.", $e->getMessage() );
			}			
		}
	}
	public function create_table_domain_mapping()
	{
		global $db, $claspages, $pages;
		
		$domain_mapping_table_name = $this->add_prefix( 'domain_mapping' );
		$wp_dm_create_table_sql = $claspages->get_table_create_sql( 'domain_mapping', true );
		$wp_dm_create_table_sql = str_replace( "`domain_mapping`", $domain_mapping_table_name, $wp_dm_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$domain_mapping_table_name}" );
			$this->dbconnection->query( $wp_dm_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' domain_mapping table.", $e->getMessage() );
		}
		
		foreach( array( $claspages, $pages ) as $site )
		{
			if( ! $site->table_exists( $site->add_prefix( 'domain_mapping' ) ) ) {
				continue;
			}
	
			foreach( $this->blogs as $blog )
			{
				if( $blog->domain_type != 'mapped' || $blog->old_site->name != $site->name ) {
					continue;
				}
			
				$dm_row = $blog->old_site->get_domain_mapped_row( $blog->db_row['blog_id'] );
				$dm_row['blog_id'] = $blog->new_id;
				unset( $dm_row['id'] );
			
				$columns = $this->db->escape_fields( $dm_row, $this->dbname, $domain_mapping_table_name );
				try
				{
					$this->dbconnection->query( "INSERT INTO {$domain_mapping_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
				}
				catch( PDOException $e )
				{
					script_die( "Unable to insert '{$this->name}' domain_mapping row.", $e->getMessage() );
				}
			}
		}
		
		$domain_mapping_table_name = $this->add_prefix( 'domain_mapping_reseller_log' );
		$wp_dm_create_table_sql = $claspages->get_table_create_sql( 'domain_mapping_reseller_log', true );
		$wp_dm_create_table_sql = str_replace( "`domain_mapping_reseller_log`", $domain_mapping_table_name, $wp_dm_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$domain_mapping_table_name}" );
			$this->dbconnection->query( $wp_dm_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' domain_mapping_reseller_log table.", $e->getMessage() );
		}
	}
	public function create_table_users()
	{
		global $db, $claspages, $pages;
		
		if( ! $claspages->table_exists( $claspages->add_prefix( 'users' ) ) ) {
			return;
		}
	
		$users_table_name = $this->add_prefix( 'users' );
		$users_create_table_sql = $claspages->get_table_create_sql( 'users', true );
		$users_create_table_sql = str_replace( "`users`", $users_table_name, $users_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$users_table_name}" );
			$this->dbconnection->query( $users_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' users table.", $e->getMessage() );
		}
		
		foreach( $this->users as $username => $users )
		{
			$keys = array_keys( $users );
			$user = $users[ $keys[0] ];
			$db_row = $user->get_new_user_table_row();
			$columns = $this->db->escape_fields( $db_row, $this->dbname, $users_table_name );

			try
			{
				$this->dbconnection->query( "INSERT INTO {$users_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( "Unable to insert '{$this->name}' user '{$db_row['user_login']}' row.", $e->getMessage() );
			}
		}
	}
	public function create_table_usermeta()
	{
		global $db, $claspages, $pages;
		
		$usermeta_table_name = $this->add_prefix( 'usermeta' );
		$usermeta_create_table_sql = $claspages->get_table_create_sql( 'usermeta', true );
		$usermeta_create_table_sql = str_replace( "`usermeta`", $usermeta_table_name, $usermeta_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$usermeta_table_name}" );
			$this->dbconnection->query( $usermeta_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' usermeta table.", $e->getMessage() );
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
				$columns = $this->db->escape_fields( $db_row, $this->dbname, $usermeta_table_name );

				try
				{
					$this->dbconnection->query( "INSERT INTO {$usermeta_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
				}
				catch( PDOException $e )
				{
					script_die( "Unable to insert '{$this->name}' usermeta '{$user_id}' key '{$key}' row.", $e->getMessage() );
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
						$columns = $this->db->escape_fields( $db_row, $this->dbname, $usermeta_table_name );

						try
						{
							$this->dbconnection->query( "INSERT INTO {$usermeta_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
						}
						catch( PDOException $e )
						{
							script_die( "Unable to insert '{$this->name}' usermeta '{$user_id}' key '{$db_row['meta_key']}' row.", $e->getMessage() );
						}
					}
				}
			}
		}
	}
	public function create_table_blog_versions()
	{
		global $db, $claspages, $pages;
		
		$blog_versions_table_name = $this->add_prefix( 'blog_versions' );
		$blog_versions_create_table_sql = $claspages->get_table_create_sql( 'blog_versions', true );
		$blog_versions_create_table_sql = str_replace( "`blog_versions`", $blog_versions_table_name, $blog_versions_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$blog_versions_table_name}" );
			$this->dbconnection->query( $blog_versions_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' blog_versions table.", $e->getMessage() );
		}
		
		foreach( array( $claspages, $pages ) as $site )
		{
			$data = $site->get_blog_versions();
			foreach( $data as $row )
			{
				$blog_id = $this->get_new_blog_id( $site->name, $row['blog_id'] );
				if( FALSE === $blog_id )
					continue;
				$row['blog_id'] = $blog_id;
				
				$columns = $this->db->escape_fields( $row, $this->dbname, $blog_versions_table_name );
				try
				{
					$this->dbconnection->query( "INSERT INTO {$blog_versions_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
				}
				catch( PDOException $e )
				{
					script_die( "Unable to insert '{$this->name}' blog_versions row.", $e->getMessage() );
				}
			}
		}
	}
	public function create_table_registration_log()
	{
		global $db, $claspages, $pages;
		
		$registration_log_table_name = $this->add_prefix( 'registration_log' );
		$registration_log_create_table_sql = $claspages->get_table_create_sql( 'registration_log', true );
		$registration_log_create_table_sql = str_replace( "`registration_log`", $registration_log_table_name, $registration_log_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$registration_log_table_name}" );
			$this->dbconnection->query( $registration_log_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' registration_log table.", $e->getMessage() );
		}
		
		foreach( array( $claspages, $pages ) as $site )
		{
			$data = $site->get_registration_log();
			foreach( $data as $row )
			{
				$blog_id = $this->get_new_blog_id( $site->name, $row['blog_id'] );
				if( FALSE === $blog_id )
					continue;
				$row['blog_id'] = $blog_id;
				unset( $row['ID'] );
				
				$columns = $this->db->escape_fields( $row, $this->dbname, $registration_log_table_name );
				try
				{
					$this->dbconnection->query( "INSERT INTO {$registration_log_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
				}
				catch( PDOException $e )
				{
					script_die( "Unable to insert '{$this->name}' registration_log row.", $e->getMessage() );
				}
			}
		}
	}
	public function create_table_signups()
	{
		global $db, $claspages, $pages;
		
		$signups_table_name = $this->add_prefix( 'signups' );
		$signups_create_table_sql = $claspages->get_table_create_sql( 'signups', true );
		$signups_create_table_sql = str_replace( "`signups`", $signups_table_name, $signups_create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$signups_table_name}" );
			$this->dbconnection->query( $signups_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' signups table.", $e->getMessage() );
		}
	}
	public function create_table_options()
	{
		global $db, $claspages, $pages;
		
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog )
		{
			$this->create_table_options_for_blog( $blog );
		}
		
		echo2( "\n" );
	}
	protected function create_table_options_for_blog( $blog )
	{
		echo2( "\n   Create options table for blog {$blog->new_id} from {$blog->old_site->name}.{$blog->old_id}..." );
		
		$op = '';
		if( $blog->old_id > 1 ) {
			$op = "{$blog->old_id}_";
		}
		$np = '';
		if( $blog->new_id > 1 ) {
			$np = "{$blog->new_id}_";
		}
		
		if( ! $blog->old_site->table_exists( $blog->old_site->add_prefix( $op.'options' ) ) ) {
			echo2( "doesn't exist." );
			return;
		}
		
		$options_table_name = $this->add_prefix( $np.'options' );
		$options_create_table_sql = $blog->old_site->get_table_create_sql( $op.'options', true );
		$options_create_table_sql = str_replace( "`{$op}options`", $options_table_name, $options_create_table_sql );

		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$options_table_name}" );
			$this->dbconnection->query( $options_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' '{$np}options' table.", $e->getMessage() );
		}
		
		$data = $blog->old_site->get_options( $blog->old_id );
		foreach( $data as $row )
		{
			switch( $row['option_name'] )
			{
				case 'siteurl':
				case 'home':
					if( $blog->new_domain != $blog->old_domain ) {
						$row['option_value'] = str_replace( $blog->old_domain, $blog->new_domain, $row['option_value'] );
					}
					break;
				case 'upload_path':
					$row['option_value'] = "wp-content/uploads/{$blog->new_id}/files";
					break;
				case 'recently_edited':
					$v = unserialize( $row['option_value'] );
					if( $v )
					{
						foreach( $v as &$p )
						{
							$p = str_replace( $blog->old_site->path, $this->path, $p );
						}
						$row['option_value'] = serialize( $v );
					}
					break;
			}
			
			$columns = $this->db->escape_fields( $row, $this->dbname, $options_table_name );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$options_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( "Unable to insert '{$this->name}' '{$np}options' row.", $e->getMessage() );
			}
		}
		
		echo2( "done." );
	}
	public function create_table_posts()
	{
		global $db, $claspages, $pages;
		
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog )
		{
			$this->create_table_posts_for_blog( $blog );
		}
		
		echo2( "\n" );
	}
	protected function create_table_posts_for_blog( $blog )
	{
		echo2( "\n   Create posts table for blog {$blog->new_id} from {$blog->old_site->name}.{$blog->old_id}..." );
		
		$op = '';
		if( $blog->old_id > 1 ) {
			$op = "{$blog->old_id}_";
		}
		$np = '';
		if( $blog->new_id > 1 ) {
			$np = "{$blog->new_id}_";
		}

		if( ! $blog->old_site->table_exists( $blog->old_site->add_prefix( $op.'posts' ) ) ) {
			echo2( "doesn't exist." );
			return;
		}
		
		$posts_table_name = $this->add_prefix( $np.'posts' );
		$posts_create_table_sql = $blog->old_site->get_table_create_sql( $op.'posts', true );
		$posts_create_table_sql = str_replace( "`{$op}posts`", $posts_table_name, $posts_create_table_sql );

		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$posts_table_name}" );
			$this->dbconnection->query( $posts_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' '{$np}posts' table.", $e->getMessage() );
		}
		
		$data = $blog->old_site->get_posts( $blog->old_id );
		foreach( $data as $row )
		{
			$row['post_author'] = $this->get_new_user_id( $blog->old_site->name, $row['post_author'] );
			if( $blog->old_domain != $blog->new_domain ) {
				$row['guid'] = str_replace( $blog->old_domain, $blog->new_domain, $row['guid'] );
			}
			
			$columns = $this->db->escape_fields( $row, $this->dbname, $posts_table_name );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$posts_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( "Unable to insert '{$this->name}' '{$np}posts' row.", $e->getMessage() );
			}
		}
		
		echo2( "done." );
	}
	public function create_table_postmeta()
	{
		global $db, $claspages, $pages;
		
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog )
		{
			$this->create_table_postmeta_for_blog( $blog );
		}
		
		echo2( "\n" );
	}
	protected function create_table_postmeta_for_blog( $blog )
	{
		echo2( "\n   Create postmeta table for blog {$blog->new_id} from {$blog->old_site->name}.{$blog->old_id}..." );
		
		$op = '';
		if( $blog->old_id > 1 ) {
			$op = "{$blog->old_id}_";
		}
		$np = '';
		if( $blog->new_id > 1 ) {
			$np = "{$blog->new_id}_";
		}

		if( ! $blog->old_site->table_exists( $blog->old_site->add_prefix( $op.'postmeta' ) ) ) {
			echo2( "doesn't exist." );
			return;
		}
		
		$postmeta_table_name = $this->add_prefix( $np.'postmeta' );
		$postmeta_create_table_sql = $blog->old_site->get_table_create_sql( $op.'postmeta', true );
		$postmeta_create_table_sql = str_replace( "`{$op}postmeta`", $postmeta_table_name, $postmeta_create_table_sql );

		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$postmeta_table_name}" );
			$this->dbconnection->query( $postmeta_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' '{$np}postmeta' table.", $e->getMessage() );
		}
		
		$data = $blog->old_site->get_postmeta( $blog->old_id );
		foreach( $data as $row )
		{
			
			
			$columns = $this->db->escape_fields( $row, $this->dbname, $postmeta_table_name );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$postmeta_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( "Unable to insert '{$this->name}' '{$np}postmeta' row.", $e->getMessage() );
			}
		}
		
		echo2( "done." );
	}
	public function create_table_comments()
	{
		global $db, $claspages, $pages;
		
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog )
		{
			$this->create_table_comments_for_blog( $blog );
		}
		
		echo2( "\n" );
	}
	protected function create_table_comments_for_blog( $blog )
	{
		echo2( "\n   Create comments table for blog {$blog->new_id} from {$blog->old_site->name}.{$blog->old_id}..." );
		
		$op = '';
		if( $blog->old_id > 1 ) {
			$op = "{$blog->old_id}_";
		}
		$np = '';
		if( $blog->new_id > 1 ) {
			$np = "{$blog->new_id}_";
		}

		if( ! $blog->old_site->table_exists( $blog->old_site->add_prefix( $op.'comments' ) ) ) {
			echo2( "doesn't exist." );
			return;
		}
		
		$comments_table_name = $this->add_prefix( $np.'comments' );
		$comments_create_table_sql = $blog->old_site->get_table_create_sql( $op.'comments', true );
		$comments_create_table_sql = str_replace( "`{$op}comments`", $comments_table_name, $comments_create_table_sql );

		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$comments_table_name}" );
			$this->dbconnection->query( $comments_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' '{$np}comments' table.", $e->getMessage() );
		}
		
		$data = $blog->old_site->get_comments( $blog->old_id );
		foreach( $data as $row )
		{
			$columns = $this->db->escape_fields( $row, $this->dbname, $comments_table_name );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$comments_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( "Unable to insert '{$this->name}' '{$np}comments' row.", $e->getMessage() );
			}
		}
		
		echo2( "done." );
	}
	public function create_table_commentmeta()
	{
		global $db, $claspages, $pages;
		
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog )
		{
			$this->create_table_commentmeta_for_blog( $blog );
		}
		
		echo2( "\n" );
	}
	protected function create_table_commentmeta_for_blog( $blog )
	{
		echo2( "\n   Create commentmeta table for blog {$blog->new_id} from {$blog->old_site->name}.{$blog->old_id}..." );
		
		$op = '';
		if( $blog->old_id > 1 ) {
			$op = "{$blog->old_id}_";
		}
		$np = '';
		if( $blog->new_id > 1 ) {
			$np = "{$blog->new_id}_";
		}

		if( ! $blog->old_site->table_exists( $blog->old_site->add_prefix( $op.'commentmeta' ) ) ) {
			echo2( "doesn't exist." );
			return;
		}
		
		$commentmeta_table_name = $this->add_prefix( $np.'commentmeta' );
		$commentmeta_create_table_sql = $blog->old_site->get_table_create_sql( $op.'commentmeta', true );
		$commentmeta_create_table_sql = str_replace( "`{$op}commentmeta`", $commentmeta_table_name, $commentmeta_create_table_sql );

		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$commentmeta_table_name}" );
			$this->dbconnection->query( $commentmeta_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' '{$np}commentmeta' table.", $e->getMessage() );
		}
		
		$data = $blog->old_site->get_commentmeta( $blog->old_id );
		foreach( $data as $row )
		{
			$columns = $this->db->escape_fields( $row, $this->dbname, $commentmeta_table_name );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$commentmeta_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( "Unable to insert '{$this->name}' '{$np}commentmeta' row.", $e->getMessage() );
			}
		}
		
		echo2( "done." );
	}
	public function create_table_links()
	{
		global $db, $claspages, $pages;
		
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog )
		{
			$this->create_table_links_for_blog( $blog );
		}
		
		echo2( "\n" );
	}
	protected function create_table_links_for_blog( $blog )
	{
		echo2( "\n   Create links table for blog {$blog->new_id} from {$blog->old_site->name}.{$blog->old_id}..." );
		
		$op = '';
		if( $blog->old_id > 1 ) {
			$op = "{$blog->old_id}_";
		}
		$np = '';
		if( $blog->new_id > 1 ) {
			$np = "{$blog->new_id}_";
		}

		if( ! $blog->old_site->table_exists( $blog->old_site->add_prefix( $op.'links' ) ) ) {
			echo2( "doesn't exist." );
			return;
		}
		
		$links_table_name = $this->add_prefix( $np.'links' );
		$links_create_table_sql = $blog->old_site->get_table_create_sql( $op.'links', true );
		$links_create_table_sql = str_replace( "`{$op}links`", $links_table_name, $links_create_table_sql );

		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$links_table_name}" );
			$this->dbconnection->query( $links_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' '{$np}links' table.", $e->getMessage() );
		}
		
		$data = $blog->old_site->get_links( $blog->old_id );
		foreach( $data as $row )
		{
			$columns = $this->db->escape_fields( $row, $this->dbname, $links_table_name );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$links_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( "Unable to insert '{$this->name}' '{$np}links' row.", $e->getMessage() );
			}
		}
		
		echo2( "done." );
	}
	public function create_table_terms()
	{
		global $db, $claspages, $pages;
		
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog )
		{
			$this->create_table_terms_for_blog( $blog );
		}
		
		echo2( "\n" );
	}
	protected function create_table_terms_for_blog( $blog )
	{
		echo2( "\n   Create terms table for blog {$blog->new_id} from {$blog->old_site->name}.{$blog->old_id}..." );
		
		$op = '';
		if( $blog->old_id > 1 ) {
			$op = "{$blog->old_id}_";
		}
		$np = '';
		if( $blog->new_id > 1 ) {
			$np = "{$blog->new_id}_";
		}

		if( ! $blog->old_site->table_exists( $blog->old_site->add_prefix( $op.'terms' ) ) ) {
			echo2( "doesn't exist." );
			return;
		}
		
		$terms_table_name = $this->add_prefix( $np.'terms' );
		$terms_create_table_sql = $blog->old_site->get_table_create_sql( $op.'terms', true );
		$terms_create_table_sql = str_replace( "`{$op}terms`", $terms_table_name, $terms_create_table_sql );

		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$terms_table_name}" );
			$this->dbconnection->query( $terms_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' '{$np}terms' table.", $e->getMessage() );
		}
		
		$data = $blog->old_site->get_terms( $blog->old_id );
		foreach( $data as $row )
		{
			$columns = $this->db->escape_fields( $row, $this->dbname, $terms_table_name );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$terms_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( "Unable to insert '{$this->name}' '{$np}terms' row.", $e->getMessage() );
			}
		}
		
		echo2( "done." );
	}
	public function create_table_term_taxonomy()
	{
		global $db, $claspages, $pages;
		
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog )
		{
			$this->create_table_term_taxonomy_for_blog( $blog );
		}
		
		echo2( "\n" );
	}
	protected function create_table_term_taxonomy_for_blog( $blog )
	{
		echo2( "\n   Create term_taxonomy table for blog {$blog->new_id} from {$blog->old_site->name}.{$blog->old_id}..." );
		
		$op = '';
		if( $blog->old_id > 1 ) {
			$op = "{$blog->old_id}_";
		}
		$np = '';
		if( $blog->new_id > 1 ) {
			$np = "{$blog->new_id}_";
		}

		if( ! $blog->old_site->table_exists( $blog->old_site->add_prefix( $op.'term_taxonomy' ) ) ) {
			echo2( "doesn't exist." );
			return;
		}
		
		$term_taxonomy_table_name = $this->add_prefix( $np.'term_taxonomy' );
		$term_taxonomy_create_table_sql = $blog->old_site->get_table_create_sql( $op.'term_taxonomy', true );
		$term_taxonomy_create_table_sql = str_replace( "`{$op}term_taxonomy`", $term_taxonomy_table_name, $cterm_taxonomy_create_table_sql );

		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$term_taxonomy_table_name}" );
			$this->dbconnection->query( $term_taxonomy_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' '{$np}term_taxonomy' table.", $e->getMessage() );
		}
		
		$data = $blog->old_site->get_term_taxonomy( $blog->old_id );
		foreach( $data as $row )
		{
			$columns = $this->db->escape_fields( $row, $this->dbname, $term_taxonomy_table_name );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$term_taxonomy_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( "Unable to insert '{$this->name}' '{$np}term_taxonomy' row.", $e->getMessage() );
			}
		}
		
		echo2( "done." );
	}
	public function create_table_term_relationships()
	{
		global $db, $claspages, $pages;
		
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog )
		{
			$this->create_table_term_relationships_for_blog( $blog );
		}
		
		echo2( "\n" );
	}
	protected function create_table_term_relationships_for_blog( $blog )
	{
		echo2( "\n   Create term_relationships table for blog {$blog->new_id} from {$blog->old_site->name}.{$blog->old_id}..." );
		
		$op = '';
		if( $blog->old_id > 1 ) {
			$op = "{$blog->old_id}_";
		}
		$np = '';
		if( $blog->new_id > 1 ) {
			$np = "{$blog->new_id}_";
		}

		if( ! $blog->old_site->table_exists( $blog->old_site->add_prefix( $op.'term_relationships' ) ) ) {
			echo2( "doesn't exist." );
			return;
		}
		
		$term_relationships_table_name = $this->add_prefix( $np.'term_relationships' );
		$term_relationships_create_table_sql = $blog->old_site->get_table_create_sql( $op.'term_relationships', true );
		$term_relationships_create_table_sql = str_replace( "`{$op}term_relationships`", $term_relationships_table_name, $term_relationships_create_table_sql );

		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$term_relationships_table_name}" );
			$this->dbconnection->query( $term_relationships_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' '{$np}term_relationships' table.", $e->getMessage() );
		}
		
		$data = $blog->old_site->get_term_relationships( $blog->old_id );
		foreach( $data as $row )
		{
			$columns = $this->db->escape_fields( $row, $this->dbname, $term_relationships_table_name );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$term_relationships_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( "Unable to insert '{$this->name}' '{$np}term_relationships' row.", $e->getMessage() );
			}
		}
		
		echo2( "done." );
	}
	public function create_table_termmeta()
	{
		global $db, $claspages, $pages;
		
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog )
		{
			$this->create_table_termmeta_for_blog( $blog );
		}
		
		echo2( "\n" );
	}
	protected function create_table_termmeta_for_blog( $blog )
	{
		echo2( "\n   Create termmeta table for blog {$blog->new_id} from {$blog->old_site->name}.{$blog->old_id}..." );
		
		$op = '';
		if( $blog->old_id > 1 ) {
			$op = "{$blog->old_id}_";
		}
		$np = '';
		if( $blog->new_id > 1 ) {
			$np = "{$blog->new_id}_";
		}

		if( ! $blog->old_site->table_exists( $blog->old_site->add_prefix( $op.'termmeta' ) ) ) {
			echo2( "doesn't exist." );
			return;
		}
		
		$termmeta_table_name = $this->add_prefix( $np.'termmeta' );
		$termmeta_create_table_sql = $blog->old_site->get_table_create_sql( $op.'termmeta', true );
		$termmeta_create_table_sql = str_replace( "`{$op}termmeta`", $termmeta_table_name, $termmeta_create_table_sql );

		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$termmeta_table_name}" );
			$this->dbconnection->query( $termmeta_create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create '{$this->name}' '{$np}termmeta' table.", $e->getMessage() );
		}
		
		$data = $blog->old_site->get_termmeta( $blog->old_id );
		foreach( $data as $row )
		{
			$columns = $this->db->escape_fields( $row, $this->dbname, $termmeta_table_name );
			try
			{
				$this->dbconnection->query( "INSERT INTO {$termmeta_table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
			}
			catch( PDOException $e )
			{
				script_die( "Unable to insert '{$this->name}' '{$np}termmeta' row.", $e->getMessage() );
			}
		}
		
		echo2( "done." );
	}
}

