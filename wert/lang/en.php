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
    'nav_dashboard' => 'Dashboard',
    'nav_all_items' => 'All Items',
    'nav_new_item' => 'New Item',
    'nav_settings' => 'Settings',
    'nav_activities' => 'Activities',
    'nav_export' => 'Export',
    'nav_admin'  => 'Admin',
    'nav_logout' => 'Logout',
    
    // Export Dropdown
    'nav_export_csv' => 'CSV File',
    'nav_export_html' => 'HTML Page',
    'nav_export_pdf' => 'PDF Detailed',
    'nav_export_pdf_compact' => 'PDF Compact',
    
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
    'btn_add_another' => 'Add another',
    'btn_back_to_overview' => 'Back to overview',
    'btn_share' => 'Share',
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
    'field_location' => 'Room',
    'col_ort'        => 'Room',
    'field_notes' => 'Notes',
    'field_image' => 'Image',
    'field_created_by' => 'Created by',
    'field_username' => 'Username',
    'field_password' => 'Password',
    'field_role' => 'Role',
    'field_search' => 'Search',
    
    // Placeholder
    'placeholder_search' => 'Search...',
    'placeholder_notes'  => 'Notes...',
    'placeholder_select' => '-- Please select --',
    'placeholder_all_locations' => 'All Rooms',
    'placeholder_all_categories' => 'All Categories',
    
    // ============================================================================
    // TABLE HEADERS
    // ============================================================================
    'tab_name' => 'Name',
    'tab_image' => 'Image',
    'tab_category' => 'Category',
    'tab_location' => 'Room',
    'tab_price' => 'Price',
    'tab_purchase_date' => 'Purchase Date',
    'tab_created_by' => 'Created by',
    'tab_documents' => 'Docs',
    'tab_notes' => 'Notes',
    'tab_actions' => 'Actions',
    'tab_username' => 'Username',
    'tab_role' => 'Role',
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
    'form_location' => 'Room',
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
    'add_documents'  => 'Add documents',
    
    // Help Texts
    'form_help_created_by_default' => 'Default: %s (can be changed in settings)',
    'form_help_created_by_empty' => 'Leave empty for automatic use of current user',
    
    // Success Messages
    'msg_item_created' => 'Item successfully added',
    'msg_item_updated' => 'Item successfully updated',
    
    // Error Messages
    'error_invalid_id' => 'Invalid ID',
    'error_item_not_found' => 'Entry not found',
    'error_generic' => 'An error occurred. Please try again.',
    
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
    'nav_stats'                    => 'Statistics',
    
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
    'settings_users_title' => 'User Management',
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
    'export_location' => 'Room',
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
    'csv_header_location' => 'Room',
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
    'html_footer_created' => 'Created with ValuSafe',
    
    // ============================================================================
    // HEADER & NAVIGATION
    // ============================================================================
    'header_app_title' => 'Valuables Inventory Management',
    'header_app_title_mobile' => 'My Valuables',
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
    'index_success_hidden'  => '%d entries hidden',
    'index_success_visible' => '%d entries visible again',
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

    // Action labels (Activity Log)
    'action_created'    => 'Created',
    'action_updated'    => 'Updated',
    'action_deleted'    => 'Deleted',
    'action_uploaded'   => 'Uploaded',
    'action_downloaded' => 'Downloaded',
    'action_exported'   => 'Exported',
    'action_login'      => 'Logged in',
    'action_logout'     => 'Logged out',

    // Field labels (formatChanges)
    'field_hidden'      => 'Hidden',
    'field_description' => 'Description',
    'label_empty'       => 'empty',

    // Column preset
    'columns_preset_complete' => 'Complete',

    // activity_log.php
    'page_activity_log'     => 'Activity Log',
    'log_all_users'         => 'All Users',
    'log_all_actions'       => 'All Actions',
    'log_all_areas'         => 'All Areas',
    'log_hours_ago'         => '%s hrs ago',
    'log_days_ago'          => '%s days ago',
    'log_show_details'      => 'Show details',
    'log_hide_details'      => 'Hide details',

    'log_col_area'          => 'Area',
    'log_col_details'       => 'Details',
    'log_date_from'         => 'From',
    'log_date_to'           => 'To',
    'log_cleanup_link'      => 'Clean up old logs',
    // settings.php
    'settings_error_theme_save'    => 'Error saving theme',
    'settings_error_invalid_theme' => 'Invalid theme selected',
    'settings_error_creator_empty' => 'Default creator cannot be empty',
    'settings_error_creator_long'  => 'Default creator is too long (max. 100 characters)',
    'settings_success_creator'     => 'Default creator saved successfully',
    'settings_error_save'          => 'Error saving',
    'settings_save_theme'          => 'Save theme',
    'settings_security_session'    => 'Security & Session',
    'settings_expires_in'          => 'Expires in',
    'settings_role_switch'         => 'Admin: Switch role',
    'settings_role_switch_desc'    => 'Test the view as a different role.',
    'settings_login_as_editor'     => 'Log in as <strong>Editor</strong>',
    'settings_login_as_viewer'     => 'Log in as <strong>Viewer</strong>',
    'stats_items'                  => 'Items',
    'stats_locations'              => 'Locations',
    'stats_categories'             => 'Categories',
    'error_loading'                => 'Error loading.',

    // index.php
    'index_show_hidden'            => 'Show hidden',
    // manage_kategorien.php
    'cat_invalid_id'               => 'Invalid ID',
    'cat_in_use'                   => 'Category cannot be deleted because it is still in use',
    'cat_deleted'                  => 'Category successfully deleted',
    'cat_added'                    => 'Category successfully added',
    'cat_error_delete'             => 'Error deleting',
    'cat_error_add'                => 'Error adding',
    'cat_confirm_delete'           => 'Really delete this category?',

    // manage_orte.php
    'loc_invalid_id'               => 'Invalid ID',
    'loc_in_use'                   => 'Location cannot be deleted because it is still in use',
    'loc_deleted'                  => 'Location successfully deleted',
    'loc_added'                    => 'Location successfully added',
    'loc_error_delete'             => 'Error deleting',
    'loc_error_add'                => 'Error adding',
    'loc_confirm_delete'           => 'Really delete this location?',

    // manage_users.php
    'user_no_permission'           => 'This function is not available to you.',
    'user_passwords_mismatch'      => 'Passwords do not match!',
    'user_invalid_role'            => 'Invalid role!',
    'user_cannot_delete_self'      => 'You cannot delete your own account!',
    'user_cannot_change_own_role'  => 'You cannot change your own role!',
    'user_deleted'                 => 'User successfully deleted!',
    'user_password_changed'        => 'Password successfully changed!',
    'user_role_changed'            => 'Role successfully changed!',
    'user_error_delete'            => 'Error deleting user!',
    'user_error_password'          => 'Error changing password!',
    'user_error_role'              => 'Error changing role!',
    'user_invalid_csrf'            => 'Invalid CSRF token!',

    // manage_documents.php
    'doc_invalid_id'               => 'Invalid ID',
    'doc_too_large'                => 'File too large (max. 5 MB)',
    'doc_deleted'                  => 'Document deleted',
    'doc_error_delete'             => 'Error deleting',
    'doc_confirm_delete'           => 'Really delete this document?',

    // bulk actions
    'bulk_entry_selected'          => 'entry selected',
    'bulk_entries_selected'        => 'entries selected',
    'bulk_entries_deleted'         => 'entries deleted',
    'bulk_entries_hidden'          => 'entries hidden',
    'bulk_entries_visible'         => 'entries visible again',
    'bulk_entries_updated'         => 'entries updated',
    'bulk_invalid_ids'             => 'Invalid IDs',
    'bulk_invalid_field'           => 'Invalid field',
    'bulk_confirm_delete'          => 'Really delete this entry?',

    // backup.php
    'backup_success'               => 'Backup successfully created!',
    'backup_failed'                => 'Backup failed. Please check the cron log.',
    'backup_not_found'             => 'File not found or invalid path.',
    'backup_running'               => 'Backup running…',

    // cleanup_activity_logs.php
    'log_invalid_days'             => 'Invalid number of days',
    'log_entries_deleted'          => '%d old log entries deleted (older than %d days).',
    'log_oldest_entry'             => 'Oldest entry',
    'log_newest_entry'             => 'Newest entry',
    'log_no_entries'               => 'No entries',
    'log_confirm_delete'           => 'Really delete old logs? This action cannot be undone!',

    // image_manager.php / ajax
    'error_csrf_invalid'           => 'Invalid CSRF token',
    'error_invalid_data'           => 'Invalid data',
    'error_delete_failed'          => 'Delete failed',
    'error_no_ids'                 => 'No IDs provided',

    'search_advanced'          => 'Advanced Search',
    'filter_label_search'       => 'Search',
    'filter_label_category'     => 'Category',
    'filter_label_location'     => 'Room',
    'filter_label_price'        => 'Price',
    'filter_label_date'         => 'Purchase date',
    'filter_placeholder_search' => 'Name, description, notes...',
    'filter_only_with_image'    => 'With image only',
    'filter_only_with_docs'     => 'With documents only',
    'filter_price_from'         => 'From',
    'filter_price_to'           => 'To',
    'stats_entries_found'       => 'entries found',
    'stats_entry_found'         => 'entry found',
    'stats_total_label'         => 'Total value',
    'stats_average_label'       => 'Average',

    // Barcode
    'barcode_placeholder'       => 'Enter or scan barcode / ISBN',
    'price_decimal_hint'        => 'Decimals with a point or comma are possible (e.g. 123.45 or 123,45)',
    'barcode_scan_button'       => '📷 Scan',
    'barcode_manual_prompt'     => 'Enter barcode / ISBN:',
    'barcode_in_frame'          => 'Hold the barcode within the frame',

    // Custom Fields
    'custom_field_placeholder'  => 'Custom value',
    'custom_field_type'         => 'Field type',
    'custom_field_number'       => 'Number',

    // Public view link
    'public_visible_label'      => 'Publicly visible',
    'public_visible_hint'       => 'Show this item on the public collection link',

    // Formular-Sektionen
    'form_section_basic' => 'Basic Information',
    'form_section_value' => 'Value & Purchase',
    'form_section_notes' => 'Notes',

    // Empty State
    'empty_state_no_items' => 'No items yet',
    'empty_state_no_items_hint' => 'Add your first item and start your inventory.',
    'empty_state_no_results' => 'No results found',
    'empty_state_no_results_hint' => 'The active filters return no results. Reset filters?',
    'empty_state_clear_filter' => 'Reset filters',

    // Dashboard Trend
    'dashboard_vs_30days' => 'vs. last month',

    // Dashboard Zeitwert
    'dashboard_zeitwert' => '⏱ Current Value',
    'dashboard_zeitwert_desc' => 'of',

    'dashboard_zeitwert_von' => 'of',

    'dashboard_zeitwert_bewertet' => 'items valued',

    // DSGVO Settings
    'settings_privacy_title' => 'Privacy & my data',
    'settings_privacy_desc' => 'Under GDPR you have the right to access, export and delete your data.',
    'settings_export_title' => 'Export my data',
    'settings_export_desc' => 'Downloads all your stored data as a JSON file (user data, items, activity log).',
    'settings_export_btn' => 'Download data',
    'settings_delete_account_title' => 'Delete account',
    'settings_delete_account_desc' => 'Deletes your account permanently. Your items will remain.',
    'settings_delete_account_btn' => 'Delete account',
    'settings_delete_confirm_title' => 'Really delete account?',
    'settings_delete_confirm_desc' => 'This action is irreversible. To confirm, enter your username:',
    'settings_delete_confirm_btn' => 'Delete permanently',

    // Location management
    'standort_raum'             => 'Location',
    'standort_position'         => 'Position',
    'standort_kein'             => '— no location —',
    'standort_position_waehlen' => 'Select location first…',
    'alle_raeume'               => 'All rooms',
    'alle_positionen'           => 'All positions',
    // --- backup.php Keys (v3.27) ---
    'backup_page_title' => 'Backup Management',
    'backup_stat_total' => 'Total backups',
    'backup_stat_size' => 'Total size',
    'backup_content_title' => 'Backup content',
    'backup_type_db_desc' => 'All tables as SQL dump',
    'backup_btn_save_config' => 'Save configuration',
    'backup_btn_create' => 'Create backup now',
    'backup_btn_email' => 'Configure email',
    'backup_files_title' => 'Backup files',
    'backup_no_files' => 'No backups available yet.',
    'backup_bulk_delete' => 'Delete selected',
    'backup_age_yesterday' => 'Yesterday',
    'backup_email_title' => 'Email notification',
    'backup_email_label' => 'Email address:',
    'backup_email_hint' => 'Leave empty to disable notifications.',
    'backup_download_disabled' => 'Download disabled by operator',

    // --- public.php Käufer-Anfrage Keys (v3.27) ---
    'public_no_items' => 'No items shared yet',
    'public_no_items_desc' => 'The owner has not shared any items publicly yet.',
    'public_btn_interest' => 'Express interest',
    'public_email_label' => 'Your e-mail *',
    'public_message_hint' => '(optional, max. 200 characters)',
    'public_ph_name' => 'John Doe',
    'public_ph_email' => 'john@example.com',
    'public_ph_message' => 'I am interested in this item...',

    // --- public.php Modal-Keys (v3.27) ---
    'public_name_label' => 'Your name *',
    'public_message_label' => 'Message',
    'public_btn_send' => 'Send inquiry',
    'public_anfrage_ok' => 'Your inquiry was sent successfully!',
    'public_anfrage_no_email' => 'The operator has not yet provided a contact e-mail. Please try again later.',
    'public_anfrage_error' => 'An error occurred while sending. Please try again later.',

    // Backup Management
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

    // Next-Interface
    'search_placeholder'        => 'Search…',
    'nav_items'                 => 'items',
    'nav_add'                   => 'Add',
    'filter_all'                => 'All',
    'sort_manual_active'        => '↕ Manual — sort alphabetically',
    'sort_manual'               => '↕ Manual sort',
    'detail_select_item'        => 'Select an item',

    // Next-Interface — Bild 1: Item-Liste / Advanced Search
    'per_page'                  => 'Per page',
    'col_bild'                  => 'Image',
    'col_kategorie'             => 'Category',
    'col_preis'                 => 'Price',
    'col_dokumente'             => 'Documents',
    'col_aktueller_wert'        => 'Current value',

    // Next-Interface — Bild 2: Schadenfall-Bereitschaft
    'claims_readiness'          => 'Claims readiness',
    'readiness_photo'           => 'Photo',
    'readiness_price'           => 'Price',
    'readiness_purchase_date'   => 'Purchase date',
    'readiness_perfect_msg'     => 'Fully documented — perfectly prepared for a claim!',

    // Next-Interface — Bild 3: Versicherungsübersicht
    'insurance_overview'        => 'Insurance overview',
    'col_bezeichnung'           => 'Name',
    'col_aufbewahrungsort'      => 'Storage location',
    'col_wert'                  => 'Value',
    'n_items'                   => '%d items',
    'btn_export_pdf'            => 'Export PDF',
    'btn_manage'                => 'Manage',

    // Next-Interface — Bild 4: Statistiken
    'stats_top10_total_value'   => 'Top 10 total value',
    'col_rang'                  => 'Rank',
    'col_anzahl'                => 'Count',
    'col_gesamtwert'            => 'Total value',

    // Next-Interface — Bild 5: Bild-Galerie
    'image_gallery'             => 'Image gallery',
    'gallery_total_images'      => 'Total images',
    'gallery_total_size'        => 'Total size',
    'gallery_avg_size'          => 'Avg. file size',
    'view_list'                 => 'List',
    'view_grid'                 => 'Grid',

    // Next-Interface — Bild 6: Dashboard
    'dash_users'                => 'Users',
    'dash_items'                => 'Items',
    'dash_locations_detail'     => 'Locations · %d rooms · %d pos.',
    'dash_total_value'          => 'Total value',
    'dash_categories'           => 'Categories',
    'dash_online'               => 'Online',
    'dash_nobody_active'        => 'Nobody active',
    'dash_public_link'          => 'Public link',
    'dash_recent_uploads'       => 'Recent uploads',
    'dash_most_valuable'        => 'Most valuable items',
    'link_manage'               => 'Manage →',
    'link_view'                 => 'View →',
    'link_view_all'             => 'View all →',
    'link_statistics'           => 'Statistics →',
    'link_settings'             => 'Settings →',
    'link_user_management'      => 'User Management →',


    // Next-Interface — Online widget & nav
    'online_widget_title'       => 'Online',
    'online_widget_none'        => 'Nobody active',
    'online_widget_link'        => 'User Management →',
    'nav_locations'             => 'Locations',


    // Pagination
    'pagination_prev'           => '« Previous',
    'pagination_next'           => 'Next »',

    // Locations (backend/locations.php)
    'loc_new_room'              => 'New Room',
    'loc_new_location'          => 'New Location',
    'loc_new_position'          => 'New Position',
    'loc_room_name'             => 'Room name',
    'loc_location_name'         => 'Location name',
    'loc_position_name'         => 'Position name',
    'loc_location'              => 'Location',
    'loc_positions'             => 'Positions',
    'loc_usage'                 => 'Usage',
    'loc_rooms_in_use'          => 'Rooms in use',

    // Buttons (additional)
    'btn_create'                => 'Create',


    // Locations page
    'loc_page_title'                => 'Location Management',
    'loc_rooms_total'               => 'Total rooms',
    'loc_locations_label'           => 'Locations',
    'loc_positions_label'           => 'Positions',
    'loc_tab_rooms'                 => 'Rooms',
    'loc_tab_locations'             => 'Locations',
    'loc_tab_positions'             => 'Positions',
    'loc_all_rooms'                 => 'All Rooms',
    'loc_all_locations'             => 'All Locations',
    'loc_all_positions'             => 'All Positions',
    'loc_no_rooms'                  => 'No rooms yet',
    'loc_no_locations'              => 'No locations yet',
    'loc_no_positions'              => 'No positions yet',
    'loc_none'                      => 'None',
    'loc_err_room_in_use'           => 'This room is still in use',
    'loc_err_location_has_positions'=> 'This location still has positions',
    'loc_err_position_in_use'       => 'This position is still in use',

    // Dashboard storage widget
    'dash_storage'                  => 'Storage',
    'dash_storage_images'           => 'Images',
    'dash_storage_documents'        => 'Documents',
    'dash_storage_files'            => 'files',

    // Permissions page
    'perm_page_title'               => 'Permissions – Admin',
    'perm_group_items'              => 'Items',
    'perm_group_bulk'               => 'Bulk actions',
    'perm_group_media'              => 'Images & Documents',
    'perm_group_export'             => 'Export',
    'perm_group_settings'           => 'Settings',
    'perm_group_admin'              => 'Admin area',
    'perm_admin_full_access'        => 'Admin always has full access',
    'perm_admin_full_access_badge'  => 'Admin – Full access (fixed)',
    'perm_admin_fixed_note'         => 'Admin rights are fixed.',
    'perm_locked'                   => 'This permission is fixed',

    // Public settings page
    'btn_preview'                   => 'Preview',
    'btn_save_qr'                   => 'Save QR',
    'pub_generate_link'             => 'Generate new link',
    'pub_revoke_link'               => 'Deactivate link',
    'pub_no_link_active'            => 'No public link active',
    'pub_generate_hint'             => 'Generate a link to share your marked items publicly.',
    'pub_appearance'                => 'Appearance',
    'pub_page_title_label'          => 'Page title',
    'pub_description_label'         => 'Description',
    'pub_show_price'                => 'Show purchase price',
    'pub_show_location'             => 'Show location',
    'pub_visibility_label'          => 'Item visibility',
    'pub_own_items_only'            => 'Users see only their own items',
    'pub_notes_title'               => 'Notes',
    'pub_of'                        => 'of',
    'pub_items_marked_public'       => 'items marked as public',


    // Stats page (additional)
    'stats_top10_categories'    => 'Top 10 Categories',
    'stats_top10_locations'     => 'Top 10 Locations',
    'stats_top10_valuable'      => 'Top 10 Most Valuable',
    'stats_top10_value'         => 'Top 10 Total Value',
    'stats_top_categories'      => 'Top Categories',
    'stats_top_locations'       => 'Top Locations',
    'stats_top_items'           => 'Top Items',
    'stats_most_valuable'       => 'Most Valuable Items',
    'stats_total_wealth'        => 'Total Wealth',
    'stats_wealth_by_category'  => 'Wealth by Category',
    'stats_value_trend'         => 'Value Trend',
    'stats_avg_value'           => 'Avg. Value',
    'stats_pieces'              => 'pieces',
    'stats_months'              => 'months',
    'stats_no_categories'       => 'No categories found',
    'stats_no_locations'        => 'No locations found',
    'stats_no_items'            => 'No items found',
    'stats_no_history'          => 'No history available',

    // Dashboard (additional)
    'nav_categories'            => 'Categories',


    // Activity log
    'act_recent_activity'       => 'Recent Activity',
    'act_col_user'              => 'User',
    'act_col_action'            => 'Action',
    'act_col_item'              => 'Item / Area',
    'act_col_time'              => 'Time',
    'act_none_yet'              => 'No activity recorded yet',
    'act_older_than_days'       => 'Older than %d days',
    'act_older_than_year'       => 'Older than 1 year',
    'act_delete_all'            => 'Delete all',
    'act_created'               => 'created',
    'act_updated'               => 'edited',
    'act_deleted'               => 'deleted',
    'act_login'                 => 'logged in',
    'act_logout'                => 'logged out',

    // Time ago
    'time_just_now'             => 'just now',
    'time_min'                  => 'min',
    'time_hours'                => 'h',
    'time_days'                 => 'd',
    'time_weeks'                => 'w',


    // backend/index.php
    'loc_tab_positions_short'       => 'Pos.',
    'perm_manage_link'              => 'Manage permissions →',
    'dash_quick_access'             => 'Quick access',
    'dash_no_categories'            => 'No categories yet',
    'dash_all_categories'           => 'All categories →',
    'sys_info'                      => 'System info',
    'sys_logs'                      => 'System logs',
    'sys_login_security'            => 'Login security',
    'sys_backup'                    => 'Backup',
    'sys_restore'                   => 'Restore',
    'sys_extended_logs'             => 'Extended logs',

    // permissions.php
    'perm_page_heading'             => 'Permissions overview',
    'perm_editor_badge'             => 'Editor – configurable',
    'perm_reader_badge'             => 'Reader – configurable',
    'perm_col_action'               => 'Action',
    'perm_col_admin'                => 'Admin',
    'perm_col_editor'               => 'Editor',
    'perm_col_reader'               => 'Reader',

    // public_settings.php
    'pub_page_heading'              => 'Public view link',
    'pub_page_subtitle'             => 'Share your collection without login – secured by token',
    'pub_link_status'               => 'Link status',
    'pub_link_active'               => 'Link is active',
    'btn_copy'                      => 'Copy',
    'pub_placeholder_title'         => 'My Collection',
    'pub_placeholder_desc'          => 'Short description of your collection...',
    'pub_own_items_desc'            => 'When enabled, each user only sees items where "Created by" matches their username. Admins and users with "See all" permission are exempt.',
    'pub_note_token_title'          => 'Token-secured',
    'pub_note_token_desc'           => 'only those who know the link can view the page',
    'pub_note_noindex'              => 'not indexed by search engines',
    'pub_note_mark_items_title'     => 'Mark items',
    'pub_note_mark_items_desc'      => 'when editing an item, tick "Publicly visible"',
    'pub_note_new_link_title'       => 'New link',
    'pub_note_new_link_desc'        => 'immediately invalidates old links',
    'pub_note_deactivate_title'     => 'Deactivate',
    'pub_note_deactivate_desc'      => 'link is immediately inaccessible',
    'pub_no_public_items_hint'      => 'Edit items and check the box "Publicly visible".',


    // Permissions — action labels
    'perm_checkbox_hint'            => 'Click a checkbox to change the permission immediately.',
    'perm_act_items_view'           => 'View items (main list)',
    'perm_act_items_search'         => 'Advanced search & filter',
    'perm_act_items_add'            => 'Add new item',
    'perm_act_items_edit'           => 'Edit item',
    'perm_act_items_delete'         => 'Delete item',
    'perm_act_items_hide'           => 'Hide / show item',
    'perm_act_items_value'          => 'Manage current value & history',
    'perm_act_bulk_delete'          => 'Delete multiple items',
    'perm_act_bulk_hide'            => 'Hide multiple items',
    'perm_act_bulk_update'          => 'Set category / room for multiple items',
    'perm_act_media_view'           => 'View images',
    'perm_act_media_upload'         => 'Upload images',
    'perm_act_media_delete'         => 'Delete images',
    'perm_act_docs_view'            => 'View documents',
    'perm_act_docs_upload'          => 'Upload documents',
    'perm_act_docs_delete'          => 'Delete documents',
    'perm_act_export_pdf'           => 'Export PDF',
    'perm_act_export_csv'           => 'Export Excel/CSV',
    'perm_act_export_html'          => 'Export HTML',
    'perm_act_export_insurance'     => 'Insurance export',
    'perm_act_export_qr'            => 'Generate QR codes',
    'perm_act_settings_theme'       => 'Change theme',
    'perm_act_settings_lang'        => 'Change language',
    'perm_act_settings_columns'     => 'Configure columns',
    'perm_act_settings_password'    => 'Change own password',
    'perm_act_admin_users'          => 'Manage users',
    'perm_act_admin_categories'     => 'Manage categories',
    'perm_act_admin_locations'      => 'Manage locations',
    'perm_act_admin_logs'           => 'View activity log',
    'perm_act_admin_backup'         => 'Create & manage backups',
    'perm_act_admin_permissions'    => 'Configure permissions',
    'perm_act_admin_restore'        => 'Restore backups',


    'nav_permissions' => 'Permissions',

    // Locations — usage badges
    'loc_items_count' => 'items',
    'loc_not_used' => 'Not used',
    'loc_err_room_items_used' => 'This room is still used by %d item(s).',
    'loc_err_position_items_used' => 'This position is still used by %d item(s).',

    'gallery_col_preview' => 'Preview',
    'gallery_col_location' => 'Location',
    'gallery_col_size' => 'Size',

    // ============================================================================
    // 2FA SETUP
    // ============================================================================
    '2fa_page_title' => '2FA Setup',
    '2fa_page_subtitle' => 'Protect your account with an additional security step.',
    '2fa_status_active' => '2FA is active',
    '2fa_status_active_desc' => 'Your account is protected with TOTP.',
    '2fa_btn_show_backup' => '🔑 Show backup codes',
    '2fa_btn_disable' => 'Disable 2FA',
    '2fa_status_inactive' => '2FA is not active',
    '2fa_status_inactive_desc' => 'Enable 2FA for more security.',
    '2fa_app_hint' => 'You need an authenticator app on your smartphone:',
    '2fa_btn_setup' => '🔐 Set up 2FA',
    '2fa_step1_title' => 'Step 1: Scan QR code',
    '2fa_step1_desc' => 'Open your authenticator app and scan this QR code:',
    '2fa_manual_entry' => 'Enter secret manually',
    '2fa_step2_title' => 'Step 2: Confirm code',
    '2fa_code_label' => '6-digit code from the app',
    '2fa_code_placeholder' => '6-or-8-digits',
    '2fa_code_hint' => '6-digit app code or 8-digit backup code',
    '2fa_btn_confirm' => '✅ Confirm & activate',
    '2fa_btn_cancel' => 'Cancel',
    '2fa_backup_title' => '🔑 Backup codes',
    '2fa_backup_success' => '✅ 2FA successfully activated! Store these backup codes safely.',
    '2fa_backup_desc' => 'Use these codes if you lose access to your authenticator app.',
    '2fa_backup_once' => 'Each code can only be used once.',
    '2fa_btn_print' => '🖨 Print',
    '2fa_btn_done' => 'Done',
    '2fa_disable_title' => 'Disable 2FA',
    '2fa_disable_warning' => '⚠️ After disabling, your account will only be protected with a password.',
    '2fa_disable_code_label' => 'Code from the authenticator app',
    '2fa_disabled_msg' => '2FA has been disabled.',
    '2fa_error_session' => 'Session expired. Please restart.',
    '2fa_error_code_invalid' => 'Code invalid or expired. Please try again.',
    '2fa_error_disable' => 'Code invalid. 2FA was not disabled.',
    '2fa_error_generic' => 'Code invalid.',


    // 4.3.5 — Texte aus alert()/confirm()/prompt(), vorher fest verdrahtet
    'error_camera_unavailable' => 'Camera not available: %s',
    'error_cover_load'         => 'Cover could not be loaded: %s',
    'log_enter_valid_days'     => 'Please enter a valid number of days.',
    'error_unknown'            => 'Unknown error',
    'error_save_prefix'        => 'Error while saving: %s',
    'index_select_action'      => 'Please select an action',
    'confirm_delete_image'     => 'Really delete this image?',
    'confirm_remove_avatar'    => 'Remove profile picture?',
    'loc_err_cannot_delete'    => '%s and cannot be deleted.',

    // 4.3.8 — Schluessel zu tn()-Aufrufen, die bisher nur ihren deutschen Rueckfalltext zeigten
    'export_location_unknown' => 'Unknown',
    'form_change_name'        => 'Change name',
    'form_save_anyway'        => 'Save anyway',
    'index_bulk_action'       => 'Action…',
    'index_bulk_apply'        => 'Apply',
    'index_bulk_hide'         => 'Hide',
    'index_bulk_unhide'       => 'Show again',
    'index_filter_category'   => 'Filter by category',
    'nav_gallery'             => 'Gallery',
    'nav_help'                => 'Help & information',
    'nav_insurance'           => 'Insurance',
    'nav_profile'             => 'Profile',
    'settings_language_title' => 'Language',

    // ── Versicherungsuebersicht (4.3.17) ──────────────────────────────
    'ins_cov_title'        => 'Coverage',
    'ins_cov_ok'           => 'Sum insured covers the recorded value (%s %%)',
    'ins_cov_tight'        => 'Slightly underinsured — the sum insured covers %s %% of the recorded value',
    'ins_cov_under'        => 'Underinsured — the sum insured covers only %s %% of the recorded value',
    'ins_cov_gap'          => 'Shortfall: %s',
    'ins_cov_no_sum'       => 'No sum insured recorded — coverage cannot be assessed',
    'ins_cov_unvalued'     => 'Without a value: %s of %s items — the recorded value is a lower bound',
    'ins_print'            => 'Print / Save as PDF',
    'ins_created_on'       => 'Created on %s',
    'ins_doc_created_on'   => 'Document created on %s',
    'ins_none_yet'         => 'No insurance policies yet.',
    'ins_provider'         => 'Provider',
    'ins_contract_no'      => 'No.',
    'ins_premium'          => 'Premium',
    'ins_per_year'         => '%s/year',
    'ins_sum'              => 'Sum insured',
    'ins_sum_short'        => 'Sum ins.',
    'ins_start'            => 'Start',
    'ins_until'            => 'Valid until',
    'ins_expired'          => 'expired',
    'ins_contract_data'    => 'Contract details',
    'ins_deductible'       => 'Deductible',
    'ins_payment_mode'     => 'Payment mode',
    'ins_notice_period'    => 'Notice period',
    'ins_object'           => 'Property',
    'ins_address'          => 'Address',
    'ins_building_type'    => 'Building type',
    'ins_living_area'      => 'Living area',
    'ins_rooms'            => 'Rooms',
    'ins_floor'            => 'Floor',
    'ins_ground_floor'     => 'Ground floor',
    'ins_floor_nth'        => 'Floor %s',
    'ins_cellar'           => 'Cellar',
    'ins_yes'              => 'Yes',
    'ins_no'               => 'No',
    'ins_modules'          => 'Add-on cover',
    'ins_module_bike'      => 'Bicycle theft',
    'ins_module_glass'     => 'Glass breakage',
    'ins_module_elemental' => 'Natural hazards',
    'ins_module_surge'     => 'Power surge',
    'ins_no_items'         => 'No items assigned.',
    'ins_note'             => 'Note',
    'ins_items_count'      => '%s items',
    'ins_total_all'        => 'Total value of all insured items',
    'ins_value_eur'        => 'Value (€)',
];
