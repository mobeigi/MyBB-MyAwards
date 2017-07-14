<?php

/*
*
* My Awards Plugin v2.4
* Copyright 2012 Jesse Labrocca
* http://www.mybbcentral.com
* No one is authorized to redistribute or remove copyright without my expressed permission.
*
*/


define("IN_MYBB", 1);
define('THIS_SCRIPT', 'myawards.php');

$templatelist = "my_awards,my_awards_profile,my_awards_page_uid,my_awards_page_uid_rows,my_awards_page_uid_none,my_awards_page_all,my_awards_page_all_rows,my_awards_page_awid,my_awards_page_awid_none,my_awards_page_awid_rows,my_awards_page_main,my_awards_page_main_rows,";

require_once "./global.php";

	$lang->load("myawards");

	add_breadcrumb($lang->awards_head, "myawards.php");

	$cawards = $cache->read("myawards");
	
	$plugins->run_hooks("myawards_start");


	
	
if($mybb->input['uid']) {

	add_breadcrumb($lang->user_awards);

  $user = get_user(intval($mybb->input['uid']));
  $profilelink = build_profile_link($user['username'],$user['uid']);

	eval("\$awbody  .= \"".$templates->get("my_awards_page_uid")."\";");

	$awuid = intval($mybb->input['uid']);
	$query = $db->simple_select("myawards_users", "*", "awuid={$awuid}");
	
	while($results = $db->fetch_array($query))
	{
		$bgcolor = alt_trow();

		$award_name = $cawards[$results['awid']]['awname'];
		$award_img = $cawards[$results['awid']]['awimg'];
		
		$date = my_date($mybb->settings['dateformat'], $results['awutime']).", ".my_date($mybb->settings['timeformat'], $results['awutime']);

		eval("\$awbody  .= \"".$templates->get("my_awards_page_uid_rows")."\";");

		$set = 1;

	}

	if(!$set){

		$bgcolor = alt_trow();
		eval("\$awbody  .= \"".$templates->get("my_awards_page_uid_none")."\";");

	}

}


else if($mybb->input['awid']) {

	add_breadcrumb($lang->awarded);

	eval("\$awbody  .= \"".$templates->get("my_awards_page_awid")."\";");

		$awid = intval($mybb->input['awid']);
		$query = $db->query("
			SELECT u.*, m.username AS username
			FROM ".TABLE_PREFIX."myawards_users u
			LEFT JOIN ".TABLE_PREFIX."users m ON (m.uid=u.awuid)
			WHERE u.awid='".intval($mybb->input['awid'])."'
			ORDER BY u.awutime
		");

	while($results = $db->fetch_array($query))
	{
		$bgcolor = alt_trow();

		$date = my_date($mybb->settings['dateformat'], $results['awutime']).", ".my_date($mybb->settings['timeformat'], $results['awutime']);
		
		$award_name = $cawards[$results['awid']]['awname'];
		$award_img = $cawards[$results['awid']]['awimg'];
		
		if (!$results['awuid']) {
			eval("\$awbody  .= \"".$templates->get("my_awards_page_awid_none")."\";");

			break;
		}

		$profilelink = build_profile_link($results['username'],$results['awuid']);

		eval("\$awbody  .= \"".$templates->get("my_awards_page_awid_rows")."\";");

	}

}


else if($mybb->input['all']) {

	add_breadcrumb($lang->user_awards);

	eval("\$awbody  .= \"".$templates->get("my_awards_page_all")."\";");
		
		$query = $db->query("
			SELECT u.username,au.*, a.* 
			FROM ".TABLE_PREFIX."myawards a
			LEFT JOIN ".TABLE_PREFIX."myawards_users au ON (au.awid=a.awid)
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=au.awuid)
			ORDER BY au.awutime
		");

	while($results = $db->fetch_array($query))
	{
		$bgcolor = alt_trow();

		if(empty($results['awutime'])) {
			continue;
		}
		
		$award_name = $cawards[$results['awid']]['awname'];
		$award_img = $cawards[$results['awid']]['awimg'];
		
		$profilelink = build_profile_link($results['username'],$results['awuid']);

		$date = my_date($mybb->settings['dateformat'], $results['awutime'])." ".my_date($mybb->settings['timeformat'], $results['awutime']);

		eval("\$awbody  .= \"".$templates->get("my_awards_page_all_rows")."\";");

	}

}


else {

	add_breadcrumb($lang->awards_list);

	eval("\$awbody  .= \"".$templates->get("my_awards_page_main")."\";");
	
	
	if(!empty($cawards)) {
		foreach($cawards as $results)
		{
			$bgcolor = alt_trow();
			
			eval("\$awbody  .= \"".$templates->get("my_awards_page_main_rows")."\";");
		}
	}

}

	eval("\$myawards = \"".$templates->get("my_awards")."\";");
	$plugins->run_hooks("myawards_end");
	output_page($myawards);

?>