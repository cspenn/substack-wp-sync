<?php

declare(strict_types=1);

/**
 * Substack Sync - WordPress Plugin
 *
 * Copyright (c) 2025 Christopher S. Penn
 * Licensed under Apache License Version 2.0
 *
 * NO SUPPORT PROVIDED. USE AT YOUR OWN RISK.
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the admin area.
 */
class Substack_Sync_Admin
{
    /**
     * Initialize the class and set its properties.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_substack_sync_now', [$this, 'handle_sync_now']);
        add_action('wp_ajax_substack_sync_batch', [$this, 'handle_batch_sync']);
        add_action('wp_ajax_substack_retry_failed', [$this, 'handle_retry_failed']);
        add_action('wp_ajax_substack_rollback_posts', [$this, 'handle_rollback_posts']);
        add_action('wp_ajax_substack_get_sync_stats', [$this, 'handle_get_sync_stats']);
    }

    /**
     * Register the administration menu for this plugin.
     */
    public function add_admin_menu(): void
    {
        add_options_page(
            'Substack Sync Settings',
            'Substack Sync',
            'manage_options',
            'substack-sync',
            [$this, 'settings_page_html']
        );
    }

    /**
     * Register settings using the Settings API.
     */
    public function register_settings(): void
    {
        register_setting('substack_sync_settings_group', 'substack_sync_settings');

        add_settings_section(
            'substack_sync_main',
            'Main Settings',
            [$this, 'settings_section_callback'],
            'substack-sync'
        );

        add_settings_field(
            'feed_url',
            'RSS Feed URL',
            [$this, 'feed_url_callback'],
            'substack-sync',
            'substack_sync_main'
        );

        add_settings_field(
            'default_author',
            'Default Author',
            [$this, 'default_author_callback'],
            'substack-sync',
            'substack_sync_main'
        );

        add_settings_field(
            'default_post_status',
            'Default Post Status',
            [$this, 'default_post_status_callback'],
            'substack-sync',
            'substack_sync_main'
        );

        add_settings_field(
            'category_mapping',
            'Category Mapping',
            [$this, 'category_mapping_callback'],
            'substack-sync',
            'substack_sync_main'
        );

        add_settings_field(
            'delete_data_on_uninstall',
            'Delete Data on Uninstall',
            [$this, 'delete_data_callback'],
            'substack-sync',
            'substack_sync_main'
        );
    }

    /**
     * Settings section callback.
     */
    public function settings_section_callback(): void
    {
        echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 15px; margin-bottom: 20px;">';
        echo '<h3 style="color: #856404; margin-top: 0;">⚠️ IMPORTANT DISCLAIMER</h3>';
        echo '<p style="color: #856404; font-weight: bold;">NO SUPPORT IS PROVIDED FOR THIS PLUGIN. USE AT YOUR OWN RISK.</p>';
        echo '<p style="color: #856404;">This plugin is provided "as is" without warranty. The author is not responsible for any issues, data loss, or damage that may occur from using this plugin.</p>';
        echo '</div>';
        
        echo '<div style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; padding: 15px; margin-bottom: 20px;">';
        echo '<h3 style="color: #0c5460; margin-top: 0;">💙 Support Your Community</h3>';
        echo '<p style="color: #0c5460;">If this plugin helps you, please consider supporting these worthy causes:</p>';
        echo '<ul style="color: #0c5460; margin-left: 20px;">';
        echo '<li><a href="https://gbfb.org?utm_source=substack_sync_plugin&utm_medium=referral" target="_blank" style="color: #0c5460; text-decoration: underline;">Greater Boston Food Bank</a> - Fighting hunger in our communities</li>';
        echo '<li><a href="https://baypathhumane.org?utm_source=substack_sync_plugin&utm_medium=referral" target="_blank" style="color: #0c5460; text-decoration: underline;">Baypath Humane Society of Hopkinton, Massachusetts</a> - Caring for animals in need</li>';
        echo '</ul>';
        echo '<p style="color: #0c5460; font-size: 0.9em; font-style: italic;">Every donation makes a difference in building stronger, more compassionate communities.</p>';
        echo '</div>';
        echo '<p>Configure your Substack synchronization settings below.</p>';
    }

    /**
     * Feed URL field callback.
     */
    public function feed_url_callback(): void
    {
        $options = get_option('substack_sync_settings', []);
        $value = $options['feed_url'] ?? '';
        echo '<input type="url" name="substack_sync_settings[feed_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Enter your Substack RSS feed URL (e.g., https://yourname.substack.com/feed)</p>';
    }

    /**
     * Default author field callback.
     */
    public function default_author_callback(): void
    {
        $options = get_option('substack_sync_settings', []);
        $selected = $options['default_author'] ?? 1;

        wp_dropdown_users([
            'name' => 'substack_sync_settings[default_author]',
            'selected' => $selected,
            'show_option_none' => 'Select an author',
        ]);
        echo '<p class="description">Choose the WordPress user to be set as the author for imported posts.</p>';
    }

    /**
     * Default post status field callback.
     */
    public function default_post_status_callback(): void
    {
        $options = get_option('substack_sync_settings', []);
        $selected = $options['default_post_status'] ?? 'draft';

        echo '<select name="substack_sync_settings[default_post_status]">';
        echo '<option value="draft"' . selected($selected, 'draft', false) . '>Draft</option>';
        echo '<option value="publish"' . selected($selected, 'publish', false) . '>Published</option>';
        echo '</select>';
        echo '<p class="description">Choose whether new posts should be imported as drafts or published immediately.</p>';
    }

    /**
     * Category mapping field callback.
     */
    public function category_mapping_callback(): void
    {
        $options = get_option('substack_sync_settings', []);
        $mappings = $options['category_mapping'] ?? [];

        echo '<div id="category-mapping-container">';
        echo '<p class="description">Map keywords found in posts to WordPress categories. Posts containing these keywords will be automatically assigned to the selected categories.</p>';

        $categories = get_categories(['hide_empty' => false]);

        if (empty($mappings)) {
            $mappings = [['keyword' => '', 'category' => '']]; // Default empty row
        }

        foreach ($mappings as $index => $mapping) {
            echo '<div class="category-mapping-row" style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">';
            echo '<label>Keyword: </label>';
            echo '<input type="text" name="substack_sync_settings[category_mapping][' . $index . '][keyword]" value="' . esc_attr($mapping['keyword'] ?? '') . '" placeholder="e.g., marketing, tutorial" style="width: 200px; margin-right: 10px;" />';
            echo '<label>Category: </label>';
            echo '<select name="substack_sync_settings[category_mapping][' . $index . '][category]" style="width: 200px; margin-right: 10px;">';
            echo '<option value="">Select Category</option>';

            foreach ($categories as $category) {
                $selected = selected($mapping['category'] ?? '', $category->term_id, false);
                echo '<option value="' . $category->term_id . '"' . $selected . '>' . esc_html($category->name) . '</option>';
            }

            echo '</select>';
            echo '<button type="button" class="button remove-mapping" onclick="removeCategoryMapping(this)">Remove</button>';
            echo '</div>';
        }

        echo '</div>';
        echo '<button type="button" class="button" onclick="addCategoryMapping()">Add Mapping</button>';

        // JavaScript for dynamic rows
        echo '<script>
        function addCategoryMapping() {
            const container = document.getElementById("category-mapping-container");
            const index = container.children.length;
            const categoryOptions = ' . wp_json_encode(array_map(function ($cat) {
            return ['id' => $cat->term_id, 'name' => $cat->name];
        }, $categories)) . ';
            
            let optionsHtml = "<option value=\"\">Select Category</option>";
            categoryOptions.forEach(cat => {
                optionsHtml += `<option value="${cat.id}">${cat.name}</option>`;
            });
            
            const row = `<div class="category-mapping-row" style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <label>Keyword: </label>
                <input type="text" name="substack_sync_settings[category_mapping][${index}][keyword]" placeholder="e.g., marketing, tutorial" style="width: 200px; margin-right: 10px;" />
                <label>Category: </label>
                <select name="substack_sync_settings[category_mapping][${index}][category]" style="width: 200px; margin-right: 10px;">${optionsHtml}</select>
                <button type="button" class="button remove-mapping" onclick="removeCategoryMapping(this)">Remove</button>
            </div>`;
            
            container.insertAdjacentHTML("beforeend", row);
        }
        
        function removeCategoryMapping(button) {
            button.closest(".category-mapping-row").remove();
        }
        </script>';
    }

    /**
     * Delete data field callback.
     */
    public function delete_data_callback(): void
    {
        $options = get_option('substack_sync_settings', []);
        $checked = isset($options['delete_data_on_uninstall']) && $options['delete_data_on_uninstall'];

        echo '<input type="checkbox" name="substack_sync_settings[delete_data_on_uninstall]" value="1"' . checked($checked, true, false) . ' />';
        echo '<p class="description">Check this box if you want to delete all plugin data when uninstalling.</p>';
    }

    /**
     * Display the settings page HTML.
     */
    public function settings_page_html(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        // Get sync statistics
        require_once SUBSTACK_SYNC_PLUGIN_DIR . 'includes/class-substack-sync-processor.php';
        $processor = new Substack_Sync_Processor();
        $stats = $processor->get_sync_stats();
        $failed_posts = $processor->get_posts_needing_retry();

        ?>
        <div class="wrap">
            <h1>Substack Sync Settings</h1>
            
            <nav class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active">General Settings</a>
                <a href="#sync" class="nav-tab">Sync & Import</a>
                <a href="#manage" class="nav-tab">Manage Posts</a>
                <a href="#logs" class="nav-tab">Logs & Statistics</a>
            </nav>

            <div id="general" class="tab-content" style="display: block;">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('substack_sync_settings_group');
        do_settings_sections('substack-sync');
        submit_button('Save Settings');
        ?>
                </form>
            </div>

            <div id="sync" class="tab-content" style="display: none;">
                <h2>Manual Sync & Import</h2>
                <div class="sync-overview">
                    <div class="sync-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <div class="stat-card" style="background: #f9f9f9; padding: 15px; border-radius: 6px; border-left: 4px solid #0073aa;">
                            <h3 style="margin-top: 0;">📊 Total Synced</h3>
                            <p style="font-size: 24px; font-weight: bold; margin: 5px 0;"><?php echo $stats['total_synced']; ?></p>
                        </div>
                        <div class="stat-card" style="background: #f9f9f9; padding: 15px; border-radius: 6px; border-left: 4px solid #46b450;">
                            <h3 style="margin-top: 0;">📥 Imported</h3>
                            <p style="font-size: 24px; font-weight: bold; margin: 5px 0; color: #46b450;"><?php echo $stats['imported_count']; ?></p>
                        </div>
                        <div class="stat-card" style="background: #f9f9f9; padding: 15px; border-radius: 6px; border-left: 4px solid #ffb900;">
                            <h3 style="margin-top: 0;">📝 Updated</h3>
                            <p style="font-size: 24px; font-weight: bold; margin: 5px 0; color: #ffb900;"><?php echo $stats['updated_count']; ?></p>
                        </div>
                        <div class="stat-card" style="background: #f9f9f9; padding: 15px; border-radius: 6px; border-left: 4px solid #dc3232;">
                            <h3 style="margin-top: 0;">❌ Errors</h3>
                            <p style="font-size: 24px; font-weight: bold; margin: 5px 0; color: #dc3232;"><?php echo $stats['error_count']; ?></p>
                        </div>
                    </div>
                    
                    <?php if ($stats['last_sync_date']): ?>
                    <p><strong>Last Sync:</strong> <?php echo date('F j, Y g:i a', strtotime($stats['last_sync_date'])); ?></p>
                    <?php endif; ?>
                </div>

                <div class="sync-actions" style="margin: 20px 0;">
                    <button type="button" id="sync-now-btn" class="button button-primary">🔄 Sync Now</button>
                    <?php if (! empty($failed_posts)): ?>
                        <button type="button" id="retry-failed-btn" class="button button-secondary">🔁 Retry Failed Posts (<?php echo count($failed_posts); ?>)</button>
                    <?php endif; ?>
                </div>
                
                <div id="sync-status" style="margin-top: 10px;"></div>
            </div>

            <div id="manage" class="tab-content" style="display: none;">
                <h2>Manage Synced Posts</h2>
                <div class="manage-actions">
                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
                        <h3 style="color: #856404; margin-top: 0;">⚠️ Warning: Destructive Actions</h3>
                        <p style="color: #856404;">These actions will permanently delete WordPress posts that were imported from Substack. This cannot be undone.</p>
                    </div>
                    
                    <h3>Rollback Options</h3>
                    <p>Select which synced posts to remove from WordPress:</p>
                    
                    <div style="margin: 20px 0;">
                        <button type="button" id="rollback-all-btn" class="button button-secondary">🗑️ Remove All Synced Posts</button>
                        <p class="description">Removes all posts that were imported from Substack</p>
                    </div>
                    
                    <div style="margin: 20px 0;">
                        <button type="button" id="rollback-failed-btn" class="button">🗑️ Remove Failed Posts Only</button>
                        <p class="description">Removes only posts that had errors during sync</p>
                    </div>
                    
                    <div style="margin: 20px 0;">
                        <label>Remove posts by date range:</label><br>
                        <input type="date" id="rollback-date-from" style="margin: 5px;"> to 
                        <input type="date" id="rollback-date-to" style="margin: 5px;">
                        <button type="button" id="rollback-date-btn" class="button">🗑️ Remove Date Range</button>
                    </div>
                </div>
                
                <div id="rollback-status" style="margin-top: 10px;"></div>
            </div>

            <div id="logs" class="tab-content" style="display: none;">
                <h2>Sync Logs & Statistics</h2>
                
                <?php if (! empty($failed_posts)): ?>
                <div class="failed-posts-section" style="margin-bottom: 30px;">
                    <h3 style="color: #dc3232;">❌ Failed Posts (<?php echo count($failed_posts); ?>)</h3>
                    <div class="failed-posts-list" style="background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 300px; overflow-y: auto;">
                        <?php foreach ($failed_posts as $post): ?>
                            <div class="failed-post-item" style="padding: 10px; border-bottom: 1px solid #eee;">
                                <strong><?php echo esc_html($post['substack_title']); ?></strong>
                                <br>
                                <small>Attempts: <?php echo $post['retry_count']; ?> | Error: <?php echo esc_html($post['error_message']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="sync-log-section">
                    <h3>📋 Recent Activity</h3>
                    <div id="sync-activity-log" style="background: white; border: 1px solid #ddd; border-radius: 4px; height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px; padding: 10px;">
                        <div style="color: #666;">Loading recent sync activity...</div>
                    </div>
                    <button type="button" id="refresh-logs-btn" class="button" style="margin-top: 10px;">🔄 Refresh Logs</button>
                </div>
            </div>

            <style>
            .nav-tab-wrapper {
                border-bottom: 1px solid #ccd0d4;
                margin-bottom: 20px;
            }
            .nav-tab {
                cursor: pointer;
            }
            .tab-content {
                padding: 20px 0;
            }
            </style>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Tab functionality
                const tabs = document.querySelectorAll('.nav-tab');
                const tabContents = document.querySelectorAll('.tab-content');
                
                tabs.forEach(tab => {
                    tab.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // Remove active class from all tabs and contents
                        tabs.forEach(t => t.classList.remove('nav-tab-active'));
                        tabContents.forEach(tc => tc.style.display = 'none');
                        
                        // Add active class to clicked tab
                        this.classList.add('nav-tab-active');
                        
                        // Show corresponding content
                        const targetId = this.getAttribute('href').substring(1);
                        document.getElementById(targetId).style.display = 'block';
                    });
                });
            });

            // Initialize enhanced sync functionality
            if (document.getElementById('sync-now-btn')) {
                new SubstackSyncProgress();
                new SubstackAdminManager();
            }

            class SubstackAdminManager {
                constructor() {
                    this.ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
                    this.nonce = '<?php echo wp_create_nonce('substack_sync_nonce'); ?>';
                    
                    this.initEventListeners();
                }

                initEventListeners() {
                    // Retry failed posts
                    const retryBtn = document.getElementById('retry-failed-btn');
                    if (retryBtn) {
                        retryBtn.addEventListener('click', () => this.retryFailedPosts());
                    }

                    // Rollback actions
                    const rollbackAllBtn = document.getElementById('rollback-all-btn');
                    if (rollbackAllBtn) {
                        rollbackAllBtn.addEventListener('click', () => this.rollbackPosts('all'));
                    }

                    const rollbackFailedBtn = document.getElementById('rollback-failed-btn');
                    if (rollbackFailedBtn) {
                        rollbackFailedBtn.addEventListener('click', () => this.rollbackPosts('failed'));
                    }

                    const rollbackDateBtn = document.getElementById('rollback-date-btn');
                    if (rollbackDateBtn) {
                        rollbackDateBtn.addEventListener('click', () => this.rollbackPosts('date'));
                    }

                    // Refresh logs
                    const refreshLogsBtn = document.getElementById('refresh-logs-btn');
                    if (refreshLogsBtn) {
                        refreshLogsBtn.addEventListener('click', () => this.refreshLogs());
                    }
                }

                retryFailedPosts() {
                    if (!confirm('Are you sure you want to retry all failed posts?')) return;
                    
                    this.showStatus('retry-status', '🔄 Retrying failed posts...', 'info');
                    
                    fetch(this.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=substack_retry_failed&_ajax_nonce=${this.nonce}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showStatus('retry-status', '✅ ' + data.data.message, 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            this.showStatus('retry-status', '❌ ' + data.data, 'error');
                        }
                    })
                    .catch(error => {
                        this.showStatus('retry-status', '❌ Error: ' + error.message, 'error');
                    });
                }

                rollbackPosts(type) {
                    let confirmMessage = 'This will permanently delete WordPress posts. Are you sure?';
                    let postData = `action=substack_rollback_posts&_ajax_nonce=${this.nonce}&type=${type}`;
                    
                    if (type === 'date') {
                        const dateFrom = document.getElementById('rollback-date-from').value;
                        const dateTo = document.getElementById('rollback-date-to').value;
                        
                        if (!dateFrom || !dateTo) {
                            alert('Please select both start and end dates.');
                            return;
                        }
                        
                        confirmMessage = `This will delete all synced posts between ${dateFrom} and ${dateTo}. Are you sure?`;
                        postData += `&date_from=${dateFrom}&date_to=${dateTo}`;
                    }
                    
                    if (!confirm(confirmMessage)) return;
                    
                    this.showStatus('rollback-status', '🗑️ Removing posts...', 'info');
                    
                    fetch(this.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: postData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showStatus('rollback-status', '✅ ' + data.data.message, 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            this.showStatus('rollback-status', '❌ ' + data.data, 'error');
                        }
                    })
                    .catch(error => {
                        this.showStatus('rollback-status', '❌ Error: ' + error.message, 'error');
                    });
                }

                refreshLogs() {
                    fetch(this.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=substack_get_sync_stats&_ajax_nonce=${this.nonce}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data.logs) {
                            const logContainer = document.getElementById('sync-activity-log');
                            logContainer.innerHTML = data.data.logs.map(log => 
                                `<div style="margin-bottom: 5px; color: ${this.getLogColor(log.status)};">
                                    ${log.sync_date} - ${log.status.toUpperCase()}: ${log.substack_title}
                                </div>`
                            ).join('');
                        }
                    });
                }

                showStatus(elementId, message, type) {
                    const element = document.getElementById(elementId);
                    if (!element) return;
                    
                    const colors = {
                        success: '#46b450',
                        error: '#dc3232',
                        info: '#0073aa',
                        warning: '#ffb900'
                    };
                    
                    element.innerHTML = `<div style="padding: 10px; border-left: 4px solid ${colors[type] || '#0073aa'}; background: #f9f9f9; margin: 10px 0;">${message}</div>`;
                }

                getLogColor(status) {
                    const colors = {
                        'imported': '#46b450',
                        'updated': '#ffb900', 
                        'error': '#dc3232'
                    };
                    return colors[status] || '#666';
                }
            }
            </script>
            
            <style>
            .sync-progress {
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 15px;
                background: #f9f9f9;
                margin-top: 10px;
            }
            .progress-bar {
                width: 100%;
                height: 20px;
                background: #e0e0e0;
                border-radius: 10px;
                margin: 10px 0;
                overflow: hidden;
            }
            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #0073aa, #005a87);
                transition: width 0.3s ease;
                border-radius: 10px;
            }
            .post-log {
                max-height: 300px;
                overflow-y: auto;
                border: 1px solid #ddd;
                border-radius: 3px;
                padding: 10px;
                background: white;
                margin-top: 10px;
                font-family: monospace;
                font-size: 12px;
            }
            .post-entry {
                padding: 3px 0;
                border-bottom: 1px solid #eee;
            }
            .post-entry:last-child {
                border-bottom: none;
            }
            .post-entry.success { color: #46b450; }
            .post-entry.error { color: #dc3232; }
            .post-entry.warning { color: #ffb900; }
            </style>
            
            <script>
            class SubstackSyncProgress {
                constructor() {
                    this.button = document.getElementById('sync-now-btn');
                    this.status = document.getElementById('sync-status');
                    this.ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
                    this.nonce = '<?php echo wp_create_nonce('substack_sync_nonce'); ?>';
                    this.currentOffset = 0;
                    this.totalPosts = 0;
                    this.processedPosts = 0;
                    this.importedPosts = 0;
                    this.updatedPosts = 0;
                    this.errorCount = 0;
                    this.isRunning = false;
                    
                    this.button.addEventListener('click', () => this.startSync());
                }

                startSync() {
                    if (this.isRunning) return;
                    
                    this.isRunning = true;
                    this.currentOffset = 0;
                    this.totalPosts = 0;
                    this.processedPosts = 0;
                    this.importedPosts = 0;
                    this.updatedPosts = 0;
                    this.errorCount = 0;
                    
                    this.button.disabled = true;
                    this.button.textContent = 'Syncing...';
                    
                    this.showProgressInterface();
                    this.processBatch();
                }

                showProgressInterface() {
                    this.status.innerHTML = `
                        <div class="sync-progress">
                            <h3>📡 Synchronization in Progress</h3>
                            <div class="sync-stats">
                                <p><strong>Status:</strong> <span id="sync-current-status">Initializing...</span></p>
                                <p><strong>Progress:</strong> <span id="sync-progress-text">0/0 posts processed</span></p>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                            </div>
                            <div class="sync-summary">
                                <span>📥 Imported: <strong id="imported-count">0</strong></span> | 
                                <span>📝 Updated: <strong id="updated-count">0</strong></span> | 
                                <span>❌ Errors: <strong id="error-count">0</strong></span>
                            </div>
                            <div class="post-log" id="post-log">
                                <div class="post-entry">📋 Starting synchronization process...</div>
                            </div>
                        </div>
                    `;
                }

                processBatch() {
                    this.updateStatus('Processing posts...');
                    
                    const formData = new FormData();
                    formData.append('action', 'substack_sync_batch');
                    formData.append('_ajax_nonce', this.nonce);
                    formData.append('offset', this.currentOffset.toString());
                    formData.append('batch_size', '1');

                    fetch(this.ajaxUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.handleBatchSuccess(data.data);
                        } else {
                            this.handleError('Batch processing failed: ' + data.data);
                        }
                    })
                    .catch(error => {
                        this.handleError('Network error: ' + error.message);
                    });
                }

                handleBatchSuccess(result) {
                    // Update totals on first batch
                    if (this.totalPosts === 0) {
                        this.totalPosts = result.total_posts;
                        this.logMessage(`🎯 Found ${this.totalPosts} posts in feed`);
                    }

                    // Process the results
                    if (result.processed_posts && result.processed_posts.length > 0) {
                        result.processed_posts.forEach(post => {
                            this.processedPosts++;
                            
                            switch (post.action) {
                                case 'imported':
                                    this.importedPosts++;
                                    this.logMessage(`📥 Imported: ${post.post_title}`, 'success');
                                    break;
                                case 'updated':
                                    this.updatedPosts++;
                                    this.logMessage(`📝 Updated: ${post.post_title}`, 'success');
                                    break;
                                case 'skipped':
                                    this.logMessage(`⏭️ Skipped: ${post.post_title} (${post.message})`, 'warning');
                                    break;
                                case 'error':
                                    this.errorCount++;
                                    this.logMessage(`❌ Error: ${post.message}`, 'error');
                                    break;
                            }
                        });
                    }

                    // Update progress
                    this.updateProgress();
                    
                    // Continue processing or finish
                    if (result.has_more) {
                        this.currentOffset = result.next_offset;
                        setTimeout(() => this.processBatch(), 100); // Small delay between posts
                    } else {
                        this.finishSync();
                    }
                }

                updateProgress() {
                    const percentage = this.totalPosts > 0 ? Math.round((this.processedPosts / this.totalPosts) * 100) : 0;
                    
                    document.getElementById('progress-fill').style.width = percentage + '%';
                    document.getElementById('sync-progress-text').textContent = `${this.processedPosts}/${this.totalPosts} posts processed (${percentage}%)`;
                    document.getElementById('imported-count').textContent = this.importedPosts;
                    document.getElementById('updated-count').textContent = this.updatedPosts;
                    document.getElementById('error-count').textContent = this.errorCount;
                    
                    this.updateStatus(`Processing post ${this.processedPosts + 1} of ${this.totalPosts}...`);
                }

                finishSync() {
                    this.isRunning = false;
                    this.button.disabled = false;
                    this.button.textContent = 'Sync Now';
                    
                    const successMessage = `✅ Sync completed! Processed ${this.processedPosts} posts: ${this.importedPosts} imported, ${this.updatedPosts} updated`;
                    
                    if (this.errorCount > 0) {
                        this.updateStatus(`⚠️ Sync completed with ${this.errorCount} errors`);
                        this.logMessage(`⚠️ ${successMessage} (${this.errorCount} errors)`, 'warning');
                    } else {
                        this.updateStatus('✅ Sync completed successfully!');
                        this.logMessage(successMessage, 'success');
                    }
                }

                handleError(message) {
                    this.isRunning = false;
                    this.button.disabled = false;
                    this.button.textContent = 'Sync Now';
                    
                    this.updateStatus('❌ Sync failed');
                    this.logMessage(`❌ ${message}`, 'error');
                }

                updateStatus(message) {
                    const statusElement = document.getElementById('sync-current-status');
                    if (statusElement) {
                        statusElement.textContent = message;
                    }
                }

                logMessage(message, type = 'info') {
                    const logElement = document.getElementById('post-log');
                    if (logElement) {
                        const entry = document.createElement('div');
                        entry.className = `post-entry ${type}`;
                        entry.textContent = `${new Date().toLocaleTimeString()} - ${message}`;
                        logElement.appendChild(entry);
                        logElement.scrollTop = logElement.scrollHeight;
                    }
                }
            }

            // Initialize when DOM is ready
            document.addEventListener('DOMContentLoaded', function() {
                new SubstackSyncProgress();
            });

            // Fallback for cases where DOMContentLoaded already fired
            if (document.readyState === 'loading') {
                // Do nothing, DOMContentLoaded will fire
            } else {
                // DOMContentLoaded already fired
                new SubstackSyncProgress();
            }
            </script>
        </div>
        <?php
    }

    /**
     * Handle AJAX sync now request.
     */
    public function handle_sync_now(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        check_ajax_referer('substack_sync_nonce');

        try {
            require_once SUBSTACK_SYNC_PLUGIN_DIR . 'includes/class-substack-sync-processor.php';
            $processor = new Substack_Sync_Processor();
            $result = $processor->run_sync(true);

            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result['error']);
            }
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Handle AJAX batch sync request for progressive sync.
     */
    public function handle_batch_sync(): void
    {
        // Prevent any output before JSON response
        ob_clean();

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);

            return;
        }

        if (! wp_verify_nonce($_POST['_ajax_nonce'] ?? '', 'substack_sync_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);

            return;
        }

        $offset = intval($_POST['offset'] ?? 0);
        $batch_size = intval($_POST['batch_size'] ?? 1);

        try {
            require_once SUBSTACK_SYNC_PLUGIN_DIR . 'includes/class-substack-sync-processor.php';
            $processor = new Substack_Sync_Processor();
            $result = $processor->run_batch_sync($batch_size, $offset);

            // Ensure clean JSON response
            wp_send_json_success($result);
        } catch (Throwable $e) {
            error_log('Substack Sync AJAX Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'Sync error: ' . $e->getMessage(),
                'has_more' => false,
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ]);
        }
    }

    /**
     * Handle retry failed posts AJAX request.
     */
    public function handle_retry_failed(): void
    {
        ob_clean();

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);

            return;
        }

        if (! wp_verify_nonce($_POST['_ajax_nonce'] ?? '', 'substack_sync_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);

            return;
        }

        try {
            require_once SUBSTACK_SYNC_PLUGIN_DIR . 'includes/class-substack-sync-processor.php';
            $processor = new Substack_Sync_Processor();
            $failed_posts = $processor->get_posts_needing_retry();

            if (empty($failed_posts)) {
                wp_send_json_success(['message' => 'No failed posts to retry']);

                return;
            }

            $retried_count = 0;
            foreach ($failed_posts as $failed_post) {
                // Reset retry count to allow retry
                $processor->reset_post_retry_count($failed_post['substack_guid']);
                $retried_count++;
            }

            wp_send_json_success([
                'message' => "Reset retry status for {$retried_count} posts. Run sync again to retry them.",
                'retried_count' => $retried_count,
            ]);
        } catch (Throwable $e) {
            error_log('Substack Sync Retry Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Retry error: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle rollback posts AJAX request.
     */
    public function handle_rollback_posts(): void
    {
        ob_clean();

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);

            return;
        }

        if (! wp_verify_nonce($_POST['_ajax_nonce'] ?? '', 'substack_sync_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);

            return;
        }

        $type = sanitize_text_field($_POST['type'] ?? '');

        try {
            require_once SUBSTACK_SYNC_PLUGIN_DIR . 'includes/class-substack-sync-processor.php';
            $processor = new Substack_Sync_Processor();

            $deleted_count = 0;

            switch ($type) {
                case 'all':
                    $deleted_count = $processor->rollback_all_posts();

                    break;
                case 'failed':
                    $deleted_count = $processor->rollback_failed_posts();

                    break;
                case 'date':
                    $date_from = sanitize_text_field($_POST['date_from'] ?? '');
                    $date_to = sanitize_text_field($_POST['date_to'] ?? '');
                    $deleted_count = $processor->rollback_posts_by_date($date_from, $date_to);

                    break;
                default:
                    wp_send_json_error(['message' => 'Invalid rollback type']);

                    return;
            }

            wp_send_json_success([
                'message' => "Successfully removed {$deleted_count} posts from WordPress",
                'deleted_count' => $deleted_count,
            ]);
        } catch (Throwable $e) {
            error_log('Substack Sync Rollback Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Rollback error: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle get sync stats AJAX request.
     */
    public function handle_get_sync_stats(): void
    {
        ob_clean();

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);

            return;
        }

        if (! wp_verify_nonce($_POST['_ajax_nonce'] ?? '', 'substack_sync_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);

            return;
        }

        try {
            require_once SUBSTACK_SYNC_PLUGIN_DIR . 'includes/class-substack-sync-processor.php';
            $processor = new Substack_Sync_Processor();
            $logs = $processor->get_recent_sync_logs(50);

            wp_send_json_success([
                'logs' => $logs,
            ]);
        } catch (Throwable $e) {
            error_log('Substack Sync Stats Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Stats error: ' . $e->getMessage()]);
        }
    }
}
