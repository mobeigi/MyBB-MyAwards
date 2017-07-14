<?php

/*
*
* My Awards Plugin v 2.4
* Copyright 2012 Jesse Labrocca
* http://www.mybbcentral.com
* No one is authorized to redistribute or remove copyright without my expressed permission.
*
*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    $init_check = "&#77-121-98-98-32-67-101-110-116-114-97-108;";
    $init_error = str_replace("-",";&#", $init_check);
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.<br />". $init_error);
} 

// Load language packs for this section
$lang->load("user_myawards");

$page->add_breadcrumb_item("My Awards", "index.php?module=user-awards");

	switch ($mybb->input['action'])
	{

	case "awards_edit":
	$nav = "myawards_edit";
    break;
	case "awards_add":
	$nav = "myawards_add";
    break;
	case "awards_grant":
	$nav = "myawards_grant";
    break;
	default:
    $nav = "myawards_home";

	}

log_admin_action();

	$page->output_header($lang->myawards_admin_index);

	$sub_tabs['myawards_home'] = array(
		'title' => $lang->myawards_admin_sub_home,
		'link' => "index.php?module=user-awards",
		'description' => $lang->myawards_admin_sub_home_desc
	);

	$sub_tabs['myawards_add'] = array(
		'title' => $lang->myawards_admin_sub_add,
		'link' => "index.php?module=user-awards&amp;action=awards_add",
		'description' => $lang->myawards_admin_sub_add_desc
	);

	$sub_tabs['myawards_grant'] = array(
		'title' => $lang->myawards_admin_sub_grant,
		'link' => "index.php?module=user-awards&amp;action=awards_grant&amp;awid=". $mybb->input['awid'],
		'description' => $lang->myawards_admin_sub_grant_desc
	);


	$page->output_nav_tabs($sub_tabs, $nav);


if($page->active_action != "awards")
	{
		return;
	}



if($mybb->input['action'] == "awards_add")
{

		$form = new Form("index.php?module=user-awards&amp;action=awards_save", "post", "awards_save",1);

		$form_container = new FormContainer($lang->management);

	$form_container->output_row($lang->name." <em>*</em>", "", $form->generate_text_box('awname', $mybb->input['awname'], array('id' => 'awname')), 'awname');
	$form_container->output_row($lang->desc." <em>*</em>", "", $form->generate_text_box('awdescr', $mybb->input['awdescr'], array('id' => 'description')), 'awdescr');
	$form_container->output_row($lang->upload, $lang->upload_desc, $form->generate_file_upload_box("awardsupload", array('style' => 'width: 230px;')), 'file');

		$form_container->end();
		$form_container->construct_row();
	
		$buttons[] = $form->generate_submit_button($lang->submit);
		$form->output_submit_wrapper($buttons);
		$form->end();
	

}


if($mybb->input['action'] == "awards_edit")
{

	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=user-awards");
	}
	
$query = $db->simple_select("myawards", "*", "awid='".intval($mybb->input['awid'])."'");
$award = $db->fetch_array($query);


		$form = new Form("index.php?module=user-awards&amp;action=awards_edit_save", "post", "awards_edit_save",1);

		$form_container = new FormContainer($lang->management);

	$query = $db->simple_select("myawards","*","awid='".$db->escape_string($mybb->input['awid'])."'");
	$awards = $db->fetch_array($query);

			$form_container->output_row("{$awards['awname']}","<img src=\"{$mybb->settings['bburl']}/uploads/awards/{$awards['awimg']}\" alt=\"\" /><br />{$awards['awdescr']}");

		$form_container->output_row($lang->name." <em>*</em>", "", $form->generate_text_box('awname', $award['awname'], array('id' => 'awname')), 'awname');
		$form_container->output_row($lang->desc." <em>*</em>", "", $form->generate_text_box('awdescr', $award['awdescr'], array('id' => 'description')), 'awdescr');
		$form_container->output_row($lang->upload, $lang->upload_desc, $form->generate_file_upload_box("awardsupload", array('style' => 'width: 230px;')), 'file');

		echo $form->generate_hidden_field("awid", $award['awid'])."\n";

		$form_container->construct_row();
		$form_container->end();
	
		$buttons[] = $form->generate_submit_button($lang->submit);
		$form->output_submit_wrapper($buttons);
		$form->end();
	

}



if($mybb->input['action'] == "awards_search")
{

	$query = $db->simple_select("myawards","*","awid='".$db->escape_string($mybb->input['awid'])."'");
	$awards = $db->fetch_array($query);

	$table = new Table;
	$table->construct_header("{$awards['awdescr']}", array('colspan' => '2', 'class' => 'align_center'));
	$table->construct_cell("<img src=\"{$mybb->settings['bburl']}/uploads/awards/{$awards['awimg']}\" alt=\"\" />", array('class' => 'align_center'));
	$table->construct_row();
	$table->output("{$awards['awname']}");


		$form_container = new FormContainer($lang->management);

		$form_container->output_row_header($lang->username, array('class' => 'align_left', width => '18%'));
		$form_container->output_row_header($lang->reason, array('class' => 'align_left', width => '60%'));
		$form_container->output_row_header($lang->date, array('class' => 'align_center'));
		$form_container->output_row_header($lang->options, array('class' => 'align_center'));

	$query = $db->query("
		SELECT a.*, u.username AS username
		FROM ".TABLE_PREFIX."myawards_users a
		LEFT JOIN ".TABLE_PREFIX."users u ON (a.awuid=u.uid)
		WHERE awid = ".intval($mybb->input['awid'])."
	");

	while($awards = $db->fetch_array($query))
	{

	$date = my_date($mybb->settings['dateformat'], $awards['awutime']).", ".my_date($mybb->settings['timeformat'], $awards['awutime']);

			$form_container->output_cell("{$awards['username']}");
			$form_container->output_cell("{$awards['awreason']}");
			$form_container->output_cell("{$date}");
			$form_container->output_cell("<div style=\"text-align:center\"><a href=\"index.php?module=user-awards&amp;action=awards_delete_user&amp;id={$awards['id']}&amp;awid={$awards['awid']}&amp;awuid={$awards['awuid']}&amp;my_post_key={$mybb->post_code}\">Delete</a></div>");



		$form_container->construct_row();

	}

		$form_container->end();

}

if($mybb->input['action'] == "awards_delete_user")
{

	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=user-awards");
	}
	
	$db->query("UPDATE ".TABLE_PREFIX."users SET awards=awards-1 WHERE uid='".intval($mybb->input['awuid'])."'");
	$db->delete_query("myawards_users","id='".intval($mybb->input['id'])."'");


		flash_message($lang->delete_mem_award, 'success');
		admin_redirect("index.php?module=user-awards&amp;action=awards_search&amp;awid=". intval($mybb->input['awid']));
}

if($mybb->input['action'] == "awards_delete")
{


	$query = $db->simple_select("myawards_users", "awuid,id", "awid='".intval($mybb->input['awid'])."'");

	while($awards = $db->fetch_array($query))
	{

	$db->delete_query("myawards_users","id='".intval($awards['id'])."'");
	$db->query("UPDATE ".TABLE_PREFIX."users SET awards=awards-1 WHERE uid='".intval($awards['awuid'])."'");
	}

	$db->delete_query("myawards","awid='".intval($mybb->input['awid'])."'");
	awards_cache();

		flash_message($lang->delete_mem_award, 'success');
		admin_redirect("index.php?module=user-awards");
}


if($mybb->input['action'] == "awards_grant")
{

	if (!$mybb->input['awid']){

		flash_message($lang->error_award, 'error');
		admin_redirect("index.php?module=user-awards");
	}

	$form = new Form("index.php?module=user-awards&amp;action=awards_do_grant", "post", "awards_grant",1);

	$form_container = new FormContainer($lang->awgive_header);

	$query = $db->simple_select("myawards","*","awid='".intval($mybb->input['awid'])."'");
	$awards = $db->fetch_array($query);

	$form_container->output_row("{$awards['awname']}","<img src=\"{$mybb->settings['bburl']}/uploads/awards/{$awards['awimg']}\" alt=\"\" /><br />{$awards['awdescr']}");

	$form_container->output_row($lang->username." <em>*</em>",$lang->user_w, $form->generate_text_box('username', $mybb->input['username'], array('id' => 'username')), 'username');

	$form_container->output_row($lang->reason, $lang->reason_w, $form->generate_text_box('awreason', $mybb->input['awreason'], array('id' => 'awreason')), 'awreason');

    $form_container->output_row($lang->send_private, $lang->send_private_desc, $form->generate_check_box("pm_check", "send_a_pm", "Send PM", array('id' => 'pm_check')), 'pm_check');	
	
	$pm_message = $lang->sprintf($lang->admin_pm_message, $awards['awname']);

	$form_container->output_row($lang->message_contents, $lang->message_contents_desc, $form->generate_text_area('pm_info', $pm_message, array('id' => 'pm_info')), 'pm_info');
	
	echo $form->generate_hidden_field("awid", $mybb->input['awid'])."\n";

	$form_container->end();
	$form_container->construct_row();

	// Autocompletion for usernames
	echo '
	<script type="text/javascript" src="../jscripts/autocomplete.js?ver=140"></script>
	<script type="text/javascript">
	<!--
		new autoComplete("username", "../xmlhttp.php?action=get_users", {valueSpan: "username"});
	// -->
	</script>';
	
		$buttons[] = $form->generate_submit_button($lang->submit);
		$form->output_submit_wrapper($buttons);
		$form->end();
	

}


if($mybb->input['action'] == "awards_do_grant" && $mybb->request_method == "post")
{

	$query = $db->simple_select("users", "uid", "username='".$db->escape_string($mybb->input['username'])."'");
	$user = $db->fetch_field($query, "uid");

		if(!$user)
		{
					flash_message($lang->granted_failure, 'error');
					admin_redirect("index.php?module=user-awards&amp;action=awards_grant");
		}

					$insert = array( 
						"awid" => intval($mybb->input['awid']),
						"awuid" => $user,
						"awreason" => $db->escape_string($mybb->input['awreason']),
						"awutime" => time(),
					); 

					$db->insert_query("myawards_users",$insert);

					$db->query("UPDATE ".TABLE_PREFIX."users SET awards=awards+1 WHERE uid='".$user."'");

					if($mybb->input['pm_check'] == "send_a_pm") {
						require_once(MYBB_ROOT . "/inc/datahandlers/pm.php");

						$pmhandler = new PMDataHandler();

						$pmmessage = $mybb->input['pm_info'];

						$pm = array(
							"subject" => $lang->pm_subject,
							"message" => $pmmessage,
							"icon" => 0,
							"fromid" => $mybb->user['uid'],
							"do" => '',
							"pmid" => ''
						);
						
						$pm['toid'] = explode(",", $user);
						$pm['toid'] = array_map("trim", $pm['toid']);

						$pm['options'] = array(
							"savecopy" => 0,
							"saveasdraft" => 0,
							"signature" => 0,
							"disablesmilies" => 0,
						);

						$pmhandler->admin_override = 1;
						$pmhandler->set_data($pm);

						if(!$pmhandler->validate_pm())
						{
							$pm_errors = $pmhandler->get_friendly_errors();
							$send_errors = inline_error($pm_errors);
							flash_message("Could not send PM", 'error');
							admin_redirect("index.php?module=user-awards");
						}
						else
						{
							$pminfo = $pmhandler->insert_pm();
						}
					}
					
					flash_message($lang->granted_success, 'success');
					admin_redirect("index.php?module=user-awards");

}

if($mybb->input['action'] == "awards_edit_save" && $mybb->request_method == "post")
{

					$update = array( 
						"awname" => $db->escape_string($mybb->input['awname']),
						"awdescr" => $db->escape_string($mybb->input['awdescr'])
					); 



 if($_FILES['awardsupload']['type'])
 {

	$dirpath = MYBB_ROOT.$mybb->settings['uploadspath'].'/awards';

	$file_type = $_FILES['awardsupload']['type'];

	switch(strtolower($file_type))
	{
		case "image/gif":
		case "image/jpeg":
		case "image/x-jpg":
		case "image/x-jpeg":
		case "image/pjpeg":
		case "image/jpg":
		case "image/png":
		case "image/x-png":
			$img_type =  1;
			break;
		default:
			$img_type = 0;
	}

	if($img_type == 0)
	{

		flash_message($lang->error_file_type, 'error');
		admin_redirect("index.php?module=user-awards&amp;action=awards_edit&amp;awid=".intval($mybb->input['awid']));

	}

        if ($_FILES['awardsupload']['error'] == '0')
        {
                $imgfile = $_FILES['awardsupload']['tmp_name'];
                $newfile = $dirpath . '/' . $_FILES['awardsupload']['name'];
                if (!copy($imgfile, $newfile) && !$mybb->input['edit'])
                {

				flash_message($lang->image_error, 'error');
				admin_redirect("index.php?module=user-awards&amp;action=awards_edit&amp;awid=".intval($mybb->input['awid']));
                }

		$update[awimg] = $_FILES['awardsupload']['name'];
 
		}				
 }

					$db->update_query("myawards", $update, "awid='". intval($mybb->input['awid']) ."'");
					awards_cache();

					flash_message($lang->image_success, 'success');
					admin_redirect("index.php?module=user-awards");


}


if($mybb->input['action'] == "awards_save"  && $mybb->request_method == "post")
{

	$dirpath = MYBB_ROOT.$mybb->settings['uploadspath'].'/awards';

	$file_type = $_FILES['awardsupload']['type'];

	switch(strtolower($file_type))
	{
		case "image/gif":
		case "image/jpeg":
		case "image/x-jpg":
		case "image/x-jpeg":
		case "image/pjpeg":
		case "image/jpg":
		case "image/png":
		case "image/x-png":
			$img_type =  1;
			break;
		default:
			$img_type = 0;
	}

	if($img_type == 0)
	{

		flash_message($lang->error_file_type, 'error');
		admin_redirect("index.php?module=user-awards&amp;action=awards_edit&amp;awid=".intval($mybb->input['awid']));

	}

        if ($_FILES['awardsupload']['error'] == '0')
        {
                $imgfile = $_FILES['awardsupload']['tmp_name'];
                $newfile = $dirpath . '/' . $_FILES['awardsupload']['name'];
                if (!copy($imgfile, $newfile) && !$mybb->input['edit'])
                {

				flash_message($lang->image_error, 'error');
				admin_redirect("index.php?module=user-awards&amp;action=awards_edit&amp;awid=".intval($mybb->input['awid']));
                }



					$update = array( 
						"awname" => $db->escape_string($mybb->input['awname']),
						"awimg" => $_FILES['awardsupload']['name'],
						"awdescr" => $db->escape_string($mybb->input['awdescr'])
					); 


					$db->insert_query("myawards", $update);
					awards_cache();

					flash_message($lang->image_success, 'success');
					admin_redirect("index.php?module=user-awards");

        }else{

		flash_message($lang->image_error, 'error');
		admin_redirect("index.php?module=user-awards&amp;action=awards_edit&amp;awid=".intval($mybb->input['awid']));


	}


}

if(!$mybb->input['action'])
{


		$form = new Form("index.php?module=user-awards", "post");

		$form_container = new FormContainer($lang->management);
		$form_container->output_row_header($lang->award_name, array('class' => 'align_left', width => '75%'));
		$form_container->output_row_header($lang->icon, array('class' => 'align_center'));
		$form_container->output_row_header($lang->options, array('class' => 'align_center'));

		$query = $db->simple_select("myawards", "*");

		while($awards = $db->fetch_array($query))
		{

			$form_container->output_cell("<div style=\"padding-left: ".(40*($depth-1))."px;\"><a href=\"index.php?module=user-awards&amp;action=awards_edit&amp;awid={$awards['awid']}\"><strong>{$awards['awname']}</strong></a><br /><small>{$awards['awdescr']}</small></div>");
			$form_container->output_cell("<div style=\"text-align:center;\" ><img src=\"{$mybb->settings['bburl']}/uploads/awards/{$awards['awimg']}\" alt=\"\" /></div>");

		$popup = new PopupMenu("award_{$awards['awid']}", $lang->options);

		$popup->add_item($lang->edit, "index.php?module=user-awards&amp;action=awards_edit&amp;awid={$awards['awid']}&amp;my_post_key={$mybb->post_code}");
		$popup->add_item($lang->grant, "index.php?module=user-awards&amp;action=awards_grant&amp;awid={$awards['awid']}&amp;my_post_key={$mybb->post_code}");
		$popup->add_item($lang->recipients, "index.php?module=user-awards&amp;action=awards_search&amp;awid={$awards['awid']}");
		$popup->add_item($lang->delete, "index.php?module=user-awards&amp;action=awards_delete&amp;awid={$awards['awid']}&amp;my_post_key={$mybb->post_code}");

		$form_container->output_cell($popup->fetch(), array("class" => "align_center"));

		$form_container->construct_row();

		}

		$form_container->end();
		$form->end();

}
   $page->output_footer();

   
   function awards_cache()
   {
		global $db, $cache;
		
		$awards = array();
		
   			$query = $db->simple_select("myawards", "*");
			while($data = $db->fetch_array($query))
			{
				$awards[$data['awid']] = array("id"=>$data['awid'],"awname"=>$data['awname'],"awimg"=>$data['awimg'], "awdescr"=>$data['awdescr']);
			}

		$cache->update("myawards", $awards);
	
	}


?>