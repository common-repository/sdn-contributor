<?php
/*
Plugin Name: SDN Contributor
Plugin URI: http://wiki.zsapping.com/pub:projects:sdn_contributor:index
Description: Adds a sidebar widget to display information about you as a SAP Developer Network contributor.
Author: zSAPping (Oliver Kohl)
Version: 1.2.1
Author URI: http://blog.zsapping.com
Wordpress: Version 2.5+
*/

include 'xx_xml.php';

define("SDN_BC_URL", "https://www.sdn.sap.com/irj/servlet/prt/portal/prtroot/com.sap.sdn.businesscard.SDNBusinessCard?u=[bcid]");
define("SDN_POINTS_URL", "https://www.sdn.sap.com/irj/servlet/prt/portal/prtroot/pcd!3aportal_content!2fcom.sap.sdn.folder.sdn!2fcom.sap.sdn.folder.application!2fcom.sap.sdn.folder.iviews!2fcom.sap.sdn.folder.crp!2fcom.sap.sdn.app.crp.mypoints?userid=[bcid]");
define("SDN_BLOG_RSS", "http://weblogs.sdn.sap.com/pub/q/weblog_rss_author?x-author=[bloggerid]&x-ver=1.0&x-mimetype=application%2Frdf%2Bxml");
define("SDN_BLOG_HOME", "https://www.sdn.sap.com/irj/sdn/weblogs?blog=/pub/u/[bloggerid]");

// This gets called at the plugins_loaded action
function widget_sdn_init() {
    
    // Check for the required API functions
    if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
        return;

    // This saves options and prints the widget's config form.
    function widget_sdn_control() {
        $options = $newoptions = get_option('widget_sdn');
        if ( $_POST['sdn-submit'] ) {
            $newoptions['title'] = $_POST['sdn-title'];
            $newoptions['bcid'] = $_POST['sdn-bc-id'];
            $newoptions['blogid'] = $_POST['sdn-blog-id'];
            $newoptions['blognumberitems'] = $_POST['sdn-blog-numberitems'];
            $newoptions['blogshowdate'] = isset($_POST['sdn-blog-show-date']);
            $newoptions['blogshowdesc'] = isset($_POST['sdn-blog-show-description']);
            $newoptions['opennewwindow'] = isset($_POST['sdn-open-new-window']);
        }
        if ( $options != $newoptions ) {
            $options = $newoptions;
            update_option('widget_sdn', $options);
        }
        $blogshowdate = $options['blogshowdate'] ? 'checked="checked"' : '';
        $blogshowdesc = $options['blogshowdesc'] ? 'checked="checked"' : '';
        $opennewwindow = $options['opennewwindow'] ? 'checked="checked"' : '';
    ?>
                <div style="text-align:right">
                <label for="sdn-title" style="line-height:35px;display:block;"><?php _e('Widget Title:', 'widgets'); ?> <input type="text" id="sdn-title" name="sdn-title" value="<?php echo wp_specialchars($options['title'], true); ?>" /></label>
                <label for="sdn-bc-id" style="line-height:35px;display:block;"><?php _e('Your SDN BC Id:', 'widgets'); ?> <input type="text" id="sdn-bc-id" name="sdn-bc-id" value="<?php echo wp_specialchars($options['bcid'], true); ?>" /></label>
                <label for="sdn-blog-id" style="line-height:35px;display:block;"><?php _e('Your SDN Blogger Id:', 'widgets'); ?> <input type="text" id="sdn-blog-id" name="sdn-blog-id" value="<?php echo wp_specialchars($options['blogid'], true); ?>" /></label>
                <label for="sdn-blog-numberitems" style="line-height:35px;display:block;">Number of blog posts to show: 
                        <select id="sdn-blog-numberitems" name="sdn-blog-numberitems">
                                 <option value="3" <?php selected('3',$options['blognumberitems']); ?>>3</option>
                                 <option value="5" <?php selected('5',$options['blognumberitems']); ?>>5</option>
                                 <option value="10" <?php selected('10',$options['blognumberitems']); ?>>10</option>
                                 <option value="20" <?php selected('20',$options['blognumberitems']); ?>>20</option>
                        </select>
                </label>
                <label for="sdn-blog-show-date">Show blog post date? <input class="checkbox" type="checkbox" <?php echo $blogshowdate; ?> id="sdn-blog-show-date" name="sdn-blog-show-date" /></label><br/>
                <label for="sdn-blog-show-description">Show blog post description? <input class="checkbox" type="checkbox" <?php echo $blogshowdesc; ?> id="sdn-blog-show-description" name="sdn-blog-show-description" /></label><br/>
                <label for="sdn-open-new-window">Links open new window? <input class="checkbox" type="checkbox" <?php echo $opennewwindow; ?> id="sdn-open-new-window" name="sdn-open-new-window" /></label>
                <input type="hidden" name="sdn-submit" id="sdn-submit" value="1" />
                </div>
    <?php
    }

    // This prints the widget
    function widget_sdn($args) {
        extract($args);
        $options = (array) get_option('widget_sdn');

        // Open links in new windows if requested
        $linktarget = '';
        if (!$options['$opennewwindow']) { 
            $linktarget = ' target="_blank"';
        }           

?>
    <?php echo $before_widget; ?>
    <?php echo $before_title . "{$options['title']}" . $after_title; ?>
    <div id="sdn-box" style="margin:0;padding:0;border:none;">
<?php

        if ($options['bcid'] <> "") {

            // Build URL and fetch SDN Contribution Points page for parsing.
            $sdn_points = str_replace("[bcid]", $options['bcid'], SDN_POINTS_URL);
        
            // Using curl via shell_exec because of ISP limitations.
            $buffer = strip_tags(getUrlContent($sdn_points));
            
            // Substring username between first apperance of "User:" and "Points:".
            $bug_offset = 0;
            if (strpos($buffer, 'User::') !== false) {
                $bug_offset ++;
            }
            $start_index = strpos($buffer, 'User:') + 5 + $bug_offset;
            $end_index = strpos($buffer, 'Total Annual Points:');
            $username = substr($buffer, $start_index, $end_index - $start_index);
        
            // Substring username between first apperance of "Points:" and second "Points".
            $start_index = strpos($buffer, 'Total Lifetime Points:') + 22 + $bug_offset;
            $end_index = strpos($buffer, 'Points per categories', $start_index);
            $total_points = substr($buffer, $start_index, $end_index - $start_index);
            $sdn_bc_url = str_replace("[bcid]", $options['bcid'], SDN_BC_URL);

?>
        <ul style="padding-bottom: 20px">
            <li class="no_bullet">Name: <a href="<?php echo $sdn_bc_url; ?>"<?php echo $linktarget; ?>><?php echo $username; ?></a></li>
            <li class="no_bullet">Total Points: <a href="<?php echo $sdn_points; ?>"<?php echo $linktarget; ?>><?php echo $total_points; ?></a></li>
        </ul>
<?php

        }

        // Print latest SDN blog posts, if SDN blogger id available.
        if ($options['blogid'] <> "") {
            $sdn_blog_rss = str_replace("[bloggerid]", $options['blogid'], SDN_BLOG_RSS);
            $sdn_blog_home = str_replace("[bloggerid]", $options['blogid'], SDN_BLOG_HOME);

            // Create blog home and rss url.
            $sdn_blog_rss = str_replace("[bloggerid]", $options['blogid'], SDN_BLOG_RSS);
            $sdn_blog_home = str_replace("[bloggerid]", $options['blogid'], SDN_BLOG_HOME);
            
            // Read feed content
            $content = getUrlContent($sdn_blog_rss);
            $feed = new xx_xml($content,'contents');
            
            // Convert feed content into arrays.
            $hrefs = $feed->data ['rdf:RDF|item|link']['data'];
            $titles = $feed->data ['rdf:RDF|item|title']['data'];
            $pubdates = $feed->data ['rdf:RDF|item|dc:date']['data'];
            $descriptions = $feed->data ['rdf:RDF|item|description']['data'];
            $feed_home = $feed->data ['rdf:RDF|channel|link']['data']['0'];
            
?>
        <?php echo $before_title . "Blog Posts on SDN" . $after_title; ?>
        <ul>
<?php
        
            for ( $i = 0; $i < $options['blognumberitems']; $i++) {
        
                // Trancate post title if requested
                if ($options['blogtrunctitle'] && (strlen($title)>30)) {
                    $title = substr($title, 0, 30) . " ... ";
                }
                
?>
        <li><a href="<?php echo $hrefs[$i]; ?>"  title="<?php echo htmlentities($titles[$i], ENT_QUOTES); ?>"<?php echo $linktarget; ?>><?php echo htmlentities($titles[$i], ENT_QUOTES); ?></a>
<?php

                if ($options['blogshowdate']) { 

?><small> on <?php echo date('D, j M', strtotime($pubdates[$i])); ?></small><?php

                }

                if ($options['blogshowdesc'] && $descriptions[$i] <> "") {

?>
            <br /><?php echo htmlentities($descriptions[$i], ENT_QUOTES); ?>
<?php

                }
?></li><?php
            }
?>
        <li class="no_bullet"><a href="<?php echo $feed_home; ?>"<?php echo $linktarget; ?>>More...</a></li>
        </ul>
<?php

        }
        
?>
        </div>
        <?php echo $after_widget; ?>
<?php

    }

    // Reads and returns the content of a site for a given URL.
    function getUrlContent($feedurl, $removeDT=false) {
        
        # Init Curl
        $ch = curl_init();
        
        # Now get XML feed
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $feedurl);
        curl_setopt($ch, CURLOPT_POST, FALSE);
    
        //Allow weak SSL connections
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt'); 

        // Go for it!!!
        $result = curl_exec($ch);
        
        // Workaround for SDN specific bug in feed.
        if ($removeDT)
            $result = removeDocType($result);
        
        // Look at the returned header
        $resultArray = curl_getinfo($ch);
        
        if ($resultArray['http_code'] != "200") {
            echo "could not open XML input: ".$event;
        }
        
        # close curl
        curl_close($ch);
    
        return $result;
    }
    
    // Removes the DOCTYPE tag from a given content string.
    function removeDocType($content) {
        $dtpos = strpos($content, "<!DOCTYPE");
        $xmlsspos = strpos($content, "]>") + 3;
        
        if ($dtpos !== FALSE) {
            $result = substr_replace($content, " ", $dtpos ,$xmlsspos - $dtpos);
            return $result;
        }
        else {
            return $content;
        }
    } 
    
    // Tell Dynamic Sidebar about our new widget and its control
    register_sidebar_widget('SDN Contributor', 'widget_sdn');
    register_widget_control('SDN Contributor', 'widget_sdn_control', 300, 220);
}

// Delay plugin execution to ensure Dynamic Sidebar has a chance to load first
add_action('widgets_init', 'widget_sdn_init');

?>
