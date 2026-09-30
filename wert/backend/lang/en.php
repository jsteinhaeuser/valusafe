<?php
/**
 * English Translations
 * 
 * STRUCTURE:
 * - nav_* = Navigation
 * - btn_* = Buttons
 * - field_* = Form Fields
 * - msg_* = Messages (Success/Error)
 * - tab_* = Table Headers
 * - txt_* = General Text
 */

return [
    // ============================================================================
    // NAVIGATION
    // ============================================================================
    'nav_logout' => 'Logout',
    
    // Export Dropdown
    
    // ============================================================================
    // GENERAL
    // ============================================================================
    'app_title' => 'Valuables Inventory Management',
    'logged_in_as' => 'Logged in as',
    'session_expires_in' => 'Session expires in',
    
    // ============================================================================
    // BUTTONS
    // ============================================================================
    'btn_save' => 'Save',
    'btn_cancel' => 'Cancel',
    'btn_delete' => 'Delete',
    'btn_edit' => 'Edit',
    'btn_back' => 'Back',
    'btn_search' => 'Search',
    'btn_filter' => 'Filter',
    'btn_reset' => 'Reset',
    'btn_add' => 'Add',
    'btn_upload' => 'Upload',
    'btn_download' => 'Download',
    'btn_view' => 'View',
    'btn_close' => 'Close',
    'btn_confirm' => 'Confirm',
    'btn_apply' => 'Apply',
    
    // ============================================================================
    // FORM FIELDS
    // ============================================================================
    'field_name' => 'Name',
    'field_price' => 'Price',
    'field_purchase_date' => 'Purchase Date',
    'field_listed_date' => 'Listed on',
    'field_category' => 'Category',
    'field_location' => 'Location',
    'field_notes' => 'Notes',
    'field_image' => 'Image',
    'field_created_by' => 'Created by',
    'field_username' => 'Username',
    'field_password' => 'Password',
    'field_role' => 'Role',
    'field_search' => 'Search',
    
    // Placeholder
    'placeholder_search' => 'Search...',
    'placeholder_select' => '-- Please select --',
    'placeholder_all_locations' => 'All Locations',
    'placeholder_all_categories' => 'All Categories',
    
    // ============================================================================
    // TABLE HEADERS
    // ============================================================================
    'tab_name' => 'Name',
    'tab_image' => 'Image',
    'tab_category' => 'Category',
    'tab_location' => 'Location',
    'tab_price' => 'Price',
    'tab_purchase_date' => 'Purchase Date',
    'tab_created_by' => 'Created by',
    'tab_documents' => 'Docs',
    'tab_notes' => 'Notes',
    'tab_username' => 'Username',
    'tab_created_at' => 'Created',
    'tab_last_login' => 'Last Login',
    
    // ============================================================================
    // STATISTICS
    // ============================================================================
    'stats_entries' => 'Entries',
    'stats_entry' => 'Entry',
    'stats_total_value' => 'Total Value',
    'stats_average' => 'Average',
    'stats_maximum' => 'Maximum',
    
    // ============================================================================
    // MESSAGES - SUCCESS
    // ============================================================================
    'msg_saved_success' => 'Successfully saved',
    'msg_deleted_success' => 'Successfully deleted',
    'msg_created_success' => 'Successfully created',
    'msg_updated_success' => 'Successfully updated',
    'msg_uploaded_success' => 'Successfully uploaded',
    
    // ============================================================================
    // MESSAGES - ERROR
    // ============================================================================
    'msg_error_generic' => 'An error occurred',
    'msg_error_required' => 'This field is required',
    'msg_error_file_too_large' => 'File is too large',
    'msg_error_invalid_type' => 'Invalid file type',
    'msg_error_upload_failed' => 'Upload failed',
    'msg_error_not_found' => 'Not found',
    
    // ============================================================================
    // CONFIRMATIONS
    // ============================================================================
    'confirm_delete' => 'Really delete?',
    'confirm_delete_item' => 'Really delete this item?',
    'confirm_delete_user' => 'Really delete this user?',
    'confirm_discard_changes' => 'Discard changes?',
    
    // ============================================================================
    // PAGE TITLES
    // ============================================================================
    'page_dashboard' => 'Dashboard',
    'page_overview' => 'Overview',
    'page_new_item' => 'New Item',
    'page_edit_item' => 'Edit Item',
    'page_settings' => 'Settings',
    'page_activities' => 'Activities',
    'page_login' => 'Login',
    
    // ============================================================================
    // ROLES
    // ============================================================================
    'role_admin' => 'Admin',
    'role_editor' => 'Editor',
    'role_viewer' => 'Viewer',
    
    // ============================================================================
    // COLUMN SELECTOR
    // ============================================================================
    'columns_title' => 'Customize Columns',
    'columns_preset_minimal' => 'Minimal',
    'columns_preset_standard' => 'Standard',
    'columns_preset_full' => 'Full',
    'columns_preset_inventory' => 'Inventory',
    'columns_save' => 'Save',
    'columns_reset' => 'Reset',
    
    // ============================================================================
    // MISC
    // ============================================================================
    'no_entries' => 'No entries found',
    'no_image' => 'No image',
    'never' => 'Never',
    'loading' => 'Loading...',
    
    // ============================================================================
    // DASHBOARD
    // ============================================================================
    'dashboard_title' => 'Dashboard',
    'dashboard_welcome' => 'Welcome back',
    'dashboard_welcome_message' => 'Here is your overview.',
    'dashboard_total_value' => 'Total Value',
    'dashboard_total_value_desc' => 'All Items',
    'dashboard_total_items' => 'Items',
    'dashboard_total_items_desc' => 'In Inventory',
    'dashboard_avg_value' => 'Average Value',
    'dashboard_avg_value_desc' => 'Per Item',
    'dashboard_max_value' => 'Most Valuable',
    'dashboard_max_value_desc' => 'Single Item',
    'dashboard_top_category' => 'Most Valuable Category',
    'dashboard_item_singular' => 'Item',
    'dashboard_item_plural' => 'Items',
    'dashboard_top_5_valuable' => 'Top 5 Most Valuable',
    'dashboard_recently_added' => 'Recently Added',
    'dashboard_no_items' => 'No items yet',
    'dashboard_value_by_category' => 'Value by Category',
    'dashboard_quick_actions' => 'Quick Actions',
    'dashboard_new_item' => 'New Item',
    'dashboard_all_items' => 'All Items',
    'dashboard_pdf_export' => 'PDF Export',
    'dashboard_create_backup' => 'Create Backup',
    'dashboard_categories' => 'Categories',
    'dashboard_locations' => 'Locations',
    'dashboard_settings' => 'Settings',
    'dashboard_users' => 'Users',
    'dashboard_recent_activity' => 'Recent Activity',
    'dashboard_view_all_activity' => 'View All Activities',
    'dashboard_time_minutes' => '%d min ago',
    'dashboard_time_hours' => '%d hrs ago',
    'dashboard_time_days' => '%d days ago',
    
    // ============================================================================
    // ADD & EDIT FORMS
    // ============================================================================
    'form_new_item' => 'New Item',
    'form_edit_item' => 'Edit Item',
    'form_name' => 'Name',
    'form_location' => 'Location',
    'form_category' => 'Category',
    'form_purchase_date' => 'Purchase Date',
    'form_listed_date' => 'Listed Here On',
    'form_price' => 'Price (€)',
    'form_created_by' => 'Created By',
    'form_notes' => 'Notes',
    'form_image' => 'Image',
    'form_current_image' => 'Current Image',
    'form_upload_new_image' => 'Upload New Image',
    'form_no_image' => 'No image available',
    
    // Upload Zone
    'upload_drag_or_click' => 'Drag image here or click',
    'upload_max_size' => 'Max. %s MB',
    'upload_formats' => 'JPG, PNG, GIF, WebP',
    'upload_replaces_current' => 'Current image will be replaced if a new one is uploaded',
    'upload_camera' => 'Camera',
    'upload_gallery' => 'Gallery',
    'upload_remove_image' => 'Remove image',
    'upload_remove_new_image' => 'Remove new image',
    'upload_error_size' => 'File too large! Maximum: %s MB',
    'upload_error_type' => 'Invalid file type! Allowed: JPG, PNG, GIF, WebP',
    
    // Form Buttons
    'form_save' => 'Save',
    'form_cancel' => 'Cancel',
    'form_documents' => 'Documents',
    
    // Help Texts
    'form_help_created_by_default' => 'Default: %s (can be changed in settings)',
    'form_help_created_by_empty' => 'Leave empty for automatic use of current user',
    
    // Success Messages
    'msg_item_created' => 'Item successfully added',
    'msg_item_updated' => 'Item successfully updated',
    
    // Error Messages
    'error_item_not_found' => 'Entry not found',
    'error_generic'              => 'An error occurred',
    'error_loading'              => 'Error loading data',

    // Categories
    'cat_management'             => 'Category Management',
    'cat_management_desc'        => 'Manage the categories for your items here',
    'cat_add_new'                => 'Add New Category',
    'cat_name_label'             => 'Category Name',
    'cat_placeholder'            => 'e.g. Electronics, Furniture, ...',
    'cat_all'                    => 'All Categories',
    'cat_col_name'               => 'Category',
    'cat_none'                   => 'No categories yet. Add a new category above!',
    'cat_edit_title'             => 'Edit Category',
    'cat_delete_title'           => 'Delete Category?',
    'cat_in_use_title'           => 'Category still in use',
    'cat_name_empty'             => 'Category name must not be empty',
    'cat_already_exists'         => 'This category already exists',
    'cat_in_use'                 => 'This category is still used by %d items and cannot be deleted',
    'cat_added_success'          => 'Category successfully added',
    'cat_updated_success'        => 'Category successfully updated',
    'cat_deleted_success'        => 'Category successfully deleted',

    // Locations
    'loc_management'             => 'Location Management',
    'loc_management_desc'        => 'Manage the storage locations for your items here',
    'loc_add_new'                => 'Add New Location',
    'loc_name_label'             => 'Location Name',
    'loc_placeholder'            => 'e.g. Living Room, Office, Basement...',
    'loc_all'                    => 'All Locations',
    'loc_col_name'               => 'Location Name',
    'loc_none'                   => 'No locations yet. Create your first location to organise items.',
    'loc_edit_title'             => 'Edit Location',
    'loc_delete_title'           => 'Delete Location?',
    'loc_name_empty'             => 'Location name must not be empty',
    'loc_already_exists'         => 'This location already exists',
    'loc_in_use'                 => 'This location is still used by %d items and cannot be deleted',
    'loc_in_use_alert'           => 'This location is still in use and cannot be deleted.',
    'loc_added_success'          => 'Location successfully added',
    'loc_updated_success'        => 'Location successfully updated',
    'loc_deleted_success'        => 'Location successfully deleted',
    'loc_total'                  => 'Total Locations',
    'loc_in_use_label'           => 'In Use',
    'loc_unused'                 => 'Unused',
    'loc_add_btn'                => 'New Location',
    'loc_col_usage'              => 'Usage',
    'loc_col_value'              => 'Total Value',
    
    // Confirmations
    'confirm_cancel_unsaved' => 'Really cancel? Unsaved changes will be lost.',
    
    // Activity History
    'activity_history_title' => 'Change History',
    'activity_view_full' => 'View full history',
    
    // ============================================================================
    // SETTINGS
    // ============================================================================
    'settings_title' => 'Settings',
    'settings_back' => 'Back to Overview',
    
    // Tabs
    'settings_tab_theme' => 'Appearance',
    'settings_tab_profile' => 'Profile',
    'settings_tab_master' => 'Master Data',
    'settings_tab_users' => 'User Management',
    'settings_tab_backup' => 'Backup',
    'settings_tab_stats' => 'Statistics',
    'settings_tab_security' => 'Security',
    'settings_tab_tools' => 'Tools',
    
    // Theme
    'settings_theme_title' => 'Theme Selection',
    'settings_theme_select' => 'Choose a theme',
    'settings_theme_save' => 'Save theme',
    'settings_theme_saved' => 'Theme successfully saved',
    
    // Documents
    'settings_docs_title' => 'Documents Feature',
    'settings_docs_description' => 'Enable or disable the ability to add documents to items.',
    'settings_docs_enabled' => 'Enabled - Documents column is displayed',
    'settings_docs_disabled' => 'Disabled - Documents column is hidden',
    'settings_docs_save' => 'Save document settings',
    'settings_docs_saved' => 'Document settings saved',
    
    // Profile
    'settings_creator_title' => 'Default Creator',
    'settings_creator_description' => 'This name will be automatically entered as "Created by" when you add new items.',
    'settings_creator_save' => 'Save default creator',
    'settings_creator_saved' => 'Default creator successfully saved',
    'settings_creator_help' => 'This name will be automatically used for new entries',
    
    // Security
    'settings_security_title' => 'Security',
    'settings_session_info' => 'Session Information',
    'settings_logged_in_as' => 'Logged in as',
    'settings_your_role' => 'Your role',
    'settings_last_activity' => 'Last activity',
    'settings_session_expires' => 'Session expires in',
    'settings_minutes' => 'minutes',
    'settings_logout' => 'Logout',
    
    // Master Data
    'settings_master_categories' => 'Manage Categories',
    'settings_master_categories_desc' => 'Organize your valuables with categories like "Electronics", "Jewelry", "Furniture" etc.',
    'settings_master_locations' => 'Manage Locations',
    'settings_master_locations_desc' => 'Define where your valuables are located - e.g. "Living Room", "Basement", "Office" etc.',
    'settings_categories_count' => 'Categories created',
    'settings_locations_count' => 'Locations created',
    'settings_manage_categories' => 'Manage Categories',
    'settings_manage_locations' => 'Manage Locations',
    'settings_overview' => 'Overview',
    'settings_top_categories' => 'Top Categories',
    'settings_top_locations' => 'Top Locations',
    
    // User Management
    'settings_users_title'       => 'User Management',
    'users_total'                => 'Total',
    'users_editors'              => 'Editors',
    'users_viewers'              => 'Viewers',
    'users_new_user'             => 'New User',
    'users_edit_user'            => 'Edit User',
    'users_create_user'          => 'Create User',
    'users_save_changes'         => 'Save Changes',
    'users_permission'           => 'Permission',
    'users_new_password'         => 'New Password (optional)',
    'users_password_placeholder' => 'Fill in only to change',
    'users_password_hint'        => 'Leave empty to keep current password',
    'users_see_all'              => 'See all items',
    'users_see_all_hint'         => 'If disabled, user only sees own items (field "Created by")',
    'users_added_success'        => 'User successfully added',
    'users_updated_success'      => 'User successfully updated',
    'users_deleted_success'      => 'User successfully deleted',
    'users_username_empty'       => 'Username must not be empty',
    'users_cannot_delete_self'   => 'You cannot delete yourself',
    'users_confirm_delete'       => 'User',
    'users_confirm_delete_text'  => 'really delete?\n\nThis action cannot be undone!',
    'error_invalid_id'           => 'Invalid ID',
    'tab_created'                => 'Created at',
    'tab_actions'                => 'Actions',
    'tab_role'                   => 'Role',
    // Statistics
    'stats_top_categories'       => 'Top Categories',
    'stats_top_locations'        => 'Top Locations',
    'stats_top_items'            => 'Top Items',
    'stats_top10_value'          => 'Top 10 Value',
    'stats_top10_categories'     => 'Top 10 Categories',
    'stats_top10_locations'      => 'Top 10 Locations',
    'stats_top10_valuable'       => 'Top 10 Most Valuable Items',
    'stats_avg_value'            => 'Avg. Value',
    'stats_pieces'               => 'pieces',
    'stats_items'                => 'items',
    'stats_no_items'             => 'No items found',
    'stats_no_categories'        => 'No categories found',
    'stats_no_locations'         => 'No locations found',
    'stats_no_history'           => 'No value changes recorded yet. As soon as you enter a current value for an item, the chart will appear here.',
    'stats_value_trend'          => 'Value Trend Over Time',
    'stats_wealth_by_category'   => 'Wealth by Category',
    'stats_total_wealth'         => 'Total Wealth',
    'stats_most_valuable'        => 'Most Valuable Items',
    'stats_months'               => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    'settings_users_description' => 'Here you can create new users, change passwords and manage permissions.',
    'settings_users_goto' => 'Go to User Management',
    'settings_permissions_title' => 'Permission Levels',
    'settings_permission_admin' => 'Admin: Full access to all functions including user management',
    'settings_permission_edit' => 'Edit: Add, edit and delete items',
    'settings_permission_read' => 'Read Only: View overview only, no changes',
    
    // Backup
    'settings_backup_title' => 'Create Backup',
    'settings_backup_description' => 'Create a complete backup of the database and all uploaded files.',
    'settings_backup_email' => 'Email address for backup delivery (optional)',
    'settings_backup_email_help' => 'Leave this field empty to download the backup directly',
    'settings_backup_create' => 'Create Backup',
    
    // Statistics
    'settings_stats_title' => 'Statistics',
    'settings_stats_items' => 'Items',
    'settings_stats_locations' => 'Locations',
    'settings_stats_categories' => 'Categories',
    'settings_stats_total_value' => 'Total Value',
    
    // Security Tab
    'settings_delete_all_title' => 'Delete All Items',
    'settings_delete_all_warning' => 'This action will delete ALL items irrevocably!',
    'settings_delete_all_backup' => 'Make sure to create a backup first!',
    'settings_delete_all_confirm' => 'Enter "DELETE" to confirm',
    'settings_delete_all_button' => 'Irrevocably delete ALL items',
    
    // Tools
    'settings_tools_title' => 'Developer Tools',
    'settings_tools_description' => 'Test tools and diagnostic functions for developers and administrators.',
    'settings_tools_open' => 'Open',
    'settings_tools_none' => 'No tools found in /Tools/ directory.',
    
    // Messages
    'msg_theme_saved' => 'Theme successfully saved',
    'msg_creator_saved' => 'Default creator successfully saved',
    'error_theme_save' => 'Error saving theme',
    'error_invalid_theme' => 'Invalid theme selected',
    'error_creator_empty' => 'Default creator cannot be empty',
    'error_creator_too_long' => 'Default creator is too long (max. 100 characters)',
    
    // ============================================================================
    // LOGIN
    // ============================================================================
    'login_title' => 'Login',
    'login_app_name' => 'Valuables Inventory Management',
    'login_username' => 'Username',
    'login_password' => 'Password',
    'login_button' => 'Sign In',
    'login_session_expired' => 'Your session has expired. Please log in again.',
    'login_fill_all_fields' => 'Please fill in all fields',
    'login_invalid_credentials' => 'Invalid username or password',
    'login_error' => 'An error occurred. Please try again later.',
    
    // ============================================================================
    // EXPORT
    // ============================================================================
    'export_pdf_title' => 'Valuables Inventory Export',
    'export_pdf_instructions' => 'PDF Export Instructions',
    'export_pdf_step1' => 'Click the "Print as PDF" button',
    'export_pdf_step2' => 'Select in print dialog: "Save as PDF" or "Microsoft Print to PDF"',
    'export_pdf_step3' => 'IMPORTANT: Enable "Background graphics" in print options!',
    'export_pdf_step4' => 'Save the file',
    'export_pdf_tip' => 'This export contains all images and details of your items!',
    'export_pdf_print_button' => 'Print as PDF',
    'export_back_button' => 'Back to Overview',
    'export_inventory_title' => 'Valuables Inventory',
    'export_date' => 'Exported on',
    'export_user' => 'User',
    'export_count' => 'Count',
    'export_entries' => 'entries',
    'export_no_entries' => 'No entries available',
    'export_no_image' => 'No image',
    'expraum_id' => 'ID',
    'export_created_by' => 'Created by',
    'export_listed_on' => 'Listed on',
    'export_location' => 'Location',
    'export_category' => 'Category',
    'export_purchase_date' => 'Purchase Date',
    'export_price' => 'Price',
    'export_notes' => 'Notes',
    'export_total_value' => 'Total value of all items',
    'export_based_on' => 'Based on',
    'export_footer' => 'Created with Valuables Inventory Management',
    
    // CSV Export
    'csv_header_id' => 'ID',
    'csv_header_name' => 'Name',
    'csv_header_location' => 'Location',
    'csv_header_category' => 'Category',
    'csv_header_purchase_date' => 'Purchase Date',
    'csv_header_price' => 'Price (EUR)',
    'csv_header_notes' => 'Notes',
    'csv_header_image' => 'Image',
    'csv_header_created_by' => 'Created By',
    'csv_header_listed_on' => 'Listed Here On',
    'csv_header_created_at' => 'Created At',
    'error_export' => 'Error during export. Please try again later.',
    'error_loading_data' => 'Error loading data.',
    'tip' => 'Tip',
    
    // HTML Export
    'html_export_title' => 'Valuables Inventory Export',
    'html_exported_at' => 'Exported at',
    'html_user' => 'User',
    'html_entry_count' => 'Entry count',
    'html_format' => 'Format',
    'html_format_desc' => 'HTML with embedded images (Excel compatible)',
    'html_table_image' => 'Image',
    'html_summary' => 'Summary',
    'html_total_count' => 'Total count',
    'html_items_count' => 'items',
    'html_total_value' => 'Total value',
    'html_footer_text' => 'This file can be opened directly in Excel/LibreOffice.',
    
    // ============================================================================
    // HEADER & NAVIGATION
    // ============================================================================
    'header_app_title' => 'Valuables Inventory Management',
    'header_keyboard_shortcuts' => 'Keyboard Shortcuts',
    'header_shortcuts_new' => 'New Entry',
    'header_shortcuts_save' => 'Save',
    'header_shortcuts_back' => 'Back',
    'header_shortcuts_close' => 'Close',
    'header_shortcuts_show' => 'Show keyboard shortcuts',
    'header_logged_in_as' => 'Logged in as',
    'header_session_expires' => 'Session expires in',
    'header_session_expired' => 'Expired',
    'header_logout' => 'Logout',
    
    // Navigation
    'nav_dashboard' => 'Dashboard',
    'nav_all_items' => 'All Items',
    'nav_new_item' => 'New Item',
    'nav_settings' => 'Settings',
    'nav_activities' => 'Activities',
    'nav_export' => 'Export',
    'nav_export_csv' => 'CSV File',
    'nav_export_html' => 'HTML Page',
    'nav_export_pdf' => 'PDF Detailed',
    'nav_export_pdf_compact' => 'PDF Compact',
    
    // Meta
    'app_description' => 'Manage and track your valuable items with ease',
    
    // ============================================================================
    // INDEX / OVERVIEW
    // ============================================================================
    'index_no_entries' => 'No entries found',
    'index_entries_selected' => 'entries selected',
    'index_select_all' => 'Select all',
    'index_filter' => 'Filter',
    'index_reset' => 'Reset',
    'index_columns' => 'Columns',
    'index_edit' => 'Edit',
    'index_delete' => 'Delete',
    'index_confirm_delete' => 'Really delete?',
    'index_bulk_change_category' => 'Change Category',
    'index_bulk_change_location' => 'Change Location',
    'index_bulk_delete' => 'Delete',
    'index_bulk_cancel' => 'Cancel',
    'index_select_category' => 'Select category...',
    'index_select_location' => 'Select location...',
    'index_confirm_bulk_delete' => 'Really delete %d entries? This action cannot be undone!',
    'index_confirm_category_change' => 'Really change category for selected entries?',
    'index_confirm_location_change' => 'Really change location for selected entries?',
    'index_confirm_reset_columns' => 'Really reset to default settings?',
    'index_columns_saved' => 'Column selection saved',
    'index_columns_reset' => 'Column selection reset',
    'index_error_save' => 'Error saving',
    'index_error_reset' => 'Error resetting',
    'index_success_deleted' => '%d entries successfully deleted',
    'index_success_updated' => '%d entries updated',
    'index_error_bulk_action' => 'Error in bulk action',
    
    // Export Page Numbers
    'export_page' => 'Page',
    'export_of' => 'of',
    
    // Settings - Additional Tabs
    'settings_categories_description' => 'Organize your valuables with categories',
    'settings_locations_description' => 'Define storage locations',
    'settings_current' => 'Current',
    'settings_categories' => 'Categories',
    'settings_locations' => 'Locations',
    'settings_add_category' => 'Add Category',
    'settings_add_location' => 'Add Location',
    'settings_user_management' => 'User Management',
    'settings_create_user' => 'Create New User',
    'settings_username' => 'Username',
    'settings_role' => 'Role',
    'settings_created_at' => 'Created At',
    'settings_actions' => 'Actions',
    'settings_create_backup' => 'Create Backup',
    'settings_create_full_backup' => 'Create Full Backup',
    'settings_restore_backup' => 'Restore Backup',
    'settings_recent_backups' => 'Recent Backups',
    'settings_date' => 'Date',
    'settings_size' => 'Size',
    'settings_download' => 'Download',
    'settings_statistics' => 'Statistics',
    'settings_stats_description' => 'Overview of your valuables collection',
    'settings_total_value' => 'Total Value',
    'settings_item_count' => 'Number of Items',
    'settings_average_value' => 'Average Value',
    'settings_most_expensive' => 'Most Expensive Item',
    'settings_by_category' => 'By Category',
    'settings_by_location' => 'By Location',
    'settings_delete_all_items' => 'Delete All Items',
    'settings_delete_warning' => 'Caution! This action cannot be undone',
    'settings_database_cleanup' => 'Database Cleanup',
    'settings_cleanup_description' => 'Clean up orphaned entries',
    'settings_system_logs' => 'System Logs',
    'settings_show_last' => 'Show last',
    'settings_entries' => 'Entries',
    'settings_advanced_security' => 'Advanced Security',
    'settings_security_description' => 'Security settings for administrators',
    'settings_developer_tools' => 'Developer Tools',
    'settings_system_info' => 'System Information',
    'settings_php_version' => 'PHP Version',
    'settings_mysql_version' => 'MySQL Version',
    'settings_server_software' => 'Server Software',
    'settings_directory_status' => 'Directory Status',
    'settings_directory' => 'Directory',
    'settings_status' => 'Status',
    'settings_writable' => 'Writable',
    'settings_not_writable' => 'Not Writable',
    'settings_php_extensions' => 'Loaded PHP Extensions',
    'settings_save' => 'Save',
    'settings_cancel' => 'Cancel',
    'settings_add' => 'Add',
    'settings_reset' => 'Reset',
    'settings_export' => 'Export',
    'settings_import' => 'Import',
    'settings_open' => 'Open',
    'settings_close' => 'Close',
    'settings_confirm' => 'Confirm',
    'settings_no_tools_found' => 'No tools found',
    'settings_tools_hint' => 'Place PHP files in the Tools folder',
    'settings_saved_success' => 'Successfully saved',
    'settings_save_error' => 'Error saving',
    'settings_confirm_delete' => 'Really delete?',
    'settings_deleted_success' => 'Successfully deleted',
    // Settings Profile Tab - Missing Keys
    'settings_default_creator_title' => 'Default Creator',
    'settings_default_creator_description' => 'This name will be automatically entered as "Created by" when you add new items.',
    'settings_default_creator_label' => 'Default Creator',
    'settings_default_creator_hint' => 'This name will be used automatically for new entries',
    'settings_save_creator' => 'Save Default Creator',
    'settings_security_section' => 'Security',

    // Dashboard Widget Labels
    'nav_categories' => 'Categories',
    'btn_manage' => 'Manage',
    // Who is online?
    'online_widget_title'  => 'Online',
    'online_widget_none'   => 'Nobody active',
    'online_widget_link'   => 'User Management →',
    'online_window'        => 'Last 15 min.',
    'online_just_now'      => 'Just now',
    'online_minutes_ago'   => '%d min. ago',
    'online_col_user'      => 'User',
    'online_col_page'      => 'Current Page',
    'online_col_ip'        => 'IP Address',
    'online_col_seen'      => 'Last Active',
    'online_status_dot'    => '🟢 Online',
    'online_section_title' => '🟢 Currently Active Users',
    'online_section_none'  => 'Nobody active right now (last 15 min.)',

    // Backup Management (backup.php)
    'backup_management'         => 'Backup Management',
    'backup_total'              => 'Total Backups',
    'backup_total_size'         => 'Total Size',
    'backup_last'               => 'Last Backup',
    'backup_days_ago'           => '%d days ago',
    'backup_no_email'           => 'No Email',
    'backup_content'            => 'Backup Content',
    'backup_save_config'        => 'Save Configuration',
    'backup_restore'            => 'Restore Backup',
    'backup_create_now'         => 'Create Backup Now',
    'backup_email_configure'    => 'Configure Email',
    'backup_files'              => 'Backup Files',
    'backup_col_filename'       => 'Filename',
    'backup_col_type'           => 'Type',
    'backup_size'               => 'Size',
    'backup_col_created'        => 'Created',
    'backup_col_age'            => 'Age',
    'backup_type_db'            => '🗄️ DB',
    'backup_type_files'         => '🖼️ Files',
    'backup_type_php'           => '📄 PHP',
    'backup_confirm_delete'     => 'Really delete this backup?',
    'backup_confirm_bulk_delete'=> 'Permanently delete backup(s)?',
    'backup_selected'           => 'selected',
    'backup_email_notification' => 'Email Notification',
    'backup_cron_log'           => 'Cron Log',
    'backup_config_saved'       => '✅ Backup configuration saved.',
    'backup_created'            => '✅ Backup created: ',
    'backup_no_types'           => '❌ No backup types selected. Please check configuration.',
    'backup_email_disabled'     => '✅ Email notifications disabled.',
    'backup_email_saved'        => '✅ Email address saved.',
    'backup_email_invalid'      => '❌ Invalid email address.',
    'backup_deleted'            => '✅ Backup deleted: ',
    'backup_deleted_count'      => '✅ %d backup(s) deleted.',
    'backup_delete_error'       => '❌ %d file(s) could not be deleted.',
    'backup_none_selected'      => '❌ No backups selected.',

    // Next-Interface Strings
    'search_placeholder'        => 'Search…',
    'nav_items'                 => 'items',
    'nav_add'                   => 'Add',
    'filter_all'                => 'All',
    'sort_manual_active'        => '↕ Manual — sort alphabetically',
    'sort_manual'               => '↕ Manual sort',
    'detail_select_item'        => 'Select an item',

    // 4.3.5 — Texte aus alert()/confirm() im Backend, vorher fest verdrahtet
    'backup_select_at_least_one' => 'Please select at least one backup.',
    'log_confirm_delete_range'   => 'Really delete logs for the selected period?',
    'ins_confirm_delete'         => 'Delete insurance policy? Assignments will be removed.',
    'ins_confirm_unassign'       => 'Remove assignment?',
    'pub_confirm_new_link'       => 'Generate a new link? The old link will stop working.',
    'pub_confirm_disable_link'   => 'Really disable the link? It will stop working immediately.',
    'pub_qr_not_ready'           => 'QR code not ready yet.',
    'loc_room_has_positions'     => 'This room still contains %d position(s) and cannot be deleted.',
    'loc_position_in_use'        => 'This position is still used by %d item(s).',

    // ── System-Logs ─────────────────────────────────────────────
    'syslog_title'               => 'System logs',
    'syslog_title_extended'      => 'System logs (extended)',
    'syslog_tab_activity'        => 'Activity logs',
    'syslog_tab_security'        => 'Security logs',
    'syslog_view_only'           => 'This page only displays entries.',
    'syslog_cleanup_link'        => 'Delete or clean up entries',
    'syslog_file_note'         => 'The security log (file logs/security.log) cannot be deleted; it is trimmed automatically once it reaches 5 MB.',
    'syslog_db_note'           => 'Entries in the security_log table are deleted automatically after 90 days.',
    'syslog_activity_link'     => 'Go to the activity log (database, cleanup there)',
    'syslog_error'               => 'Error!',
    'syslog_none_found'          => 'No logs found',
    'syslog_entries_n'           => '%d entries',
    'syslog_total'               => 'Total',
    'syslog_total_all_time'      => 'Total (all time)',
    'syslog_success_24h'         => 'Success (24h)',
    'syslog_errors_24h'          => 'Errors (24h)',
    'syslog_success_rate_24h'    => 'Success rate (24h)',
    'syslog_logins_ok'           => 'Successful logins',
    'syslog_logins_failed'       => 'Failed logins',
    'syslog_col_table'           => 'Table',
    'syslog_col_event'           => 'Event',
    'syslog_source'              => 'Source',
    'syslog_source_db'           => 'Database (newer logs)',
    'syslog_source_file'         => 'File (historical logs)',
    'syslog_event_login_ok'      => 'Login OK',
    'syslog_event_login_failed'  => 'Login failed',
    'syslog_event_logout'        => 'Logout',
    'syslog_event_password'      => 'Password',
    'syslog_event_db_error'      => 'DB error',
    'syslog_event_backup'        => 'Backup',

    // ── Protokoll-Bereinigung ───────────────────────────────────
    'syscleanup_title'           => 'Activity log cleanup',
    'syscleanup_stats'           => 'Current statistics',
    'syscleanup_total'           => 'Total entries',
    'syscleanup_older_24h'       => 'Older than 24 hours',
    'syscleanup_older_7d'        => 'Older than 7 days',
    'syscleanup_older_30d'       => 'Older than 30 days',
    'syscleanup_older_3m'        => 'Older than 3 months',
    'syscleanup_heading'         => 'Clean up logs',
    'syscleanup_warn_title'      => 'Important:',
    'syscleanup_warn_text'       => 'Deleted logs cannot be restored. Create a backup before cleaning up.',
    'syscleanup_opt_24h'         => 'Delete logs older than 24 hours',
    'syscleanup_opt_7d'          => 'Delete logs older than 7 days',
    'syscleanup_opt_30d'         => 'Delete logs older than 30 days',
    'syscleanup_opt_3m'          => 'Delete logs older than 3 months (recommended)',
    'syscleanup_opt_custom'      => 'Custom',
    'syscleanup_deletes_n'       => 'Deletes %s entries',
    'syscleanup_after_tests'     => 'useful after testing',
    'syscleanup_older_than'      => 'Older than',
    'syscleanup_days_delete'     => 'days',
    'syscleanup_btn_delete'      => 'Delete selected logs now',
    'syscleanup_info'            => 'Information',
    'syscleanup_why'             => 'Why delete old logs?',
    'syscleanup_why_perf'        => 'Database performance:',
    'syscleanup_why_perf_d'      => 'Fewer entries, faster queries',
    'syscleanup_why_space'       => 'Storage:',
    'syscleanup_why_space_d'     => 'The log grows considerably over time',
    'syscleanup_why_privacy'     => 'Data protection:',
    'syscleanup_why_privacy_d'   => 'The GDPR suggests deleting old logs',
    'syscleanup_why_clarity'     => 'Clarity:',
    'syscleanup_why_clarity_d'   => 'More recent entries are more meaningful',
    'syscleanup_what'            => 'What happens when deleting?',
    'syscleanup_what_1'          => 'All entries older than the selected period are deleted permanently.',
    'syscleanup_what_2'          => 'The deletion itself is logged: who removed how many entries and when.',
    'syscleanup_what_3'          => 'More recent entries are kept in full.',
    'syscleanup_what_4'          => 'The deletion cannot be undone.',
    'syscleanup_hint'            => 'Recommendation:',
    'syscleanup_hint_text'       => 'Clean up once a year and delete entries older than one year. The database stays lean while enough history remains for audit purposes.',
    'syscleanup_to_logs'         => 'Go to activity logs',
    'syscleanup_back'            => 'Back to settings',
    'syscleanup_error'           => 'Error during cleanup: %s',
];

