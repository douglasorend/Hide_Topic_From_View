<?php
/********************************************************************************
* Subs-ImportantTopics.php - Subs of the Important Topics mod
*********************************************************************************
* This program is distributed in the hope that it is and will be useful, but
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY
* or FITNESS FOR A PARTICULAR PURPOSE,
**********************************************************************************/
if (!defined('SMF'))
	die('Hacking attempt...');

/********************************************************************************
* The functions necessary to list & mark our "important topics" to the user:
********************************************************************************/
function HTFV_Hidden_Topics()
{
	global $context, $txt, $scripturl, $modSettings, $smcFunc, $sourcedir, $user_info;

	// Set up for listing the user's hidden topics:
	$context['page_title' ] = $txt['HTFV_Hidden_Topics'];
	$context['sub_template'] = 'important_topics';

	// Let's check the URL parameters passed before going further:
	if (isset($_GET['sa']))
	{
		if ($_GET['sa'] == 'hide')
		{
			checkSession('get');
			$_GET['topic'] = (int) $_GET['topic'];
			HTFV_Change($_GET['topic'], true);
			redirectExit('topic=' . $_GET['topic'] . '.0');
		}
		elseif ($_GET['sa'] == 'show')
		{
			checkSession('get');
			$_GET['topic'] = (int) $_GET['topic'];
			HTFV_Change($_GET['topic'], false);
			redirectExit('topic=' . $_GET['topic'] . '.0');
		}
		elseif ($_GET['sa'] == 'remove')
		{
			checkSession('post');
			if (empty($_POST['remove']))
				fatal_lang_error('no_topic_id', false);
			if (!is_array($_POST['remove']))
				$_POST['remove'] = array($_POST['remove']);
			foreach ($_POST['remove'] as $topic => $ignore)
				HTFV_Change((int) $topic, false);
			redirectExit('action=profile;area=hidden_topics;u=' . $user_info['id'] . ';' . $context['session_var'] . '=' . $context['session_id']);
		}
		elseif ($_GET['sa'] == 'showall')
		{
			checkSession('post');
			$board = (int) $_POST['board'];
			HTFV_ShowAll($board);
			redirectExit('board=' . $board);
		}
	}

	// Set the options for the list component.
	$topic_listOptions = array(
		'id' => 'important_topics',
		'title' => $txt['HTFV_Hidden_Topics'],
		'items_per_page' => $modSettings['defaultMaxMessages'],
		'base_href' => $scripturl . '?action=profile;area=hidden_topics;u=' . $user_info['id'] . ';' . $context['session_var'] . '=' . $context['session_id'],
		'default_sort_col' => 'lastpost',
		'no_items_label' => $txt['HTFV_no_important_topics'],
		'get_items' => array(
			'function' => 'HTFV_Get_Topics',
		),
		'get_count' => array(
			'function' => 'HTFV_Topics_Count',
		),
		'columns' => array(
			'subject' => array(
				'header' => array(
					'value' => $txt['topics'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $scripturl, $txt;
						$board = \'<strong><a href="\' . $scripturl . \'?board=\' . $rowData["id_board"] . \'.0">\' . $rowData[\'board_name\'] . \'</a></strong>\';
						$topic = \'<strong><a href="\' . $scripturl . \'?topic=\' . $rowData["id_topic"] . \'.0">\' . $rowData[\'first_subject\'] . \'</a></strong>\';
						$user = \'<strong><a href="\' . $scripturl . \'?action=home;user=\' . $rowData["first_member"] . \'">\' . $rowData[\'first_poster\'] . \'</a></strong>\';
						return $board . " \\\\ " . $topic . \'<div class="smalltext">\' . $txt["started_by"] . " " . $user . \'</div>\';
					'),
				),
				'sort' => array(
					'default' => 'b.name, mf.subject',
					'reverse' => 'b.name DESC, mf.subject DESC',
				),
			),
			'replies' => array(
				'header' => array(
					'value' => $txt['replies'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						return comma_format($rowData[\'num_replies\']);
					'),
					'style' => 'text-align: center; width: 7%',
				),
				'sort' => array(
					'default' => 't.num_replies',
					'reverse' => 't.num_replies DESC',
				),
			),
			'views' => array(
				'header' => array(
					'value' => $txt['views'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						return comma_format($rowData[\'num_views\']);
					'),
					'style' => 'text-align: center; width: 7%',
				),
				'sort' => array(
					'default' => 't.num_views',
					'reverse' => 't.num_views DESC',
				),
			),
			'lastpost' => array(
				'header' => array(
					'value' => $txt['last_post'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $scripturl, $txt;
						$user = \'<strong><a href=\"\' . $scripturl . \'?action=home;user=\' . $rowData["last_member"] . \'">\' . $rowData[\'last_poster\'] . \'</a></strong>\';
						return "<strong>" . $txt["last_post"] . "</strong> " . $txt["by"] . " " . $user . \'<div class="smalltext">\' . timeformat($rowData[\'last_posted\']);
					'),
					'style' => 'width: 30%',
				),
				'sort' => array(
					'default' => 'ml.poster_time',
					'reverse' => 'ml.poster_time DESC',
				),
			),
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="remove[%1$d]" class="input_check" />',
						'params' => array(
							'id_topic' => false,
						),
					),
					'style' => 'text-align: center; width: 30px',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=profile;area=hidden_topics;sa=remove;u=' . $user_info['id'] . ';' . $context['session_var'] . '=' . $context['session_id'],
			'include_sort' => true,
			'include_start' => true,
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '<input type="submit" name="remove_submit" class="button_submit" value="' . $txt['HTFV_unmark_as_important'] . '" onclick="return confirm(\'' . $txt['HTFV_unmark_confirm'] . '\');" />',
				'style' => 'text-align: right;',
			),
		),
	);

	// Create the list.
	require_once($sourcedir . '/Subs-List.php');
	createList($topic_listOptions);
}

function HTFV_Topics_Count()
{
	return count($_SESSION['hide_topics']) - 1;
}

function HTFV_Get_Topics($start, $items_per_page, $sort)
{
	global $smcFunc;
	
	if (HTFV_Topics_Count() == 1)
		return array();
	$request = $smcFunc['db_query']('', '
		SELECT
			t.id_topic, t.num_replies, t.num_views, t.id_first_msg, b.id_board, b.name AS board_name,
			mf.id_member AS first_member, COALESCE(meml.real_name, ml.poster_name) AS last_poster, 
			ml.id_member AS last_member, COALESCE(memf.real_name, mf.poster_name) AS first_poster, 
			mf.subject AS first_subject, mf.poster_time AS first_posted,
			ml.subject AS last_subject, ml.poster_time AS last_posted
		FROM {db_prefix}topics AS t
			INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
			INNER JOIN {db_prefix}messages AS mf ON (mf.id_msg = t.id_first_msg)
			INNER JOIN {db_prefix}messages AS ml ON (ml.id_msg = t.id_last_msg)
			LEFT JOIN {db_prefix}members AS memf ON (memf.id_member = mf.id_member)
			LEFT JOIN {db_prefix}members AS meml ON (meml.id_member = ml.id_member)
		WHERE {query_see_board}
			AND t.id_topic IN ({array_int:hide_topics})
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:per_page}',
		array(
			'sort' => $sort,
			'start' => $start,
			'per_page' => $items_per_page,
			'hide_topics' => $_SESSION['hide_topics'],
		)
	);
	$topics = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$topics[] = $row;
	$smcFunc['db_free_result']($request);
	return $topics;
}

/********************************************************************************
* Some sub-functions of this mod:
********************************************************************************/
function HTFV_Change($topic = 0, $hide_topic = false)
{
	global $user_info, $smcFunc;

	// Abort if we are not supposed to be here!
	if (empty($user_info['id']) || empty($topic))
		fatal_lang_error('no_topic_id', false);
	
	// Get the board number that this topic resides in:
	$request = $smcFunc['db_query']('', '
		SELECT id_board
		FROM {db_prefix}topics
		WHERE id_topic IN ({int:id_topic})',
		array(
			'id_topic' => $topic = (int) $topic,
		)
	);
	list($board) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	if ($hide_topic)
	{
		// Can't do anything if the board ID is empty, either....
		$user = (int) $user_info['id'];
		if (empty($board))
			continue;

		// Insert the row into the hide_topics table:
		$smcFunc['db_insert']('insert',
			'{db_prefix}hide_topics',
			array('id_member' => 'int', 'id_topic' => 'int', 'id_board' => 'int'),
			array($user, $topic, (int) $board),
			array('id_member', 'id_topic', 'id_board')
		);
	}
	else
	{
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
	}

	// Force recache of the hidden topics for this user, then go to the board:
	unset($_SESSION['hide_topics']);
	return $board;
}

function HTFV_ShowAll($board = 0)
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

/********************************************************************************
* Our stupid, short template function: a necessary evil....
********************************************************************************/
function template_important_topics()
{
	template_show_list('important_topics');
}

?>