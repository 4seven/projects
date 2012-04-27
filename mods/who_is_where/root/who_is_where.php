<?php
/**
*
* @package who_is_where  v.0.0.1
* @version $Id: who_is_where.php 2356 2011-09-04 08:15:36Z 4seven $
* @copyright (c) 2011 / 4seven
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

define('IN_PHPBB', true);
$phpbb_root_path = './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx); 

$user->session_begin();
$auth->acl($user->data);
$user->setup('viewforum');

if($auth->acl_gets('u_viewprofile', 'a_user', 'a_useradd', 'a_userdel')){

header('Content-type: text/html; charset=utf-8');

/*echo '<fieldset style="font-size:12px;font-weight:lighter;">
	  <dl>
	  <dt style="font-weight:600;">Who</dt>	
	  <dd style="font-weight:600;">Where</dd>
	  </dl>
	  <dl>';
*/	  
	  
echo '	<table class="table1" cellspacing="1">

		<thead>
		<tr>&nbsp;</tr>	
		<tr>
			<th class="name" style="width:20%; height:22px; color:grey; padding-left:5px;">Username</a></th>
			<th class="info" style="width:30%; height:22px; color:grey; padding-left:5px;">Location</a></th>
			<th class="active" style="width:15%; height:22px; color:grey; padding-left:5px;">Arrived</a></th>
			<th class="active" style="width:35%; height:22px; color:grey; padding-left:5px;">Browser</a></th>
		</tr>
		</thead>
		<tbody>';  
	  
	  
	function user_get_name($u_id)
	{	
	   global $db;
	   $sql = 'SELECT username, user_colour
	         FROM ' . USERS_TABLE . '
	         WHERE user_id = ' . $db->sql_escape($u_id);
	   $result = $result = $db->sql_query_limit($sql, 1);
	   $row = $db->sql_fetchrow($result);
	   $db->sql_freeresult($result);
   
	   if($row)
	   {
	      return ': ' . get_username_string('no_profile', $u_id, $row['username'], $row['user_colour']);
	   }
	   else
	   {
	      return '';
	   }
	}

	// Forum info
	$sql = 'SELECT forum_id, forum_name, parent_id, forum_type, left_id, right_id
		FROM ' . FORUMS_TABLE . '
		ORDER BY left_id ASC';
	$result = $db->sql_query($sql, 600);
	
	$forum_data = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$forum_data[$row['forum_id']] = $row;
	}
	$db->sql_freeresult($result);
	
	// $sql_hidden = (!$auth->acl_get('u_viewonline')) ? 'AND session_viewonline != 0 AND user_allow_viewonline !=0' : '';

$show_guests = true;

// 	  AND session_page NOT LIKE "%who_is_%"   
	
$sql = 'SELECT DISTINCT(s.session_ip), s.session_user_id, s.session_id, s.session_time, s.session_start, s.session_page, s.session_ip, s.session_browser, s.session_viewonline, s.session_forum_id, u.user_id, u.username, u.username_clean, u.user_type, u.user_colour 
	FROM ' . SESSIONS_TABLE . ' s, ' . USERS_TABLE . ' u 
	WHERE s.session_user_id = u.user_id

		AND s.session_time >= ' . (time() - ($config['load_online_time'] * 60)) .
		((!$show_guests) ? ' AND s.session_user_id <> ' . ANONYMOUS : '') .  '
	GROUP BY session_ip, user_id';		

	$result = $db->sql_query($sql);
	
	// AND group_id != 6
		// 
	while ($row = $db->sql_fetchrow($result))
	{
	
	// var_dump($row);
	
	

	$sizeof_row[] = array();

	preg_match('#^([a-z0-9/_-]+)#i', $row['session_page'], $on_page);
	if (!sizeof($on_page))
	{
		$on_page[1] = '';
	}

	$on_apps    = explode("?",$row['session_page']); 
	$on_apps    = (!empty($on_apps[1])) ? $on_apps[1] : '';

	// old code	
	// $on_apps_u  = ($on_apps && strpos($on_apps, 'u=')) ? user_get_name(str_replace('mode=viewprofile&u=', '', $on_apps)) : '';

	preg_match('#(&u=)([0-9]+)#', $on_apps, $on_ap);

	$on_apps_u  = (!empty($on_ap[2])) ? user_get_name($on_ap[2]) : '';	

	// monitor
	// var_dump($on_ap); 

	switch ($on_page[1])
	{
		case 'index':
			$location = $user->lang['INDEX'];
			$location_url = append_sid("{$phpbb_root_path}index.$phpEx");
		break;

		case 'adm/index':
			$location = $user->lang['ACP'];
			$location_url = append_sid("{$phpbb_root_path}index.$phpEx");
		break;

		case 'posting':
		case 'viewforum':
		case 'viewtopic':
			$forum_id = $row['session_forum_id'];

			if ($forum_id && $auth->acl_get('f_list', $forum_id))
			{
				$location = '';
				$location_url = append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $forum_id);

				if ($forum_data[$forum_id]['forum_type'] == FORUM_LINK)
				{
					$location = sprintf($user->lang['READING_LINK'], $forum_data[$forum_id]['forum_name']);
					break;
				}

				switch ($on_page[1])
				{
					case 'posting':
						preg_match('#mode=([a-z]+)#', $row['session_page'], $on_page);
						$posting_mode = (!empty($on_page[1])) ? $on_page[1] : '';

						switch ($posting_mode)
						{
							case 'reply':
							case 'quote':
								$location = sprintf($user->lang['REPLYING_MESSAGE'], $forum_data[$forum_id]['forum_name']);
							break;

							default:
								$location = sprintf($user->lang['POSTING_MESSAGE'], $forum_data[$forum_id]['forum_name']);
							break;
						}
					break;

					case 'viewtopic':
						$location = sprintf($user->lang['READING_TOPIC'], $forum_data[$forum_id]['forum_name']);
					break;

					case 'viewforum':
						$location = sprintf($user->lang['READING_FORUM'], $forum_data[$forum_id]['forum_name']);
					break;
				}
			}
			else
			{
				$location = $user->lang['INDEX'];
				$location_url = append_sid("{$phpbb_root_path}index.$phpEx");
			}
		break;

		case 'search':
			$location = $user->lang['SEARCHING_FORUMS'];
			$location_url = append_sid("{$phpbb_root_path}search.$phpEx");
		break;

		case 'faq':
			$location = $user->lang['VIEWING_FAQ'];
			$location_url = append_sid("{$phpbb_root_path}faq.$phpEx");
		break;

		case 'viewonline':
			$location = $user->lang['VIEWING_ONLINE'];
			$location_url = append_sid("{$phpbb_root_path}viewonline.$phpEx");
		break;

		case 'memberlist':
			$location = $user->lang['VIEWING_MEMBERS'];

			// Grab some common modules
			$url_params = array(
				'mode=viewprofile'	    => 'VIEWING_MEMBER_PROFILE',
				'mode=profile_views'	=> 'VIEWING_PROFILE_VIEWS',	
			);
			
				foreach ($url_params as $param => $lang)
			{
				if (strpos($row['session_page'], $param) !== false)
				{
					$location = $user->lang[$lang];
					break;
				}
			}
			
			$location_url = append_sid("{$phpbb_root_path}memberlist.$phpEx", $on_apps);
		break;

		case 'mcp':
			$location = $user->lang['VIEWING_MCP'];
			$location_url = append_sid("{$phpbb_root_path}index.$phpEx");
		break;

		case 'ucp':
			$location = $user->lang['VIEWING_UCP'];

			// Grab some common modules
			$url_params = array(
				'mode=register'		=> 'VIEWING_REGISTER',
				'i=pm&mode=compose'	=> 'POSTING_PRIVATE_MESSAGE',
				'i=pm&'				=> 'VIEWING_PRIVATE_MESSAGES',
				'i=profile&'		=> 'CHANGING_PROFILE',
				'i=prefs&'			=> 'CHANGING_PREFERENCES',
			);

			foreach ($url_params as $param => $lang)
			{
				if (strpos($row['session_page'], $param) !== false)
				{
					$location = $user->lang[$lang];
					break;
				}
			}

			$location_url = append_sid("{$phpbb_root_path}index.$phpEx");
		break;

		case 'download/file':
			$location = $user->lang['DOWNLOADING_FILE'];
			$location_url = append_sid("{$phpbb_root_path}index.$phpEx");
		break;

		case 'report':
			$location = $user->lang['REPORTING_POST'];
			$location_url = append_sid("{$phpbb_root_path}index.$phpEx");
		break;
		
		
		case 'annuaire':
			$location = $user->lang['VIEWING_ANNUAIRE'];

			// Grab some common modules
			$url_params = array(
				'mode=cat&id=1'		=> 'VIEWING_ANNUAIRE_CAT_1',
				'mode=cat&id=2'		=> 'VIEWING_ANNUAIRE_CAT_2',
				'mode=cat&id=3'		=> 'VIEWING_ANNUAIRE_CAT_3',		
			);
			
				foreach ($url_params as $param => $lang)
			{
				if (strpos($row['session_page'], $param) !== false)
				{
					$location = $user->lang[$lang];
					break;
				}
			}
			
			$location_url = append_sid("{$phpbb_root_path}annuaire.$phpEx");
		break;

		default:
			$location = $user->lang['INDEX'];
			$location_url = append_sid("{$phpbb_root_path}index.$phpEx");
		break;
	}
	
	$row['username'] = str_replace('Anonymous', $user->lang['GUEST'], $row['username']);

		
		
    echo '<tr class="bg1">';
	
	$get_username = (($row['session_viewonline'] == 1) || ($row['user_allow_viewonline'] == 1)) ? '<td style="width:200px;">' . get_username_string('full', $row['session_user_id'], $row['username'], $row['user_colour']) . '</td>' : '<td style="width:20%;"><span style="font-style:italic;">' . get_username_string('full', $row['session_user_id'], $row['username'], $row['user_colour']) . '</span></td>';

	echo $get_username;
	
	echo '<td class="info" style="width:30%;"><a href="' . $location_url.'" title="'.$location.'">'.$location . '</a>' . $on_apps_u . '</td>';
	
	echo '<td class="active" style="width:15%; color:grey;">' . $user->format_date($row['session_start']) . '</td>';	
	
	echo '<td class="active" style="width:35%; color:grey;">' . (((strlen($row['session_browser'])) > 60) ? (mb_substr($row['session_browser'], 0, 60) . '...') : $row['session_browser']) . '</td>';
	
	echo '</tr>';	

	}

	echo '</tbody>
	</table>';
	
	
	$db->sql_freeresult($result);

	if (!isset($sizeof_row)){
	echo '<dt>Nobody</dt>';	
	echo '<dd>Nowhere</dd>';}

	echo '</dl>
	      </fieldset>';
		  
		  }

?>