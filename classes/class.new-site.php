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
	public function create_table( $site, $name, $table_name )
	{
		$create_table_sql = $site->get_table_create_sql( $name, true );
		$create_table_sql = str_replace( "`{$name}`", $table_name, $create_table_sql );
	
		try
		{
			$this->dbconnection->query( "DROP TABLE IF EXISTS {$table_name}" );
			$this->dbconnection->query( $create_table_sql );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to create `{$this->name}`.`{$name}` table.", "DROP TABLE IF EXISTS {$table_name}", $e->getMessage() );
		}
	}
	public function insert( $row, $name, $dbname, $table_name )
	{
		$columns = $this->db->escape_fields( $row, $dbname, $table_name );
		try
		{
			$this->dbconnection->query( "INSERT INTO {$table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");" );
		}
		catch( PDOException $e )
		{
			script_die( "Unable to insert `{$this->name}`.`{$name}` row.", "INSERT INTO {$table_name} (`" . implode( '`,`', array_keys( $columns ) ) . "`) VALUES (". implode( ',', $columns ) .");", $e->getMessage() );
		}
	}
	public function create_table_site()
	{
		global $db, $claspages, $pages;
		$name = 'site';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
		
		$row = array(
			'id' => 1,
			'domain' => $this->domain,
			'path' => '/',
		);
		$this->insert( $row, $name, $this->dbname, $table_name );
	}
	public function create_table_sitemeta()
	{
		global $db, $claspages, $pages;
		$name = 'sitemeta';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
		
		$count = 0;
		while( $rows = $claspages->get_table_row_list( $name, $count, 1000 ) )
		{
			echo2( '.' );
			
			foreach( $rows as $row )
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

				$this->insert( $row, $name, $this->dbname, $table_name );
			}
			
			$count++;
		}
	}
	public function create_table_blogs()
	{
		global $db, $claspages, $pages;
		$name = 'blogs';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
		
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog )
		{
			$row = $blog->get_new_blog_table_row();
			$this->insert( $row, $name, $this->dbname, $table_name );
		}
	}
	public function create_table_domain_mapping()
	{
		global $db, $claspages, $pages;
		$name = 'domain_mapping';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
		$this->create_table( $claspages, 'domain_mapping_reseller_log', $this->add_prefix( 'domain_mapping_reseller_log' ) );
		
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
			
				$row = $blog->old_site->get_domain_mapped_row( $blog->db_row['blog_id'] );
				$row['blog_id'] = $blog->new_id;
				unset( $row['id'] );
			
				$this->insert( $row, $name, $this->dbname, $table_name );
			}
		}
	}
	public function create_table_users()
	{
		global $db, $claspages, $pages;
		$name = 'users';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
		
		$count = 0;
		$i = 0;
		foreach( $this->users as $username => $users )
		{
			if( $i % 1000 == 0 )
			{
				echo2( '.' );
				$count++;
			}
			
			$keys = array_keys( $users );
			$user = $users[ $keys[0] ];
			$row = $user->get_new_user_table_row();

			$this->insert( $row, $name, $this->dbname, $table_name );
			$i++;
		}
	}
	public function create_table_usermeta()
	{
		global $db, $claspages, $pages;
		$name = 'usermeta';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );

		$count = 0;
		$i = 0;
		foreach( $this->users as $username => $users )
		{
			if( $i % 1000 == 0 )
			{
				echo2( '.' );
				$count++;
			}
			
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
				
				$row = array(
					'user_id' => $user->new_id,
					'meta_key' => $key,
					'meta_value' => $value,
				);
				$this->insert( $row, $name, $this->dbname, $table_name );
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
						$row = array(
							'user_id' => $user->new_id,
							'meta_key' => $this->add_prefix( $blog_id.'_'.$key ),
							'meta_value' => $value,
						);
						$this->insert( $row, $name, $this->dbname, $table_name );
					}
				}
			}
		}
	}
	public function create_table_blog_versions()
	{
		global $db, $claspages, $pages;
		$name = 'blog_versions';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
		
		foreach( array( $claspages, $pages ) as $site )
		{
			$count = 0;
			while( $rows = $site->get_table_row_list( $name, $count, 1000 ) )
			{
				echo2( '.' );
			
				foreach( $rows as $row )
				{
					$blog_id = $this->get_new_blog_id( $site->name, $row['blog_id'] );
					if( FALSE === $blog_id )
						continue;
					
					$row['blog_id'] = $blog_id;
				
					$this->insert( $row, $name, $this->dbname, $table_name );
				}
				
				$count++;
			}
		}
	}
	public function create_table_registration_log()
	{
		global $db, $claspages, $pages;
		$name = 'registration_log';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
		
		foreach( array( $claspages, $pages ) as $site )
		{
			$count = 0;
			while( $rows = $site->get_table_row_list( $name, $count, 1000 ) )
			{
				echo2( '.' );
			
				foreach( $rows as $row )
				{
					$blog_id = $this->get_new_blog_id( $site->name, $row['blog_id'] );
					if( FALSE === $blog_id )
						continue;

					$row['blog_id'] = $blog_id;
					unset( $row['ID'] );
				
					$this->insert( $row, $name, $this->dbname, $table_name );
				}
				
				$count++;
			}
		}
	}
	public function create_table_signups()
	{
		global $db, $claspages, $pages;
		$name = 'signups';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
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
		$name = 'options';
		echo2( "\n   Create options table for blog {$this->name}.{$blog->new_id} from {$blog->old_site->name}.{$blog->old_id}..." );
		
		$op = '';
		if( $blog->old_id > 1 ) {
			$op = "{$blog->old_id}_";
		}
		$np = '';
		if( $blog->new_id > 1 ) {
			$np = "{$blog->new_id}_";
		}
		
		$table_name = $this->add_prefix( $np.$name );
		if( ! $blog->old_site->table_exists( $blog->old_site->add_prefix( $op.$name ) ) ) {
			echo2( "doesn't exist." );
			return;
		}
		
		$this->create_table( $blog->old_site, $op.$name, $table_name );

		$count = 0;
		while( $rows = $blog->old_site->get_blog_table_row_list( $blog->old_id, $name, $count, 1000 ) )
		{
			echo2( '.' );
			
			foreach( $rows as $row )
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
			
				$this->insert( $row, $name, $this->dbname, $table_name );
			}
			
			$count++;
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
		$name = 'posts';
		echo2( "\n   Create {$name} table for {$this->name} blog {$blog->new_id} from {$blog->old_site->name} blog {$blog->old_id}..." );
		
		$op = '';
		if( $blog->old_id > 1 ) {
			$op = "{$blog->old_id}_";
		}
		$np = '';
		if( $blog->new_id > 1 ) {
			$np = "{$blog->new_id}_";
		}
		
		if( ! $blog->old_site->table_exists( $blog->old_site->add_prefix( $op.$name ) ) ) {
			echo2( "doesn't exist." );
			return;
		}
		
		$table_name = $this->add_prefix( $np.$name );
		$this->create_table( $blog->old_site, $op.$name, $table_name );
		
		$count = 0;
		while( $rows = $blog->old_site->get_blog_table_row_list( $blog->old_id, $name, $count, 1000 ) )
		{
			echo2( '.' );
			
			foreach( $rows as $row )
			{
				$row['post_author'] = $this->get_new_user_id( $blog->old_site->name, $row['post_author'] );
				
				$this->insert( $row, $name, $this->dbname, $table_name );
			}
			
			$count++;
		}
		
		echo2( "done." );
	}
	public function create_table_postmeta()
	{
		$this->create_table_for_all_blogs( 'postmeta' );
	}
	public function create_table_comments()
	{
		$this->create_table_for_all_blogs( 'comments' );
	}
	public function create_table_commentmeta()
	{
		$this->create_table_for_all_blogs( 'commentmeta' );
	}
	public function create_table_links()
	{
		$this->create_table_for_all_blogs( 'links' );
	}
	public function create_table_terms()
	{
		$this->create_table_for_all_blogs( 'terms' );
	}
	public function create_table_term_taxonomy()
	{
		$this->create_table_for_all_blogs( 'term_relationships' );
	}
	public function create_table_term_relationships()
	{
		$this->create_table_for_all_blogs( 'term_relationships' );
	}
	public function create_table_termmeta()
	{
		$this->create_table_for_all_blogs( 'termmeta' );
	}
	
	public function create_table_batch_create_table_queue()
	{
		global $db, $claspages, $pages;
		$name = 'batch_create_table_queue';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_batch_create_table_queuemeta()
	{
		global $db, $claspages, $pages;
		$name = 'batch_create_table_queuemeta';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_frmpro_copies()
	{
		global $db, $claspages, $pages;
		$name = 'frmpro_copies';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_gaplus_login()
	{
		global $db, $claspages, $pages;
		$name = 'gaplus_login';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_itsec_lockouts()
	{
		global $db, $claspages, $pages;
		$name = 'itsec_lockouts';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_itsec_log()
	{
		global $db, $claspages, $pages;
		$name = 'itsec_log';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_itsec_temp()
	{
		global $db, $claspages, $pages;
		$name = 'itsec_temp';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_nbt_categories_relationships_table()
	{
		global $db, $claspages, $pages;
		$name = 'nbt_categories_relationships_table';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_nbt_templates()
	{
		global $db, $claspages, $pages;
		$name = 'nbt_templates';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_nbt_templates_categories()
	{
		global $db, $claspages, $pages;
		$name = 'nbt_templates_categories';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_wiki_subscriptions()
	{
		global $db, $claspages, $pages;
		$name = 'wiki_subscriptions';
		$table_name = $this->add_prefix( $name );
		
		if( ! $pages->table_exists( $pages->add_prefix( $name ) ) ) return;
		$this->create_table( $pages, $name, $table_name );
	}
	public function create_table_orghub_category()
	{
		global $db, $claspages, $pages;
		$name = 'orghub_category';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_orghub_connections()
	{
		global $db, $claspages, $pages;
		$name = 'orghub_connections';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_orghub_site()
	{
		global $db, $claspages, $pages;
		$name = 'orghub_site';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_orghub_type()
	{
		global $db, $claspages, $pages;
		$name = 'orghub_type';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_orghub_upload()
	{
		global $db, $claspages, $pages;
		$name = 'orghub_upload';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_orghub_user()
	{
		global $db, $claspages, $pages;
		$name = 'orghub_user';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	public function create_table_smackcsv_line_log()
	{
		global $db, $claspages, $pages;
		$name = 'smackcsv_line_log';
		$table_name = $this->add_prefix( $name );
		
		if( ! $claspages->table_exists( $claspages->add_prefix( $name ) ) ) return;
		$this->create_table( $claspages, $name, $table_name );
	}
	
	public function create_table_frm_forms()
	{
		$this->create_table_for_all_blogs( 'frm_forms' );
	}
	public function create_table_frm_fields()
	{
		$this->create_table_for_all_blogs( 'frm_fields' );
	}
	public function create_table_frm_items()
	{
		$this->create_table_for_all_blogs( 'frm_items' );
	}
	public function create_table_frm_item_metas()
	{
		$this->create_table_for_all_blogs( 'frm_item_metas' );
	}
	public function create_table_wpmm_subscribers()
	{
		$this->create_table_for_all_blogs( 'wpmm_subscribers' );
	}
	public function create_table_ngg_album()
	{
		$this->create_table_for_all_blogs( 'ngg_album' );
	}
	public function create_table_ngg_gallery()
	{
		$this->create_table_for_all_blogs( 'ngg_gallery' );
	}
	public function create_table_ngg_pictures()
	{
		$this->create_table_for_all_blogs( 'ngg_pictures' );
	}
	public function create_table_redirection_404()
	{
		$this->create_table_for_all_blogs( 'redirection_404' );
	}
	public function create_table_redirection_groups()
	{
		$this->create_table_for_all_blogs( 'redirection_groups' );
	}
	public function create_table_redirection_items()
	{
		$this->create_table_for_all_blogs( 'redirection_items' );
	}
	public function create_table_redirection_logs()
	{
		$this->create_table_for_all_blogs( 'redirection_logs' );
	}
	public function create_table_redirection_modules()
	{
		$this->create_table_for_all_blogs( 'redirection_modules' );
	}

	
	protected function create_table_for_all_blogs( $name, $limit = 1000 )
	{
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog ) {
			$this->create_table_for_blog( $blog, $name );
		}
		
		echo2( "\n" );
	}
	protected function create_table_for_blog( $blog, $name, $limit = 1000 )
	{
		echo2( "\n   Create {$name} table for {$this->name} blog {$blog->new_id} from {$blog->old_site->name} blog {$blog->old_id}..." );
		
		$op = '';
		if( $blog->old_id > 1 ) {
			$op = "{$blog->old_id}_";
		}
		$np = '';
		if( $blog->new_id > 1 ) {
			$np = "{$blog->new_id}_";
		}

		$table_name = $this->add_prefix( $np.$name );
		if( ! $blog->old_site->table_exists( $blog->old_site->add_prefix( $op.$name ) ) ) {
			echo2( "doesn't exist." );
			return;
		}
		
		$this->create_table( $blog->old_site, $op.$name, $table_name );
		
		$count = 0;
		while( $rows = $blog->old_site->get_blog_table_row_list( $blog->old_id, $name, $count, $limit ) )
		{
			if( $count > 1 ) echo2( '.' );
			
			foreach( $rows as $row )
			{
				$this->insert( $row, $name, $this->dbname, $table_name );
			}
			
			$count++;
		}
		
		echo2( "done." );
	}
	public function copy_wp_folder()
	{
		global $claspages, $pages;
		
		$exclude_files = '--exclude wp-config.php --exclude=.git --exclude=error_log';
		if( !$copy_all )
		{
			$exclude_files .= ' --exclude=wp-content/blogs.dir --exclude=wp-content/uploads';
		}
	
		passthru( "rsync -az $exclude_files '{$old_site->username}@${$old_site->server_name}:{$old_site->wp_path}/'  {$this->wp_path}" );
	}
	public function copy_uploads_folder()
	{
		foreach( array_merge( array( $this->base_blog ), $this->blogs ) as $blog ) {
			$this->copy_uploads_folder_for_blog( $blog );
		}
		
		echo2( "\n" );
	}
	protected function copy_uploads_folder_for_blog( $blog )
	{
		echo2( "\n   Copy uploads folder for {$this->name} blog {$blog->new_id} from {$blog->old_site->name} blog {$blog->old_id}..." );
		
		$old_uploads_path = $blog->old_site->get_option( $blog->old_id, 'uploads_path' );
		$new_uploads_path = $this->get_option( $blog->new_id, 'uploads_path' );
		
		if( ! $old_uploads_path )
		{
			echo2( "no old uploads path." );
			return;
		}
		if( ! $new_uploads_path )
		{
			echo2( "no new uploads path." );
			return;
		}
		
		passthru( "rsync -az '{$blog->old_site->username}@${$blog->old_site->domain}:{$blog->old_site->path}/{$old_uploads_path}'  {$this->path}/{$new_uploads_path}" );
		
		echo2( "done." );
	}	
}

