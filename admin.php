<?php
if (!is_admin()) {
    die();
}
?><div class="wrap">
<h2><?php _e('Better Random Redirect','better_random_redirect'); ?></h2>
<p><?php _e('Configure the URL you want to use below.', 'better_random_redirect'); ?></p>
<p><?php _e('Cache time represents how long to cache the list of valid posts (in seconds), and therefore affects how long it takes for new posts become eligible to be part of the randomiser. Leave this setting at the default unless you have a specific reason to change it.','better_random_redirect') ?></p>
<form method="post" action="options.php">
<?php
echo settings_fields( 'better_random_redirect' );
?>
<table class="form-table">
	<tr valign="top">
            <th scope="row"><label for="id_brr_default_slug"><?php _e('Main Randomiser URL','better_random_redirect'); ?>:</label></th>
	    <td><?php echo network_site_url(); ?>/<input type="text" id="id_brr_default_slug" name="brr_default_slug" value="<?php echo get_option('brr_default_slug'); ?>" size="12" />/</td>
	</tr>
	<tr valign="top">
            <th scope="row"><label for="id_brr_default_timeout"><?php _e('Cache Time','better_random_redirect'); ?>:</label></th>
	    <td><input type="number" id="id_brr_default_timeout" name="brr_default_timeout" value="<?php echo get_option('brr_default_timeout'); ?>" /> seconds</td>
	</tr>
    </table>
    <p><?php echo sprintf(__('Tip: for random results in e.g. category \'foo\', append cat= as follows: %s','better_random_redirect'), '<code>'.network_site_url().'/'.get_option('brr_default_slug').'/?cat=foo</code>' ); ?></p>
    <?php submit_button(); ?>
</form>
</div>