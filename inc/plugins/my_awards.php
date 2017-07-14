<?php
/*
 *
 * My Awards Plugin MyBB 1.6x
 * By: Jesse Labrocca
 * Website: http://www.mybbcentral.com
 * Version: 2.4
 *
*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    $init_check = "&#77-121-98-98-32-67-101-110-116-114-97-108;";
    $init_error = str_replace("-",";&#", $init_check);
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.<br />". $init_error);
} 

$plugins->add_hook("admin_user_menu", "awards_admin_nav");
$plugins->add_hook("admin_user_action_handler", "awards_action_handler");
$plugins->add_hook("admin_user_permissions", "awards_user_permissions");
$plugins->add_hook("postbit", "awards_postbit");
$plugins->add_hook("postbit_pm", "awards_pmbit");
$plugins->add_hook("member_profile_end", "awards_profile");
$plugins->add_hook("stats_end", "awards_stats");

$plugins->add_hook("modcp_start", "awards_modcp");



function my_awards_info()
{
	return array(
		'name'			=> 'My Awards',
		'description'	=> 'Give awards icons to members.',
		'website'		=> 'http://www.mybbcentral.com',
		'author'		=> 'Jesse Labrocca',
		'authorsite'	=> 'http://www.mybbcentral.com',
		'version'		=> '2.4',
		'compatibility'	=> '18*',
	);
}

function my_awards_install()
{
	global $mybb, $db, $templates;

  	if(!$db->table_exists("myawards"))
	{
		$db->query("CREATE TABLE ".TABLE_PREFIX."myawards (
			awid smallint(4) NOT NULL auto_increment,
		    awname varchar(64) NOT NULL,
		    awimg varchar(64) NOT NULL,
        awdescr varchar(255) NOT NULL,
      	awstackable	bit(1) NOT NULL,
			PRIMARY KEY  (awid)
		) ENGINE=innodb;");
  	}

  	if(!$db->table_exists("myawards_users"))
	{
		$db->query("CREATE TABLE ".TABLE_PREFIX."myawards_users (
			id smallint(4) NOT NULL auto_increment,
			awid smallint(4) NOT NULL,
			awuid int(8) NOT NULL,
			awreason varchar(255) NOT NULL,
			awutime bigint(30),
			PRIMARY KEY  (id)
		) ENGINE=innodb;");
  	}

	$db->query("ALTER TABLE `".TABLE_PREFIX."users` ADD `awards` int(10) NOT NULL DEFAULT '0' AFTER `postnum`");

    $settings_group = array(
        "gid" => "",
        "name" => "myawards",
        "title" => "My Awards",
        "description" => "The My Awards settings group.",
        "disporder" => "30",
        "isdefault" => "0",
        );
    $db->insert_query("settinggroups", $settings_group);
    $gid = $db->insert_id();

    $setting_1 = array(
        "sid" => "",
        "name" => "myawardsenable",
        "title" => "Enable My Awards",
        "description" => "Do you want to enable the awards system?",
        "optionscode" => "onoff",
        "value" => "1",
        "disporder" => "1",
        "gid" => intval($gid),
        );
    $db->insert_query("settings", $setting_1);

    $setting_2 = array(
        "sid" => "",
        "name" => "myawardmods",
        "title" => "Award Moderators",
        "description" => "Enter uid\'s for awards moderator (comma seperated)",
        "optionscode" => "text",
        "value" => "0",
        "disporder" => "1",
        "gid" => intval($gid),
        );
    $db->insert_query("settings", $setting_2);

	myawards_upgrade_plugin();

}

function my_awards_is_installed()
{
	global $db, $mybb;
	
	if($db->table_exists("myawards")) {
		return true;
	}
	
	return false;
}

function my_awards_activate()
{
	global $mybb;
	
	myawards_upgrade_plugin();
	
	awards_cache_plugin();
	
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	//First, remove old template changes if they used 1.x version.
	find_replace_templatesets("postbit_author_user", '#<br /><!-- AWARDS -->#', '',0);
	
	//Now to 2.x template edits.
	find_replace_templatesets("member_profile", '#{\$reputation}#', "{\$reputation}\n{\$myawards}\n");
	find_replace_templatesets("stats", '#{\$footer}#', "{\$awards_stats}{\$footer}");
	find_replace_templatesets("footer", '#syndication}</a>#', "syndication}</a> | <a href=\"{\$mybb->settings['bburl']}/myawards.php\">Awards</a>");
	find_replace_templatesets("postbit", '#user_details\']}#', 'user_details\']}<br />{\$post[\'myawards\']}');
	find_replace_templatesets("postbit_classic", '#user_details\']}#', 'user_details\']}<br />{\$post[\'myawards\']}');
    rebuild_settings();
}

function my_awards_deactivate()
{
	global $mybb;

	require "../inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", '#(\n?){\$myawards}(\n?)#', '', 0);
	find_replace_templatesets("stats", '#{\$awards_stats}#', '', 0);
	find_replace_templatesets("postbit", '#'.preg_quote('<br />{$post[\'myawards\']}').'#', '',0);
	find_replace_templatesets("postbit_classic", '#'.preg_quote('<br />{$post[\'myawards\']}').'#', '',0);
	find_replace_templatesets("footer", '#'.preg_quote(' | <a href="{$mybb->settings[\'bburl\']}/myawards.php">Awards</a>').'#', '',0);

}

function my_awards_uninstall()
{
	global $mybb, $db;
	$query = $db->simple_select("settinggroups", "gid","name='myawards'");
    $g = $db->fetch_array($query);
    $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE gid='".$g['gid']."'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE gid='".$g['gid']."'");
	$db->query("ALTER TABLE ".TABLE_PREFIX."users DROP `awards`");
	$db->drop_table("myawards");
	$db->drop_table("myawards_users");	
	$db->delete_query("templates", "title IN ('my_awards','my_awards_profile','my_awards_page_all','my_awards_page_all_rows','my_awards_page_awid','my_awards_page_awid_rows','my_awards_page_awid_none','my_awards_page_main','my_awards_page_main_rows','my_awards_page_uid','my_awards_page_uid_rows','my_awards_page_uid_none') "); 
	$db->delete_query("datacache", "title='myawards'");
	
	rebuild_settings();
}

function awards_action_handler(&$action)
{
	$action['awards'] = array('active' => 'awards', 'file' => 'myawards.php');
}

function awards_admin_nav(&$sub_menu)
{
	global $mybb, $lang;
	
		$lang->load("forum_awards", false, true);
		
		end($sub_menu);
		$key = (key($sub_menu))+10;
		
		if(!$key)
		{
			$key = '60';
		}
		
		$sub_menu[$key] = array('id' => 'awards', 'title' => 'My Awards', 'link' => "index.php?module=user-awards");

}


function awards_postbit(&$post)
{
    global $db, $mybb, $lang, $cache, $first_time_only, $pids, $pawards;


    if($post['awards'] && $mybb->settings['myawardsenable'])
    {


        if($mybb->input['ajax'] || $mybb->input['mode'] == "threaded")
        {

            awards_pmbit($post);
            return $post;
        }    

    $cawards = $cache->read("myawards");

        if(!$first_time_only)
        {
            $first_time_only = true;

            $query = $db->query("
            SELECT p.uid, p.pid, a.*
            FROM ".TABLE_PREFIX."myawards_users a
            LEFT JOIN ".TABLE_PREFIX."posts p ON (p.uid=a.awuid)
            WHERE $pids
            ");

                while($create = $db->fetch_array($query))
                {
                    $pawards[$create['uid']][$create['id']] = $create['awid'];
                }

        }

    $awards = "";
    $awards = "<span style=\"white-space:normal; width: 100%;\" class=\"my_awards_postbit\">";

foreach ($pawards as $key=>$value)
    {

if($key == $post['uid'])
{

    foreach ($value as $key)
    {
		$awards .= "<img src=\"uploads/awards/".$cawards[$key]['awimg']."\" alt=\"".$cawards[$key]['awname']."\" title=\"".$cawards[$key]['awname']."\" />";
    }
}


    }

    $awards .= "</span>";

    }

    $post['myawards'] = $awards;
    
return $post;

} 

function awards_pmbit(&$post)
{
    global $db, $mybb, $lang, $cache;
    
    if($post['awards'] && $mybb->settings['myawardsenable'])
    {
    
		$cawards = $cache->read("myawards");

		$awuid = intval($post['uid']);
		
		$query = $db->simple_select("myawards_users", "*", "awuid={$awuid}");

		$awards = "";
		$awards = "<span style=\"white-space:normal; width: 100%;\" class=\"my_awards_postbit\">";

		while($results = $db->fetch_array($query))
		{
		
			$awards .= "<img src=\"uploads/awards/".$cawards[$results['awid']]['awimg']."\" alt=\"".$cawards[$results['awid']]['awname']."\" title=\"".$cawards[$results['awid']]['awname']."\" />";

		}

		$awards .= "</span>";

    }

    $post['myawards'] = $awards;
    
return $post;

} 

function awards_profile()
{
	global $db, $mybb, $lang, $memprofile, $myawards, $templates;

		$lang->load("myawards");

    if($mybb->settings['myawardsenable'])
	{

		eval("\$myawards = \"".$templates->get("my_awards_profile")."\";");

	}

return;

}

function awards_user_permissions(&$admin_permissions)
{
  	global $db, $mybb;
  
  	if($mybb->settings['myawardsenable'] == 1)
	{
		global $lang;
		
		$lang->load("myawards", false, true);
		
		$admin_permissions['awards'] = $lang->can_manage_awards;
	}
}

function awards_stats()
{
	global $mybb, $db, $templates, $awards_stats, $cache, $lang, $theme;
	
	if(intval($mybb->settings['myawardsstatslimit']) != 0) {
	
		$lang->load("myawards");
		
		$lang->awards_head = "Latest Awards Granted";
		
		$cawards = $cache->read("myawards");
		
		$awards_stats = "
	<br />
	<table border=\"0\" cellspacing=\"{$theme['borderwidth']}\" cellpadding=\"{$theme['tablespace']}\" class=\"tborder\">
		";
		
		eval("\$awards_stats .= \"".$templates->get("my_awards_page_awid")."\";");

			$query = $db->query("
				SELECT u.*, m.username AS username
				FROM ".TABLE_PREFIX."myawards_users u
				LEFT JOIN ".TABLE_PREFIX."users m ON (m.uid=u.awuid)
				ORDER BY u.awutime DESC
				LIMIT ".intval($mybb->settings['myawardsstatslimit'])."
			");

			
		while($results = $db->fetch_array($query))
		{
			$bgcolor = alt_trow();

			$award_name = $cawards[$results['awid']]['awname'];
			$award_img = $cawards[$results['awid']]['awimg'];
			
			$date = my_date($mybb->settings['dateformat'], $results['awutime']).", ".my_date($mybb->settings['timeformat'], $results['awutime']);

			$profilelink = build_profile_link($results['username'],$results['awuid']);

			eval("\$awards_stats  .= \"".$templates->get("my_awards_page_awid_rows")."\";");

		}
		
		$bgcolor = alt_trow();
		
		if(intval($db->num_rows($query)) == 0) {
			eval("\$awards_stats  .= \"".$templates->get("my_awards_page_awid_none")."\";");
		}
		
		$awards_stats .= "</table>";
	}

}

function awards_modcp()
{
	global $mybb, $cache, $plugins, $templates, $db, $lang, $modcp;

	if($mybb->input['action'] == "myawards") {
	
	add_breadcrumb("Test", "modcp.php");
	
	
	
	
	
	
	
	
	
	
	
	$query = $db->query("
		SELECT COUNT(aid) AS unapprovedattachments
		FROM  ".TABLE_PREFIX."attachments a
		LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=a.pid)
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
		WHERE a.visible='0' {$tflist}
	");
	$unapproved_attachments = $db->fetch_field($query, "unapprovedattachments");

	if($unapproved_attachments > 0)
	{
		$query = $db->query("
			SELECT t.tid, p.pid, p.uid, t.username, a.filename, a.dateuploaded
			FROM  ".TABLE_PREFIX."attachments a
			LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=a.pid)
			LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
			WHERE a.visible='0' {$tflist}
			ORDER BY a.dateuploaded DESC
			LIMIT 1
		");
		$attachment = $db->fetch_array($query);
		$attachment['date'] = my_date($mybb->settings['dateformat'], $attachment['dateuploaded']);
		$attachment['time'] = my_date($mybb->settings['timeformat'], $attachment['dateuploaded']);
		$attachment['profilelink'] = build_profile_link($attachment['username'], $attachment['uid']);
		$attachment['link'] = get_post_link($attachment['pid'], $attachment['tid']);
		$attachment['filename'] = htmlspecialchars_uni($attachment['filename']);

		eval("\$latest_attachment = \"".$templates->get("modcp_lastattachment")."\";");
	}
	else
	{
		$latest_attachment = "<span style=\"text-align: center;\">{$lang->lastpost_never}</span>";
	}

	$query = $db->query("
		SELECT COUNT(pid) AS unapprovedposts
		FROM  ".TABLE_PREFIX."posts p
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
		WHERE p.visible='0' {$tflist} AND t.firstpost != p.pid
	");
	$unapproved_posts = $db->fetch_field($query, "unapprovedposts");

	if($unapproved_posts > 0)
	{
		$query = $db->query("
			SELECT p.pid, p.tid, p.subject, p.uid, p.username, p.dateline
			FROM  ".TABLE_PREFIX."posts p
			LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
			WHERE p.visible='0' {$tflist} AND t.firstpost != p.pid
			ORDER BY p.dateline DESC
			LIMIT 1
		");
		$post = $db->fetch_array($query);
		$post['date'] = my_date($mybb->settings['dateformat'], $post['dateline']);
		$post['time'] = my_date($mybb->settings['timeformat'], $post['dateline']);
		$post['profilelink'] = build_profile_link($post['username'], $post['uid']);
		$post['link'] = get_post_link($post['pid'], $post['tid']);
		$post['subject'] = $post['fullsubject'] = $parser->parse_badwords($post['subject']);
		if(my_strlen($post['subject']) > 25)
		{
			$post['subject'] = my_substr($post['subject'], 0, 25)."...";
		}
		$post['subject'] = htmlspecialchars_uni($post['subject']);
		$post['fullsubject'] = htmlspecialchars_uni($post['fullsubject']);

		eval("\$latest_post = \"".$templates->get("modcp_lastpost")."\";");
	}
	else
	{
		$latest_post =  "<span style=\"text-align: center;\">{$lang->lastpost_never}</span>";
	}

	$query = $db->simple_select("threads", "COUNT(tid) AS unapprovedthreads", "visible=0 {$flist}");
	$unapproved_threads = $db->fetch_field($query, "unapprovedthreads");

	if($unapproved_threads > 0)
	{
		$query = $db->simple_select("threads", "tid, subject, uid, username, dateline", "visible=0 {$flist}", array('order_by' =>  'dateline', 'order_dir' => 'DESC', 'limit' => 1));
		$thread = $db->fetch_array($query);
		$thread['date'] = my_date($mybb->settings['dateformat'], $thread['dateline']);
		$thread['time'] = my_date($mybb->settings['timeformat'], $thread['dateline']);
		$thread['profilelink'] = build_profile_link($thread['username'], $thread['uid']);
		$thread['link'] = get_thread_link($thread['tid']);
		$thread['subject'] = $thread['fullsubject'] = $parser->parse_badwords($thread['subject']);
		if(my_strlen($thread['subject']) > 25)
		{
			$post['subject'] = my_substr($thread['subject'], 0, 25)."...";
		}
		$thread['subject'] = htmlspecialchars_uni($thread['subject']);
		$thread['fullsubject'] = htmlspecialchars_uni($thread['fullsubject']);

		eval("\$latest_thread = \"".$templates->get("modcp_lastthread")."\";");
	}
	else
	{
		$latest_thread = "<span style=\"text-align: center;\">{$lang->lastpost_never}</span>";
	}

	$query = $db->query("
		SELECT l.*, u.username, u.usergroup, u.displaygroup, t.subject AS tsubject, f.name AS fname, p.subject AS psubject
		FROM ".TABLE_PREFIX."moderatorlog l
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=l.tid)
		LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid=l.fid)
		LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=l.pid)
		ORDER BY l.dateline DESC
		LIMIT 5
	");
	while($logitem = $db->fetch_array($query))
	{
		$information = '';
		$logitem['action'] = $logitem['action'];
		$log_date = my_date($mybb->settings['dateformat'], $logitem['dateline']);
		$log_time = my_date($mybb->settings['timeformat'], $logitem['dateline']);
		$trow = alt_trow();
		$username = format_name($logitem['username'], $logitem['usergroup'], $logitem['displaygroup']);
		$logitem['profilelink'] = build_profile_link($username, $logitem['uid']);
		if($logitem['tsubject'])
		{
			$information = "<strong>{$lang->thread}</strong> <a href=\"".get_thread_link($logitem['tid'])."\" target=\"_blank\">".htmlspecialchars_uni($logitem['tsubject'])."</a><br />";
		}
		if($logitem['fname'])
		{
			$information .= "<strong>{$lang->forum}</strong> <a href=\"".get_forum_link($logitem['fid'])."\" target=\"_blank\">".htmlspecialchars_uni($logitem['fname'])."</a><br />";
		}
		if($logitem['psubject'])
		{
			$information .= "<strong>{$lang->post}</strong> <a href=\"".get_post_link($logitem['pid'])."#pid{$logitem['pid']}\">".htmlspecialchars_uni($logitem['psubject'])."</a>";
		}
		
		// Edited a user?
		if(!$logitem['tsubject'] || !$logitem['fname'] || !$logitem['psubject'])
		{
			$data = unserialize($logitem['data']);
			if($data['uid'])
			{
				$information = $lang->sprintf($lang->edited_user_info, htmlspecialchars_uni($data['username']), get_profile_link($data['uid']));
			}
		}

		eval("\$modlogresults .= \"".$templates->get("modcp_modlogs_result")."\";");
	}

	if(!$modlogresults)
	{
		eval("\$modlogresults = \"".$templates->get("modcp_modlogs_noresults")."\";");
	}

	$query = $db->query("
		SELECT b.*, a.username AS adminuser, u.username, (b.lifted-".TIME_NOW.") AS remaining
		FROM ".TABLE_PREFIX."banned b
		LEFT JOIN ".TABLE_PREFIX."users u ON (b.uid=u.uid)
		LEFT JOIN ".TABLE_PREFIX."users a ON (b.admin=a.uid)
		WHERE b.bantime != '---' AND b.bantime != 'perm'
		ORDER BY remaining ASC
		LIMIT 5
	");

	// Get the banned users
	while($banned = $db->fetch_array($query))
	{
		$profile_link = build_profile_link($banned['username'], $banned['uid']);

		// Only show the edit & lift links if current user created ban, or is super mod/admin
		$edit_link = '';
		if($mybb->user['uid'] == $banned['admin'] || !$banned['adminuser'] || $mybb->usergroup['issupermod'] == 1 || $mybb->usergroup['cancp'] == 1)
		{
			$edit_link = "<br /><span class=\"smalltext\"><a href=\"modcp.php?action=banuser&amp;uid={$banned['uid']}\">{$lang->edit_ban}</a> | <a href=\"modcp.php?action=liftban&amp;uid={$banned['uid']}&amp;my_post_key={$mybb->post_code}\">{$lang->lift_ban}</a></span>";
		}

		$admin_profile = build_profile_link($banned['adminuser'], $banned['admin']);

		$trow = alt_trow();

		if($banned['reason'])
		{
			$banned['reason'] = htmlspecialchars_uni($parser->parse_badwords($banned['reason']));
		}
		else
		{
			$banned['reason'] = $lang->na;
		}

		if($banned['lifted'] == 'perm' || $banned['lifted'] == '' || $banned['bantime'] == 'perm' || $banned['bantime'] == '---')
		{
			$banlength = $lang->permanent;
			$timeremaining = $lang->na;
		}
		else
		{
			$banlength = $bantimes[$banned['bantime']];
			$remaining = $banned['remaining'];

			$timeremaining = nice_time($remaining, array('short' => 1, 'seconds' => false))."";

			if($remaining <= 0)
			{
				$timeremaining = "<span style=\"color: red;\">({$lang->ban_ending_imminently})</span>";
			}
			else if($remaining < 3600)
			{
				$timeremaining = "<span style=\"color: red;\">({$timeremaining} {$lang->ban_remaining})</span>";
			}
			else if($remaining < 86400)
			{
				$timeremaining = "<span style=\"color: maroon;\">({$timeremaining} {$lang->ban_remaining})</span>";
			}
			else if($remaining < 604800)
			{
				$timeremaining = "<span style=\"color: green;\">({$timeremaining} {$lang->ban_remaining})</span>";
			}
			else
			{
				$timeremaining = "({$timeremaining} {$lang->ban_remaining})";
			}
		}

		eval("\$bannedusers .= \"".$templates->get("modcp_banning_ban")."\";");
	}

	if(!$bannedusers)
	{
		eval("\$bannedusers = \"".$templates->get("modcp_banning_nobanned")."\";");
	}

	$modnotes = $cache->read("modnotes");
	$modnotes = htmlspecialchars_uni($modnotes['modmessage']);
	
	$plugins->run_hooks("modcp_end");

	eval("\$modcp = \"".$templates->get("modcp")."\";");
	output_page($modcp);
	
	
	
	
	
	
	
	
	
	
	
		// echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><!-- start: private -->
// <html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
// <head>
// <title>Forums - Private Messaging</title>
// <!-- start: headerinclude -->
// <link rel="alternate" type="application/rss+xml" title="Latest Threads (RSS 2.0)" href="http://beta.8ez.com/syndication.php" />
// <link rel="alternate" type="application/atom+xml" title="Latest Threads (Atom 1.0)" href="http://beta.8ez.com/syndication.php?type=atom1.0" />
// <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
// <meta http-equiv="Content-Script-Type" content="text/javascript" />
// <script type="text/javascript" src="http://beta.8ez.com/jscripts/prototype.js?ver=1603"></script>
// <script type="text/javascript" src="http://beta.8ez.com/jscripts/general.js?ver=1603"></script>
// <script type="text/javascript" src="http://beta.8ez.com/jscripts/popup_menu.js?ver=1600"></script>
// <link type="text/css" rel="stylesheet" href="http://beta.8ez.com/cache/themes/theme1/global.css" />
// <link type="text/css" rel="stylesheet" href="http://beta.8ez.com/cache/themes/theme1/usercp.css" />

// </head><body>
// <table border="0" cellspacing="1" cellpadding="4" class="tborder">
// <tr>
// <td class="trow1">
// <table border="0" cellspacing="0" cellpadding="0" width="100%">
// <tr>
// <td class="trow1"><span class="smalltext"><a href="private.php">Inbox</a> | <a href="private.php?action=send">Compose Message</a> | <a  href="private.php?action=folders">Manage Folders</a> | <a  href="private.php?action=empty">Empty Folders</a> | <a href="private.php?action=export">Download Messages</a></span></td>
// </tr>
// </table>
// </td>
// </tr>
// </table>';	
	}
}

function myawards_upgrade_plugin() {
	
	global $db, $mybb, $templates;

	$db->query("ALTER TABLE  `".TABLE_PREFIX."myawards_users` CHANGE  `awuid`  `awuid` INT( 8 ) NOT NULL");
	
	$query = $db->simple_select('settings', 'name', 'name=\'myawardsstatslimit\'', array('limit => 1'));
	
	if ($db->fetch_field($query, 'name')){
		return true;
	}
	
	
	$query = $db->simple_select('templates', 'title', 'title=\'my_awards\'', array('limit => 1'));
	
	if (!$db->fetch_field($query, 'title')) {
	
		$template_1 = array(
			"title"		=> 'my_awards',
			"template"	=> '<html>
	<head>
	<title>{$mybb->settings[\\\'bbname\\\']} - {$lang->awards_head}</title>
	{$headerinclude}
	</head>
	<body>
	{$header}
	<form method="post" action="myawards.php">
	<table border="0" cellspacing="{$theme[\\\'borderwidth\\\']}" cellpadding="{$theme[\\\'tablespace\\\']}" class="tborder">
	{$awbody}
	</table>
	</form>
	{$footer}
	</body>
	</html>',
			"sid"		=> -1,
			"version"	=> 1.0,
			"dateline"	=> time(),
		);

		$db->insert_query("templates", $template_1);
	
	}
	
	
	$query = $db->simple_select('templates', 'title', 'title=\'my_awards_profile\'', array('limit => 1'));
	
	if (!$db->fetch_field($query, 'title')) {
	
		$template_2 = array(
			"title"		=> 'my_awards_profile',
			"template"	=> '<tr>
	<td class="trow1"><strong>{$lang->awards}</strong></td>
	<td class="trow1"><strong>{$memprofile[\\\'awards\\\']}</strong> [<a href="myawards.php?uid={$memprofile[\\\'uid\\\']}" rel="nofollow">{$lang->aw_details}</a>]</td>
	</tr>',
			"sid"		=> -1,
			"version"	=> 1.0,
			"dateline"	=> time(),
		);

		$db->insert_query("templates", $template_2);
	
	}
	
	$template_3 = array(
		"title"		=> 'my_awards_page_all',
		"template"	=> '
<tr>
<td class="thead" colspan="5"><strong>{$lang->awards_head}</strong></td>
</tr>
<tr>
<td class="tcat" width="18%"><strong>{$lang->name}</strong></td>
<td class="tcat"><strong>{$lang->reason}</strong></td>
<td class="tcat" width="10%" align="center"><strong>{$lang->award}</strong></td>
<td class="tcat"width="15%" align="center" ><strong>{$lang->user}</strong></td>
<td class="tcat" width="18%" align="center"><strong>{$lang->date}</strong></td>

</tr>
		',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> time(),
	);

	$db->insert_query("templates", $template_3);
	
	$template_4 = array(
		"title"		=> 'my_awards_page_all_rows',
		"template"	=> '
<tr>
<td class="{$bgcolor}"><strong>{$award_name}</strong></td>
<td class="{$bgcolor}">{$results[\\\'awreason\\\']}</td>
<td class="{$bgcolor}" align="center"><img src="{$mybb->settings[\\\'bburl\\\']}/uploads/awards/{$award_img}" alt="{$award_name}" /></td>
<td class="{$bgcolor}" align="center">{$profilelink}</td>
<td class="{$bgcolor}" align="center">{$date}</td>
</tr>
		',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> time(),
	);

	$db->insert_query("templates", $template_4);
	
	$template_5 = array(
		"title"		=> 'my_awards_page_main',
		"template"	=> '
<tr>
<td class="thead" colspan="3"><strong>{$lang->awards_head}</strong></td>
</tr>
<tr>
<td class="tcat" width="18%"><strong>{$lang->name}</strong></td>
<td class="tcat"><strong>{$lang->description}</strong></td>
<td class="tcat" width="10%" align="center"><strong>{$lang->award}</strong></td>
</tr>
		',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> time(),
	);

	$db->insert_query("templates", $template_5);
	
	$template_6 = array(
		"title"		=> 'my_awards_page_main_rows',
		"template"	=> '
<tr>
<td class="{$bgcolor}"><strong><a href="myawards.php?awid={$results[\\\'id\\\']}">{$results[\\\'awname\\\']}</a></strong></td>
<td class="{$bgcolor}">{$results[\\\'awdescr\\\']}</td>
<td class="{$bgcolor}" align="center"><img src="{$mybb->settings[\\\'bburl\\\']}/uploads/awards/{$results[\\\'awimg\\\']}" alt="{$results[\\\'awname\\\']}" /></td>
</tr>
		',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> time(),
	);

	$db->insert_query("templates", $template_6);
	
	$template_7 = array(
		"title"		=> 'my_awards_page_awid',
		"template"	=> '
<tr>
<td class="thead" colspan="4"><strong>{$lang->awards_head}</strong></td>
</tr>
<tr>
<td class="tcat" width="18%"><strong>{$lang->name}</strong></td>
<td class="tcat"><strong>{$lang->reason}</strong></td>
<td class="tcat" width="10%" align="center"><strong>{$lang->award}</strong></td>
<td class="tcat" width="18%" align="center"><strong>{$lang->date}</strong></td>
</tr>
		',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> time(),
	);

	$db->insert_query("templates", $template_7);
	
	$template_8 = array(
		"title"		=> 'my_awards_page_awid_rows',
		"template"	=> '
<tr>
<td class="{$bgcolor}"><strong>{$profilelink}</strong></td>
<td class="{$bgcolor}">{$results[\\\'awreason\\\']}</td>
<td class="{$bgcolor}" align="center"><img src="{$mybb->settings[\\\'bburl\\\']}/uploads/awards/{$award_img}" alt="{$award_name}" /></td>
<td class="{$bgcolor}" align="center">{$date}</td>
</tr>
		',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> time(),
	);

	$db->insert_query("templates", $template_8);
	
	$template_9 = array(
		"title"		=> 'my_awards_page_awid_none',
		"template"	=> '
<tr>
<td class="{$bgcolor}" colspan="4"><strong>{$lang->none_given}</strong></td>
</tr>
		',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> time(),
	);

	$db->insert_query("templates", $template_9);
	
	$template_10 = array(
		"title"		=> 'my_awards_page_uid',
		"template"	=> '
<tr>
<td class="thead" colspan="4"><strong>{$lang->awards_head} : {$profilelink}</strong></td>
</tr>
<tr>
<td class="tcat" width="18%"><strong>{$lang->name}</strong></td>
<td class="tcat"><strong>{$lang->reason}</strong></td>
<td class="tcat" width="10%" align="center"><strong>{$lang->award}</strong></td>
<td class="tcat" width="18%" align="center"><strong>{$lang->date}</strong></td>
</tr>
		',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> time(),
	);

	$db->insert_query("templates", $template_10);
	
	$template_11 = array(
		"title"		=> 'my_awards_page_uid_rows',
		"template"	=> '
<tr>
<td class="{$bgcolor}"><strong><a href="myawards.php?awid={$results[\\\'awid\\\']}">{$award_name}<a/></strong></td>
<td class="{$bgcolor}">{$results[\\\'awreason\\\']}</td>
<td class="{$bgcolor}" align="center"><img src="{$mybb->settings[\\\'bburl\\\']}/uploads/awards/{$award_img}" alt="{$award_name}" /></td>
<td class="{$bgcolor}" align="center">{$date}</td>
</tr>
		',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> time(),
	);

	$db->insert_query("templates", $template_11);
	
	$template_12 = array(
		"title"		=> 'my_awards_page_uid_none',
		"template"	=> '
<tr>
<td colspan="4" class="{$bgcolor}"><strong>{$lang->noresults}</strong></td>
</tr>
		',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> time(),
	);

	$db->insert_query("templates", $template_12);
	
	$query = $db->simple_select("settinggroups", "gid","name='myawards'");
    $g = $db->fetch_array($query);
	
	$setting_3 = array(
        "sid" => "",
        "name" => "myawardsstatslimit",
        "title" => "Award Stats Limit",
        "description" => "The latest \'X\' awards that should be shown on stats.php.",
        "optionscode" => "text",
        "value" => "10",
        "disporder" => "1",
        "gid" => intval($g['gid']),
        );
		
    $db->insert_query("settings", $setting_3);
}

function awards_cache_plugin()
{
	global $db, $cache;
	
	$awards = array();
	
		$query = $db->simple_select("myawards", "*");
		while($data = $db->fetch_array($query))
		{
			$awards[$data['awid']] = array("id"=>$data['awid'],"awname"=>$data['awname'],"awimg"=>$data['awimg'], "awdescr"=>$data['awdescr'], "awstackable"=>$data['awstackable']);
		}

	$cache->update("myawards", $awards);

}

?>