<?php

/*** Wordpress Groupblog Admin Settings ********************************/
/**
 * Acknowledgement:
 * Deanna Schneider's cets_blog_defaults plugin was a great help
 * in developing this feature for bp-groupblog. Her plugin can be
 * found at http://wpmudev.org/project/New-Blog-Defaults.
 */
function bp_groupblog_blog_defaults( $blog_id ) {
	global $bp, $wp_rewrite;
			
	switch_to_blog( $blog_id );

	// only apply defaults to groupblog blogs
	if ( bp_is_groups_component() ) {
		
		// get the site options 
		$options = get_site_option( 'bp_groupblog_blog_defaults_options' );
		
		foreach( $options as $key => $value )
			update_option( $key, $value );	
	
		// override default themes
		if ( ! empty( $options['theme'] ) ) {
			// we want something other than the default theme
			$values = explode( "|", $options['theme'] );
			switch_theme( $values[0], $values[1] );	
		}

		// groupblog bonus options
		if ( strlen( $options['default_cat_name'] ) > 0 ) {
			global $wpdb;
			$cat = $options['default_cat_name'];
			$slug = str_replace( ' ', '-', strtolower( $cat ) ); 
			$results = $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->terms SET name = %s, slug = %s  WHERE term_id = 1", $cat, $slug ) );				
		}
		if ( strlen( $options['default_link_cat'] ) > 0 ) {
			global $wpdb;
			$cat = $options['default_link_cat'];
			$slug = str_replace( ' ', '-', strtolower( $cat ) ); 
			$results = $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->terms SET name = %s, slug = %s  WHERE term_id = 2", $cat, $slug ) );	
		}
		if ( isset( $options['delete_first_post'] ) && $options['delete_first_post'] == 1 ) {
			global $wpdb;
			$statement = "UPDATE $wpdb->posts SET post_status = 'draft'  WHERE id = 1";	
			$results = $wpdb->query( $statement );
		}		
		if ( isset( $options['delete_first_comment'] ) && $options['delete_first_comment'] == 1 ) {
			wp_delete_comment( 1 );
		}
		if ( $options['delete_blogroll_links'] == 1 ) {
		 	wp_delete_link( 1 ); //delete Wordpress.com blogroll link
    	wp_delete_link( 2 ); //delete Wordpress.org blogroll link
		}
		
	}
	
	restore_current_blog();			
}

function bp_groupblog_update_defaults() {
	
	// create an array to hold the chosen options
	$newoptions = array();
	$newoptions['theme'] = $_POST['theme'];

	// groupblog validation settings
	$newoptions['allowdashes'] = ($_POST['bp_groupblog_allowdashes'] == 1) ? 1 : 0;
	$newoptions['allowunderscores'] = ($_POST['bp_groupblog_allowunderscores'] == 1) ? 1 : 0;
	$newoptions['allownumeric'] = ($_POST['bp_groupblog_allownumeric'] == 1) ? 1 : 0;
	$newoptions['minlength'] = (is_numeric($_POST['bp_groupblog_minlength']) == true) ?  $_POST['bp_groupblog_minlength'] : 4;
		
	// groupblog default settings
	$newoptions['default_cat_name'] = $_POST['default_cat_name'];
	$newoptions['default_link_cat'] = $_POST['default_link_cat'];
	if ( $_POST['delete_first_post'] == 1 )
		$newoptions['delete_first_post'] = 1;
	else
		$newoptions['delete_first_post'] = 0;
	if ( $_POST['delete_first_comment'] == 1 )
		$newoptions['delete_first_comment'] = 1;
	else
		$newoptions['delete_first_comment'] = 0;
	if ( $_POST['delete_blogroll_links'] == 1 )
		$newoptions['delete_blogroll_links'] = 1;
	else
		$newoptions['delete_blogroll_links'] = 0;

	// groupblog redirect options
	$newoptions['redirecthome'] = ($_POST['bp_groupblog_redirect_home'] == 1) ? 1 : 0;
	$newoptions['redirectblog'] = ($_POST['bp_groupblog_redirect_blog'] == 1) ? 1 : 0;
		
	// override the site option
	update_site_option ('bp_groupblog_blog_defaults_options', $newoptions); 
		
	$options = get_site_option('bp_groupblog_blog_defaults_options');				
}

/**
 * bp_groupblog_add_admin_menu()
 */
function bp_groupblog_add_admin_menu() {
	global $wpdb, $bp;
	
	if ( !is_site_admin() )
		return false;
			
	/* Add the administration tab under the "Site Admin" tab for site administrators */
	add_submenu_page( 'bp-general-settings', __( 'Group Blog Setup', 'groupblog' ), '<span class="bp-groupblog-admin-menu-header">' . __( 'Group Blog Setup', 'groupblog' ) . '&nbsp;&nbsp;&nbsp;</span>', 'manage_settings', 'bp_groupblog_management_page', 'bp_groupblog_management_page' );
		
}
add_action( 'admin_menu', 'bp_groupblog_add_admin_menu', 12 );

function bp_groupblog_management_page() {
	global $wpdb;
	
	// only allow site admins to come here.
	if( is_site_admin() == false )
		wp_die( __( 'You do not have permission to access this page.', 'groupblog' ) );
		
	// process form submission    	
  if ( $_POST['action'] == 'update' ) {
		bp_groupblog_update_defaults();
		$updated = true;
  }

	// make sure we're using latest data
	$opt = get_site_option( 'bp_groupblog_blog_defaults_options' );
	?>
			
	<?php if ($updated) { ?>
  	<div id="message" class="updated fade">
  		<p><?php _e( 'Options saved.', 'groupblog' ) ?></p>
  	</div>
  <?php	} ?>
        	
	<div class="wrap" style="position: relative">
		<h2><?php _e( 'Group Blog Settings', 'groupblog' ) ?></h2>

		<form name="blogdefaultsform" action="" method="post">
		
			<?php 
			$themes = get_themes();
			$ct = current_theme_info();
	
			$allowed_themes = get_site_allowed_themes();
			if( $allowed_themes == false )
				$allowed_themes = array();
		
			$blog_allowed_themes = wpmu_get_blog_allowedthemes();
			if( is_array( $blog_allowed_themes ) )
				$allowed_themes = array_merge( $allowed_themes, $blog_allowed_themes );
	
			if( $blog_id != 1 )
				unset( $allowed_themes[ 'h3' ] );
			
			if( isset( $allowed_themes[ wp_specialchars( $ct->stylesheet ) ] ) == false )
				$allowed_themes[ wp_specialchars( $ct->stylesheet ) ] = true;
			
			reset( $themes );
			foreach( $themes as $key => $theme ) {
				if( isset( $allowed_themes[ wp_specialchars( $theme[ 'Stylesheet' ] ) ] ) == false ) {
					unset( $themes[ $key ] );
				}
			}
			reset( $themes );
			
			// get the names of the themes & sort them
			$theme_names = array_keys( $themes );
			natcasesort( $theme_names );
			?>
	
			<h3><?php _e( 'Default Theme', 'groupblog' ) ?></h3>
			<table class="form-table">
		  	<tr valign="top">
			  	<th><?php _e( 'Select the default theme:', 'groupblog' ) ?></th>
			    <td>
						<select name="theme" size="1">
		       	
		       	<?php
						foreach ( $theme_names as $theme_name ) {
							$template = $themes[$theme_name]['Template'];
							$stylesheet = $themes[$theme_name]['Stylesheet'];
							$title = $themes[$theme_name]['Title'];
							$selected = "";
							if( $opt[theme] == $template . "|" . $stylesheet ) {
								$selected = "selected = 'selected' ";
							}
							echo('<option value="' . $template . "|" . $stylesheet .  '"' . $selected . '>' . $title . "</option>");
						}
						?>
	
						</select>
	       	</td>
	      </tr>
	    </table>
	    
			<br />
			
			<h3><?php _e( 'Validation Settings', 'groupblog' ) ?></h3>

			<div><?php _e( 'Change the default WordPress blog validation settings.' ) ?></div>
			<table class="form-table">
				<tbody>
				<tr>
					<th><?php _e( 'Allow:', 'groupblog' ) ?></th>
					<td>
						<label for="bp_groupblog_allowdashes">
		       		<input name="bp_groupblog_allowdashes" type="checkbox" id="bp_groupblog_allowdashes" value="1" <?php if ($opt['allowdashes']== 1) echo 'checked="checked"'; ?> /> <?php _e( 'Dashes', 'groupblog' ) ?> <?php _e( '(Default: Not Allowed)', 'groupblog' ) ?>
		       	</label>
					</td>
				</tr>
		    <tr>
		    	<th></th>
					<td>
						<label for="bp_groupblog_allowunderscores">
			        <input name="bp_groupblog_allowunderscores" type="checkbox" id="bp_groupblog_allowunderscores" value="1" <?php if ($opt['allowunderscores']== 1) echo 'checked="checked"'; ?> /> <?php _e( 'Underscores', 'groupblog' ) ?> <?php _e( '(Default: Not Allowed)', 'groupblog' ) ?>
			       </label>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<label for="bp_groupblog_allownumeric">
							<input name="bp_groupblog_allownumeric" type="checkbox" id="bp_groupblog_allownumeric" value="1" <?php if ($opt['allownumeric']== 1) echo 'checked="checked"'; ?> /> <?php _e( 'All Numeric Names', 'groupblog' ) ?> <?php _e( '(Default: Not Allowed)', 'groupblog' ) ?>
						</label>
					</td>
				</tr>
				<tr> 
					<th><?php _e( 'Minimum Length:', 'groupblog' ) ?></th> 
					<td>	
						<input name="bp_groupblog_minlength" style="width: 10%;" id="bp_groupblog_minlenth" value="<?php echo $opt['minlength'];?>" size="10" type="text" /><br /><?php _e( '(Default: 4)', 'groupblog' ) ?>
					</td> 
				</tr> 
			</tbody></table>		

			<br />
			
      <h3><?php _e( 'Blog Defaults', 'groupblog' ) ?></h3>
			<table class="form-table">
				<tr valign="top">
	        <th><?php _e( 'Default Post Category:', 'groupblog' ) ?></th>
					<td>
						<input name="default_cat_name" type="text" id="default_cat_name" size="30" value="<?php echo($opt['default_cat_name']); ?>"  /><br /><?php _e( '(Overwrites "Uncategorized")', 'groupblog' ) ?>
					</td>
				</tr>
				<tr valign="top">
		    	<th><?php _e( 'Default Link Category:', 'groupblog' ) ?></th>
					<td>
						<input name="default_link_cat" type="text" id="default_link_cat" size="30" value="<?php echo($opt['default_link_cat']); ?>"  /><br /><?php _e( '(Overwrites "Blogroll")', 'groupblog' ) ?>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Set First Post to Draft', 'groupblog' ) ?></th>
					<td>
						<label for="delete_first_post">
		       		<input name="delete_first_post" type="checkbox" id="delete_first_post" value="1" <?php if ($opt['delete_first_post'] == 1) echo('checked="checked"'); ?> /> <?php _e( 'Yes', 'groupblog' ) ?> <?php _e( '(Default Post "Hello World")', 'groupblog' ) ?>
		       	</label>
					</td>
				</tr>
		    <tr>
					<th><?php _e( 'Delete Initial Comment', 'groupblog' ) ?></th>
					<td>
						<label for="delete_first_comment">
			        <input name="delete_first_comment" type="checkbox" id="delete_first_comment" value="1" <?php if ($opt['delete_first_comment'] == 1) echo('checked="checked"'); ?> /> <?php _e( 'Yes', 'groupblog' ) ?>
			       </label>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Delete Blogroll Links', 'groupblog' ) ?></th>
					<td>
						<label for="delete_blogroll_links">
							<input name="delete_blogroll_links" type="checkbox" id="delete_blogroll_links" value="1" <?php if ($opt['delete_blogroll_links'] == 1) echo('checked="checked"'); ?> /> <?php _e( 'Yes', 'groupblog' ) ?>
						</label>
					</td>
				</tr>
			</table>
<!--
			<br />
			
			<h3><?php //_e( 'Redirect Options', 'groupblog' ) ?></h3>
			<table class="form-table">
				<tbody>
				<tr> 
					<th><?php //_e( 'Home Redirect:' ) ?></th> 
					<td>	
						<label><input name="bp_groupblog_redirect_home" id="bp_groupblog_redirect_home"  value="1" type="checkbox" <?php //if ($opt['redirecthome']== 1) echo 'checked="checked"'; ?>> <?php //_e( 'Redirect to Blog Front Page' ) ?></label><br /><?php //_e( 'By default the Group will display the original Group Home page. By checking this option the Group will redirect to the "Front page" as set in the "Reading Settins". This is especailly usefull when you use the BuddyPress Group Blog theme as it mimmicks the group home page, but includes frontend blog posting. (Implemented in the near future.)' ) ?>
					</td> 
				</tr>
				<tr> 
					<th><?php //_e( 'Blog Redirect:' ) ?></th> 
					<td>	
						<label><input name="bp_groupblog_redirect_blog" id="bp_groupblog_redirect_blog"  value="1" type="checkbox" <?php //if ($opt['redirectblog']== 1) echo 'checked="checked"'; ?>> <?php //_e( 'Redirect to Blog Posts Page' ) ?></label><br /><?php //_e( 'Set the behavior of the Blog link. By default it will fetch the group blog pages and latest posts within the group structure. By checking this option the Blog link will redirect to the "Posts page" as set in the "Reading Settins". This is especailly usefull if you wish the Blog link to load a blog template rather than a group template.)' ) ?>
					</td> 
				</tr> 
			</tbody></table>	
-->				    
	    <p class="submit">  
	    	<input type="hidden" name="action" value="update" />
	      <input type="submit" name="Submit" value="<?php _e( 'Save Changes', 'groupblog' ) ?>" />
	    </p>
	    
 		</form>
 		         
	</div>

<?php
}

// When a new blog is created, set the options 
add_action( 'wpmu_new_blog', 'bp_groupblog_blog_defaults' );

function bp_groupblog_setup() {
	global $wpdb;

	// Set up the array of potential defaults
	$groupblog_blogdefaults = array(
		'theme' => 'default|default',
		'delete_blogroll_links' => '1',
		'default_cat_name' => 'Uncategorized',
		'default_link_cat' => 'Links',
		'delete_first_post' => 0,
		'delete_first_comment' => 0,
		'allowdashes'=>0,
		'allowunderscores' => 0,
		'allownumeric' => 0,
		'minlength' => 4,
		'redirecthome' => 0,
		'redirectblog' => 0
	);
 	// Add a site option so that we'll know set up ran
	add_site_option( 'bp_groupblog_blog_defaults_setup', 1 );
	add_site_option( 'bp_groupblog_blog_defaults_options', $groupblog_blogdefaults);   		
}

register_activation_hook( __FILE__, 'bp_groupblog_setup' );

/*******************************************************************/

?>