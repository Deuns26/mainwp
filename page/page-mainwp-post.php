<?php

/**
 * @see MainWP_Bulk_Add
 */
class MainWP_Post {
	public static function getClassName() {
		return __CLASS__;
	}

	public static $subPages;

	public static function init() {
		/**
		 * This hook allows you to render the Post page header via the 'mainwp-pageheader-post' action.
		 * @link http://codex.mainwp.com/#mainwp-pageheader-post
		 *
		 * This hook is normally used in the same context of 'mainwp-getsubpages-post'
		 * @link http://codex.mainwp.com/#mainwp-getsubpages-post
		 *
		 * @see \MainWP_Post::renderHeader
		 */
		add_action( 'mainwp-pageheader-post', array( MainWP_Post::getClassName(), 'renderHeader' ) );

		/**
		 * This hook allows you to render the Post page footer via the 'mainwp-pagefooter-post' action.
		 * @link http://codex.mainwp.com/#mainwp-pagefooter-post
		 *
		 * This hook is normally used in the same context of 'mainwp-getsubpages-post'
		 * @link http://codex.mainwp.com/#mainwp-getsubpages-post
		 *
		 * @see \MainWP_Post::renderFooter
		 */
		add_action( 'mainwp-pagefooter-post', array( MainWP_Post::getClassName(), 'renderFooter' ) );
	}

	public static function initMenu() {
		$_page = add_submenu_page( 'mainwp_tab', __( 'Posts', 'mainwp' ), '<span id="mainwp-Posts">' . __( 'Posts', 'mainwp' ) . '</span>', 'read', 'PostBulkManage', array(
			MainWP_Post::getClassName(),
			'render',
		) );
		add_action( 'load-' . $_page, array(MainWP_Post::getClassName(), 'on_load_page'));
        add_filter( 'manage_' . $_page . '_columns', array(MainWP_Post::getClassName(), 'get_manage_columns'));

        if( !MainWP_System::is_disable_menu_item(3, 'PostBulkAdd') ) {
            add_submenu_page( 'mainwp_tab', __( 'Posts', 'mainwp' ), '<div class="mainwp-hidden">' . __( 'Add New', 'mainwp' ). '</div>', 'read', 'PostBulkAdd', array(
                MainWP_Post::getClassName(),
                'renderBulkAdd',
            ) );
        }
        if( !MainWP_System::is_disable_menu_item(3, 'PostBulkEdit') ) {
            add_submenu_page( 'mainwp_tab', __( 'Posts', 'mainwp' ), '<div class="mainwp-hidden">' . __( 'Edit Post', 'mainwp' ) . '</div>', 'read', 'PostBulkEdit', array(
                MainWP_Post::getClassName(),
                'renderBulkEdit',
            ) );
        }

        add_submenu_page( 'mainwp_tab', 'Posting new bulkpost', '<div class="mainwp-hidden">' . __( 'Posts', 'mainwp' ) . '</div>', 'read', 'PostingBulkPost', array(
            MainWP_Post::getClassName(),
            'posting',
        ) ); //removed from menu afterwards

		/**
		 * This hook allows you to add extra sub pages to the Post page via the 'mainwp-getsubpages-post' filter.
		 * @link http://codex.mainwp.com/#mainwp-getsubpages-post
		 */
		self::$subPages = apply_filters( 'mainwp-getsubpages-post', array() );
		if ( isset( self::$subPages ) && is_array( self::$subPages ) ) {
			foreach ( self::$subPages as $subPage ) {
                if( MainWP_System::is_disable_menu_item(3, 'Post' . $subPage['slug']) )
                    continue;
				add_submenu_page( 'mainwp_tab', $subPage['title'], '<div class="mainwp-hidden">' . $subPage['title'] . '</div>', 'read', 'Post' . $subPage['slug'], $subPage['callback'] );
			}
		}
		MainWP_Post::init_sub_sub_left_menu(self::$subPages);
	}

	public static function on_load_page() {
		add_action( 'admin_head', array( MainWP_Post::getClassName(), 'admin_head' ) );
		add_filter( 'hidden_columns', array(MainWP_Post::getClassName(), 'get_hidden_columns'), 10, 3);

		MainWP_System::enqueue_postbox_scripts();
		self::add_meta_boxes();
	}


	public static function get_manage_columns() {
		$colums =  array(
			'title' => 'Title',
			'author' => 'Author',
			'date' => 'Date',
			'categories' => 'Categories',
			'tags' => 'Tags',
			'post-type' => 'Post type',
			'comments' => 'Comments',
			'status' => 'Status',
			'seo-links' => 'Links',
			'seo-linked' => 'Linked',
			'seo-score' => 'SEO Score',
			'seo-readability' => 'Readability score',
			'website' => 'Website'
		);

		if ( !MainWP_Utility::enabled_wp_seo() ) {
			unset($colums['seo-links']);
			unset($colums['seo-linked']);
			unset($colums['seo-score']);
			unset($colums['seo-readability']);
		}

		return $colums;
	}

	public static function admin_head() {
		global $current_screen;
		// fake pagenow to compatible with wp_ajax_hidden_columns
		?>
		<script type="text/javascript"> pagenow = '<?php echo strip_tags(strtolower($current_screen->id)); ?>';</script>
		<?php
	}
	// to fix compatible with fake pagenow
	public static function get_hidden_columns($hidden, $screen) {
		if($screen && $screen->id == 'mainwp_page_PostBulkManage') {
			$hidden = get_user_option( 'manage' . strtolower($screen->id) . 'columnshidden' );
		}
		return $hidden;
	}


	public static function add_meta_boxes() {
		$i = 1;
		add_meta_box(
			'mwp-postbulk-contentbox-' . $i++,
			'<i class="fa fa-binoculars"></i> ' . __( 'Step 1: Search Posts', 'mainwp' ),
			array( 'MainWP_Post', 'renderSearchPosts' ),
			'mainwp_postboxes_search_posts',
			'normal',
			'core'
		);
	}

	public static function initMenuSubPages() {
		?>
		<div id="menu-mainwp-Posts" class="mainwp-submenu-wrapper">
			<div class="wp-submenu sub-open" style="">
				<div class="mainwp_boxout">
					<div class="mainwp_boxoutin"></div>
					<?php if ( mainwp_current_user_can( 'dashboard', 'manage_posts' ) ) { ?>
						<a href="<?php echo admin_url( 'admin.php?page=PostBulkManage' ); ?>" class="mainwp-submenu"><?php _e( 'Manage Posts', 'mainwp' ); ?></a>
                        <?php if ( ! MainWP_System::is_disable_menu_item(3, 'PostBulkAdd') ) { ?>
						<a href="<?php echo admin_url( 'admin.php?page=PostBulkAdd' ); ?>" class="mainwp-submenu"><?php _e( 'Add New', 'mainwp' ); ?></a>
                        <?php } ?>
					<?php } ?>
					<?php
					if ( isset( self::$subPages ) && is_array( self::$subPages ) ) {
						foreach ( self::$subPages as $subPage ) {
							if ( ! isset( $subPage['menu_hidden'] ) || ( isset( $subPage['menu_hidden'] ) && $subPage['menu_hidden'] != true ) ) {
                                if ( MainWP_System::is_disable_menu_item(3, 'Post' . $subPage['slug']) ) {
                                    continue;
                                }
								?>
								<a href="<?php echo admin_url( 'admin.php?page=Post' . $subPage['slug'] ); ?>" class="mainwp-submenu"><?php echo esc_html($subPage['title']); ?></a>
								<?php
							}
						}
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	static function init_sub_sub_left_menu( $subPages = array() ) {
		MainWP_System::add_sub_left_menu(__('Posts', 'mainwp'), 'mainwp_tab', 'PostBulkManage', 'admin.php?page=PostBulkManage', '<i class="fa fa-file-text"></i>', '' );

		$init_sub_subleftmenu = array(
			array(  'title' => __('Manage Posts', 'mainwp'),
			        'parent_key' => 'PostBulkManage',
			        'href' => 'admin.php?page=PostBulkManage',
			        'slug' => 'PostBulkManage',
			        'right' => 'manage_posts'
			),
			array(  'title' => __('Add New', 'mainwp'),
			        'parent_key' => 'PostBulkManage',
			        'href' => 'admin.php?page=PostBulkAdd',
			        'slug' => 'PostBulkAdd',
			        'right' => 'manage_posts'
			)
		);
		MainWP_System::init_subpages_left_menu($subPages, $init_sub_subleftmenu, 'PostBulkManage', 'Post');

		foreach($init_sub_subleftmenu as $item) {
            if ( MainWP_System::is_disable_menu_item(3, $item['slug']) ) {
                continue;
            }
			MainWP_System::add_sub_sub_left_menu($item['title'], $item['parent_key'], $item['slug'], $item['href'], $item['right']);
		}
	}

	/**
	 * @param string $shownPage The page slug shown at this moment
	 */
public static function renderHeader( $shownPage, $post_id = null ) {
	MainWP_UI::render_left_menu();
	?>
	<div class="mainwp-wrap">

		<h1 class="mainwp-margin-top-0"><i class="fa fa-file-text"></i> <?php _e( 'Posts', 'mainwp' ); ?></h1>

		<div id="mainwp-tip-zone">
			<?php if ( $shownPage == 'BulkManage' ) { ?>
				<?php if ( MainWP_Utility::showUserTip( 'mainwp-manageposts-tips' ) ) { ?>
					<div class="mainwp-tips mainwp-notice mainwp-notice-blue"><span class="mainwp-tip" id="mainwp-manageposts-tips"><strong><?php _e( 'MainWP Tip', 'mainwp' ); ?>: </strong><?php _e( 'You can also quickly see all Published, Draft, Pending and Trash Posts for a single site from your individual site overview recent posts widget by visiting Sites &rarr; Manage Sites &rarr; Child Site &rarr; Overview.', 'mainwp' ); ?></span><span><a href="#" class="mainwp-dismiss"><i class="fa fa-times-circle"></i> <?php _e( 'Dismiss', 'mainwp' ); ?>
							</a></span></div>
				<?php } ?>
			<?php } ?>
		</div>
		<div class="mainwp-tabs" id="mainwp-tabs">
			<?php if ( mainwp_current_user_can( 'dashboard', 'manage_posts' ) ) { ?>
				<a class="nav-tab pos-nav-tab <?php if ( $shownPage === 'BulkManage' ) {
					echo 'nav-tab-active';
				} ?>" href="admin.php?page=PostBulkManage"><?php _e( 'Manage Posts', 'mainwp' ); ?></a>
				<?php if ( $shownPage == 'BulkEdit' ) { ?>
					<a class="nav-tab pos-nav-tab nav-tab-active" href="admin.php?page=PostBulkEdit&post_id=<?php echo esc_attr($post_id); ?>"><?php _e( 'Edit Post', 'mainwp' ); ?></a>
				<?php } ?>
                <?php if ( ! MainWP_System::is_disable_menu_item(3, 'PostBulkAdd') ) { ?>
				<a class="nav-tab pos-nav-tab <?php if ( $shownPage === 'BulkAdd' ) {
					echo 'nav-tab-active';
				} ?>" href="admin.php?page=PostBulkAdd"><?php _e( 'Add new', 'mainwp' ); ?></a>
                <?php } ?>
			<?php } ?>
			<?php
			if ( isset( self::$subPages ) && is_array( self::$subPages ) ) {
				foreach ( self::$subPages as $subPage ) {
                    if ( MainWP_System::is_disable_menu_item(3, 'Post' . $subPage['slug']) )
                            continue;

					if ( isset( $subPage['tab_link_hidden'] ) && $subPage['tab_link_hidden'] == true ) {
						$tab_link = '#';
					} else {
						$tab_link = 'admin.php?page=Post' . $subPage['slug'];
					}
					?>
					<a class="nav-tab pos-nav-tab <?php if ( $shownPage === $subPage['slug'] ) {
						echo 'nav-tab-active';
					} ?>" href="<?php echo esc_url($tab_link); ?>"><?php echo esc_html($subPage['title']); ?></a>
					<?php
				}
			}
			?>
			<div class="clear"></div>
		</div>
		<div id="mainwp_wrap-inside">
			<?php
			}

			/**
			 * @param string $shownPage The page slug shown at this moment
			 */
			public static function renderFooter( $shownPage ) {
			?>
		</div>
	</div>
	<?php
}

	public static function render() {
		if ( ! mainwp_current_user_can( 'dashboard', 'manage_posts' ) ) {
			mainwp_do_not_have_permissions( __( 'manage posts', 'mainwp' ) );

			return;
		}
		$cachedSearch = MainWP_Cache::getCachedContext( 'Post' );

		$selected_sites = $selected_groups = array();
		if ($cachedSearch != null) {
			if (is_array($cachedSearch['sites'])) {
				$selected_sites = $cachedSearch['sites'];
			} else if (is_array($cachedSearch['groups'])) {
				$selected_groups = $cachedSearch['groups'];
			}
		}

		//Loads the post screen via AJAX, which redirects to the "posting()" to really post the posts to the saved sites
		self::renderHeader( 'BulkManage' );
		if (is_plugin_active('mainwp-custom-post-types/mainwp-custom-post-types.php') ):
			?>
			<div class="mainwp-notice mainwp-notice-green">You have Custom Post Type Extension activated. You can choose post type.</div>
			<?php
		endif;
		?>
		<div class="mainwp-padding-bottom-10"><?php MainWP_Tours::renderSearchPostsTours(); ?></div>
		<div class="mainwp-search-form">
			<div class="mainwp-postbox">
				<?php MainWP_System::do_mainwp_meta_boxes('mainwp_postboxes_search_posts'); ?>
			</div>
			<?php MainWP_UI::select_sites_box( __( 'Step 2: Select sites', 'mainwp' ), 'checkbox', true, true, 'mainwp_select_sites_box_left', '', $selected_sites, $selected_groups ); ?>

			<div style="clear: both;"></div>

			<input type="button" name="mainwp_show_posts" id="mainwp_show_posts" class="button-primary button button-hero mainwp-button-right" value="<?php _e( 'Show Posts', 'mainwp' ); ?>"/>
			<?php
			if ( isset( $_REQUEST['siteid'] ) && isset( $_REQUEST['postid'] ) ) {
				echo '<script>jQuery(document).ready(function() { mainwp_show_post(' . intval( $_REQUEST['siteid'] ) . ', ' . intval( $_REQUEST['postid'] ) . ', undefined)});</script>';
			} else if ( isset( $_REQUEST['siteid'] ) && isset( $_REQUEST['userid'] ) ) {
				echo '<script>jQuery(document).ready(function() { mainwp_show_post(' . intval( $_REQUEST['siteid'] ) . ', undefined, ' . intval( $_REQUEST['userid'] )  . ')});</script>';
			}
			?>
			<br/><br/>
			<span id="mainwp_posts_loading" class="mainwp-grabbing-info-note"> <i class="fa fa-spinner fa-pulse"></i> <em><?php _e( 'Grabbing information from Child Sites', 'mainwp' ) ?></em></span>
			<br/><br/>
		</div>
		<div class="clear"></div>
		<div id="mainwp_posts_error"></div>
		<div id="mainwp_posts_main" <?php if ( $cachedSearch != null ) {
			echo 'style="display: block;"';
		} ?>>
			<div class="alignleft">
				<select class="mainwp-select2" name="bulk_action" id="mainwp_bulk_action">
					<option value="none"><?php _e( 'Bulk Action', 'mainwp' ); ?></option>
					<option value="publish"><?php _e( 'Publish', 'mainwp' ); ?></option>
					<option value="unpublish"><?php _e( 'Unpublish', 'mainwp' ); ?></option>
					<option value="trash"><?php _e( 'Move to Trash', 'mainwp' ); ?></option>
					<option value="restore"><?php _e( 'Restore', 'mainwp' ); ?></option>
					<option value="delete"><?php _e( 'Delete permanently', 'mainwp' ); ?></option>
				</select>
				<input type="button" name="" id="mainwp_bulk_post_action_apply" class="button" value="<?php _e( 'Apply', 'mainwp' ); ?>"/>
			</div>
			<div class="alignright" id="mainwp_posts_total_results">
				<?php _e( 'Total Results:', 'mainwp' ); ?>
				<span id="mainwp_posts_total"><?php echo $cachedSearch != null ? esc_html($cachedSearch['count']) : '0'; ?></span>
			</div>
			<div class="clear"></div>
			<div id="mainwp_posts_content">
				<div id="mainwp_posts_wrap_table">
					<?php MainWP_Post::renderTable( true ); ?>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php

		$current_options = get_option( 'mainwp_opts_saving_status' );
		$col_orders = "";
		if (is_array($current_options) && isset($current_options['posts_col_order'])) {
			$col_orders = $current_options['posts_col_order'];
		}
		?>
		<script type="text/javascript"> var postsColOrder = '<?php echo esc_attr( strip_tags($col_orders)); ?>' ;</script>
		<?php

		if ( $cachedSearch != null ) {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function () {
					mainwp_table_sort_draggable_init('post', 'mainwp_posts_table', postsColOrder);
				});
				mainwp_posts_table_reinit();
			</script>
			<?php
		}

		self::renderFooter( 'BulkManage' );
	}

	public static function renderSearchPosts() {
		$cachedSearch = MainWP_Cache::getCachedContext( 'Post' );
		?>
		<ul class="mainwp_checkboxes">
			<li>
				<input type="checkbox" id="mainwp_post_search_type_publish" <?php echo ( $cachedSearch == null || ( $cachedSearch != null && in_array( 'publish', $cachedSearch['status'] ) ) ) ? 'checked="checked"' : ''; ?> />
				<label for="mainwp_post_search_type_publish" ><?php _e( 'Published', 'mainwp' ); ?></label>
			</li>
			<li>
				<input type="checkbox" id="mainwp_post_search_type_pending" <?php echo ( $cachedSearch != null && in_array( 'pending', $cachedSearch['status'] ) ) ? 'checked="checked"' : ''; ?> />
				<label for="mainwp_post_search_type_pending" ><?php _e( 'Pending', 'mainwp' ); ?></label>
			</li>
			<li>
				<input type="checkbox" id="mainwp_post_search_type_private" <?php echo ( $cachedSearch != null && in_array( 'private', $cachedSearch['status'] ) ) ? 'checked="checked"' : ''; ?> />
				<label for="mainwp_post_search_type_private" ><?php _e( 'Private', 'mainwp' ); ?></label>
			</li>
			<li>
				<input type="checkbox" id="mainwp_post_search_type_future" <?php echo ( $cachedSearch != null && in_array( 'future', $cachedSearch['status'] ) ) ? 'checked="checked"' : ''; ?> />
				<label for="mainwp_post_search_type_future" ><?php _e( 'Scheduled', 'mainwp' ); ?></label>
			</li>
			<li>
				<input type="checkbox" id="mainwp_post_search_type_draft" <?php echo ( $cachedSearch != null && in_array( 'draft', $cachedSearch['status'] ) ) ? 'checked="checked"' : ''; ?> />
				<label for="mainwp_post_search_type_draft" ><?php _e( 'Draft', 'mainwp' ); ?></label>
			</li>
			<li>
				<input type="checkbox" id="mainwp_post_search_type_trash" <?php echo ( $cachedSearch != null && in_array( 'trash', $cachedSearch['status'] ) ) ? 'checked="checked"' : ''; ?> />
				<label for="mainwp_post_search_type_trash" ><?php _e( 'Trash', 'mainwp' ); ?></label>
			</li>
		</ul>
        <?php
        $searchon = 'title';
        if ( $cachedSearch != null ) { $searchon = $cachedSearch['search_on']; }
        ?>
		<div class="mainwp-padding-bottom-20">
			<div class="mainwp-cols-2 mainwp-left">
				<label for="mainwp_post_search_by_keyword"><?php _e( 'Containing Keyword:', 'mainwp' ); ?></label><br/>
				<input type="text"
				       id="mainwp_post_search_by_keyword"
				       class=""
				       size="50"
				       value="<?php if ( $cachedSearch != null ) { echo esc_attr($cachedSearch['keyword']); } ?>"/> <?php _e('in', 'mainwp'); ?>
                       <select class="mainwp-select2-mini" name="post_search_on" id="mainwp_post_search_on">
                            <option value="title" <?php echo $searchon == 'title' ? 'selected' : ''; ?>><?php _e( 'Title', 'mainwp' ); ?></option>
                            <option value="content" <?php echo $searchon == 'content' ? 'selected' : ''; ?>><?php _e( 'Body', 'mainwp' ); ?></option>
                            <option value="all" <?php echo $searchon == 'all' ? 'selected' : ''; ?>><?php _e( 'Title and Body', 'mainwp' ); ?></option>
                        </select>
			</div>
			<div class="mainwp-cols-2 mainwp-left">
				<label for="mainwp_post_search_by_dtsstart"><?php _e( 'Date Range:', 'mainwp' ); ?></label><br/>
				<input type="text" id="mainwp_post_search_by_dtsstart" class="mainwp_datepicker" size="12" value="<?php if ( $cachedSearch != null ) {
					echo esc_attr($cachedSearch['dtsstart']);
				} ?>"/> <?php _e( 'to', 'mainwp' ); ?>
				<input type="text" id="mainwp_post_search_by_dtsstop" class="mainwp_datepicker" size="12" value="<?php if ( $cachedSearch != null ) {
					echo esc_attr($cachedSearch['dtsstop']);
				} ?>"/>
			</div>
			<div sytle="clear:both;"></div>
		</div>
		<?php
		if (is_plugin_active('mainwp-custom-post-types/mainwp-custom-post-types.php')):
			?>
			<br/><br/>
			<div class="mainwp-padding-bottom-20">
				<div class="mainwp-cols-2 mainwp-left">
					<label for="mainwp_get_custom_post_types_select"><?php _e('Post type:','mainwp'); ?></label><br/>
					<select id="mainwp_get_custom_post_types_select">
						<option value="any"><?php _e('All post types', 'mainwp'); ?></option>
						<option value="post"><?php _e('Post', 'mainwp'); ?></option>
						<?php
						foreach (get_post_types(array('_builtin' => false)) as $key) {
							if (!in_array($key, MainWPCustomPostType::$default_post_types))
								echo '<option value="'.esc_attr($key).'">'.esc_html($key).'</option>';
						}
						?>
					</select>
				</div>
				<div sytle="clear:both;"></div>
			</div>
			<?php
		endif;
		?>
        <br/><br/>
		<div class="mainwp-padding-bottom-20 mainwp-padding-top-20">
			<label for="mainwp_maximumPosts"><?php _e( 'Maximum number of posts to return', 'mainwp' ); ?>&nbsp;<?php MainWP_Utility::renderToolTip( __( '0 for unlimited, CAUTION: depending on your server settings a large return amount may decrease the speed of results or temporarily break communication between Dashboard and Child.', 'mainwp' ) ); ?></label><br/>
			<input type="number"
			       name="mainwp_maximumPosts"
			       class=""
			       id="mainwp_maximumPosts"
			       value="<?php echo( ( get_option( 'mainwp_maximumPosts' ) === false ) ? 50 : get_option( 'mainwp_maximumPosts' ) ); ?>"/>
		</div>
		<?php
	}

	public static function renderTable( $cached, $keyword = '', $dtsstart = '', $dtsstop = '', $status = '', $groups = '', $sites = '', $postId = 0, $userId = 0, $post_type = '', $search_on = 'all' ) {
		// to fix for ajax call
		$load_page = 'mainwp_page_PostBulkManage';
		$hidden = get_user_option( 'manage' . strtolower($load_page) . 'columnshidden' );

		?>
		<table class="wp-list-table widefat fixed posts tablesorter fix-select-all-ajax-table" id="mainwp_posts_table"
		       cellspacing="0">
			<thead>
			<tr>
				<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input
						type="checkbox"></th>
				<th scope="col" id="title" class="drag-enable manage-column <?php MainWP_Utility::gen_hidden_column('title', $hidden); ?> column-title sortable desc" style="">
					<a href="#" onclick="return false;"><span><?php _e( 'Title', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>
				<th scope="col" id="author" class="drag-enable manage-column <?php MainWP_Utility::gen_hidden_column('author', $hidden); ?> column-author sortable desc" style="">
					<a href="#" onclick="return false;"><span><?php _e( 'Author', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>
				<th scope="col" id="categories" class="drag-enable manage-column <?php MainWP_Utility::gen_hidden_column('categories', $hidden); ?> column-categories sortable desc" style="">
					<a href="#" onclick="return false;"><span><?php _e( 'Categories', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>
				<th scope="col" id="tags" class="drag-enable manage-column <?php MainWP_Utility::gen_hidden_column('tags', $hidden); ?> column-tags sortable desc" style="">
					<a href="#" onclick="return false;"><span><?php _e( 'Tags', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>
				<?php
				if (is_plugin_active('mainwp-custom-post-types/mainwp-custom-post-types.php')):
					?>
					<th scope="col" id="post-type" class="drag-enable manage-column <?php MainWP_Utility::gen_hidden_column('post-type', $hidden); ?> column-post-type sortable desc" style="">
						<a href="#" onclick="return false;"><span><?php _e('Post type','mainwp'); ?></span><span class="sorting-indicator"></span></a>
					</th>
					<?php
				endif;
				?>
				<th scope="col" id="comments" class="drag-enable manage-column <?php MainWP_Utility::gen_hidden_column('comments', $hidden); ?> column-comments num sortable desc" style="">
					<a href="#" onclick="return false;">
             <span><span class="vers"><img alt="Comments"
                                           src="<?php echo admin_url( 'images/comment-grey-bubble.png' ); ?>"></span></span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th scope="col" id="date" class="drag-enable manage-column <?php MainWP_Utility::gen_hidden_column('date', $hidden); ?> column-date sortable asc" style="">
					<a href="#" onclick="return false;"><span><?php _e( 'Date', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>
				<th scope="col" id="status" class="drag-enable manage-column <?php MainWP_Utility::gen_hidden_column('status', $hidden); ?> column-status sortable asc" style="width: 120px;">
					<a href="#" onclick="return false;"><span><?php _e( 'Status', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>

				<?php
				if ( MainWP_Utility::enabled_wp_seo() ) :
					?>
					<th scope="col" id="seo-links" class="drag-enable manage-column <?php MainWP_Utility::gen_hidden_column('seo-links', $hidden); ?> column-seo-links sortable desc" style="">
						<a href="#" onclick="return false;"><span title="<?php echo esc_attr__( 'Number of internal links in this post', 'mainwp' ); ?>"><?php echo __( 'Links', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
					</th>
					<th scope="col" id="seo-linked" class="drag-enable manage-column <?php MainWP_Utility::gen_hidden_column('seo-linked', $hidden); ?> column-seo-linked sortable desc" style="">
						<a href="#" onclick="return false;"><span title="<?php echo esc_attr__( 'Number of internal links linking to this post', 'mainwp' ); ?>"><?php echo __( 'Linked', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
					</th>
					<th scope="col" id="seo-score" class="drag-enable manage-column <?php MainWP_Utility::gen_hidden_column('seo-score', $hidden); ?> column-seo-score sortable desc" style="">
						<a href="#" onclick="return false;"><span title="<?php echo esc_attr__('SEO score', 'mainwp'); ?>"><?php echo __( 'SEO score', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
					</th>
					<th scope="col" id="seo-readability" class="drag-enable manage-column <?php MainWP_Utility::gen_hidden_column('seo-readability', $hidden); ?> column-seo-readability sortable desc" style="">
						<a href="#" onclick="return false;"><span title="<?php echo esc_attr__('Readability score', 'mainwp'); ?>"><?php echo __( 'Readability score', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
					</th>
					<?php
				endif;
				?>

				<th scope="col" id="website" class="drag-enable manage-column <?php MainWP_Utility::gen_hidden_column('website', $hidden); ?> column-website sortable desc" style="">
					<a href="#" onclick="return false;"><span><?php _e( 'Website', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>
			</tr>
			</thead>

			<tfoot>
			<tr>
				<th scope="col" id="cb" class="column-cb check-column" style=""><input
						type="checkbox"></th>
				<th scope="col" id="title" class="column-title <?php MainWP_Utility::gen_hidden_column('title', $hidden); ?> sortable desc" style="">
					<a href="#" onclick="return false;"><span><?php _e( 'Title', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>
				<th scope="col" id="author" class="column-author <?php MainWP_Utility::gen_hidden_column('author', $hidden); ?> sortable desc" style="">
					<a href="#" onclick="return false;"><span><?php _e( 'Author', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>
				<th scope="col" id="categories" class="column-categories <?php MainWP_Utility::gen_hidden_column('categories', $hidden); ?> sortable desc" style="">
					<a href="#" onclick="return false;"><span><?php _e( 'Categories', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>
				<th scope="col" id="tags" class="column-tags <?php MainWP_Utility::gen_hidden_column('tags', $hidden); ?> sortable desc" style="">
					<a href="#" onclick="return false;"><span><?php _e( 'Tags', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>
				<?php
				if (is_plugin_active('mainwp-custom-post-types/mainwp-custom-post-types.php')):
					?>
					<th scope="col" id="post-type" class="drag-enable <?php MainWP_Utility::gen_hidden_column('post-type', $hidden); ?> column-post-type sortable desc" style="">
						<a href="#" onclick="return false;"><span><?php _e('Post type','mainwp'); ?></span><span class="sorting-indicator"></span></a>
					</th>
					<?php
				endif;
				?>
				<th scope="col" id="comments" class="column-comments <?php MainWP_Utility::gen_hidden_column('comments', $hidden); ?> num sortable desc" style="">
					<a href="#" onclick="return false;">
                                         <span><span class="vers"><img alt="Comments"
                                                                       src="<?php echo admin_url( 'images/comment-grey-bubble.png' ); ?>"></span></span>
						<span class="sorting-indicator"></span>
					</a>
				</th>
				<th scope="col" id="date" class="column-date <?php MainWP_Utility::gen_hidden_column('date', $hidden); ?> sortable asc" style="">
					<a href="#" onclick="return false;"><span><?php _e( 'Date', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>
				<th scope="col" id="status" class="column-status <?php MainWP_Utility::gen_hidden_column('status', $hidden); ?> sortable asc" style="width: 120px;">
					<a href="#" onclick="return false;"><span><?php _e( 'Status', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>
				<?php
				if ( MainWP_Utility::enabled_wp_seo() ) :
					?>
					<th scope="col" id="seo-links" class="column-seo-links <?php MainWP_Utility::gen_hidden_column('seo-links', $hidden); ?> sortable desc" style="">
						<a href="#" onclick="return false;"><span title="<?php echo esc_attr__( 'Number of internal links in this post', 'mainwp' ); ?>"><?php echo __( 'Links', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
					</th>
					<th scope="col" id="seo-linked" class="column-seo-linked <?php MainWP_Utility::gen_hidden_column('seo-linked', $hidden); ?> sortable desc" style="">
						<a href="#" onclick="return false;"><span title="<?php echo esc_attr__( 'Number of internal links linking to this post', 'mainwp' ); ?>"><?php echo __( 'Linked', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
					</th>
					<th scope="col" id="seo-score" class="column-seo-score <?php MainWP_Utility::gen_hidden_column('seo-score', $hidden); ?> sortable desc" style="">
						<a href="#" onclick="return false;"><span title="<?php echo esc_attr__('SEO score', 'mainwp'); ?>"><?php echo __( 'SEO score', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
					</th>
					<th scope="col" id="seo-readability" class="column-seo-readability <?php MainWP_Utility::gen_hidden_column('seo-readability', $hidden); ?> sortable desc" style="">
						<a href="#" onclick="return false;"><span title="<?php echo esc_attr__('Readability score', 'mainwp'); ?>"><?php echo __( 'Readability score', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
					</th>
					<?php
				endif;
				?>
				<th scope="col" id="website" class="column-website <?php MainWP_Utility::gen_hidden_column('website', $hidden); ?> sortable desc" style="">
					<a href="#" onclick="return false;"><span><?php _e( 'Website', 'mainwp' ); ?></span><span class="sorting-indicator"></span></a>
				</th>
			</tr>
			</tfoot>

			<tbody id="the-posts-list" class="list:posts">
			<?php
			if ($cached) {
				MainWP_Cache::echoBody( 'Post' );
			} else {
				MainWP_Post::renderTableBody( $keyword, $dtsstart, $dtsstop, $status, $groups, $sites, $postId, $userId, $post_type, $search_on );
			}
			?>
			</tbody>
		</table>
		<div class="pager" id="pager">
			<form>
				<img src="<?php echo plugins_url( 'images/first.png', dirname( __FILE__ ) ); ?>" class="first">
				<img src="<?php echo plugins_url( 'images/prev.png', dirname( __FILE__ ) ); ?>" class="prev">
				<input type="text" class="pagedisplay"/>
				<img src="<?php echo plugins_url( 'images/next.png', dirname( __FILE__ ) ); ?>" class="next">
				<img src="<?php echo plugins_url( 'images/last.png', dirname( __FILE__ ) ); ?>" class="last">
				<span>&nbsp;&nbsp;<?php _e( 'Show:', 'mainwp' ); ?> </span><select class="mainwp-select2 pagesize">
					<option selected="selected" value="10">10</option>
					<option value="25">25</option>
					<option value="50">50</option>
					<option value="100">100</option>
					<option value="1000000000">All</option>
				</select><span> <?php _e( 'Posts per page', 'mainwp' ); ?></span>
			</form>
		</div>
		<?php
	}

	public static function renderTableBody( $keyword, $dtsstart, $dtsstop, $status, $groups, $sites, $postId, $userId, $post_type = '', $search_on = 'all') {
		MainWP_Cache::initCache( 'Post' );

		//Fetch all!
		//Build websites array
		$dbwebsites = array();
		if ( $sites != '' ) {
			foreach ( $sites as $k => $v ) {
				if ( MainWP_Utility::ctype_digit( $v ) ) {
					$website                    = MainWP_DB::Instance()->getWebsiteById( $v );
					$dbwebsites[ $website->id ] = MainWP_Utility::mapSite( $website, array(
						'id',
						'url',
						'name',
						'adminname',
						'nossl',
						'privkey',
						'nosslkey',
                        'http_user',
                        'http_pass'
					) );
				}
			}
		}
		if ( $groups != '' ) {
			foreach ( $groups as $k => $v ) {
				if ( MainWP_Utility::ctype_digit( $v ) ) {
					$websites = MainWP_DB::Instance()->query( MainWP_DB::Instance()->getSQLWebsitesByGroupId( $v ) );
					while ( $websites && ( $website = @MainWP_DB::fetch_object( $websites ) ) ) {
						if ( $website->sync_errors != '' ) {
							continue;
						}
						$dbwebsites[ $website->id ] = MainWP_Utility::mapSite( $website, array(
							'id',
							'url',
							'name',
							'adminname',
							'nossl',
							'privkey',
							'nosslkey',
                            'http_user',
                            'http_pass'
						) );
					}
					@MainWP_DB::free_result( $websites );
				}
			}
		}

		$output         = new stdClass();
		$output->errors = array();
		$output->posts  = 0;

		if ( count( $dbwebsites ) > 0 ) {
			$post_data = array(
				'keyword'    => $keyword,
				'dtsstart'   => $dtsstart,
				'dtsstop'    => $dtsstop,
				'status'     => $status,
                'search_on' => $search_on,
				'maxRecords' => ( ( get_option( 'mainwp_maximumPosts' ) === false ) ? 50 : get_option( 'mainwp_maximumPosts' ) ),
			);

			// Add support for custom post type
			if (is_plugin_active('mainwp-custom-post-types/mainwp-custom-post-types.php')) {
				$post_data['post_type'] = $post_type;
				if ($post_type == 'any') {
					$post_data['exclude_page_type'] = 1; // to exclude pages in posts listing, custom post type extension
				}
			}

			if ( MainWP_Utility::enabled_wp_seo() ) {
				$post_data['WPSEOEnabled'] = 1;
			}

			if ( isset( $postId ) && ( $postId != '' ) ) {
				$post_data['postId'] = $postId;
			} else if ( isset( $userId ) && ( $userId != '' ) ) {
				$post_data['userId'] = $userId;
			}

			$post_data = apply_filters('mainwp_get_all_posts_data', $post_data);
			MainWP_Utility::fetchUrlsAuthed( $dbwebsites, 'get_all_posts', $post_data, array(
				MainWP_Post::getClassName(),
				'PostsSearch_handler',
			), $output );
		}

		MainWP_Cache::addContext( 'Post', array(
			'count'    => $output->posts,
			'keyword'  => $keyword,
			'dtsstart' => $dtsstart,
			'dtsstop'  => $dtsstop,
			'status'   => $status,
			'sites'    => ($sites != '') ? $sites : '',
			'groups'   => ($groups != '') ? $groups : '',
            'search_on' => $search_on
		));

		//Sort if required
		if ( $output->posts == 0 ) {
			ob_start();
			?>
			<tr>
				<td colspan="9">No posts found</td>
			</tr>
			<?php
			$newOutput = ob_get_clean();
			echo $newOutput;
			MainWP_Cache::addBody( 'Post', $newOutput );

			return;
		}

	}

	private static function getStatus( $status ) {
		if ( $status == 'publish' ) {
			return 'Published';
		}

		return ucfirst( $status );
	}

	public static function PostsSearch_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$posts = unserialize( base64_decode( $results[1] ) );

            if(is_array($posts) && isset($posts['error'])) {
                return;
            }

			unset( $results );

			$child_to_dash_array = array();

			if (is_plugin_active('mainwp-custom-post-types/mainwp-custom-post-types.php')) {
				$child_post_ids = array();
				foreach ($posts as $post) {
					$child_post_ids[] = $post['id'];
				}
				reset($posts);

				$connections_ids = MainWPCustomPostTypeDB::Instance()->get_dash_post_ids_from_connections($website->id, $child_post_ids);
				if ( ! empty( $connections_ids ) ) {
					foreach ( $connections_ids as $key ) {
						$child_to_dash_array[ $key->child_post_id ] = $key->dash_post_id;
					}
				}
			}

			// to fix for ajax call
			$load_page = 'mainwp_page_PostBulkManage';
			$hidden = get_user_option( 'manage' . strtolower($load_page) . 'columnshidden' );

			foreach ( $posts as $post ) {
				$raw_dts = '';
				if ( isset( $post['dts'] ) ) {
					$raw_dts = $post['dts'];
					if ( ! stristr( $post['dts'], '-' ) ) {
						$post['dts'] = MainWP_Utility::formatTimestamp( MainWP_Utility::getTimestamp( $post['dts'] ) );
					}
				}

				if ( ! isset( $post['title'] ) || ( $post['title'] == '' ) ) {
					$post['title'] = '(No Title)';
				}

				ob_start();
				?>
				<tr id="post-1"
				    class="post-1 post type-post status-publish format-standard hentry category-uncategorized alternate iedit author-self"
				    valign="top">
					<th scope="row" class="check-column"><input type="checkbox" name="post[]" value="1"></th>
					<td class="title <?php MainWP_Utility::gen_hidden_column('title', $hidden); ?> column-title">
						<input class="postId" type="hidden" name="id" value="<?php echo esc_attr($post['id']); ?>"/>
						<input class="allowedBulkActions" type="hidden" name="allowedBulkActions" value="|get_edit|trash|delete|<?php if ( $post['status'] == 'publish' ) {
							echo 'unpublish|';
						} ?><?php if ( $post['status'] == 'pending' ) {
							echo 'approve|';
						} ?><?php if ( $post['status'] == 'trash' ) {
							echo 'restore|';
						} ?><?php if ( $post['status'] == 'future' || $post['status'] == 'draft' ) {
							echo 'publish|';
						} ?>"/>
						<input class="websiteId" type="hidden" name="id" value="<?php echo esc_attr($website->id); ?>"/>

						<strong>
							<abbr title="<?php echo esc_attr($post['title']); ?>">
								<?php if ( $post['status'] != 'trash' ) { ?>
									<a class="row-title"
									   href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website->id; ?>&location=<?php echo base64_encode( 'post.php?post=' . $post['id'] . '&action=edit' ); ?>"
									   title="Edit '<?php echo esc_attr($post['title']); ?>'" target="_blank"><?php echo esc_html($post['title']); ?></a>
								<?php } else { ?>
									<?php echo esc_html( $post['title'] ); ?>
								<?php } ?>
							</abbr>
						</strong>

						<div class="row-actions">
							<?php if ( $post['status'] != 'trash' ) { ?>
								<span class="edit">
		                        <?php
		                        if (isset($child_to_dash_array[$post['id']])) {
			                        ?>
			                        <img src="<?php echo plugin_dir_url(__FILE__); ?>../../mainwp/images/mainwpicon.png">
			                        <a href="post.php?post=<?php echo (int) $child_to_dash_array[$post['id']]; ?>&action=edit&select=<?php echo (int) $website->id;?>" title="Edit this item"><?php _e('Edit','mainwp'); ?></a>
			                        <?php
		                        } else {
			                        ?>
			                        <span class="edit"><a class="post_getedit"
			                                              href="#"
			                                              title="Edit this item"><?php _e( 'Edit', 'mainwp' ); ?></a>
	                                </span>
			                        <?php
		                        }
		                        ?>
		                        </span>
								<span class="trash">
                            | <a class="post_submitdelete" title="Move this item to the Trash" href="#"><?php _e( 'Trash', 'mainwp' ); ?></a>
                        </span>
							<?php } ?>

							<?php if ( $post['status'] == 'future' || $post['status'] == 'draft' ) { ?>
								<span class="publish">
                            | <a class="post_submitpublish" title="Publish this item" href="#"><?php _e( 'Publish', 'mainwp' ); ?></a>
                        </span>
							<?php } ?>

							<?php if ( $post['status'] == 'pending' ) { ?>
								<span class="post-approve">
                            | <a class="post_submitapprove" title="Approve this item" href="#"><?php _e( 'Approve', 'mainwp' ); ?></a>
                        </span>
							<?php } ?>

							<?php if ( $post['status'] == 'publish' ) { ?>
								<span class="view">
                            | <a
										href="<?php echo $website->url . ( substr( $website->url, - 1 ) != '/' ? '/' : '' ) . '?p=' . $post['id']; ?>" class="mainwp-may-hide-referrer"
										target="_blank" title="View â€œ<?php echo esc_attr($post['title']); ?>ï¿½?" rel="permalink"><?php _e( 'View', 'mainwp' ); ?></a>
                        </span>
								<span class="unpublish">
                            | <a class="post_submitunpublish" title="Unpublish this item" href="#"><?php _e( 'Unpublish', 'mainwp' ); ?></a>
                        </span>
							<?php } ?>

							<?php if ( $post['status'] == 'trash' ) { ?>
								<span class="restore">
                           <a class="post_submitrestore" title="Restore this item" href="#"><?php _e( 'Restore', 'mainwp' ); ?></a>
                        </span>
								<span class="trash">
                            | <a class="post_submitdelete_perm" title="Delete this item permanently" href="#"><?php _e( 'Delete
                            Permanently', 'mainwp' ); ?></a>
                        </span>
							<?php } ?>
						</div>
						<div class="row-actions-working">
							<i class="fa fa-spinner fa-pulse"></i> <?php _e( 'Please wait...', 'mainwp' ); ?></div>
					</td>
					<td class="author <?php MainWP_Utility::gen_hidden_column('author', $hidden); ?> column-author">
						<?php echo esc_attr($post['author']); ?>
					</td>
					<td class="categories <?php MainWP_Utility::gen_hidden_column('categories', $hidden); ?> column-categories">
						<?php echo esc_attr( $post['categories'] ); ?>
					</td>
					<td class="tags <?php MainWP_Utility::gen_hidden_column('tags', $hidden); ?> column-tags"><?php echo( $post['tags'] == '' ? 'No Tags' : $post['tags'] ); ?></td>
					<?php
					if (is_plugin_active('mainwp-custom-post-types/mainwp-custom-post-types.php')):
						?>
						<td class="post-type <?php MainWP_Utility::gen_hidden_column('post-type', $hidden); ?> column-post-type"><?php echo esc_html($post['post_type']) ?></td>
						<?php
					endif;
					?>
					<td class="comments <?php MainWP_Utility::gen_hidden_column('comments', $hidden); ?> column-comments">
						<div class="post-com-count-wrapper">
							<a href="<?php echo admin_url( 'admin.php?page=CommentBulkManage&siteid=' . $website->id . '&postid=' . $post['id'] ); ?>" title="0 pending" class="post-com-count"><span
									class="comment-count"><abbr title="<?php echo esc_attr($post['comment_count']); ?>"><?php echo esc_attr($post['comment_count']); ?></abbr></span></a>
						</div>
					</td>
					<td class="date <?php MainWP_Utility::gen_hidden_column('date', $hidden); ?> column-date"><abbr raw_value="<?php echo esc_attr($raw_dts); ?>"
					                                                                                                title="<?php echo esc_attr($post['dts']); ?>"><?php echo esc_html($post['dts']); ?></abbr>
					</td>
					<td class="status <?php MainWP_Utility::gen_hidden_column('status', $hidden); ?> column-status"><?php echo self::getStatus( $post['status'] ); ?></td>
					<?php
					if ( MainWP_Utility::enabled_wp_seo() ) {
						$count_seo_links = $count_seo_linked = null;
						$seo_score = $readability_score = '';
						if ( isset($post['seo_data'])) {
							$seo_data = $post['seo_data'];
							$count_seo_links = esc_html($seo_data['count_seo_links']);
							$count_seo_linked = esc_html($seo_data['count_seo_linked']);
							$seo_score = $seo_data['seo_score'];
							$readability_score = $seo_data['readability_score'];
						}
						?>
						<td class="<?php MainWP_Utility::gen_hidden_column('seo-links', $hidden); ?> column-seo-links" ><abbr raw_value="<?php echo $count_seo_links !== null ? $count_seo_links : -1; ?>" title=""><?php echo $count_seo_links !== null ? $count_seo_links : ''; ?></abbr></td>
						<td class="<?php MainWP_Utility::gen_hidden_column('seo-linked', $hidden); ?> column-seo-linked"><abbr raw_value="<?php echo $count_seo_linked !== null ? $count_seo_linked : -1; ?>" title=""><?php echo $count_seo_linked !== null ? $count_seo_linked : ''; ?></abbr></td>
						<td class="<?php MainWP_Utility::gen_hidden_column('seo-score', $hidden); ?> column-seo-score"><abbr raw_value="<?php echo $seo_score ? 1 : 0; ?>" title=""><?php echo  $seo_score; ?></abbr></td>
						<td class="<?php MainWP_Utility::gen_hidden_column('seo-readability', $hidden); ?> column-seo-readability"><abbr raw_value="<?php echo $readability_score ? 1 : 0; ?>" title=""><?php echo $readability_score; ?></abbr></td>
						<?php
					};
					?>
					<td class="website <?php MainWP_Utility::gen_hidden_column('website', $hidden); ?> column-website">
						<a href="<?php echo esc_url($website->url); ?>" target="_blank"><?php echo esc_html($website->url); ?></a>

						<div class="row-actions">
							<span class="edit"><a href="admin.php?page=managesites&dashboard=<?php echo $website->id; ?>"><?php _e( 'Overview', 'mainwp' ); ?></a> | <a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website->id; ?>" target="_blank"><?php _e( 'WP Admin', 'mainwp' ); ?></a></span>
						</div>
					</td>
				</tr>
				<?php
				$newOutput = ob_get_clean();
				echo $newOutput;

				MainWP_Cache::addBody( 'Post', $newOutput );
				$output->posts ++;
			}
			unset( $posts );
		} else {
			$output->errors[ $website->id ] = MainWP_Error_Helper::getErrorMessage( new MainWP_Exception( 'NOMAINWP', $website->url ) );
		}
	}

	public static function renderBulkAdd() {
		if ( ! mainwp_current_user_can( 'dashboard', 'manage_posts' ) ) {
			mainwp_do_not_have_permissions( __( 'manage posts', 'mainwp' ) );

			return;
		}
		$src = get_site_url() . '/wp-admin/post-new.php?post_type=bulkpost&hideall=1' . ( isset( $_REQUEST['select'] ) ? '&select=' . esc_attr( $_REQUEST['select'] ) : '' );
		$src = apply_filters( 'mainwp_bulkpost_edit_source', $src );
		//Loads the post screen via AJAX, which redirects to the "posting()" to really post the posts to the saved sites
		self::renderHeader( 'BulkAdd' ); ?>
		<iframe scrolling="auto" id="mainwp_iframe" src="<?php echo $src; ?>"></iframe>
		<?php
		self::renderFooter( 'BulkAdd' );
	}

	public static function renderBulkEdit() {
		if ( ! mainwp_current_user_can( 'dashboard', 'manage_posts' ) ) {
			mainwp_do_not_have_permissions( __( 'manage posts', 'mainwp' ) );
			return;
		}

		$post_id = isset( $_REQUEST['post_id'] ) ? intval($_REQUEST['post_id']) : 0;
		$src = get_site_url() . '/wp-admin/post.php?post_type=bulkpost&hideall=1&action=edit&post=' . esc_attr( $post_id ) . ( isset( $_REQUEST['select'] ) ? '&select=' . esc_attr( $_REQUEST['select'] ) : '' ) ;
		$src = apply_filters( 'mainwp_bulkpost_edit_source', $src );

		//Loads the post screen via AJAX, which redirects to the "posting()" to really post the posts to the saved sites
		self::renderHeader( 'BulkEdit' , $post_id ); ?>
		<iframe scrolling="auto" id="mainwp_iframe" src="<?php echo $src; ?>"></iframe>
		<?php
		self::renderFooter( 'BulkEdit' );
	}


    public static function hookPostsSearch_handler( $data, $website, &$output ) {
        $posts = array();
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$posts = unserialize( base64_decode( $results[1] ) );
			unset( $results );
        }
        $output->results[ $website->id ] = $posts;
    }


	public static function getCategories() {
		$websites = array();
		if ( isset( $_REQUEST['sites'] ) && ( $_REQUEST['sites'] != '' ) ) {
			$siteIds          = explode( ',', urldecode( $_REQUEST['sites'] ) );
			$siteIdsRequested = array();
			foreach ( $siteIds as $siteId ) {
				$siteId = $siteId;
				if ( ! MainWP_Utility::ctype_digit( $siteId ) ) {
					continue;
				}
				$siteIdsRequested[] = $siteId;
			}

			$websites = MainWP_DB::Instance()->getWebsitesByIds( $siteIdsRequested );
		} else if ( isset( $_REQUEST['groups'] ) && ( $_REQUEST['groups'] != '' ) ) {
			$groupIds          = explode( ',', urldecode( $_REQUEST['groups'] ) );
			$groupIdsRequested = array();
			foreach ( $groupIds as $groupId ) {
				$groupId = $groupId;

				if ( ! MainWP_Utility::ctype_digit( $groupId ) ) {
					continue;
				}
				$groupIdsRequested[] = $groupId;
			}

			$websites = MainWP_DB::Instance()->getWebsitesByGroupIds( $groupIdsRequested );
		}

		$selectedCategories = array();
		$selectedCategories2 = array();

		if ( isset( $_REQUEST['selected_categories'] ) && ( $_REQUEST['selected_categories'] != '' ) ) {
			$selectedCategories = explode( ',', urldecode( $_REQUEST['selected_categories'] ) );
		}

		if (isset($_REQUEST['post_id'])) {
			$post_id = (int) $_REQUEST['post_id'];
			if (current_user_can('edit_post', $post_id)) {
				$selectedCategories2 = get_post_meta( $post_id, '_categories', true );
			}
		}

		if ( ! is_array( $selectedCategories ) ) $selectedCategories = array();
		if ( ! is_array( $selectedCategories2 ) ) $selectedCategories2 = array();

		$allCategories = array( 'Uncategorized' );
		if ( count( $websites ) > 0 ) {
			foreach ( $websites as $website ) {
				$cats = json_decode( $website->categories, true );
				if ( is_array( $cats ) && ( count( $cats ) > 0 ) ) {
					$allCategories = array_unique( array_merge( $allCategories, $cats ) );
				}
			}
		}

		if ( count( $allCategories ) > 0 ) {
			natcasesort( $allCategories );
			foreach ( $allCategories as $category ) {
				echo '<li class="popular-category sitecategory"><label class="selectit"><input value="' . $category . '" type="checkbox" name="post_category[]" ' . ( in_array( $category, $selectedCategories ) || in_array( $category, $selectedCategories2 ) ? 'checked' : '' ) . '> ' . $category . '</label></li>';
			}
		}
		die();
	}

	public static function posting() {
		$succes_message = '';
		if ( isset( $_GET['id'] ) ) {
			$edit_id = get_post_meta($_GET['id'], '_mainwp_edit_post_id', true);
			if ($edit_id) {
				$succes_message = __('Post has been updated successfully', 'mainwp');
			} else {
				$succes_message = __('New post created', 'mainwp');
			}
		}

		//Posts the saved sites
		?>
		<div class="wrap">
			<h2><?php $edit_id ? _e('Edit Post', 'mainwp') : _e('New Post', 'mainwp') ?></h2>
			<?php
			do_action( 'mainwp_bulkpost_before_post', $_GET['id'] );

			$skip_post = false;
			if ( isset( $_GET['id'] ) ) {
				if ( 'yes' == get_post_meta( $_GET['id'], '_mainwp_skip_posting', true ) ) {
					$skip_post = true;
					wp_delete_post( $_GET['id'], true );
				}
			}

			if ( ! $skip_post ) {
				if ( isset( $_GET['id'] ) ) {
					$id   = intval($_GET['id']);
					$post = get_post( $id );
					if ( $post ) {
						$selected_by     = get_post_meta( $id, '_selected_by', true );
						$selected_sites  = unserialize( base64_decode( get_post_meta( $id, '_selected_sites', true ) ) );
						$selected_groups = unserialize( base64_decode( get_post_meta( $id, '_selected_groups', true ) ) );

						/** @deprecated */
						$post_category = base64_decode( get_post_meta( $id, '_categories', true ) );

						$post_tags   = base64_decode( get_post_meta( $id, '_tags', true ) );
						$post_slug   = base64_decode( get_post_meta( $id, '_slug', true ) );
						$post_custom = get_post_custom( $id );
						//                if (isset($post_custom['_tags'])) $post_custom['_tags'] = base64_decode(trim($post_custom['_tags']));

						$galleries = get_post_gallery( $id, false );
						$post_gallery_images = array();

						if ( is_array($galleries) && isset($galleries['ids']) ) {
							$attached_images = explode( ',', $galleries['ids'] );
							foreach( $attached_images as $attachment_id ) {
								$attachment = get_post( $attachment_id );
								if ( $attachment ) {
									$post_gallery_images[] = array(
										'id' => $attachment_id,
										'alt' => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
										'caption' => $attachment->post_excerpt,
										'description' => $attachment->post_content,
										'src' => $attachment->guid,
										'title' => $attachment->post_title
									);
								}
							}
						}

						include_once( ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'post-thumbnail-template.php' );
						$featured_image_id = get_post_thumbnail_id( $id );
                        $post_featured_image = null;
                        $featured_image_data = null;
						$mainwp_upload_dir   = wp_upload_dir();
						$post_status = get_post_meta( $id, '_edit_post_status', true );
						$new_post = array(
							'post_title'     => $post->post_title,
							'post_content'   => $post->post_content,
							'post_status'    => ($post_status == 'pending') ? 'pending' : $post->post_status, //was 'publish'
							'post_date'      => $post->post_date,
							'post_date_gmt'  => $post->post_date_gmt,
							'post_tags'      => $post_tags,
							'post_name'      => $post_slug,
							'post_excerpt'   => $post->post_excerpt,
							'comment_status' => $post->comment_status,
							'ping_status'    => $post->ping_status,
							'id_spin'        => $post->ID,
						);

						if ( $featured_image_id != null ) { //Featured image is set, retrieve URL
							$img                 = wp_get_attachment_image_src( $featured_image_id, 'full' );
							$post_featured_image = $img[0];
                            $attachment = get_post( $featured_image_id );
                            $featured_image_data = array(
										'alt' => get_post_meta( $featured_image_id, '_wp_attachment_image_alt', true ),
										'caption' => $attachment->post_excerpt,
										'description' => $attachment->post_content,
										'title' => $attachment->post_title
									);
						}

						$dbwebsites = array();
						if ( $selected_by == 'site' ) { //Get all selected websites
							foreach ( $selected_sites as $k ) {
								if ( MainWP_Utility::ctype_digit( $k ) ) {
									$website                    = MainWP_DB::Instance()->getWebsiteById( $k );
									$dbwebsites[ $website->id ] = MainWP_Utility::mapSite( $website, array(
										'id',
										'url',
										'name',
										'adminname',
										'nossl',
										'privkey',
										'nosslkey',
                                        'http_user',
                                        'http_pass'
									) );
								}
							}
						} else { //Get all websites from the selected groups
							foreach ( $selected_groups as $k ) {
								if ( MainWP_Utility::ctype_digit( $k ) ) {
									$websites = MainWP_DB::Instance()->query( MainWP_DB::Instance()->getSQLWebsitesByGroupId( $k ) );
									while ( $websites && ( $website = @MainWP_DB::fetch_object( $websites ) ) ) {
										if ( $website->sync_errors != '' ) {
											continue;
										}
										$dbwebsites[ $website->id ] = MainWP_Utility::mapSite( $website, array(
											'id',
											'url',
											'name',
											'adminname',
											'nossl',
											'privkey',
											'nosslkey',
                                            'http_user',
                                            'http_pass'
										) );
									}
									@MainWP_DB::free_result( $websites );
								}
							}
						}

						$output         = new stdClass();
						$output->ok     = array();
						$output->errors = array();
						$startTime      = time();

						if ( count( $dbwebsites ) > 0 ) {
							$post_data = array(
								'new_post'            => base64_encode( serialize( $new_post ) ),
								'post_custom'         => base64_encode( serialize( $post_custom ) ),
								'post_category'       => base64_encode( $post_category ),
								'post_featured_image' => base64_encode( $post_featured_image ),
								'post_gallery_images' => base64_encode( serialize( $post_gallery_images ) ),
								'mainwp_upload_dir'   => base64_encode( serialize( $mainwp_upload_dir ) ),
                                'featured_image_data' => base64_encode( serialize( $featured_image_data ) ),
							);
							MainWP_Utility::fetchUrlsAuthed( $dbwebsites, 'newpost', $post_data, array(
								MainWP_Bulk_Add::getClassName(),
								'PostingBulk_handler',
							), $output );
						}

						$failed_posts = array();
						foreach ( $dbwebsites as $website ) {
							if ( ( $output->ok[ $website->id ] == 1 ) && ( isset( $output->added_id[ $website->id ] ) ) ) {
								do_action( 'mainwp-post-posting-post', $website, $output->added_id[ $website->id ], ( isset( $output->link[ $website->id ] ) ? $output->link[ $website->id ] : null ) );
								do_action( 'mainwp-bulkposting-done', $post, $website, $output );
							} else {
								$failed_posts[] = $website->id;
							}
						}

						$del_post    = true;
						$saved_draft = get_post_meta( $id, '_saved_as_draft', true );
						if ( $saved_draft == 'yes' ) {
							if ( count( $failed_posts ) > 0 ) {
								$del_post = false;
								update_post_meta( $post->ID, '_selected_sites', base64_encode( serialize( $failed_posts ) ) );
								update_post_meta( $post->ID, '_selected_groups', '' );
								wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
							}
						}

						if ( $del_post ) {
							wp_delete_post( $id, true );
						}

						$countSites = 0;
						$countRealItems = 0;
						foreach ( $dbwebsites as $website ) {
							if ( isset( $output->ok[ $website->id ] ) && $output->ok[ $website->id ] == 1 ) {
								$countSites++;
								$countRealItems++;
							}
						}

						if ( ! empty( $countSites ) ) {
							$seconds = ( time() - $startTime );
							MainWP_Twitter::updateTwitterInfo( 'new_post', $countSites, $seconds, $countRealItems, $startTime, 1 );
						}

						if ( MainWP_Twitter::enabledTwitterMessages() ) {
							$twitters = MainWP_Twitter::getTwitterNotice( 'new_post' );
							if ( is_array( $twitters ) ) {
								foreach ( $twitters as $timeid => $twit_mess ) {
									if ( ! empty( $twit_mess ) ) {
										$sendText = MainWP_Twitter::getTwitToSend( 'new_post', $timeid );
										?>
										<div class="mainwp-tips mainwp-notice mainwp-notice-blue twitter">
											<span class="mainwp-tip" twit-what="new_post" twit-id="<?php echo $timeid; ?>"><?php echo $twit_mess; ?></span>&nbsp;<?php MainWP_Twitter::genTwitterButton( $sendText ); ?>
											<span><a href="#" class="mainwp-dismiss-twit mainwp-right"><i class="fa fa-times-circle"></i> <?php _e( 'Dismiss', 'mainwp' ); ?>
												</a></span></div>
										<?php
									}
								}
							}
						}

						?>

						<div class="mainwp-notice mainwp-notice-green">
							<?php foreach ( $dbwebsites as $website ) {
								?>
								<a href="<?php echo admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ); ?>"><?php echo stripslashes( $website->name ); ?></a>
								: <?php echo( isset( $output->ok[ $website->id ] ) && $output->ok[ $website->id ] == 1 ? $succes_message . ' <a href="' . $output->link[ $website->id ] . '" class="mainwp-may-hide-referrer" target="_blank">View Post</a>' : $output->errors[ $website->id ] ); ?><br/>
							<?php } ?>
						</div>
						<?php
					} // if ($post)
				} else {
					?>
					<div class="error below-h2">
						<p>
							<strong><?php _e( 'ERROR', 'mainwp' ); ?></strong>: <?php _e( 'An undefined error occured!', 'mainwp' ); ?>
						</p>
					</div>
					<?php
				}
			} // no skip posting
			?>
			<br/>
			<a href="<?php echo get_admin_url() ?>admin.php?page=PostBulkAdd" class="add-new-h2" target="_top"><?php _e( 'Add new', 'mainwp' ); ?></a>
			<a href="<?php echo get_admin_url() ?>admin.php?page=PostBulkManage" class="add-new-h2" target="_top"><?php _e( 'Return
            to Manage Posts', 'mainwp' ); ?></a>

		</div>
		<?php
	}

	public static function PostsGetTerms_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result                       = $results[1];
			$cats                         = unserialize( base64_decode( $result ) );
			$output->cats[ $website->id ] = is_array( $cats ) ? $cats : array();
		} else {
			$output->errors[ $website->id ] = MainWP_Error_Helper::getErrorMessage( new MainWP_Exception( 'NOMAINWP', $website->url ) );
		}
	}

	public static function getTerms( $websiteid, $prefix = '', $what = 'site', $gen_type = 'post' ) {
		$output         = new stdClass();
		$output->errors = array();
		$output->cats   = array();
		$dbwebsites     = array();
		if ( $what == 'group' ) {
			$input_name = 'groups_selected_cats_' . $prefix . '[]';
		} else {
			$input_name = 'sites_selected_cats_' . $prefix . '[]';
		}

		if ( ! empty( $websiteid ) ) {
			if ( MainWP_Utility::ctype_digit( $websiteid ) ) {
				$website                    = MainWP_DB::Instance()->getWebsiteById( $websiteid );
				$dbwebsites[ $website->id ] = MainWP_Utility::mapSite( $website, array(
					'id',
					'url',
					'name',
					'adminname',
					'nossl',
					'privkey',
					'nosslkey',
                    'http_user',
                    'http_pass'
				) );
			}
		}

		if ( $gen_type == 'post' ) {
			$bkc_option_path = 'default_keywords_post';
			$keyword_option  = 'keywords_page';
		} else if ( $gen_type == 'page' ) {
			$bkc_option_path = 'default_keywords_page';
			$keyword_option  = 'keywords_page';
		}

		if ( $prefix == 'bulk' ) {
			$opt           = apply_filters( 'mainwp-get-options', $value = '', 'mainwp_content_extension', 'bulk_keyword_cats', $bkc_option_path );
			$selected_cats = unserialize( base64_decode( $opt ) );
		} else // is number 0,1,2, ...
		{
			$opt = apply_filters( 'mainwp-get-options', $value = '', 'mainwp_content_extension', $keyword_option );
			if ( is_array( $opt ) && is_array( $opt[ $prefix ] ) ) {
				$selected_cats = unserialize( base64_decode( $opt[ $prefix ]['selected_cats'] ) );
			}
		}
		$selected_cats = is_array( $selected_cats ) ? $selected_cats : array();
		$ret           = '';
		if ( count( $dbwebsites ) > 0 ) {
			$opt       = apply_filters( 'mainwp-get-options', $value = '', 'mainwp_content_extension', 'taxonomy' );
			$post_data = array(
				'taxonomy' => base64_encode( $opt ),
			);
			MainWP_Utility::fetchUrlsAuthed( $dbwebsites, 'get_terms', $post_data, array(
				MainWP_Post::getClassName(),
				'PostsGetTerms_handler',
			), $output );
			foreach ( $dbwebsites as $siteid => $website ) {
				$cats = array();
				if ( is_array( $selected_cats[ $siteid ] ) ) {
					foreach ( $selected_cats[ $siteid ] as $val ) {
						$cats[] = $val['term_id'];
					}
				}
				if ( ! empty( $output->errors[ $siteid ] ) ) {
					$ret .= '<p> ' . __( 'Error - ', 'mainwp' ) . $output->errors[ $siteid ] . '</p>';
				} else {
					if ( count( $output->cats[ $siteid ] ) > 0 ) {
						foreach ( $output->cats[ $siteid ] as $cat ) {
							if ( $cat->term_id ) {
								if ( in_array( $cat->term_id, $cats ) ) {
									$checked = ' checked="checked" ';
								} else {
									$checked = '';
								}
								$ret .= '<div class="mainwp_selected_sites_item ' . ( ! empty( $checked ) ? 'selected_sites_item_checked' : '' ) . '"><input type="checkbox" name="' . $input_name . '" value="' . $siteid . ',' . $cat->term_id . ',' . stripslashes( $cat->name ) . '" ' . $checked . '/><label>' . $cat->name . '</label></div>';
							}
						}
					} else {
						$ret .= '<p>No categories have been found!</p>';
					}
				}
			}
		} else {
			$ret .= '<p>' . __( 'ERROR: ', 'mainwp' ) . ' no site</p>';
		}
		echo $ret;
	}

	public static function getPost() {
		$postId       = $_POST['postId'];
		$postType     = $_POST['postType'];
		$websiteId = $_POST['websiteId'];

		if ( ! MainWP_Utility::ctype_digit( $postId ) ) {
			die( json_encode( array( 'error' => 'Invalid request!' ) ) );
		}
		if ( ! MainWP_Utility::ctype_digit( $websiteId ) ) {
			die( json_encode( array( 'error' => 'Invalid request!' ) ) );
		}

		$website = MainWP_DB::Instance()->getWebsiteById( $websiteId );
		if ( ! MainWP_Utility::can_edit_website( $website ) ) {
			die( json_encode( array( 'error' => 'You can not edit this website!' ) ) );
		}

		try {
			$information = MainWP_Utility::fetchUrlAuthed( $website, 'post_action', array(
				'action'    => 'get_edit',
				'id'        => $postId,
				'post_type' => $postType
			) );

		} catch ( MainWP_Exception $e ) {
			die( json_encode( array( 'error' => MainWP_Error_Helper::getErrorMessage($e) ) ) );
		}

		if (is_array($information) && isset($information['error'])) {
			die( json_encode( array( 'error' => $information['error'] ) ) );
		}

		if ( ! isset( $information['status'] ) || ( $information['status'] != 'SUCCESS' ) ) {
			die( json_encode( array( 'error' => 'Unexpected error.' ) ) );
		} else {
			$ret = MainWP_Post::newPost($information['my_post']);
			if (is_array($ret) && isset($ret['id'])) {
				update_post_meta( $ret['id'], '_selected_sites', base64_encode( serialize( array($websiteId) ) ) );
				update_post_meta( $ret['id'], '_mainwp_edit_post_site_id', $websiteId );
			}
			//die( json_encode( $ret ) );
            wp_send_json( $ret );
		}
	}

	static function newPost($post_data = array() ) {
		//Read form data
		$new_post            = maybe_unserialize( base64_decode( $post_data['new_post'] ) );
		$post_custom         = maybe_unserialize( base64_decode( $post_data['post_custom'] ) );
		$post_category       = rawurldecode( isset( $post_data['post_category'] ) ? base64_decode( $post_data['post_category'] ) : null );
		$post_tags           = rawurldecode( isset( $new_post['post_tags'] ) ? $new_post['post_tags'] : null );
		$post_featured_image = base64_decode( $post_data['post_featured_image'] );
		$post_gallery_images = base64_decode( $post_data['post_gallery_images'] );
		$upload_dir          = maybe_unserialize( base64_decode( $post_data['child_upload_dir'] ) );
		return MainWP_Post::createPost( $new_post, $post_custom, $post_category, $post_featured_image, $upload_dir, $post_tags, $post_gallery_images ); // to edit
	}

	static function createPost( $new_post, $post_custom, $post_category, $post_featured_image, $upload_dir, $post_tags, $post_gallery_images) {
		global $current_user;

		if (!isset($new_post['edit_id']))
			return array('error' => 'Empty post id');

		$post_author = $current_user->ID;
		$new_post['post_author'] = $post_author;
		$new_post['post_type'] = isset($new_post['post_type']) && ($new_post['post_type'] == 'page') ? 'bulkpage' : 'bulkpost';

		//Search for all the images added to the new post
		//some images have a href tag to click to navigate to the image.. we need to replace this too
		$foundMatches = preg_match_all( '/(<a[^>]+href=\"(.*?)\"[^>]*>)?(<img[^>\/]*src=\"((.*?)(png|gif|jpg|jpeg))\")/ix', $new_post['post_content'], $matches, PREG_SET_ORDER );
		if ( $foundMatches > 0 ) {
			//We found images, now to download them so we can start balbal
			foreach ( $matches as $match ) {
				$hrefLink = $match[2];
				$imgUrl   = $match[4];

				if ( ! isset( $upload_dir['baseurl'] ) || ( 0 !== strripos( $imgUrl, $upload_dir['baseurl'] ) ) ) {
					continue;
				}

				if ( preg_match( '/-\d{3}x\d{3}\.[a-zA-Z0-9]{3,4}$/', $imgUrl, $imgMatches ) ) {
					$search         = $imgMatches[0];
					$replace        = '.' . $match[6];
					$originalImgUrl = str_replace( $search, $replace, $imgUrl );
				} else {
					$originalImgUrl = $imgUrl;
				}

				try {
					$downloadfile      = MainWP_Utility::uploadImage( $originalImgUrl );
					$localUrl          = $downloadfile['url'];

					$linkToReplaceWith = dirname( $localUrl );
					if ( '' !== $hrefLink ) {
						$server     = get_option( 'mainwp_child_server' );
						$serverHost = parse_url( $server, PHP_URL_HOST );
						if ( ! empty( $serverHost ) && strpos( $hrefLink, $serverHost ) !== false ) {
							$serverHref               = 'href="' . $serverHost;
							$replaceServerHref        = 'href="' . parse_url( $localUrl, PHP_URL_SCHEME ) . '://' . parse_url( $localUrl, PHP_URL_HOST );
							$new_post['post_content'] = str_replace( $serverHref, $replaceServerHref, $new_post['post_content'] );
						}
					}
					$lnkToReplace = dirname( $imgUrl );
					if ( 'http:' !== $lnkToReplace && 'https:' !== $lnkToReplace ) {
						$new_post['post_content'] = str_replace( $lnkToReplace, $linkToReplaceWith, $new_post['post_content'] );
					}
				} catch ( Exception $e ) {

				}
			}
		}

		if ( has_shortcode( $new_post['post_content'], 'gallery' ) ) {
			if ( preg_match_all( '/\[gallery[^\]]+ids=\"(.*?)\"[^\]]*\]/ix', $new_post['post_content'], $matches, PREG_SET_ORDER ) ) {
				$replaceAttachedIds = array();
				if (is_array($post_gallery_images)) {
					foreach($post_gallery_images as $gallery){
						if (isset($gallery['src'])) {
							try {
								$upload = MainWP_Utility::uploadImage( $gallery['src'], $gallery, true ); //Upload image to WP, check if existed
								if ( null !== $upload ) {
									$replaceAttachedIds[$gallery['id']] = $upload['id'];
								}
							} catch ( Exception $e ) {

							}
						}
					}
				}
				if (count($replaceAttachedIds) > 0) {
					foreach ( $matches as $match ) {
						$idsToReplace = $match[1];
						$idsToReplaceWith = "";
						$originalIds = explode(',', $idsToReplace);
						foreach($originalIds as $attached_id) {
							if (!empty($originalIds) && isset($replaceAttachedIds[$attached_id])) {
								$idsToReplaceWith .= $replaceAttachedIds[$attached_id].",";
							}
						}
						$idsToReplaceWith = rtrim($idsToReplaceWith,",");
						if (!empty($idsToReplaceWith)) {
							$new_post['post_content'] = str_replace( '"' . $idsToReplace . '"', '"'.$idsToReplaceWith.'"', $new_post['post_content'] );
						}
					}
				}
			}
		}

		$is_sticky = false;
		if (isset($new_post['is_sticky'])) {
			$is_sticky = !empty($new_post['is_sticky']) ? true : false;
			unset($new_post['is_sticky']);
		}
		$edit_id = $new_post['edit_id'];
		unset($new_post['edit_id']);

		$wp_error = null;
		//Save the post to the wp
		remove_filter( 'content_save_pre', 'wp_filter_post_kses' );  // to fix brake scripts or html
		$post_status             = $new_post['post_status'];
		$new_post['post_status'] = 'auto-draft';
		$new_post_id             = wp_insert_post( $new_post, $wp_error );

		//Show errors if something went wrong
		if ( is_wp_error( $wp_error ) ) {
			return array( 'error' => $wp_error->get_error_message());
		}

		if ( empty( $new_post_id ) ) {
			return array( 'error' => 'Undefined error' );
		}

		wp_update_post( array( 'ID' => $new_post_id, 'post_status' => $post_status ) );

		foreach ( $post_custom as $meta_key => $meta_values ) {
			foreach ( $meta_values as $meta_value ) {
				add_post_meta( $new_post_id, $meta_key, $meta_value );
			}
		}

		// update meta for bulkedit
		update_post_meta( $new_post_id, '_mainwp_edit_post_id', $edit_id );
		update_post_meta($new_post_id, '_slug', base64_encode($new_post['post_name']) );
		if ( isset( $post_category ) && '' !== $post_category ) {
			update_post_meta($new_post_id, '_categories', base64_encode($post_category) );
		}

		if ( isset( $post_tags ) && '' !== $post_tags ) {
			update_post_meta($new_post_id, '_tags', base64_encode($post_tags) );
		}
		if ($is_sticky) {
			update_post_meta( $new_post_id, '_sticky', base64_encode('sticky') );
		}
		//end//


		//If featured image exists - set it
		if ( null !== $post_featured_image ) {
			try {
				$upload = MainWP_Utility::uploadImage( $post_featured_image ); //Upload image to WP

				if ( null !== $upload ) {
					update_post_meta( $new_post_id, '_thumbnail_id', $upload['id'] ); //Add the thumbnail to the post!
				}
			} catch ( Exception $e ) {

			}
		}

		$ret['success']  = true;
		$ret['id'] = $new_post_id;
		return $ret;
	}



	public static function testPost() {
		do_action( 'mainwp-do-action', 'test_post' );
	}

	public static function setTerms( $postId, $cat_id, $taxonomy, $websiteIdEnc ) {
		if ( ! MainWP_Utility::ctype_digit( $postId ) ) {
			return;
		}
		$websiteId = $websiteIdEnc;
		if ( ! MainWP_Utility::ctype_digit( $websiteId ) ) {
			return;
		}

		$website = MainWP_DB::Instance()->getWebsiteById( $websiteId );
		if ( ! MainWP_Utility::can_edit_website( $website ) ) {
			return;
		}

		try {
			$information = MainWP_Utility::fetchUrlAuthed( $website, 'set_terms', array(
				'id'       => base64_encode( $postId ),
				'terms'    => base64_encode( $cat_id ),
				'taxonomy' => base64_encode( $taxonomy ),
			) );
		} catch ( MainWP_Exception $e ) {
			return;
		}
		if ( ! isset( $information['status'] ) || ( $information['status'] != 'SUCCESS' ) ) {
			return;
		}
	}

	public static function insertComments( $postId, $comments, $websiteId ) {
		if ( ! MainWP_Utility::ctype_digit( $postId ) ) {
			return;
		}
		if ( ! MainWP_Utility::ctype_digit( $websiteId ) ) {
			return;
		}
		$website = MainWP_DB::Instance()->getWebsiteById( $websiteId );
		if ( ! MainWP_Utility::can_edit_website( $website ) ) {
			return;
		}
		try {
			MainWP_Utility::fetchUrlAuthed( $website, 'insert_comment', array(
				'id'       => $postId,
				'comments' => base64_encode( serialize( $comments ) ),
			) );
		} catch ( MainWP_Exception $e ) {
			return;
		}

		return;
	}

	public static function addStickyOption() {
		global $wp_meta_boxes;

		if ( isset( $wp_meta_boxes['bulkpost']['side']['core']['submitdiv'] ) ) {
			$wp_meta_boxes['bulkpost']['side']['core']['submitdiv']['callback'] = array(
				self::getClassName(),
				'post_submit_meta_box',
			);
		}
	}

	public static function submitbox_misc_actions($post) {
		// fake publish button
		?>
		<script type="text/javascript">
			jQuery(document).ready(function () {
				jQuery('#publish').hide();
				jQuery('#publish').attr('disabled','disabled');
				jQuery('#publish').after('<input name="publish" id="publish" class="fake-publish-button button button-primary button-large" value="Publish" type="submit">');
			});

		</script>
		<?php
	}

	public static function post_submit_meta_box( $post ) {
		@ob_start();
		post_submit_meta_box( $post );

		$out = @ob_get_contents();
		@ob_end_clean();

		$_sticky = get_post_meta($post->ID, '_sticky', true);
		$is_sticky = false;
		if (!empty($_sticky)) {
			$_sticky = base64_decode($_sticky);
			if ($_sticky == 'sticky')
				$is_sticky = true;
		}

		$edit_id = get_post_meta($post->ID, '_mainwp_edit_post_id', true);
		// modify html output
		if ($edit_id) {
			$find    = '<input type="submit" name="publish" id="publish" class="button button-primary button-large" value="' . translate( 'Publish' ) . '"  />';
			$replace = '<input type="submit" name="publish" id="publish" class="button button-primary button-large" value="' . translate( 'Update' ) . '"  />';
			$out = str_replace( $find, $replace, $out );
		}

		$find    = "<select name='post_status' id='post_status'>";
		$replace = "<select name='mainwp_edit_post_status' id='post_status'>";  // to fix: saving pending status
		$out = str_replace( $find, $replace, $out );

		$find    = ' <label for="visibility-radio-public" class="selectit">' . translate( 'Public' ) . '</label><br />';
		$replace = '<span id="sticky-span"><input id="sticky" name="sticky" type="checkbox" value="sticky" ' . ( $is_sticky ? 'checked' : '' ) . '/> <label for="sticky" class="selectit">' . translate( 'Stick this post to the front page' ) . '</label><br /></span>';
		$replace .= '<input type="checkbox" style="display:none" name="hidden_post_sticky" id="hidden-post-sticky" value="sticky" />';
		echo str_replace( $find, $find . $replace, $out );
	}

	public static function add_sticky_handle( $post_id ) {
		// OK, we're authenticated: we need to find and save the data
		$post = get_post( $post_id );
		if ( $post->post_type == 'bulkpost' && isset( $_POST['sticky'] ) ) {
			update_post_meta( $post_id, '_sticky', base64_encode( $_POST['sticky'] ) );

			return base64_encode( $_POST['sticky'] );
		}

		if ($post->post_type == 'bulkpost' && isset($_POST['mainwp_edit_post_status'])) {
			update_post_meta( $post_id, '_edit_post_status', $_POST['mainwp_edit_post_status'] );
		}

		return $post_id;
	}
}
