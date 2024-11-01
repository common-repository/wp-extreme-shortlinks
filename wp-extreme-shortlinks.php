<?php
/*
Plugin Name: WP Extreme Shortlinks
Plugin URI: http://www.callum-macdonald.com/code/wp-extreme-shortlinks/
Description: Looking for regular shortlinks? KEEP WALKING. Something a little different you say? You like the dark and dangerous do you? Well then, step right this way...
Version: 0.3
Author: Callum Macdonald
Author URI: http://www.callum-macdonald.com/
*/

// This code is released under the GPLv3 or later.

// @todo Add some kind of caching, the function is called many times for the same id

//define('SHORTLINK_USE_UPPERCASE', true);
//define('SHORTLINK_MAX_ID_LENGTH', 3);
//define('SHORTLINK_DOMAIN', false);
//define('SHORTLINK_EXTREME', true);
//define('SHORTLINK_SHOW_MENU', false);

add_action('init', 'wpes_init');

function wpes_init() {
	
	// Only take action if all our config vars are set in wp-config.php
	if (
		defined('SHORTLINK_USE_UPPERCASE') && defined('SHORTLINK_DOMAIN') && defined('SHORTLINK_EXTREME')
		&&
		( ( SHORTLINK_EXTREME && defined('SHORTLINK_MAX_ID_LENGTH') ) || ! SHORTLINK_EXTREME )
	) {
	    wpes_check_for_shortlink();
		add_filter('pre_get_shortlink', 'wpes_pre_get_shortlink', 10, 4);
		define('WPES_CONFIGURED', true);
	}

}

// Add the create pages options
add_action('admin_menu','wpes_admin_menu');

function wpes_admin_menu() {

	if (function_exists('add_submenu_page') && ( !defined('SHORTLINK_SHOW_MENU') || SHORTLINK_SHOW_MENU) )
		add_options_page(__('Extreme shortlinks', 'extreme_shortlinks'),__('Shortlinks', 'extreme_shortlinks'),'manage_options',__FILE__,'wpes_options_page');

}

function wpes_check_for_shortlink() {

	// Map our vars to the default wp query vars
	$ourvars = array('p' => 'p', 'c' => 'cat', 't' => 'tag_id', 'a' => 'author');

	// Default max id length is 3 chars
	$shortlink_max_id_length = 3;
	if (defined('SHORTLINK_MAX_ID_LENGTH'))
		$shortlink_max_id_length = SHORTLINK_MAX_ID_LENGTH;

	// Is one of our get vars set?
	foreach ($ourvars as $var_name => $wp_var_name) {
		if (isset($_GET[$var_name]))
            return wpes_do_shortlink($wp_var_name, $_GET[$var_name]);
	}

	// Get the request
	$request = wpes_get_request();

	// Are we using just /XX style for posts?
	if (SHORTLINK_EXTREME == true && strlen($request) <= $shortlink_max_id_length)
        return wpes_do_shortlink('p', $request, true);

}

function wpes_do_shortlink($wp_var_name, $val, $extreme = false) {

    $val = wpes_decode($val, $extreme);

    switch ($wp_var_name) {

        case 'p':

            $url = get_permalink($val);
            break;

        case 'cat':

            $url = get_term_link(intval($val), 'category');
            break;

        case 'tag_id':

            $url = get_term_link(intval($val), 'post_tag');
            break;

        case 'author':
            $url = get_author_posts_url($val);
            break;

    }

    // See if we got a url above, if we did, redirect and goodbye
    if (!empty($url) && is_string($url)) {
		do_action('pre_shortlink_redirect');
        wp_redirect($url, 301);
		do_action('post_shortlink_redirect');
        exit(); // Our work here is done...
    }

}

function wpes_get_request() {
	
	// Modified from WP::parse_request lines 153-194 as at revision 15410
	// http://core.trac.wordpress.org/browser/tags/3.0.1/wp-includes/classes.php#L153
	if ( isset($_SERVER['PATH_INFO']) )
		$pathinfo = $_SERVER['PATH_INFO'];
	else
		$pathinfo = '';
	$pathinfo_array = explode('?', $pathinfo);
	$pathinfo = str_replace("%", "%25", $pathinfo_array[0]);
	$req_uri = $_SERVER['REQUEST_URI'];
	$req_uri_array = explode('?', $req_uri);
	$req_uri = $req_uri_array[0];
	$self = $_SERVER['PHP_SELF'];
	$home_path = parse_url(home_url());
	if ( isset($home_path['path']) )
		$home_path = $home_path['path'];
	else
		$home_path = '';
	$home_path = trim($home_path, '/');

	// Trim path info from the end and the leading home path from the
	// front.  For path info requests, this leaves us with the requesting
	// filename, if any.  For 404 requests, this leaves us with the
	// requested permalink.
	$req_uri = str_replace($pathinfo, '', rawurldecode($req_uri));
	$req_uri = trim($req_uri, '/');
	$req_uri = preg_replace("|^$home_path|", '', $req_uri);
	$req_uri = trim($req_uri, '/');
	$pathinfo = trim($pathinfo, '/');
	$pathinfo = preg_replace("|^$home_path|", '', $pathinfo);
	$pathinfo = trim($pathinfo, '/');
	$self = trim($self, '/');
	$self = preg_replace("|^$home_path|", '', $self);
	$self = trim($self, '/');
	
	// Changes begin here, above is copied directly
	
	// The requested permalink is in $pathinfo for path info requests and
	//  $req_uri for other requests.
	if ( ! empty($pathinfo) ) {
		$request = $pathinfo;
	} else {
		$request = $req_uri;
	}
	
	return $request;
	
}

function wpes_pre_get_shortlink($shortlink, $id, $context, $allow_slugs) {

	// If $shortlink has already been set, honour it
	if ($shortlink != false)
		return $shortlink;

    $shortlink = wpes_generate_shortlink($id, $context, $allow_slugs);

    if (!empty($shortlink))
        return $shortlink;

	// If we haven't generated a shortlink, return false to let other plugins or WP handle it
	return false;
	
}

function wpes_generate_shortlink($id = false, $context = 'post', $allow_slugs = false) {

	// If this is the homepage and a page, return the SHORTLINK_DOMAIN
	if (is_front_page() && is_page())
		return(SHORTLINK_DOMAIN);

    $object_id = false;
    $object_type = false;

	// Contexts are blog, media, post, query
    // I think this is based on when the shortlink is being generated (head, post link, etc)
	global $wp_query;
	// Context == query means this will be the header shortlink
	if ( 'query' == $context ) {
        if ( is_single() || is_page() )
            $object_type = 'p';
        elseif(is_category())
            $object_type = 'cat';
        elseif (is_tag())
            $object_type = 'tag_id';
        elseif (is_author())
            $object_type = 'author';
        $object_id = $wp_query->get_queried_object_id();
	}
    elseif ( 'post' == $context ) {
		$post = get_post($id);
		$object_id = $post->ID;
        $object_type = 'p';
	}

    if (!empty($object_type) && !empty($object_id))
        return wpes_shortlink_url($object_type, $object_id);

}

function wpes_shortlink_url($object_type, $object_id) {

	// Map our vars to the default wp query vars
	$ourvars = array('p' => 'p', 'c' => 'cat', 't' => 'tag_id', 'a' => 'author');

    $key = array_flip($ourvars);

    $prepend = '?' . $key[$object_type] . '=';

    if ($prepend == '?p=' && SHORTLINK_EXTREME)
        $prepend = '';

    $path = $prepend . wpes_encode($object_id, empty($prepend));
    
    // If we have a custom shortlink domain, use it now
    if (defined('SHORTLINK_DOMAIN') && SHORTLINK_DOMAIN && is_string(SHORTLINK_DOMAIN))
		return rtrim(SHORTLINK_DOMAIN, '/') . '/' . ltrim($path, '/');

    // Default fallback, use home_url
    return home_url($path);
    
}

// Encode based on the selected encoding setting
function wpes_encode($numeric, $extreme = false) {
	
    return dec2any($numeric, false, wpes_usable_characters($extreme));

}

// Decode based on the chosen settings
function wpes_decode($encoded, $extreme = false) {

    return any2dec($encoded, false, wpes_usable_characters($extreme));
    
}

function wpes_usable_characters($extreme = false) {

	if (SHORTLINK_USE_UPPERCASE)
		$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	else
		$chars = '0123456789abcdefghijklmnopqrstuvwxyz';
	
    return $chars;
    
}

// dec2any and any2dec copied from http://php.net/base_convert#52450 with
// permission from Matvei Stefarov.
if (!function_exists('dec2any')) :
function dec2any( $num, $base=62, $index=false ) {
    if (! $base ) {
        $base = strlen( $index );
    } else if (! $index ) {
        $index = substr( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" ,0 ,$base );
    }
    $out = "";
    $num = intval($num);
    for ( $t = floor( log10( $num ) / log10( $base ) ); $t >= 0; $t-- ) {
        $a = floor( $num / pow( $base, $t ) );
        $out = $out . substr( $index, $a, 1 );
        $num = $num - ( $a * pow( $base, $t ) );
    }
    return $out;
}
endif;

if (!function_exists('any2dec')) :
function any2dec( $num, $base=62, $index=false ) {
    if (! $base ) {
        $base = strlen( $index );
    } else if (! $index ) {
        $index = substr( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 0, $base );
    }
    $out = 0;
    $len = strlen( $num ) - 1;
    for ( $t = 0; $t <= $len; $t++ ) {
        $out = $out + strpos( $index, substr( $num, $t, 1 ) ) * pow( $base, $len - $t );
    }
    return $out;
}
endif;

function wpes_options_page() {

	if (!function_exists('wpes_yesno')):
	function wpes_yesno($yes = true) { echo '<span style="color: ' . ($yes ? 'green' : 'red') . ';">' . ($yes ? 'yes' : 'no') . '</span>;'; }
	endif;

	?>
<div class="wrap">
<h2><?php _e('Exreme Shortlinks', 'extreme_shortlinks'); ?></h2>
<?php
if (defined('SHORTLINK_USE_UPPERCASE') || defined('SHORTLINK_DOMAIN') || defined('SHORTLINK_EXTREME') || defined('SHORTLINK_MAX_ID_LENGTH')) {
	?>
	<h3><?php _e('Validation', 'extreme_shortlinks'); ?></h3>
	<p>SHORTLINK_USE_UPPERCASE set in wp-config.php: <?php wpes_yesno(defined('SHORTLINK_USE_UPPERCASE'));  if (defined('SHORTLINK_USE_UPPERCASE')) { ?> valid: <?php wpes_yesno(is_bool(SHORTLINK_USE_UPPERCASE)); } ?></p>
	<p>SHORTLINK_DOMAIN set in wp-config.php: <?php wpes_yesno(defined('SHORTLINK_DOMAIN'));  if (defined('SHORTLINK_DOMAIN')) { ?> valid: <?php wpes_yesno(is_bool(SHORTLINK_DOMAIN) && SHORTLINK_DOMAIN === false || is_string(SHORTLINK_DOMAIN)); if (is_string(SHORTLINK_DOMAIN)) { ?> set to: &quot;<?php echo SHORTLINK_DOMAIN; ?>&quot<?php; } } ?></p>
	<p>SHORTLINK_EXTREME set in wp-config.php: <?php wpes_yesno(defined('SHORTLINK_EXTREME'));  if (defined('SHORTLINK_EXTREME')) { ?> valid: <?php wpes_yesno(is_bool(SHORTLINK_EXTREME)); } ?></p>
	<?php if (defined('SHORTLINK_EXTREME')) { ?><p>SHORTLINK_MAX_ID_LENGTH set in wp-config.php: <?php wpes_yesno(defined('SHORTLINK_MAX_ID_LENGTH'));  if (defined('SHORTLINK_MAX_ID_LENGTH')) { ?> valid: <?php wpes_yesno(is_int(SHORTLINK_MAX_ID_LENGTH)); } ?></p><?php } ?>
	<?php
}
else {
?>
<p>Set your options below, then copy the code into wp-config.php, just above the <code>/* That's all, stop editing! Happy blogging. */</code> line.</p>
<p><strong>Very important: Set these options only once and do not change them.</strong> If you change them, you will probably break your old shortlinks. See below for the full info.</p>
<textarea cols="60" rows="6">define('SHORTLINK_USE_UPPERCASE', true);
define('SHORTLINK_DOMAIN', false);
define('SHORTLINK_EXTREME', false);
define('SHORTLINK_MAX_ID_LENGTH', 3);
define('SHORTLINK_SHOW_MENU', true);</textarea><?php } // End of if some vars set ?>
<h3>Summary</h3>
<ul>
	<li>SHORTLINK_USE_UPPERCASE<br />Do you want to use both lowercase and UPPERCASE letters in your shortlinks? Set to true or false.</li>
	<li>SHORTLINK_DOMAIN<br /> If you have a custom short domain, point it at your WordPress install and enter it like <code>define('SHORTLINK_DOMAIN', 'http://ex.mp/');</code>. Set this as false if you do not have a different domain.</li>
	<li>SHORTLINK_EXTREME<br /> Do you want to enable extreme shortlinks for posts? This option makes post links 1 character shorter by living on the edge! Set to true or false.</li>
	<li>SHORTLINK_MAX_ID_LENGTH<br /> This defines how long your shortlinks can be. Set this as an integer (a number with no quotes), you can safely increase it later.</li>
	<li>SHORTLINK_SHOW_MENU<br />Show this admin menu and page. Set it to true initially, then this page will validate your options. Once they're correct, set it to false and hide this page.</li>
</ul>
<h3>Full explanation</h3>
<p>This plugin uses a simple approach to shortlinks. The links are are /?a=X for author pages, /?c=X for category pages, /?p=X for post and pages, and /?t=X for tags. X is the object ID encoded in base36 or base 62 depending on the SHORTLINK_USE_UPPERCASE setting.</p>
<p>If you enable SHORTLINK_EXTREME, then post and page shortlinks will be shortened to simply /X.</p>
<p>SHORTLINK_USE_UPPERCASE controls whether we use only lower case or both lower and UPPER case letters to create shortlinks. Shortlinks using only lower case letters are easier to tell people verbally. That makes them better when printed, shared by phone, etc. When clicking links, it makes no difference. Using upper case letters makes the links shorter.</p>
<p>Here's some numbers to put this in perspective:</p>
<ul><li>Lowercase, 3 characters = 46'656 posts</li>
<li>Uppercase, 3 characters = 238'328</li>
<li>Lowercase, 4 characters = 1'679'616</li>
<li>Uppercase, 4 characters = 14'776'336</li></ul>
<p>Once you've installed this plugin and started sharing your shortlinks, it's important you keep to the same format. You can use another plugin so long as it supports the same format. This is important. Think long and hard before you decide which shortlink format to use, you'll break all your old shortlinks if you change later.</p>
<p>If your domain name is a long one, you might want to buy a shorter domain just for shortlinks. If you do that, point it at the same WordPress site in your web server configuration and then set the new domain in SHORTLINK_DOMAIN. Your shortlinks will now be set with the short domain. The shortlinks will actually work on both domains, so you can safely change domains without breaking any old shortlinks.</p>
<p>I'm using this plugin on the site <a href="http://alts.to/" title="Find alternatives..." target="_blank">alts.to</a>. So long as that site uses WordPress, I'll have to maintain this plugin for my own needs. So, hopefully, this plugin will be around for a while. If I stop maintaining it for any reason, I've written the code to be as forwards compatible as possible, so hopefully it will "just work" for many versions of WordPress to come. If it does break for any reason, the code is all released under the GPL and available through <a href="http://wordpress.org/extend/plugins/wp-extreme-shortlinks/" title="WP Extreme Shortlinks on WordPress.org" target="_blank">wordpress.org</a>, so another developer could fix problems in the future.</p>
<p>Happy shortlinking.</p>
</div>
	<?php

}

/* Quick comment to see if that bumps the damned last updated field. */
