<?php

define("token", "-");

function request($method, $data = [])
{
	$url = "https://api.telegram.org/bot" . token . "/" . $method;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	$result = curl_exec($ch);
	if (curl_error($ch)) {
		var_dump(curl_error($ch));
		curl_close($ch);
	} else {
		curl_close($ch);
		return json_decode($result);
	}
}

class MineSweeper
{

	private $grid = array();
	private $number_of_mines = NULL;
	private $game_id = NULL;

	public function __construct($id = 0, $rows = 8, $columns = 7, $mines = 10)
	{
		for ($row = 0; $row < $rows; $row++) {
			for ($column = 0; $column < $columns; $column++) {
				$this->grid[$row][$column] = NULL;
			}
		}

		$this->number_of_mines = $mines;
		$this->game_id = $id;
		$this->placeMines();
		$this->calculateHints();
	}

	public function getGrid()
	{
		return $this->grid;
	}

	public function setGrid($row, $column, array $data)
	{
		$this->grid[$row][$column] = $data;
	}

	public function getColumns()
	{
		return count($this->grid[0]);
	}

	public function getRows()
	{
		return count($this->grid);
	}

	public function getNumberOfMines()
	{
		return $this->number_of_mines;
	}

	public function getGameID()
	{
		return $this->game_id;
	}

	public function placeMines()
	{
		$minesPlaced = 0;
		$mines = $this->getNumberOfMines();
		$rowNum = $this->getRows();
		$colNum = $this->getColumns();
		$id = $this->getGameID();
		while ($minesPlaced < $mines) {
			$row = mt_rand(0, $rowNum - 1);
			$column = mt_rand(0, $colNum - 1);
			if ($this->getGrid()[$row][$column] == NULL) {
				$data = $id . "i" . (($row * ($rowNum - 1)) + $column) . "nbh";
				$this->setGrid($row, $column, array("text" => "\xe2\xac\x9c", "callback_data" => $data));
				$minesPlaced++;
			}
		}
	}

	public function calculateHints()
	{
		$rowNum = $this->getRows();
		$colNum = $this->getColumns();
		for ($row = 0; $row < $rowNum; $row++) {
			for ($column = 0; $column < $colNum; $column++) {
				if ($this->getGrid()[$row][$column] == NULL) {
					$this->setGrid($row, $column, $this->minesNear($row, $column));
				}
			}
		}
	}

	public function minesNear($row, $column)
	{
		$rowNum = $this->getRows();
		$colNum = $this->getColumns();
		$id = $this->getGameID();
		$mines = 0;

		$mines += $this->mineAt($row - 1, $column - 1, $rowNum, $colNum);
		$mines += $this->mineAt($row - 1, $column, $rowNum, $colNum);
		$mines += $this->mineAt($row - 1, $column + 1, $rowNum, $colNum);
		$mines += $this->mineAt($row, $column - 1, $rowNum, $colNum);
		$mines += $this->mineAt($row, $column + 1, $rowNum, $colNum);
		$mines += $this->mineAt($row + 1, $column - 1, $rowNum, $colNum);
		$mines += $this->mineAt($row + 1, $column, $rowNum, $colNum);
		$mines += $this->mineAt($row + 1, $column + 1, $rowNum, $colNum);
		if ($mines > 0) {
			$data = $id . "i" . (($row * ($rowNum - 1)) + $column) . "n" . $mines . "h";
			return array("text" => "\xe2\xac\x9c", "callback_data" => $data);
		} else {
			$data = $id . "i" . (($row * ($rowNum - 1)) + $column) . "neh";
			return array("text" => "\xe2\xac\x9c", "callback_data" => $data);
		}
	}

	public function mineAt($row, $column, $rowNum, $colNum)
	{
		if (
			$row >= 0 && $row < $rowNum
			&& $column >= 0 && $column < $colNum
			&& $this->getGrid()[$row][$column] != NULL
			&& strpos($this->getGrid()[$row][$column]["callback_data"], "nbh") !== false
		) {
			return 1;
		} else {
			return 0;
		}
	}
}

function convertNum2Emo($str)
{
	$emoji = [
		"\x30\xe2\x83\xa3", "\x31\xe2\x83\xa3", "\x32\xe2\x83\xa3",
		"\x33\xe2\x83\xa3", "\x34\xe2\x83\xa3", "\x35\xe2\x83\xa3",
		"\x36\xe2\x83\xa3", "\x37\xe2\x83\xa3", "\x38\xe2\x83\xa3"
	];
	$num = range(0, 8);
	$emojiNum = str_replace($num, $emoji, $str);

	return $emojiNum;
}

function createNewSingle($chat_id, $rows, $columns, $mines)
{
	$conn = connectDB();
	$sql = "INSERT INTO game (user_id, created_at)
			VALUES ('" . $chat_id . "', '" . date("Y-m-d") . "')";
	$conn->query($sql);

	$game_id = $conn->insert_id;
	$mineSweeper = new MineSweeper($game_id, $rows, $columns, $mines);
	$grid = $mineSweeper->getGrid();

	$data_1 = $game_id . "i0nch";
	$data_2 = $game_id . "i" . $mines . "nds";
	$grid[$rows] = array(
		array("text" => "تغییر حالت به \xf0\x9f\x9a\xa9", "callback_data" => $data_1),
		array("text" => "\xe2\x9d\x93 راهنمای حالت \xf0\x9f\x92\xa3", "callback_data" => $data_2)
	);

	$key_game = array(
		"inline_keyboard" => $grid
	);
	$json_key_game = json_encode($key_game);
	var_dump(
		request("sendMessage", [
			"chat_id" => $chat_id,
			"text" => "مین‌های باقی مانده: *" . $mines
				. "*\nحالت فعلی: \xf0\x9f\x92\xa3 (اشتباه \xf0\x9f\x98\xa7 = انفجار \xf0\x9f\x92\xa5)"
				. "\n@mine",
			"parse_mode" => "markdown",
			"reply_markup" => $json_key_game
		])
	);
	$json_key_game = str_replace("\\", "\\\\", $json_key_game);
	$sql = "UPDATE game SET json_key='" . $json_key_game . "'
	        WHERE id=" . $game_id;
	$conn->query($sql);
	$conn->close();
}

function createNewMulti($user_id)
{
	$conn = connectDB();
	$sql = "SELECT id, inline_name FROM user
	        WHERE id!='" . $user_id . "' AND request='1'
			ORDER BY RAND()
			LIMIT 1";
	$result = $conn->query($sql);
	if ($result->num_rows == 0) {
		$sql = "UPDATE user SET request='1'
				WHERE id=" . $user_id;
		$conn->query($sql);
		var_dump(
			request("sendMessage", [
				"chat_id" => $user_id,
				"text" => "\xe2\x9a\xa0 هم بازی پیدا نشد!\n"
					. "\xe2\x8f\xb3 کمی منتظر بمانید.."
			])
		);
	} else {
		$row_friend = $result->fetch_assoc();
		$sql = "SELECT inline_name FROM user
				WHERE id=" . $user_id;
		$result = $conn->query($sql);
		$row_user = $result->fetch_assoc();
		$sql = "UPDATE user SET request='0'
				WHERE id=" . $row_friend["id"];
		$conn->query($sql);

		$sql = "INSERT INTO game (user_id, friend_id, turn, created_at)
				VALUES ('" . $row_friend["id"] . "', '" . $user_id . "', '" . $row_friend["id"] . "', '" . date("Y-m-d") . "')";
		$conn->query($sql);

		$game_id = $conn->insert_id;
		$mineSweeper = new MineSweeper($game_id, 8, 7, 15);
		$grid = $mineSweeper->getGrid();

		$data_1 = $user_id . "i0nus";
		$data_2 = $row_friend["id"] . "i0nus";
		$text_1 =  "\x30\xe2\x83\xa3 " . $row_user["inline_name"] . " \xf0\x9f\x94\xb4";
		$text_2 = "\xf0\x9f\x8e\xae \x30\xe2\x83\xa3 " . $row_friend["inline_name"] . " \xf0\x9f\x94\xb5";
		$grid[8] = array(
			array("text" => $text_1, "callback_data" => $data_1),
			array("text" => $text_2, "callback_data" => $data_2)
		);
		$data_1 = $game_id . "i0ngs";
		$data_2 = $game_id . "i" . time() . "nws";
		$grid[9] = array(
			array("text" => "\xf0\x9f\x92\xac چت با حریف!", "callback_data" => $data_1),
			array("text" => "\xe2\x9c\x82 خاتمه بازی", "callback_data" => $data_2)
		);

		$key_game = array(
			"inline_keyboard" => $grid
		);
		$json_key_game = json_encode($key_game);
		var_dump(
			request("sendMessage", [
				"chat_id" => $row_friend["id"],
				"text" => "\xf0\x9f\x8e\xae #نوبت\_بازی: " . $row_friend["inline_name"] . " \xf0\x9f\x94\xb5\n"
					. "\xf0\x9f\x92\xa3 مین‌های باقی مانده: *15*\n"
					. "\xf0\x9f\x93\xa2 @mine",
				"parse_mode" => "markdown",
				"reply_markup" => $json_key_game
			])
		);
		var_dump(
			request("sendMessage", [
				"chat_id" => $user_id,
				"text" => "\xf0\x9f\x8e\xae نوبت بازی: " . $row_friend["inline_name"] . " \xf0\x9f\x94\xb5\n"
					. "\xf0\x9f\x92\xa3 مین‌های باقی مانده: *15*\n"
					. "\xf0\x9f\x93\xa2 @mine",
				"parse_mode" => "markdown",
				"reply_markup" => $json_key_game
			])
		);
		$json_key_game = str_replace("\\", "\\\\", $json_key_game);
		$sql = "UPDATE game SET json_key='" . $json_key_game . "'
				WHERE id=" . $game_id;
		$conn->query($sql);
	}
	$result->free();
	$conn->close();
}

function createNewMulti($user_id, $friend_id)
{
	$conn = connectDB();
	$sql = "SELECT inline_name FROM user
	        WHERE id='" . $user_id . "' OR id='" . $friend_id . "'
			LIMIT 2";
	$result = $conn->query($sql);

	$row_friend = $result->fetch_assoc();

	$sql = "INSERT INTO game (user_id, friend_id, turn, created_at)
				VALUES ('" . $row_friend["id"] . "', '" . $user_id . "', '" . $row_friend["id"] . "', '" . date("Y-m-d") . "')";
	$conn->query($sql);

	$game_id = $conn->insert_id;
	$mineSweeper = new MineSweeper($game_id, 8, 7, 15);
	$grid = $mineSweeper->getGrid();

	$data_1 = $user_id . "i0nus";
	$data_2 = $row_friend["id"] . "i0nus";
	$text_1 =  "\x30\xe2\x83\xa3 " . $row_user["inline_name"] . " \xf0\x9f\x94\xb4";
	$text_2 = "\xf0\x9f\x8e\xae \x30\xe2\x83\xa3 " . $row_friend["inline_name"] . " \xf0\x9f\x94\xb5";
	$grid[8] = array(
		array("text" => $text_1, "callback_data" => $data_1),
		array("text" => $text_2, "callback_data" => $data_2)
	);
	$data_1 = $game_id . "i0ngs";
	$data_2 = $game_id . "i" . time() . "nws";
	$grid[9] = array(
		array("text" => "\xf0\x9f\x92\xac چت با حریف!", "callback_data" => $data_1),
		array("text" => "\xe2\x9c\x82 خاتمه بازی", "callback_data" => $data_2)
	);

	$key_game = array(
		"inline_keyboard" => $grid
	);
	$json_key_game = json_encode($key_game);
	var_dump(
		request("sendMessage", [
			"chat_id" => $row_friend["id"],
			"text" => "\xf0\x9f\x8e\xae #نوبت\_بازی: " . $row_friend["inline_name"] . " \xf0\x9f\x94\xb5\n"
				. "\xf0\x9f\x92\xa3 مین‌های باقی مانده: *15*\n"
				. "\xf0\x9f\x93\xa2 @mine",
			"parse_mode" => "markdown",
			"reply_markup" => $json_key_game
		])
	);
	var_dump(
		request("sendMessage", [
			"chat_id" => $user_id,
			"text" => "\xf0\x9f\x8e\xae نوبت بازی: " . $row_friend["inline_name"] . " \xf0\x9f\x94\xb5\n"
				. "\xf0\x9f\x92\xa3 مین‌های باقی مانده: *15*\n"
				. "\xf0\x9f\x93\xa2 @mine",
			"parse_mode" => "markdown",
			"reply_markup" => $json_key_game
		])
	);
	$json_key_game = str_replace("\\", "\\\\", $json_key_game);
	$sql = "UPDATE game SET json_key='" . $json_key_game . "'
				WHERE id=" . $game_id;
	$conn->query($sql);
	$result->free();
	$conn->close();
}

function updateGameKeyEmpty(array $grid, $row, $col)
{
	$rowNum = (sizeof($grid) - 1);
	$colNum = sizeof($grid[0]);

	if ($row - 1 >= 0 && $col - 1 >= 0) {
		$data = $grid[$row - 1][$col - 1]->callback_data;
		$item = substr($data, strpos($data, "n") + 1, 1);
		$hint = substr($data, strpos($data, "n") + 2, 1);
		if ($hint == "h" && $item > 0 && $item <= 8) {
			$grid[$row - 1][$col - 1]->text = convertNum2Emo($item);
			$grid[$row - 1][$col - 1]->callback_data = str_replace("h", "s", $data);
		} elseif ($hint == "h" && $item == "e") {
			$grid[$row - 1][$col - 1]->text = " ";
			$grid[$row - 1][$col - 1]->callback_data = str_replace("h", "s", $data);
			$grid = updateGameKeyEmpty($grid, $row - 1, $col - 1);
		}
	}
	if ($row - 1 >= 0) {
		$data = $grid[$row - 1][$col]->callback_data;
		$item = substr($data, strpos($data, "n") + 1, 1);
		$hint = substr($data, strpos($data, "n") + 2, 1);
		if ($hint == "h" && $item > 0 && $item <= 8) {
			$grid[$row - 1][$col]->text = convertNum2Emo($item);
			$grid[$row - 1][$col]->callback_data = str_replace("h", "s", $data);
		} elseif ($hint == "h" && $item == "e") {
			$grid[$row - 1][$col]->text = " ";
			$grid[$row - 1][$col]->callback_data = str_replace("h", "s", $data);
			$grid = updateGameKeyEmpty($grid, $row - 1, $col);
		}
	}
	if ($row - 1 >= 0 && $col + 1 < $colNum) {
		$data = $grid[$row - 1][$col + 1]->callback_data;
		$item = substr($data, strpos($data, "n") + 1, 1);
		$hint = substr($data, strpos($data, "n") + 2, 1);
		if ($hint == "h" && $item > 0 && $item <= 8) {
			$grid[$row - 1][$col + 1]->text = convertNum2Emo($item);
			$grid[$row - 1][$col + 1]->callback_data = str_replace("h", "s", $data);
		} elseif ($hint == "h" && $item == "e") {
			$grid[$row - 1][$col + 1]->text = " ";
			$grid[$row - 1][$col + 1]->callback_data = str_replace("h", "s", $data);
			$grid = updateGameKeyEmpty($grid, $row - 1, $col + 1);
		}
	}
	if ($col - 1 >= 0) {
		$data = $grid[$row][$col - 1]->callback_data;
		$item = substr($data, strpos($data, "n") + 1, 1);
		$hint = substr($data, strpos($data, "n") + 2, 1);
		if ($hint == "h" && $item > 0 && $item <= 8) {
			$grid[$row][$col - 1]->text = convertNum2Emo($item);
			$grid[$row][$col - 1]->callback_data = str_replace("h", "s", $data);
		} elseif ($hint == "h" && $item == "e") {
			$grid[$row][$col - 1]->text = " ";
			$grid[$row][$col - 1]->callback_data = str_replace("h", "s", $data);
			$grid = updateGameKeyEmpty($grid, $row, $col - 1);
		}
	}
	if ($col + 1 < $colNum) {
		$data = $grid[$row][$col + 1]->callback_data;
		$item = substr($data, strpos($data, "n") + 1, 1);
		$hint = substr($data, strpos($data, "n") + 2, 1);
		if ($hint == "h" && $item > 0 && $item <= 8) {
			$grid[$row][$col + 1]->text = convertNum2Emo($item);
			$grid[$row][$col + 1]->callback_data = str_replace("h", "s", $data);
		} elseif ($hint == "h" && $item == "e") {
			$grid[$row][$col + 1]->text = " ";
			$grid[$row][$col + 1]->callback_data = str_replace("h", "s", $data);
			$grid = updateGameKeyEmpty($grid, $row, $col + 1);
		}
	}
	if ($row + 1 < $rowNum && $col - 1 >= 0) {
		$data = $grid[$row + 1][$col - 1]->callback_data;
		$item = substr($data, strpos($data, "n") + 1, 1);
		$hint = substr($data, strpos($data, "n") + 2, 1);
		if ($hint == "h" && $item > 0 && $item <= 8) {
			$grid[$row + 1][$col - 1]->text = convertNum2Emo($item);
			$grid[$row + 1][$col - 1]->callback_data = str_replace("h", "s", $data);
		} elseif ($hint == "h" && $item == "e") {
			$grid[$row + 1][$col - 1]->text = " ";
			$grid[$row + 1][$col - 1]->callback_data = str_replace("h", "s", $data);
			$grid = updateGameKeyEmpty($grid, $row + 1, $col - 1);
		}
	}
	if ($row + 1 < $rowNum) {
		$data = $grid[$row + 1][$col]->callback_data;
		$item = substr($data, strpos($data, "n") + 1, 1);
		$hint = substr($data, strpos($data, "n") + 2, 1);
		if ($hint == "h" && $item > 0 && $item <= 8) {
			$grid[$row + 1][$col]->text = convertNum2Emo($item);
			$grid[$row + 1][$col]->callback_data = str_replace("h", "s", $data);
		} elseif ($hint == "h" && $item == "e") {
			$grid[$row + 1][$col]->text = " ";
			$grid[$row + 1][$col]->callback_data = str_replace("h", "s", $data);
			$grid = updateGameKeyEmpty($grid, $row + 1, $col);
		}
	}
	if ($row + 1 < $rowNum && $col + 1 < $colNum) {
		$data = $grid[$row + 1][$col + 1]->callback_data;
		$item = substr($data, strpos($data, "n") + 1, 1);
		$hint = substr($data, strpos($data, "n") + 2, 1);
		if ($hint == "h" && $item > 0 && $item <= 8) {
			$grid[$row + 1][$col + 1]->text = convertNum2Emo($item);
			$grid[$row + 1][$col + 1]->callback_data = str_replace("h", "s", $data);
		} elseif ($hint == "h" && $item == "e") {
			$grid[$row + 1][$col + 1]->text = " ";
			$grid[$row + 1][$col + 1]->callback_data = str_replace("h", "s", $data);
			$grid = updateGameKeyEmpty($grid, $row + 1, $col + 1);
		}
	}

	return $grid;
}

function updateMultiKey($from_id, $message_id, $message_text, $data)
{


	$key_game = array(
		"inline_keyboard" => $grid
	);
	$json_key_game = json_encode($key_game);
	if ($create_user == $from_id) {
		var_dump(
			request("editMessageText", [
				"chat_id" => $from_id,
				"message_id" => $message_id,
				"text" => $message_text,
				"parse_mode" => "markdown",
				"reply_markup" => $json_key_game
			])
		);
		var_dump(
			request("editMessageText", [
				"chat_id" => $friend_user,
				"message_id" => $message_id + 1,
				"text" => $message_text,
				"parse_mode" => "markdown",
				"reply_markup" => $json_key_game
			])
		);
	} else {
		var_dump(
			request("editMessageText", [
				"chat_id" => $from_id,
				"message_id" => $message_id,
				"text" => $message_text,
				"parse_mode" => "markdown",
				"reply_markup" => $json_key_game
			])
		);
		var_dump(
			request("editMessageText", [
				"chat_id" => $create_user,
				"message_id" => $message_id - 1,
				"text" => $message_text,
				"parse_mode" => "markdown",
				"reply_markup" => $json_key_game
			])
		);
	}
}

function updateSingleKey($from_id, $message_id, $message_text, $data)
{


	$key_game = array(
		"inline_keyboard" => $grid
	);
	$json_key_game = json_encode($key_game);
	var_dump(
		request("editMessageText", [
			"chat_id" => $from_id,
			"message_id" => $message_id,
			"text" => $message_text,
			"parse_mode" => "markdown",
			"reply_markup" => $json_key_game
		])
	);
}


// get new incoming update
$update = json_decode(file_get_contents("php://input"));


if (isset($update->callback_query)) {
	// get callback query fields from update
	$id = $update->callback_query->id;
	$from_id = $update->callback_query->from->id;
	$message_id = $update->callback_query->message->message_id;
	$message_text = $update->callback_query->message->text;
	$data = $update->callback_query->data;
} else {
	// get message fields from update
	$from_id = $update->message->from->id;
	$text = $update->message->text;
}
