<?php
/********************************************************************************
* Subs-HideTopics.php - Subs of the Hide Topics From View mod
*********************************************************************************
* This program is distributed in the hope that it is and will be useful, but
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY
* or FITNESS FOR A PARTICULAR PURPOSE,
**********************************************************************************/
if (!defined('SMF'))
	die('Hacking attempt...');

function HTFV_Load()
{
	global $smcFunc, $user_info;

	// Are we a guest?  Yeah, guests can't hide topics!
	if (empty($user_info['id']))
	{
		$_SESSION['hide_topics'] = $_SESSION['affected_boards'] = array();
		return;
	}

	// If the list of topics they don't want to see isn't loaded, let's load it right now:
	if (!isset($_SESSION['hide_topics']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_topic, id_board
			FROM {db_prefix}hide_topics
			WHERE id_member = {int:id_member}',
			array(
				'id_member' => (int) $user_info['id'],
			)
		);
		$hide_topics = $affected_boards = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$hide_topics[] = $row['id_topic'];
			$affected_boards[$row['id_board']] = $row['id_board'];
		}
		$smcFunc['db_free_result']($request);
		$_SESSION['hide_topics'] = $hide_topics;
		$_SESSION['affected_boards'] = $affected_boards;
	}
}

function HTFV_Actions(&$actions)
{
	$actions['htfv_hide'] = array('Subs-HideTopics.php', 'HTFV_Change');
	$actions['htfv_show'] = array('Subs-HideTopics.php', 'HTFV_Change');
	$actions['htfv_showall'] = array('Subs-HideTopics.php', 'HTFV_ShowAll');
}

function HTFV_Change()
{
	global $user_info, $smcFunc, $topic;

	// Abort if we are not supposed to be here!
	if (empty($user_info['id']) || empty($topic))
		fatal_lang_error('no_topic_id', false);

	// Get the board number that this topic resides in:
	$request = $smcFunc['db_query']('', '
		SELECT id_board
		FROM {db_prefix}topics
		WHERE id_topic = {int:id_topic}',
		array(
			'id_topic' => (int) $topic,
		)
	);
	list($board) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Remove any existing entries for this topic:
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}hide_topics
		WHERE id_member = {int:id_member}
			AND id_topic = {int:id_topic}',
		array(
			'id_member' => (int) $user_info['id'],
			'id_topic' => (int) $topic,
		)
	);

	// If we are hiding the topic, do so here:
	if ($_GET['action'] == 'htfv_hide')
	{
		$smcFunc['db_insert']('insert',
			'{db_prefix}hide_topics',
			array('id_member' => 'int', 'id_topic' => 'int', 'id_board' => 'int'),
			array((int) $user_info['id'], (int) $topic, (int) $board),
			array('id_member', 'id_topic', 'id_board')
		);
	}
	
	// Force recache of the hidden topics for this user, then go to the board:
	unset($_SESSION['hide_topics']);
	redirectExit('board=' . $board);
}

function HTFV_ShowAll()
{
	global $user_info, $smcFunc, $board;

	// Abort if we are not supposed to be here!
	if (empty($user_info['id']) || empty($board))
		fatal_lang_error('no_board', false);

	// Remove any existing entries for this topic:
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}hide_topics
		WHERE id_member = {int:id_member}
			AND id_board = {int:id_board}',
		array(
			'id_member' => (int) $user_info['id'],
			'id_board' => (int) $board,
		)
	);

	// Force recache of the hidden topics for this user, then go to the board:
	unset($_SESSION['hide_topics']);
	redirectExit('board=' . $board);
}

function HTFV_MessageIndex(&$buttons)
{
	global $board, $scripturl, $user_info;

	if (!empty($user_info['id']) && !empty($board) && in_array($board, $_SESSION['affected_boards']))
		$buttons['htfv_showall'] = array(
			'text' => 'htfv_showall', 
			'lang' => true, 
			'url' => $scripturl . '?action=htfv_showall;board=' . $board,
		);
}

function HTFV_Display(&$buttons)
{
	global $topic, $scripturl, $user_info;

	if (empty($user_info['id']) || empty($topic))
		return;
	if (in_array($topic, $_SESSION['hide_topics']))
		$buttons['htfv_show'] = array(
			'text' => 'htfv_show', 
			'lang' => true, 
			'url' => $scripturl . '?action=htfv_show;topic=' . $topic,
		);
	else
		$buttons['htfv_hide'] = array(
			'text' => 'htfv_hide', 
			'lang' => true, 
			'url' => $scripturl . '?action=htfv_hide;topic=' . $topic,
		);
}

function HTFV_Process(&$board)
{
	global $user_info, $smcFunc;

	// We can ignore guests, as well as boards that the last topic posted in isn't in the "hide topics" list:
	if (empty($user_info['id']) || empty($_SESSION['hide_topics']) || !in_array($board['id_topic'], $_SESSION['hide_topics']))
		return;
		
	// Let's find the most recent topic that isn't in the "hide topics" list:
	$result = $smcFunc['db_query']('', '
		SELECT 
			IFNULL(m.poster_time, 0) AS poster_time, IFNULL(mem.member_name, m.poster_name) AS poster_name,
			m.subject, m.id_topic, IFNULL(mem.real_name, m.poster_name) AS real_name, t.id_topic,
			' . ($user_info['is_guest'] ? ' 1 AS is_read, 0 AS new_from' : '
			(IFNULL(lb.id_msg, 0) >= b.id_msg_updated) AS is_read, IFNULL(lb.id_msg, -1) + 1 AS new_from') . '
		FROM {db_prefix}topics AS t
			LEFT JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
			LEFT JOIN {db_prefix}messages AS m ON (m.id_msg = t.id_last_msg)
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = m.id_member)
			LEFT JOIN {db_prefix}log_boards AS lb ON (lb.id_board = b.id_board AND lb.id_member = {int:current_member})
		WHERE t.id_topic NOT IN ({array_int:hide_topics})
			AND t.id_board = {int:id_board}
		ORDER BY t.id_last_msg DESC
		LIMIT 1',
		array(
			'hide_topics' => $_SESSION['hide_topics'],
			'id_board' => $board['id_board'],
			'current_member' => $user_info['id'],
		)
	);
	$row = $smcFunc['db_fetch_assoc']($result);
	$smcFunc['db_free_result']($result);
	
	// If the row isn't empty, overwrite the necessary board information fields:
	if (!empty($row))
		foreach ($row as $variable => $value)
			$board[$variable] = $value;
}

?>