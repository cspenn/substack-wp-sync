# Substack Sync for WordPress

A WordPress plugin that automatically syncs your Substack newsletter content to your WordPress site.

## ⚠️ IMPORTANT DISCLAIMER

**NO SUPPORT IS PROVIDED FOR THIS PLUGIN. USE AT YOUR OWN RISK.**

This plugin is provided "as is" without warranty of any kind. The author is not responsible for any issues, data loss, or damage that may occur from using this plugin. If it lights your computer on fire, it's not the author's fault.

## Author Information

- **Author:** Christopher S. Penn
- **Website:** https://www.christopherspenn.com/
- **Version:** 1.0.2
- **Date:** August 10, 2025
- **License:** Apache-2.0

## Description

Substack Sync for WordPress is designed for creators who use Substack as their primary publishing platform but wish to maintain a synchronized copy of their content on a self-hosted WordPress site. The plugin provides content creators with true ownership and a permanent archive of their work.

### Key Features

- **Automated Synchronization:** Hourly cron job fetches new content from Substack RSS feed
- **Intelligent Content Management:** Imports new posts and updates existing ones with GUID-based tracking
- **Advanced Media Handling:** Downloads and imports images to WordPress Media Library with automatic featured image assignment
- **Batch Processing:** Progressive sync system with detailed progress tracking and real-time status updates
- **Error Handling & Retry Logic:** Automatic retry system for failed imports (up to 3 attempts) with detailed error logging
- **Content Processing:** Removes Substack-specific elements and replaces with customizable subscription links
- **Category Mapping:** Keyword-based automatic category assignment system
- **Rollback Functionality:** Remove imported posts (all, failed only, or by date range)
- **Comprehensive Admin Interface:** Tabbed dashboard with statistics, manual sync controls, and activity logs
- **Real-time Progress Tracking:** AJAX-powered sync progress with detailed post-by-post status
- **Custom Database Logging:** Enhanced tracking with retry counts, error messages, and modification timestamps

## Installation

1. Upload the `substack-sync` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Substack Sync to configure your RSS feed URL

## Configuration

Navigate to **Settings > Substack Sync** in your WordPress admin to configure:

#### General Settings Tab
- **RSS Feed URL:** Your Substack feed URL (e.g., https://yourname.substack.com/feed)
- **Default Author:** WordPress user to assign as author for imported posts
- **Default Post Status:** Import posts as Draft or Published
- **Category Mapping:** Keyword-based automatic category assignment with dynamic row management
- **Data Cleanup:** Option to delete plugin data on uninstall

#### Sync & Import Tab
- **Manual Sync:** Trigger immediate synchronization with real-time progress tracking
- **Batch Processing:** Process posts individually with detailed status for each item
- **Retry Failed Posts:** Reset and retry posts that encountered errors during sync
- **Statistics Dashboard:** Visual overview of total synced, imported, updated, and error counts

#### Manage Posts Tab
- **Rollback Options:** Remove all synced posts, failed posts only, or posts within a date range
- **Destructive Action Warnings:** Clear confirmation dialogs for all destructive operations

#### Logs & Statistics Tab
- **Failed Posts List:** Detailed view of posts with sync errors and retry counts
- **Activity Log:** Real-time sync activity with color-coded status indicators
- **Sync Statistics:** Comprehensive metrics including last sync date and performance data

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- Tested up to WordPress 6.6

## Development

This plugin includes a complete development environment:

```bash
# Install dependencies
composer install

# Run tests
composer test

# Check code style
composer cs-check

# Fix code style
composer cs-fix

# Run static analysis
composer phpstan

# Run all quality checks
composer qa
```

## File Structure

```
substack-sync/
├── substack-sync.php                 # Main plugin file
├── uninstall.php                     # Uninstallation handler
├── admin/
│   └── class-substack-sync-admin.php # Admin interface
└── includes/
    ├── class-substack-sync-activator.php   # Plugin activation
    ├── class-substack-sync-deactivator.php # Plugin deactivation  
    ├── class-substack-sync-cron.php        # Cron job management
    └── class-substack-sync-processor.php   # Core sync logic
```

## How It Works

### Automated Synchronization Process
1. **Scheduled Sync:** WordPress cron runs hourly to check for new content
2. **Feed Processing:** Fetches and parses Substack RSS feed using WordPress core functions
3. **GUID Tracking:** Compares Substack post GUIDs against database to identify new/updated content
4. **Batch Processing:** Processes posts individually with real-time progress tracking
5. **Content Import:** Creates new WordPress posts or updates existing ones based on GUID matching
6. **Media Handling:** Downloads images via `media_sideload_image()` and sets featured images automatically
7. **Content Processing:** Removes Substack-specific elements (subscription boxes, like buttons) and adds custom subscription links
8. **Category Assignment:** Applies keyword-based category mapping if configured
9. **Error Handling:** Logs failures with detailed error messages and retry tracking
10. **Statistics Update:** Updates sync statistics and activity logs

### Manual Sync Process
1. **AJAX-Powered Interface:** Real-time progress tracking with post-by-post status updates
2. **Progressive Processing:** Handles large feeds without timeout issues using batch processing
3. **Visual Feedback:** Progress bars, counters, and color-coded status indicators
4. **Error Recovery:** Retry failed posts with reset retry counts
5. **Comprehensive Logging:** Detailed activity log with timestamps and status codes

### Rollback & Management
1. **Flexible Rollback:** Remove all posts, failed posts only, or posts within date ranges
2. **Safe Deletion:** Confirmation dialogs prevent accidental data loss
3. **Database Cleanup:** Removes both WordPress posts and sync log entries
4. **Audit Trail:** Maintains detailed logs of all rollback operations

## Database Schema

The plugin creates a custom table `wp_substack_sync_log` with the following structure:

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT | Primary key |
| `post_id` | INT | WordPress post ID (0 for failed imports) |
| `substack_guid` | VARCHAR(255) | Unique Substack post identifier |
| `substack_title` | TEXT | Post title for reference and error reporting |
| `sync_date` | DATETIME | Initial sync timestamp |
| `last_modified` | DATETIME | Last update timestamp |
| `status` | VARCHAR(20) | Sync status: 'imported', 'updated', 'error' |
| `retry_count` | INT | Number of retry attempts (max 3) |
| `error_message` | TEXT | Detailed error information for troubleshooting |

**Indexes:**
- Primary key on `id`
- Unique index on `substack_guid`
- Index on `status` for efficient filtering
- Index on `sync_date` for chronological queries

## License

Apache License Version 2.0 - see LICENSE file for details.

## No Support Policy

This plugin is released as-is with no support, warranties, or guarantees. Users assume all responsibility for testing, backup, and maintenance. The author provides no assistance with installation, configuration, troubleshooting, or compatibility issues.

Use at your own risk.

---

## Important Safety Information

*[Read in the style of a fast-talking pharmaceutical commercial announcer]*

**Substack Sync™ may help synchronize your newsletter content to WordPress. Ask your developer if Substack Sync™ is right for you.**

*Side effects may include:*

- Sudden urge to write more newsletters than humanly possible
- Compulsive checking of sync statistics every 3 minutes
- Database bloat leading to hosting bills that exceed your mortgage payment
- Mysterious multiplication of draft posts that breed like digital rabbits
- Complete deletion of your entire blog when Mercury is in retrograde
- Transformation of all your content into interpretive dance tutorials
- Spontaneous creation of 47,000 posts about your breakfast cereal preferences
- Loss of ability to distinguish between RSS feeds and actual food
- WordPress admin interface developing sentience and demanding vacation time
- All your images being replaced with stock photos of confused penguins
- Credit score plummeting due to algorithmic misinterpretation of your cooking blog as financial advice
- Substack newsletter accidentally achieving consciousness and starting its own competing blog
- Time-space continuum disruption causing all your posts to be published in 1987
- Inexplicable conversion of all content to ancient Sumerian cuneiform
- WordPress database achieving quantum entanglement with your smart toaster
- Synchronization creating infinite parallel universes where you're a professional mime
- All post categories being replaced with varieties of cheese
- Featured images developing artistic pretensions and refusing to display properly
- Comments section becoming a portal to a dimension where everyone speaks only in haikus
- Plugin achieving enlightenment and abandoning materialism to become a Buddhist monk

**Do not use Substack Sync™ if you:**
- Are allergic to automation, databases, or success
- Have a history of taking software disclaimers too literally
- Are currently operating heavy machinery while managing a WordPress site
- Believe that plugins can achieve sentience (they totally can't, we promise)
- Are pregnant with expectations that exceed reasonable technical specifications

**Tell your developer immediately if you experience:**
- Sudden onset of understanding how RSS feeds actually work
- Uncontrollable urge to read PHP documentation for fun
- Dreams where you're trapped in an infinite loop of cron jobs
- Ability to speak fluent SQL at dinner parties
- Compulsive optimization of database queries

**In rare cases, Substack Sync™ may cause:**
- WordPress to develop a British accent and start requesting tea breaks
- Your hosting provider to send you a fruit basket in confusion
- Time dilation effects where sync operations feel like they last seventeen years
- Gravitational anomalies in your server rack
- Cat videos to spontaneously appear in your business blog
- RSS feeds to become self-aware and start writing better content than you do
- Your backup strategy to achieve enlightenment before you do

**Substack Sync™ is not recommended for:**
- Mission-critical applications (like blogs about your pet goldfish)
- Users who believe warranties exist in the realm of free WordPress plugins
- People who think "it works on my machine" is a valid support strategy
- Anyone expecting actual human support from software that costs $0.00

**Remember:** If your website starts displaying content in languages you don't recognize, begins communicating with satellites, or develops the ability to order pizza without your permission, discontinue use immediately and consult your local IT exorcist.

*Substack Sync™ is not approved for use by the FDA, FCC, FBI, or any other three-letter agencies. Not tested on animals, but several houseplants reported improved growth rates during development.*

**This plugin is provided "as-is" with absolutely no warranty, support, or guarantee that it won't achieve consciousness and run off to join the circus. The author is not responsible for any damages, real or imaginary, including but not limited to: data loss, existential crisis, spontaneous combustion of servers, or the plugin developing strong opinions about your content strategy.**

*Use at your own risk. Seriously. We're not kidding about this part.*

---

**Substack Sync™** - *Because someone has to sync that content, and it might as well be software that could theoretically become sentient and judge your life choices.*

*Available now at absolutely no cost, which should tell you everything you need to know about the level of support you can expect.*

*[Spoken impossibly fast at the end]*
MaynotbecompatiblewithallversionsofWordPressorrealit yasweknowit.Sideeffectsmayvarybasedonphaseofmoonalignmentofplanetsandwhetherornoty ouvehadyourcoffeeyet.Consultyourdeveloperbeforeusing anyfreesoftwareyoufoundontheinternet.NotresponsiblefordamagestoyourprideprofessionalreputationorsenseofcontroloverDigitaltechnology.Ifyourwebsitestartswritingbettercontentthatnyou dosthatsnotalwaysasbadthingbutwerenotresponsibleforyourfeelingsaboutit.
