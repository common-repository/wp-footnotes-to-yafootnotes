<?php

/*
Plugin Name: WP-Footnotes to YAFootnotes
Version: 1.0
Description: Automatically changes footnote syntax from WP-Footnotes to YAFootnotes Plugin.
Author: Maris Svirksts
Author URI: http://www.moskjis.com/
Plugin URI: http://www.moskjis.com/wordpress/wordpress-plugins/wp-footnotes-to-yafootnotes
*/

/*  
TODO: alter it so it reads variables from WP-Footnotes plugin if it's working, otherwise use default variables, update readme to inform about that.

== Copyright ==

Copyright 2009  Maris Svirksts  (email : maris.svirksts@gmail.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/* Version check */
global $wp_version;

$exit_msg='This plugin needs Wordpress, version 2.7 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update!</a>';

if (version_compare($wp_version,"2.7","<"))
{
	exit ($exit_msg);
}

//constants
define('MS_CHANGE_NOTES_OPEN', " ((");
define('MS_CHANGE_NOTES_CLOSE', "))");
define('MS_NEW_CHANGE_NOTES_OPEN', "[[");
define('MS_NEW_CHANGE_NOTES_CLOSE', "]]");
define('MS_NEW_CHANGE_COUNT_OPEN', "{{");
define('MS_NEW_CHANGE_COUNT_CLOSE', "}}");
define('MS_CHANGE_NOTES_VERSION', '1.0');

//To change footnotes on the fly
function MsChangeFootNotes($content)
{
	$count = 1;
	$first = strpos($content,MS_CHANGE_NOTES_OPEN);
	$last = strpos($content,MS_CHANGE_NOTES_CLOSE);

	while(($first !== FALSE) AND ($last !== FALSE) AND ($first < $last)) {
		$data = substr($content,$first,$last-$first+strlen(MS_CHANGE_NOTES_CLOSE));
		$countString = MS_NEW_CHANGE_COUNT_OPEN . $count . MS_NEW_CHANGE_COUNT_CLOSE;
		$content = substr_replace($content, $countString, $first,strlen($data));//so we replace only one string
		$replaceOld = array(MS_CHANGE_NOTES_OPEN,MS_CHANGE_NOTES_CLOSE);
		$replaceNew = MS_NEW_CHANGE_NOTES_OPEN . $count . MS_NEW_CHANGE_NOTES_CLOSE;
		$data = "<p>" . str_replace($replaceOld,$replaceNew,$data) . "</p>";
		$content .= $data;
		$count++;
		$first = strpos($content,MS_CHANGE_NOTES_OPEN);
		$last = strpos($content,MS_CHANGE_NOTES_CLOSE);
	}
	return $content;
}

//change database field
function ms_footnotes_db($id, $content) {
	global $wpdb;

	$sqlQuery = $wpdb->prepare("UPDATE $wpdb->posts SET post_content = %s WHERE ID = %d;", $content, $id);
	$wpdb->query( $sqlQuery ); //alter posts in database
	return 1;
}

//To change footnotes permanently
function ms_change_footnotes() {
	$posts = get_posts('numberposts=-1'); //get all posts
	foreach($posts as $post) {
		$flag_post_changed = FALSE;//reset
		$first = strpos($post->post_content,MS_CHANGE_NOTES_OPEN);
		$last = strpos($post->post_content,MS_CHANGE_NOTES_CLOSE);
		if(($first !== FALSE) AND ($last !== FALSE) AND ($first < $last)) {
			$flag_post_changed = TRUE; //check if there are syntax from WP-Footnotes plugin
		}
		if($flag_post_changed !== FALSE) { //If post has old footnotes syntax in it
			$changedPost = MsChangeFootNotes($post->post_content); //change post
			ms_footnotes_db($post->ID, $changedPost); //change post in database
		}
	}
	return 1;
}

//register our settings
function register_ms_footnotes_settings() {
	register_setting( 'ms-footnotes-settings-group', 'change_syntax' );
}

//Create administration page content
function ms_footnotes_options() {
?>
<div class="wrap">
<h2><?php _e('Permanent changes: WP-Footnotes to YAFootnotes.', 'ms_change_footnotes_trans') ?></h2>

<form method="post">
	<?php settings_fields( 'ms-footnotes-settings-group' ); ?>
	<p><?php _e('This action will permanantly change footnote syntax in your posts.', 'ms_change_footnotes_trans') ?> <strong><?php _e('Please, back up your database first.', 'ms_change_footnotes_trans') ?></strong></p>
	<p><?php _e("Take notice that you can check your site now (please, disable WP-Footnotes plugin first), see if footnotes syntax is correct. If it isn't, you can still disable this plugin without any permanent changes to your posts.", 'ms_change_footnotes_trans') ?></p>
	<p><?php _e("After changes have been made you can disable this plugin as it doesn't need to change anything anymore.",'ms_change_footnotes_trans') ?></p>
	<input type="hidden" name="change_syntax" value="change" />
	<input type="submit" class="submit-button" value="<?php _e('Change Syntax','ms_change_footnotes_trans') ?>" />
</form>
</div>
<?php }

//For plugin translation
function ms_change_footnotes_init ()
{
	load_plugin_textdomain ('ms_change_footnotes_trans');
}

//To create administration menu
function ms_footnotes_menu() {
	add_options_page('Footnotes Migration', 'Change Footnotes', 'administrator', 'ms-footnotes-plugin-settings', 'ms_footnotes_options');
	add_action( 'admin_init', 'register_ms_footnotes_settings' );
}

//If choose to change posts permanently
if ($_REQUEST["change_syntax"] == "change") {
	ms_change_footnotes();
}

// create plugin settings menu
add_action('admin_menu', 'ms_footnotes_menu');

//Change syntax on the fly
add_filter('the_content', 'MsChangeFootNotes');

//For plugin translation
add_action ('init', 'ms_change_footnotes_init');
?>