<?php

/*
Plugin Name: TOC For Wordpress
Plugin URI: http://geeklu.com/2010/08/toc-for-wordpress
Description: Insert a simple and clean Table of Contents into your posts.
Version: 1.0
Author: Luke
Author URI: http://geeklu.com
*/

//Default TOC Style
$default_style=<<< STYLE
.toc {
		border: 1px dashed gray;
		padding: 10px;
		float: right;
		font-size: 0.95em;
		margin: 5px;
}
.toc h2 {
		text-align: center;
		font-size: 1em;
		font-weight: bold;
}
.toc ul, .toc ol {
		list-style: none;
		padding: 0;
}
.toc ul ul, .toc ol ol {
		margin: 0 0 0 2em;
}
.toc-end {
		/*clear: both;*/
}
STYLE;


function active_simple_toc(){
	    global $default_style;
        add_option('toc_depth','4','the depth of the toc list');
		add_option('toc_list_type','ul','ul or ol');
		add_option('toc_style',$default_style,'the style of the toc');
		add_option('toc_tilte','Contents','the title of toc');
}

function deactive_simple_toc(){
    delete_option('toc_depth');
	delete_option('toc_list_type');
	delete_option('toc_style');
	delete_option('toc_tilte');
}


function toc_init() {
	$domain = "toc";
	$plugin_dir = str_replace( basename(__FILE__) , "" , plugin_basename(__FILE__) );
	load_plugin_textdomain( $domain, "wp-content/plugins/" . $plugin_dir , $plugin_dir );
}

function toc_options_page() {
	if( isset($_POST["Submit"]) ):
	
		check_admin_referer('toc-update-options');
		
		$depth = (int)$_POST["depth"];
		update_option( "toc_depth", $depth );
		
		$list_type = "ul";
		if (in_array($_POST["list_type"], array("ul", "ol")))
			$list_type = $_POST["list_type"];
		
		update_option( "toc_list_type", $list_type );
		
		$style = $_POST["style"];
		
		update_option( "toc_style", $style );
		
		update_option( "toc_title", $_POST["title"] );
		
		?>
		<p><div id="message" class="updated">
			<p><strong>
			<?php _e("Your settings have been updated.", "toc"); ?>
			</strong></p>
		</div></p>
		<?php
	
	endif;
	
    global $default_style;
	
	$depth     = (int)get_option("toc_depth");
	$depth     = $depth ? $depth : 4;
	$list_type = get_option("toc_list_type");
	$list_type = $list_type ? $list_type : "ul";
	$style     = get_option("toc_style");
	$style = $style?$style:$default_style;
	$title     = get_option("toc_title");
	$title     = $title ? $title : "Contents";
	?>
	<div class="wrap">
		
		<h2><?php _e("TOC: Table Of Contents", "toc"); ?></h2>
		
		<h3><?php _e("Options", "toc"); ?></h3>
		
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo plugin_basename(__FILE__) ?>">
			<?php wp_nonce_field('toc-update-options'); ?>
			<table class="form-table" >
				<tbody>
					<tr valign="top">
						<th scope="row"> 
							<label for="depth"><?php _e("Depth", "toc"); ?></label>
						</th>
						<td>
							<input type="text" size="7" name="depth" value="<?php echo esc_attr($depth); ?>" class="small-text " tabindex="1" />			
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"> 
							<label for="depth"><?php _e("Title", "toc"); ?></label>
						</th>
						<td>
							<input type="text" name="title" value="<?php echo esc_attr($title); ?>" class="text " tabindex="1" />			
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"> 
							<label for="list_type"><?php _e("List Type", "toc"); ?></label>
						</th>
						<td>
							<select name="list_type">
								<option value="ul"<?php if($list_type == "ul") { ?> selected="selected"<?php } ?>><?php _e("ul (Unordered)", "toc"); ?></option>
								<option value="ol"<?php if($list_type == "ol") { ?> selected="selected"<?php } ?>><?php _e("ol (Ordered)", "toc"); ?></option>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"> 
							<label for="style"><?php _e("Style", "toc"); ?></label>
						</th>
						<td>
							<textarea name="style" style="width:80%;height:450px"><?php echo esc_attr($style); ?>
                            </textarea>
						</td>
					</tr>
				</tbody>				
			</table>
			
			<p class="submit">
				<input type="submit" name="Submit" value="<?php _e('Save Changes',"toc"); ?>" />
			</p>
			
		</form>
		
		<h3><?php _e("Usage", "toc"); ?></h3>
		
		<p><?php _e("To insert a Table of Contents, simply place the following code into a post:", "toc"); ?></p>
		
		<p><code><?php _e("[toc]", "toc"); ?></code></p>
		
		<p><?php _e("You can override the various setting on a per-post basis using parameters:", "toc"); ?></p>
		
		<p><code><?php _e("[toc depth=\"2\" listtype=\"ul\" title=\"Contents\"]", "toc"); ?></code></p>
			
	</div>
	<?php
}

function toc_admin_menu() {
	add_options_page( __("TOC", "toc"), __("TOC", "toc"), 8, __FILE__, "toc_options_page");
}

// open a nested list
function toc_open_level($new, $cur, $first, $type) {
	$levels = $new - $cur;
	$out = "";
	for($i = $cur; $i < $new; $i++) {
	
		$level = $i - $first + 2;
		if(($level % 2) == 0)
			$out .= "<{$type} class='toc-even level-{$level}'>\n";
		else
			$out .= "<{$type} class='toc-odd level-{$level}'>\n";
	}
	return $out;
}

// close the list
function toc_close_level( $new, $cur, $first, $type ) {
	$out = "";
	for($i = $cur; $i > $new; $i--)
		$out .= "</{$type}>\n";
	return $out;
}

$toc_used_names = array();

function toc_get_unique_name($heading) {
	global $toc_used_names;
	
	$n = str_replace(" ", "_", strip_tags($heading));
	$n = preg_replace("#[^A-Za-z0-9\-_\:\.]#", "", $n);
	$n = preg_replace("#^[^A-Za-z]*?([A-Za-z])#", "$1", $n);
	
	
	if (isset($toc_used_names[$n])) {
		$toc_used_names[$n]++;
		$n .= "_" . $toc_used_names[$n];	
		$toc_used_names[$n] = 0;	
	} else {
		$toc_used_names[$n] = 0;
	}
	
	return $n;
}

function toc_unique_names_reset() {
	global $toc_used_names;
	$toc_used_names = array();
	return true;
}

function toc_shortcode_toc($attribs) {
	global $post;
	
	toc_unique_names_reset();
	
	// replace with default values
	$attribs = shortcode_atts(
		array(
			"depth" => get_option("toc_depth"),
			"listtype" => get_option("toc_list_type"),
			"title" => get_option("toc_title")
		), 
		$attribs);
		
	extract($attribs);
	
	$depth = $depth ? $depth : 4;	
	$list_type = $listtype ? $listtype : "ol";
	$title = $title ? $title : "Contents";

	// get the post
	// don't consider stuff in <pre>s
	$content = preg_replace("#<pre.*?>(.|\n|\r)*?<\/pre>#i", "", $post->post_content);
	
	$lowest_heading = 1;
	
	// calculate the lowest value heading (ie <hN> where N is a number)
	// in the post
	for($i = 1; $i <= 6; $i++)
		if( preg_match("#<h" . $i . "#i", $content) ) {
			$lowest_heading = $i;
			break;
		}
		
	// maximum
	$max_heading = $lowest_heading + $depth - 1;
	
	// find page separation points
	$next_pages = array();
	preg_match_all("#<\!--nextpage-->#i", $content, $next_pages, PREG_OFFSET_CAPTURE);
	$next_pages = $next_pages[0];
	
	// get all headings in post
	$headings = array();
	preg_match_all("#<h([1-6])>(.*?)</h[1-6]>#i", $content, $headings, PREG_OFFSET_CAPTURE);
	
	$cur_level = $lowest_heading;
	
	$out = "<div class='toc toc'>\n";
	
	if ($title) 
		$out .= "<h2>" . $title . "</h2>\n";
		
	$out .= toc_open_level($lowest_heading, $lowest_heading-1, $lowest_heading, $list_type);	
	
	$first = true;
	
	$tabs = 1;
	
	// headings...
	foreach($headings[2] as $i => $heading) {
		$level = $headings[1][$i][0]; // <hN>
		
		if($level > $max_heading) // heading too deep
			continue;
		
		if($level > $cur_level) { // this needs to be nested
			$out .= str_repeat("\t", $tabs+1) . toc_open_level( $level, $cur_level, $lowest_heading, $list_type );
			$first = true;
			$tabs += 2;
		}
			
		if(!$first)
			$out .= str_repeat("\t", $tabs) . "</li>\n";
		$first = false;
			
		if($level < $cur_level) { // jump back up from nest
			$out .= str_repeat("\t", $tabs-1) . toc_close_level( $level, $cur_level, $lowest_heading, $list_type );
			$tabs -= 2;
		}
			
		$name = toc_get_unique_name($heading[0]);
		
		$page_num = 1;
		$pos = $heading[1];
		
		// find the current page
		foreach($next_pages as $p) {
			if($p[1] < $pos)
				$page_num++;
		}
		
		// output the Contents item with link to the heading. Uses
		// unique ID based on the $prefix variable.
		if($page_num != 1||is_home())
			$out .= str_repeat("\t", $tabs) . "<li>\n" . str_repeat("\t", $tabs+1) . "<a href=\"?p=" . $post->ID . "&page=" . $page_num . "#" . esc_attr($name). "\">" . $heading[0] . "</a>\n";
		else
			$out .= str_repeat("\t", $tabs) . "<li>\n" . str_repeat("\t", $tabs+1) . "<a href=\"#" . esc_attr($name). "\">" . $heading[0] . "</a>\n";
			
		$cur_level = $level; // set the current level we are at
	}
	
	if(!$first)
		$out .= str_repeat("\t", $tabs) . "</li>\n";
	
	// close up the list
	$out .= toc_close_level( 0, $cur_level, $lowest_heading, $list_type );
	
	$out .= "</div>\n";
	
	$out .= "<div class='toc-end'>&nbsp;</div>";
	
	// return value to repalce the Shortcode
	return $out;
}

function toc_heading_anchor($match) {
	$name = toc_get_unique_name($match[2]);
	return '<span id="' . esc_attr($name) . '">' . $match[0] . '</span>';
}

function toc_the_content($content) {
	toc_unique_names_reset();
	$out = preg_replace_callback("#<h([1-6])>(.*?)</h[1-6]>#i", "toc_heading_anchor", $content);
	return $out;
}

function toc_wp_head() {
    global $default_style;
	$style = get_option("toc_style");
	$style = $style ? $style:$default_style;
	echo("<style type=\"text/css\">".$style."</style>");
}

add_action( "init", "toc_init" );
add_action( "admin_menu", "toc_admin_menu" );
add_shortcode( "toc", "toc_shortcode_toc" );
add_action( "the_content", "toc_the_content" );
add_action( "wp_head", "toc_wp_head" );
register_activation_hook(__FILE__,'active_simple_toc');
register_deactivation_hook(__FILE__,'deactive_simple_toc');

?>