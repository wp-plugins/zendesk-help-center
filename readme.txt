=== Zendesk Help Center Backup by BestWebSoft ===
Contributors: bestwebsoft
Donate link: https://www.2checkout.com/checkout/purchase?sid=1430388&quantity=10&product_id=13
Tags: zendesk, zendesk help center, backup, help center, backup data, save zendesk help center data, zendesk help center data to database, database, zen desk, zendesk hc, zendesk help center backup, zendesk help center backup plugin, synchronize zendesk help center, zendeks, zendks, zednesk, help centre, backup time, zendesk backup log, zendesk data to database. 
Requires at least: 4.0
Tested up to: 4.1.1
Stable tag: 0.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin allows to backup Zendesk Help Center.

== Description ==

Note: This is a public beta version, which contains basic product options. You are welcome to suggest new features and usability improvements.
<a href="http://support.bestwebsoft.com/hc/en-us/requests/new">Submit a new feature</a>

Zendesk Help Center Backup is a simple yet highly convenient plugin that creates a backup copy of your Zendesk Help Center to the database. The backup is made through the cron within the time frame specified in the settings. The time of the last backup, as well as logs, are displayed on the plugin settings page. Also, in case backup error occurs, the plugin sends a message to the email, which is also specified in the settings. 

= Translation =

* Russian (ru_RU)

If you create your own language pack or update an existing one, you can send <a href="http://codex.wordpress.org/Translating_WordPress" target="_blank">the text of PO and MO files</a> to <a href="http://bestwebsoft.com/" target="_blank">BestWebSoft</a> and we'll add it to the plugin. You can download the latest version of the program for working with PO and MO files <a href="http://www.poedit.net/download.php" target="_blank">Poedit</a>.

== Installation ==

1. Upload `zendesk-hc` folder to `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Plugin settings are located in 'BWS Plugin', 'Zendesk HC Backup'.

== Frequently Asked Questions ==

= What exactly will be backed up? =

The plugin creates a backup of the following Zendesk Help Center data:
- Categories
- Sections
- Articles
- Article Comments
- Article Labels
- Article Attachments

= Are Article Attachments saved? = 

Yes, Article Attachments are located in WP Downloads folder in zendesk_hc_attachments folder. 

= Does the plugin store backups by versions? =

No, solely the current (pending) backup version is kept. For instance, if some elements were deleted from your Zendesk Help Center, when synchronizing (creating a backup) this data will also be removed. 

= Does the plugin display the data from Zendesk HC ? =

No, the plugin merely creates a backup of your data from Zendesk HC you specified to the database. 

= I have some problems with the plugin's work. What Information should I provide to receive proper support? =

Please make sure that the problem hasn't been discussed yet on our forum (<a href="http://support.bestwebsoft.com" target="_blank">http://support.bestwebsoft.com</a>). If no, please provide the following data along with your problem's description:
1. the link to the page where the problem occurs
2. the name of the plugin and its version. If you are using a pro version - your order number.
3. the version of your WordPress installation
4. copy and paste into the message your system status report. Please read more here: <a href="https://docs.google.com/document/d/1Wi2X8RdRGXk9kMszQy1xItJrpN0ncXgioH935MaBKtc/edit" target="_blank">Instuction on System Status</a>

== Screenshots ==

1. Plugin main page.
2. Plugin settings page.

== Changelog ==

= V0.1 - 16.04.2015 =
Bugfix : The code refactoring was performed.

== Upgrade Notice ==

= V0.1 =
The code refactoring was performed.
