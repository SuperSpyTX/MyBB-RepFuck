<?php
/*
* 
* [Release] RepFuck 1.0
* Allows you to remove all positive reputation on a user. This is a portover from Boxxy's mod.
*
* USAGE: Upload directory contents.  Install plugin.
* You're not allowed to redistribute this plugin without my expressed permission.
*
*/
if(!defined("IN_MYBB"))
{
	die("
		<!-----------------------
		...LOL JK. Now gtfo out.
		------------------------->
		Press Ctrl + U to see document passwords.
		");
}
$plugins->add_hook("moderation_start", "repfuck_action"); //Basic Hooks
$plugins->add_hook("reputation_add_end", "repfuck_injectbutton"); //Basic Hooks

// Plugin information
function repfuck_info()
	{

	return array(
		"name"		=> "RepFuck",
		"description"	=> "Allows you to remove all positive reputation on a user.",
		"author"		=> ".SuPaH sPii",
		"authorwebsite"	=> "http://mn.vc/",
		"website"	=>  "http://mn.vc",
		"version"		=> "1.0",
		"compatibility"	=> "16*"
		);
	}
	
function repfuck_activate()
{
	global $db, $mybb;
    
    if(!function_exists("find_replace_templatemods")) {
                    //Custom Template Replacement Mod - The other one sucks cock, this one = no more preg replacing!
        function find_replace_templatemods($title, $find, $replace, $autocreate=1)
        {
            global $db, $mybb;
            
            $return = false;
            
            $template_sets = array(-2, -1);
            
            // Select all global with that title
            $query = $db->simple_select("templates", "tid, template", "title = '".$db->escape_string($title)."' AND sid='-1'");
            while($template = $db->fetch_array($query))
            {
                // Update the template if there is a replacement term or a change
                $new_template = str_replace($find, $replace, $template['template']);
                if($new_template == $template['template'])
                {
                    continue;
                }
                
                // The template is a custom template.  Replace as normal.
                $updated_template = array(
                    "template" => $db->escape_string($new_template)
                );
                $db->update_query("templates", $updated_template, "tid='{$template['tid']}'");
            }
            
            // Select all other modified templates with that title
            $query = $db->simple_select("templates", "tid, sid, template", "title = '".$db->escape_string($title)."' AND sid > 0");
            while($template = $db->fetch_array($query))
            {
                // Keep track of which templates sets have a modified version of this template already
                $template_sets[] = $template['sid'];
                
                // Update the template if there is a replacement term or a change
                $new_template = str_replace($find, $replace, $template['template']);
                if($new_template == $template['template'])
                {
                    continue;
                }
                
                // The template is a custom template.  Replace as normal.
                $updated_template = array(
                    "template" => $db->escape_string($new_template)
                );
                $db->update_query("templates", $updated_template, "tid='{$template['tid']}'");
                
                $return = true;
            }
            
            // Add any new templates if we need to and are allowed to
            if($autocreate != 0)
            {
                // Select our master template with that title
                $query = $db->simple_select("templates", "title, template", "title='".$db->escape_string($title)."' AND sid='-2'", array('limit' => 1));
                $master_template = $db->fetch_array($query);
                $master_template['new_template'] = preg_replace($find, $replace, $master_template['template']);
                
                if($master_template['new_template'] != $master_template['template'])
                {
                    // Update the rest of our template sets that are currently inheriting this template from our master set			
                    $query = $db->simple_select("templatesets", "sid", "sid NOT IN (".implode(',', $template_sets).")");
                    while($template = $db->fetch_array($query))
                    {
                        $insert_template = array(
                            "title" => $db->escape_string($master_template['title']),
                            "template" => $db->escape_string($master_template['new_template']),
                            "sid" => $template['sid'],
                            "version" => $mybb->version_code,
                            "status" => '',
                            "dateline" => TIME_NOW
                        );
                        $db->insert_query("templates", $insert_template);
                        
                        $return = true;
                    }
                }
            }
            
            return $return;
        }
        
    }
    
    $rows = $db->fetch_field($query, "rows");

    $repfuck_group = array(
        "gid" => "NULL",
        "name" => "repfuck",
        "title" => "RepFuck Settings",
        "description" => "These settings change the behaviour of RepFuck.",
        "disporder" => $rows+1,
        "isdefault" => "no",
    );

    $db->insert_query("settinggroups", $repfuck_group);
    $gid = $db->insert_id();
	
	
    $setting_1 = array(
        "sid" => "NULL",
        "name" => "repfuckgids",
        "title" => "Usergroup IDs",
        "description" => "Which usergroups are allowed to repfuck?",
        "optionscode" => "text",
        "value" => "3,4",
        "disporder" => "2",
        "gid" => intval($gid),
        );
    $setting_2 = array(
        "sid" => "NULL",
        "name" => "repfucknrep",
        "title" => "Negative Reputation Number",
        "description" => "How much repfuck do you need in order to satisfy yourself? (-20, etc) (0 = disabled)",
        "optionscode" => "text",
        "value" => "-20",
        "disporder" => "3",
        "gid" => intval($gid),
        );    
    $setting_3 = array(
        "sid" => "NULL",
        "name" => "repfucknmsg",
        "title" => "Negative Reputation Comment",
        "description" => "What kind of comment do you need to provide?",
        "optionscode" => "text",
        "value" => "Congratulations, you just got repfucked!  Want a cookie?",
        "disporder" => "4",
        "gid" => intval($gid),
        );
    $setting_4 = array(
        "sid" => "NULL",
        "name" => "repfucktakerep",
        "title" => "Remove Reputation",
        "description" => "Take away all of the users positive reputation?",
        "optionscode" => "onoff",
        "value" => 1,
        "disporder" => "5",
        "gid" => intval($gid),
        );    
        

    $db->insert_query("settings", $setting_1);
    $db->insert_query("settings", $setting_2);
    $db->insert_query("settings", $setting_3);
    $db->insert_query("settings", $setting_4);
	
	
	rebuild_settings();
	
    find_replace_templatemods("reputation_add", '</form>', '</form><br>{$repfuckbutton}');
}

function repfuck_deactivate()
{
	global $mybb, $db, $cache, $templates;
    $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name ='repfuck'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='repfuckgids'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='repfucknrep'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='repfucknmsg'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='repfucktakerep'");
    find_replace_templatemods("reputation_add", '<br>{$repfuckbutton}', '');
}

function repfuck_action()
{
    global $db, $mybb, $session, $plugins, $online_status;
    if($mybb->input['action'] != "repfuck") {
        return;
    }
    
    $dopeeversmoked = "User has been repfucked successfully";
    $error = false;
    
    if (!verify_post_check($mybb->input['my_post_key'], true)) {
        $dopeeversmoked = "Invalid authorization match!";
        $error = true;
    }
    
    if(!in_array($mybb->user['usergroup'], explode(",", $mybb->settings['repfuckgids']))) {
        error_no_permission();
    }
    
    $useruid = intval($mybb->input['rfuid']);
    if($useruid == 0 && !$error) {
        $dopeeversmoked = "Invalid User ID!";
        $error = true;
    }
    
    if(is_super_admin($useruid) && !$error) {
        $dopeeversmoked = "You cannot repfuck that person, they also have permission to repfuck people!";
        $error = true;
    }
    
    $userchk = get_user($useruid);
    
    if(in_array($userchk['usergroup'], explode(",", $mybb->settings['repfuckgids'])) && !is_super_admin($mybb->user['uid']) && !$error) {
        $dopeeversmoked = "You cannot repfuck that person, they also have permission to repfuck people!";
        $error = true;
    }
    
    $nrep = intval($mybb->settings['repfucknrep']);
    $nmsg = stripslashes($db->escape_string($mybb->settings['repfucknmsg']));
    if($error) {
        $dopeeversmoked = "Error: ".$dopeeversmoked;
    }
    $repfuckpage = "<html>
        <head>
        <title>{$mybb->settings['bbname']} - RepFuck</title>
        {$headerinclude}
        </head>
        <body onunload=\"window.opener.location.reload();\">
        <br />
        <table border=\"0\" cellspacing=\"{$theme['borderwidth']}\" cellpadding=\"{$theme['tablespace']}\" class=\"tborder\">
            <tr>
            <td class=\"trow1\" style=\"padding: 20px\">
                    <strong>{$vote_title}</strong><br /><br />
            <blockquote>".$dopeeversmoked."</blockquote>
        <center>        <script type=\"text/javascript\">
                <!--
                document.write('[<a href=\"javascript:window.close();\">Close Window</a>]');
                // -->
                </script></center>
                    </td>
            </tr>
        </table>
        </body>
        </html>";
        
    if(!$error) {
        $db->query("DELETE FROM `".TABLE_PREFIX."reputation` WHERE uid=".$useruid." AND adduid=".$mybb->user['uid'].""); // delete own reputation to prevent multireps.
        
        if($mybb->settings['repfucktakerep'] == 1) {
            $db->query("DELETE FROM `".TABLE_PREFIX."reputation` WHERE uid=".$useruid." AND reputation NOT LIKE '-%' ORDER BY `dateline` DESC");
        }
        
        if($nrep != 0) {
            $db->query("INSERT INTO `".TABLE_PREFIX."reputation` (`uid`, `adduid`, `reputation`, `dateline`, `comments`) VALUES ('".$useruid."', '".$mybb->user['uid']."', '".$nrep."', '".TIME_NOW."', '".$nmsg."')");
        }
    }
    print($repfuckpage);
}

function repfuck_injectbutton()
{
    global $db, $mybb, $session, $plugins, $online_status, $repfuckbutton;
    if(in_array($mybb->user['usergroup'], explode(",", $mybb->settings['repfuckgids']))) {
        $repfuckbutton = "<form action=\"moderation.php?action=repfuck&rfuid=".intval($mybb->input['uid'])."\" method=\"post\"><input type=\"hidden\" name=\"my_post_key\" value=\"".generate_post_check()."\"><input type=\"submit\" name=\"Repfuck\" value=\"Repfuck\" />";
    }
}
?>