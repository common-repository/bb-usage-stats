<?php
/*
Plugin Name: bb: Usage Stats 1.0.1
Plugin URI: http://devcorner.georgievi.net/pages/wordpress/wp-plugins/bbusage-stats
Description: Track post, page, category and homepage views. Build usage statistics, display usage graphs, top pages, posts, categories. For additional information and updates visit the <a href="http://devcorner.georgievi.net/pages/wordpress/wp-plugins/bbusage-stats" target="_blank">plugin page</a>.
Author: Ivan Georgiev
Version: 1.0.1
Author URI: http://devcorner.georgievi.net/
*/

if (!class_exists('bbUsageStats')) {

define('BB_USAGE_STATS_DIR', dirname(__FILE__).'/');       // Absolute path to plugin directory
define('BB_USAGE_STATS', basename(BB_USAGE_STATS_DIR));    // Plugin directory
define('BB_USAGE_STATS_URL', plugins_url(BB_USAGE_STATS)); // URL to plugin directory

// Trackable item types
define('BB_USAGE_STATS_HOME', 'HOME');
define('BB_USAGE_STATS_CATEGORY', 'CATEGORY');
define('BB_USAGE_STATS_PAGE', 'PAGE');
define('BB_USAGE_STATS_POST', 'POST');


class bbUsageStatsMapper {
    private static $inst;
	public $tblPrefix = 'bbusagestats_';

    private function __construct() {
		global $wpdb;
		$this->tblPrefix = $wpdb->prefix.$this->tblPrefix;
		$this->tblTrack = $this->tblPrefix.'track';
	}

	public static function Instance() {
		if (! self::$inst instanceof self) {
			self::$inst = new self();
		}
		return self::$inst;
	}
	
	public function addTrack($id, $type) {
        global $wpdb;

        if (is_null($id)) $id = 0;
        if ($id != 0) {
            $wpdb->query("INSERT INTO $this->tblTrack (track_date, object_type, object_id, counter)
                                 VALUES (CURDATE(), '$type', $id, 1)
                              ON DUPLICATE KEY UPDATE counter = counter + 1");
            if (!empty($wpdb->last_error)) return;
        }
        $wpdb->query("INSERT INTO $this->tblTrack (track_date, object_type, object_id, counter)
                             VALUES (CURDATE(), '$type', 0, 1)
                          ON DUPLICATE KEY UPDATE counter = counter + 1");
	}
	
	public function setupDB() {
        global $wpdb;
 
        $charset_collate = '';
        if($wpdb->supports_collation()) {
            if(!empty($wpdb->charset)) {
                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            }
            if(!empty($wpdb->collate)) {
                $charset_collate .= " COLLATE $wpdb->collate";
            }
        }
        $create_table = array();
        
        $create_table[$this->tblTrack] = "CREATE TABLE IF NOT EXISTS `$this->tblTrack` (
                                    `track_id` int(11) NOT NULL AUTO_INCREMENT,
                                    `object_type` enum('AGGREGATE','HOME','CATEGORY','PAGE','POST') NOT NULL,
                                    `object_id` int(11) NOT NULL,
                                    `track_date` date NOT NULL,
                                    `counter` int(11) NOT NULL DEFAULT '0',
                                    PRIMARY KEY (`track_id`),
                                    UNIQUE KEY `track_natural_key` (`track_date`,`object_type`,`object_id`)
                                ) $charset_collate;";
								
        foreach ($create_table as $tab => $sql)
            maybe_create_table($tab, $sql);
	}
}




class bbUsageStats {
    private static $inst;
	
	public static function Instance() {
		if (! self::$inst instanceof self) {
			self::$inst = new self();
		}
		return self::$inst;
	}
       
    function trackCurrent() {
        $objInfo = $this->getCurrent();
        if ($objInfo) {
			bbUsageStatsMapper::Instance()->addTrack($objInfo->id, $objInfo->type);
        }
    }
    
    /**
     * Find the current object id and type.
     * @return object Object with properties [id] and [type] or NULL if unknown.
     */
    function getCurrent() {
        if (is_home()) {
            $id = NULL;
            $type = BB_USAGE_STATS_HOME;
        } elseif (is_page()) {
            $type = BB_USAGE_STATS_PAGE;
            $id = get_the_ID();
        } elseif (is_category()) {
            $type = BB_USAGE_STATS_CATEGORY;
            $cat_obj = $GLOBALS['wp_query']->get_queried_object();
            $id = $cat_obj->term_id;
        } elseif (is_single()) {
            $type = BB_USAGE_STATS_POST;
            $id = get_the_ID();
        } else {
            return NULL;
        }
        return (object) array('id' => $id, 'type' => $type);
    }
    
    function activatePlugin() {
        if(@is_file(ABSPATH.'/wp-admin/upgrade-functions.php')) {
            include_once(ABSPATH.'/wp-admin/upgrade-functions.php');
        } elseif(@is_file(ABSPATH.'/wp-admin/includes/upgrade.php')) {
            include_once(ABSPATH.'/wp-admin/includes/upgrade.php');
        } else {
            die('We have problem finding your \'/wp-admin/upgrade-functions.php\' and \'/wp-admin/includes/upgrade.php\'');
        }
		bbUsageStatsMapper::Instance()->setupDB();
    }
    
    function getItemLink($type, $id) {
        switch ($type) {
            case BB_USAGE_STATS_PAGE:
            case BB_USAGE_STATS_POST:
                $post = get_post($id);
                $url = esc_attr(get_permalink($post));
                $title = esc_html($post->post_title);
                $link = "<a target=\"_blank\" href=\"$url\">$title</a>";
                break;
            
            case BB_USAGE_STATS_CATEGORY:
                $cat = get_category($id);
                $url = esc_attr(get_category_link($id));
                $title = $cat->name;
                $link = "<a target=\"_blank\" href=\"$url\">$title</a>";
                break;
            default:
                $link = 'Unknown type: '.$type;
        }
        return $link;
    }
	
	function getMonthDays($month, $year) {
		static $MONTH_DAYS = array(0,31,28,31,30,31,30,31,31,30,31,30,31);
		if ($month == 2) {
			$isLeap = (0 == $year % 400) || ( (0 == $year % 4) && (0 != $year %100) );
			return $isLeap ? 29 : 28;
		}
		return $MONTH_DAYS[$month];
	}
	
    
    function adminMenu() {
		if (function_exists('add_menu_page')) {
				add_menu_page('Stats', 'Stats', 'publish_posts', 'bb-usage-stats', array($this,'showStats'), BB_USAGE_STATS_URL.'/image/stats.png');
		}
    }
    
    function dashboard() {
        wp_add_dashboard_widget('bb-usage-stats-dashboard', 'bb: Usage Stats', array($this,'showStats'));
    }
    
    function showStats() {
        include(BB_USAGE_STATS_DIR.'stats.php');
    }
}


$bbUsageStats = bbUsageStats::Instance();
add_action('wp_footer', array($bbUsageStats, 'trackCurrent'));
add_action('activate_'.BB_USAGE_STATS.'/bb-usage-stats.php', array($bbUsageStats, 'activatePlugin'));
add_action('admin_menu', array($bbUsageStats, 'adminMenu'));
add_action('wp_dashboard_setup', array($bbUsageStats, 'dashboard') );

}
?>
