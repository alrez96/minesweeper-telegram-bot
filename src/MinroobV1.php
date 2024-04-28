<?php

define("token", "-");

class MineSweeper
{

	private $grid = array();
	private $number_of_mines = NULL;
	private $game_id = NULL;

	public function __construct($id = NULL, $rows = 8, $columns = 7, $mines = 10)
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

function connectDB()
{
	$servername = "localhost";
	$username = "botir_bot";
	$password = "-";
	$dbname = "botir_test";

	$conn = new mysqli($servername, $username, $password, $dbname);
	$conn->set_charset("utf8mb4");
	return $conn;
}

function newUser($data)
{
	$conn = connectDB();
	$sql = "INSERT INTO user (id, first_name, last_name, inline_name, username)
			VALUES ('" . $data[0] . "', '" . $data[1] . "', '" . $data[2] . "',
					'" . $data[3] . "', '" . $data[4] . "')";
	$conn->query($sql);
	$conn->close();
}

function findUser($user_id)
{
	$conn = connectDB();
	$sql = "SELECT id FROM user
	        WHERE id=" . $user_id;
	$result = $conn->query($sql);
	if ($result->num_rows == 0) {
		$result->free();
		$conn->close();
		return false;
	} else {
		$result->free();
		$conn->close();
		return true;
	}
}

function createNewGameSingle($user_id, $rows, $columns, $mines)
{
	$conn = connectDB();
	$sql = "INSERT INTO game (player_host)
			VALUES ('" . $user_id . "')";
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
			"chat_id" => $user_id,
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

function createNewGameMulti($user_id)
{
	$conn = connectDB();
	$sql = "SELECT id, inline_name FROM user
	        WHERE id!='" . $user_id . "' AND request_for_multiplayer='1'
			ORDER BY RAND()
			LIMIT 1";
	$result = $conn->query($sql);
	if ($result->num_rows == 0) {
		$sql = "UPDATE user SET request_for_multiplayer='1'
				WHERE id=" . $user_id;
		$conn->query($sql);
		var_dump(
			request("sendMessage", [
				"chat_id" => $user_id,
				"text" => "\xe2\x8f\xb3 کمی منتظر بمانید..."
			])
		);
	} else {
		$row_host = $result->fetch_assoc();
		$sql = "SELECT inline_name FROM user
				WHERE id=" . $user_id;
		$result = $conn->query($sql);
		$row_guest = $result->fetch_assoc();
		$sql = "UPDATE user SET request_for_multiplayer='0'
				WHERE id=" . $row_host["id"];
		$conn->query($sql);

		$sql = "INSERT INTO game (player_host, player_guest, player_last)
				VALUES ('" . $row_host["id"] . "', '" . $user_id . "', '" . $row_host["id"] . "')";
		$conn->query($sql);

		$game_id = $conn->insert_id;
		$mineSweeper = new MineSweeper($game_id, 8, 7, 15);
		$grid = $mineSweeper->getGrid();

		$data_1 = $user_id . "i0nus";
		$data_2 = $row_host["id"] . "i0nus";
		$text_1 =  "\x30\xe2\x83\xa3\xf0\x9f\x94\xb4 " . $row_guest["inline_name"];
		$text_2 = "\xf0\x9f\x8e\xae \x30\xe2\x83\xa3\xf0\x9f\x94\xb5 " . $row_host["inline_name"];
		$grid[8] = array(
			array("text" => $text_1, "callback_data" => $data_1),
			array("text" => $text_2, "callback_data" => $data_2)
		);
		$data_1 = $game_id . "i0ngs";
		$data_2 = $game_id . "i" . time() . "nws";
		$grid[9] = array(
			array("text" => "\xf0\x9f\x92\xac چت با حریف", "callback_data" => $data_1),
			array("text" => "\xe2\x9c\x82 خاتمه بازی", "callback_data" => $data_2)
		);

		$key_game = array(
			"inline_keyboard" => $grid
		);
		$json_key_game = json_encode($key_game);
		var_dump(
			request("sendMessage", [
				"chat_id" => $row_host["id"],
				"text" => "\xf0\x9f\x8e\xae #نوبت\_بازی: " . $row_host["inline_name"] . " \xf0\x9f\x94\xb5\n"
					. "\xf0\x9f\x92\xa3 مین‌های باقی مانده: *15*\n"
					. "\xf0\x9f\x93\xa2 @mine",
				"parse_mode" => "markdown",
				"reply_markup" => $json_key_game
			])
		);
		var_dump(
			request("sendMessage", [
				"chat_id" => $user_id,
				"text" => "\xf0\x9f\x8e\xae نوبت بازی: " . $row_host["inline_name"] . " \xf0\x9f\x94\xb5\n"
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

function convertNum2Emo($str)
{
	$emoji = [
		"\x30\xe2\x83\xa3", "\x31\xe2\x83\xa3", "\x32\xe2\x83\xa3",
		"\x33\xe2\x83\xa3", "\x34\xe2\x83\xa3", "\x35\xe2\x83\xa3",
		"\x36\xe2\x83\xa3", "\x37\xe2\x83\xa3", "\x38\xe2\x83\xa3"
	];
	$num = range(0, 8);
	$enojiNum = str_replace($num, $emoji, $str);

	return $enojiNum;
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

function updateGameKeyMulti($user_id, $message_id, $data, $message_text)
{
	$message_text_1 = $message_text_2 = $message_text;
	$game_id = substr($data, 0, strpos($data, "i"));
	$key_num = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
	$item = substr($data, strpos($data, "n") + 1, 1);
	$is_End = 0;
	$is_cut = 0;
	$conn = connectDB();
	$sql = "SELECT player_host, player_guest, json_key, player_last FROM game
	        WHERE id=" . $game_id;
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	$json_key = json_decode($row["json_key"]);
	$player_host = $row["player_host"];
	$player_guest = $row["player_guest"];
	$player_turn = $row["player_last"];
	$player_host_name = getUserInlineName($player_host);
	$player_guest_name = getUserInlineName($player_guest);
	if ($user_id == $player_host)
		$player_name = $player_host_name;
	else
		$player_name = $player_guest_name;
	$grid = $json_key->inline_keyboard;
	if ($item != "w" && $item != "r" && $item != "g") {
		$row = floor($key_num / (sizeof($grid) - 3));
		$col = $key_num % (sizeof($grid) - 3);
	}

	if ($item == "w") {
		$data = $grid[8][1]->callback_data;
		$mine_blue = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
		$data = $grid[8][0]->callback_data;
		$mine_red = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
		$is_cut = 1;
		$is_End = 1;
		if (
			$mine_blue == $mine_red || ($mine_blue > $mine_red && $user_id == $player_guest)
			|| ($mine_blue < $mine_red && $user_id == $player_host)
		) {
			$message_text_1 = $message_text_2 = "\xe2\x9c\x82\xef\xb8\x8f بازی توسط " . $player_name . " تمام شد!\n"
				. "\xf0\x9f\x91\x8e این بازی برنده نداشت!"
				. "\n\xe2\x9a\x94 نتایج کل مسابقات بین شما دو نفر:\n"
				. "\xf0\x9f\x93\xa2 @mine";
			$text_1 = convertNum2Emo($mine_red) . "\xf0\x9f\x94\xb4 " . $player_guest_name;
			$text_2 = convertNum2Emo($mine_blue) . "\xf0\x9f\x94\xb5 " . $player_host_name;
			$grid[8][0]->text = $text_1;
			$grid[8][1]->text = $text_2;
			$player_turn = NULL;
		} else {
			$message_text_1 = $message_text_2 = "\xe2\x9c\x82\xef\xb8\x8f بازی توسط " . $player_name . " تمام شد!\n"
				. "\xf0\x9f\x8f\x86 برنده مسابقه: " . $player_name
				. "\n\xe2\x9a\x94 نتایج کل مسابقات بین شما دو نفر:\n"
				. "\xf0\x9f\x93\xa2 @mine";
			if ($mine_blue > $mine_red) {
				$text_1 = convertNum2Emo($mine_red) . "\xf0\x9f\x94\xb4 " . $player_guest_name;
				$text_2 = "\xf0\x9f\x8f\x85 " . convertNum2Emo($mine_blue) . "\xf0\x9f\x94\xb5 " . $player_host_name;
			} else {
				$text_1 = "\xf0\x9f\x8f\x85 " . convertNum2Emo($mine_red) . "\xf0\x9f\x94\xb4 " . $player_guest_name;
				$text_2 = convertNum2Emo($mine_blue) . "\xf0\x9f\x94\xb5 " . $player_host_name;
			}
			$grid[8][0]->text = $text_1;
			$grid[8][1]->text = $text_2;
			$player_turn = $user_id;
		}
	} elseif ($item == "g") {
		return;
	} elseif ($item == "r") {
		if ($player_host == $user_id) {
			$data = $grid[9][0]->callback_data;
			if ($key_num == 0)
				$new_num = 1;
			else
				$new_num = 3;
			$grid[9][0]->callback_data = $game_id . "i" . $new_num . "nrs";
			$data = $player_host . "i" . time() . "nvs";
			$key_req = array(
				"inline_keyboard" => array(
					array(
						array("text" => "\xf0\x9f\x92\xaa قبول درخواست", "callback_data" => $data)
					)
				)
			);
			$json_key_req = json_encode($key_req);
			var_dump(
				request("sendMessage", [
					"chat_id" => $player_guest,
					"text" => "حریفت بهت درخواست بازی مجدد داده و تو رو به مبارزه دعوت کرده \xf0\x9f\x98\x8e",
					"reply_markup" => $json_key_req
				])
			);
		} else {
			$data = $grid[9][0]->callback_data;
			if ($key_num == 0)
				$new_num = 2;
			else
				$new_num = 3;
			$grid[9][0]->callback_data = $game_id . "i" . $new_num . "nrs";
			$data = $player_guest . "i" . time() . "nvs";
			$key_req = array(
				"inline_keyboard" => array(
					array(
						array("text" => "\xf0\x9f\x92\xaa قبول درخواست", "callback_data" => $data)
					)
				)
			);
			$json_key_req = json_encode($key_req);
			var_dump(
				request("sendMessage", [
					"chat_id" => $player_host,
					"text" => "حریفت بهت درخواست بازی مجدد داده و تو رو به مبارزه دعوت کرده \xf0\x9f\x98\x8e",
					"reply_markup" => $json_key_req
				])
			);
		}
	} elseif ($item == "e") {
		$grid[$row][$col]->text = " ";
		$grid[$row][$col]->callback_data = $game_id . "i" . $key_num . "nes";
		$grid = updateGameKeyEmpty($grid, $row, $col);
	} elseif ($item == "b") {
		if ($player_host == $user_id) {
			$data = $grid[8][1]->callback_data;
			$user_point = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
			$user_point++;
			$grid[8][1]->callback_data = $player_host . "i" . $user_point . "nus";
			$text = "\xf0\x9f\x94\xb5";
		} else {
			$data = $grid[8][0]->callback_data;
			$user_point = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
			$user_point++;
			$grid[8][0]->callback_data = $player_guest . "i" . $user_point . "nus";
			$text = "\xf0\x9f\x94\xb4";
		}
		$grid[$row][$col]->text = $text;
		$grid[$row][$col]->callback_data = $game_id . "i" . $key_num . "nbs";
	} else {
		$grid[$row][$col]->text = convertNum2Emo($item);
		$grid[$row][$col]->callback_data = $game_id . "i" . $key_num . "n" . $item . "s";
	}

	if ($item != "w" && $item != "r" && $item != "g") {
		if ($player_host == $user_id && $item != "b")
			$player_turn = $player_guest;
		elseif ($player_guest == $user_id && $item != "b")
			$player_turn = $player_host;
		elseif ($player_host == $user_id && $item == "b")
			$player_turn = $player_host;
		else
			$player_turn = $player_guest;

		$grid[9][1]->callback_data = $game_id . "i" . time() . "nws";

		$data = $grid[8][1]->callback_data;
		$mine_blue = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
		$data = $grid[8][0]->callback_data;
		$mine_red = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
		if ($mine_blue == 8 || $mine_red == 8) {
			$is_End = 1;
		}
		$mines = 15 - ($mine_blue + $mine_red);
		$str = "\xf0\x9f\x92\xa3 مین‌های باقی مانده: *" . $mines . "*\n"
			. "\xf0\x9f\x93\xa2 @mine";
		if ($player_host == $user_id && !$is_End) {
			if ($player_turn != $player_host) {
				$text_1 =  "\xf0\x9f\x8e\xae " . convertNum2Emo($mine_red) . "\xf0\x9f\x94\xb4 " . $player_guest_name;
				$text_2 = convertNum2Emo($mine_blue) . "\xf0\x9f\x94\xb5 " . $player_host_name;
			} else {
				$text_1 = convertNum2Emo($mine_red) . "\xf0\x9f\x94\xb4 " . $player_guest_name;
				$text_2 = "\xf0\x9f\x8e\xae " . convertNum2Emo($mine_blue) . "\xf0\x9f\x94\xb5 " . $player_host_name;
			}
		} elseif (!$is_End) {
			if ($player_turn != $player_guest) {
				$text_1 = convertNum2Emo($mine_red) . "\xf0\x9f\x94\xb4 " . $player_guest_name;
				$text_2 = "\xf0\x9f\x8e\xae " . convertNum2Emo($mine_blue) . "\xf0\x9f\x94\xb5 " . $player_host_name;
			} else {
				$text_1 = "\xf0\x9f\x8e\xae " . convertNum2Emo($mine_red) . "\xf0\x9f\x94\xb4 " . $player_guest_name;
				$text_2 = convertNum2Emo($mine_blue) . "\xf0\x9f\x94\xb5 " . $player_host_name;
			}
		} else {
			if ($player_host == $user_id) {
				$text_1 = convertNum2Emo($mine_red) . "\xf0\x9f\x94\xb4 " . $player_guest_name;
				$text_2 = "\xf0\x9f\x8f\x85 " . convertNum2Emo($mine_blue) . "\xf0\x9f\x94\xb5 " . $player_host_name;
			} else {
				$text_1 = "\xf0\x9f\x8f\x85 " . convertNum2Emo($mine_red) . "\xf0\x9f\x94\xb4 " . $player_guest_name;
				$text_2 = convertNum2Emo($mine_blue) . "\xf0\x9f\x94\xb5 " . $player_host_name;
			}
		}
		$grid[8][0]->text = $text_1;
		$grid[8][1]->text = $text_2;

		if ($player_host == $user_id && !$is_End) {
			if ($player_turn != $player_host) {
				$text_1 = "\xf0\x9f\x8e\xae نوبت بازی: " . $player_guest_name . " \xf0\x9f\x94\xb4\n";
				$text_2 = "\xf0\x9f\x8e\xae #نوبت\_بازی: " . $player_guest_name . " \xf0\x9f\x94\xb4\n";
			} else {
				$text_1 = "\xf0\x9f\x8e\xae #نوبت\_بازی: " . $player_host_name . " \xf0\x9f\x94\xb5\n";
				$text_2 = "\xf0\x9f\x8e\xae نوبت بازی: " . $player_host_name . " \xf0\x9f\x94\xb5\n";
			}
			$message_text_1 = $text_1 . $str;
			$message_text_2 = $text_2 . $str;
		} elseif (!$is_End) {
			if ($player_turn != $player_guest) {
				$text_1 = "\xf0\x9f\x8e\xae نوبت بازی: " . $player_host_name . " \xf0\x9f\x94\xb5\n";
				$text_2 = "\xf0\x9f\x8e\xae #نوبت\_بازی: " . $player_host_name . " \xf0\x9f\x94\xb5\n";
			} else {
				$text_1 = "\xf0\x9f\x8e\xae #نوبت\_بازی: " . $player_guest_name . " \xf0\x9f\x94\xb4\n";
				$text_2 = "\xf0\x9f\x8e\xae نوبت بازی: " . $player_guest_name . " \xf0\x9f\x94\xb4\n";
			}
			$message_text_1 = $text_1 . $str;
			$message_text_2 = $text_2 . $str;
		} else {
			for ($x = 0; $x < sizeof($grid) - 2; $x++) {
				for ($y = 0; $y < sizeof($grid[0]); $y++) {
					if (strpos($grid[$x][$y]->callback_data, "bh") !== false)
						$grid[$x][$y] = array("text" => "\xf0\x9f\x92\xa3", "url" => "https://t.me/test2_7bot");
					else {
						$text = $grid[$x][$y]->text;
						$grid[$x][$y] = array("text" => $text, "url" => "https://t.me/test2_7bot");
					}
				}
			}
			$data = $game_id . "i0nrs";
			$grid[9] = array(
				array("text" => "\xf0\x9f\x91\x88\xf0\x9f\x8f\xbb درخواست بازی مجدد \xf0\x9f\x91\x89\xf0\x9f\x8f\xbb", "callback_data" => $data)
			);
			$data = $game_id . "i0nms";
			$grid[10] = array(
				array("text" => "\xf0\x9f\x91\xa4 بازی با حریف ناشناس \xf0\x9f\x8e\xb2", "callback_data" => $data)
			);
			if ($player_host == $user_id) {
				$message_text_1 = $message_text_2 = "\xf0\x9f\x8f\x86 برنده مسابقه: " . $player_host_name
					. "\n\xe2\x9a\x94 نتایج کل مسابقات بین شما دو نفر:\n"
					. "\xf0\x9f\x93\xa2 @mine";
			} else {
				$message_text_1 = $message_text_2 = "\xf0\x9f\x8f\x86 برنده مسابقه: " . $player_guest_name
					. "\n\xe2\x9a\x94 نتایج کل مسابقات بین شما دو نفر:\n"
					. "\xf0\x9f\x93\xa2 @mine";
			}
		}
	} elseif ($is_cut == 1) {
		for ($x = 0; $x < sizeof($grid) - 2; $x++) {
			for ($y = 0; $y < sizeof($grid[0]); $y++) {
				$text = $grid[$x][$y]->text;
				$grid[$x][$y] = array("text" => $text, "url" => "https://t.me/test2_7bot");
			}
		}

		$data = $game_id . "i0nrs";
		$grid[9] = array(
			array("text" => "\xf0\x9f\x91\x88\xf0\x9f\x8f\xbb درخواست بازی مجدد \xf0\x9f\x91\x89\xf0\x9f\x8f\xbb", "callback_data" => $data)
		);
		$data = $game_id . "i0nms";
		$grid[10] = array(
			array("text" => "\xf0\x9f\x91\xa4 بازی با حریف ناشناس \xf0\x9f\x8e\xb2", "callback_data" => $data)
		);
	}

	$key_game = array(
		"inline_keyboard" => $grid
	);
	$json_key_game = json_encode($key_game);
	$json_key_game_save = str_replace("\\", "\\\\", $json_key_game);
	if ($player_turn == NULL) {
		$sql = "UPDATE game SET json_key='" . $json_key_game_save . "', game_is_over='" . $is_End . "'
				WHERE id=" . $game_id;
	} else {
		$sql = "UPDATE game SET json_key='" . $json_key_game_save . "', player_last='" . $player_turn . "', game_is_over='" . $is_End . "'
				WHERE id=" . $game_id;
	}
	$conn->query($sql);

	if ($player_host == $user_id) {
		var_dump(
			request("editMessageText", [
				"chat_id" => $user_id,
				"message_id" => $message_id,
				"text" => $message_text_1,
				"parse_mode" => "markdown",
				"reply_markup" => $json_key_game
			])
		);
		var_dump(
			request("editMessageText", [
				"chat_id" => $player_guest,
				"message_id" => $message_id + 1,
				"text" => $message_text_2,
				"parse_mode" => "markdown",
				"reply_markup" => $json_key_game
			])
		);
	} else {
		var_dump(
			request("editMessageText", [
				"chat_id" => $user_id,
				"message_id" => $message_id,
				"text" => $message_text_1,
				"parse_mode" => "markdown",
				"reply_markup" => $json_key_game
			])
		);
		var_dump(
			request("editMessageText", [
				"chat_id" => $player_host,
				"message_id" => $message_id - 1,
				"text" => $message_text_2,
				"parse_mode" => "markdown",
				"reply_markup" => $json_key_game
			])
		);
	}

	$result->free();
	$conn->close();
}

function updateGameKeySingle($user_id, $message_id, $data)
{
	$game_id = substr($data, 0, strpos($data, "i"));
	$key_num = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
	$item = substr($data, strpos($data, "n") + 1, 1);
	$gameLost = false;
	$gameWin = false;
	$conn = connectDB();
	$sql = "SELECT json_key FROM game
	        WHERE id=" . $game_id;
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	$json_key = json_decode($row["json_key"]);
	$grid = $json_key->inline_keyboard;
	if ($item != "c" && $item != "j") {
		$row = floor($key_num / (sizeof($grid) - 2));
		$col = $key_num % (sizeof($grid) - 2);
	}
	$state = substr($grid[sizeof($grid) - 1][0]->callback_data, strpos($grid[sizeof($grid) - 1][0]->callback_data, "n") + 1, 1);
	if (($state == "j"))
		$currentState = "حالت فعلی: \xf0\x9f\x9a\xa9 (هر جا که میدونی مینه پرچم \xf0\x9f\x9a\xa9 بگذار)";
	else
		$currentState = "حالت فعلی: \xf0\x9f\x92\xa3 (اشتباه \xf0\x9f\x98\xa7 = انفجار \xf0\x9f\x92\xa5)";

	if (($state == "j") && ($item == "e" || $item == "b" || ($item > 0 && $item <= 8))) {
		if (strpos($data, "f")) {
			$grid[$row][$col]->text = "\xe2\xac\x9c";
			$grid[$row][$col]->callback_data = $game_id . "i" . $key_num . "n" . $item . "h";

			$data = $grid[sizeof($grid) - 1][0]->callback_data;
			$flagNum = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
			$flagNum--;
			$grid[sizeof($grid) - 1][0]->callback_data = $game_id . "i" . $flagNum . "njh";
		} else {
			$grid[$row][$col]->text = "\xf0\x9f\x9a\xa9";
			$grid[$row][$col]->callback_data = $game_id . "i" . $key_num . "n" . $item . "f";

			$data = $grid[sizeof($grid) - 1][0]->callback_data;
			$flagNum = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
			$flagNum++;
			$grid[sizeof($grid) - 1][0]->callback_data = $game_id . "i" . $flagNum . "njh";
		}
	} elseif ($item == "b") {
		$gameLost = 1;
		$data = $grid[sizeof($grid) - 1][1]->callback_data;
		$mines = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
		for ($x = 0; $x < sizeof($grid) - 1; $x++) {
			for ($y = 0; $y < sizeof($grid[0]); $y++) {
				if (strpos($grid[$x][$y]->callback_data, "bh") !== false)
					$grid[$x][$y] = array("text" => "\xf0\x9f\x92\xa3", "url" => "https://t.me/test2_7bot");
				elseif (strpos($grid[$x][$y]->callback_data, "bf") !== false)
					$grid[$x][$y] = array("text" => "\xf0\x9f\x9a\xa9", "url" => "https://t.me/test2_7bot");
				elseif (strpos($grid[$x][$y]->callback_data, "f") !== false)
					$grid[$x][$y] = array("text" => "\xe2\x9d\x8c", "url" => "https://t.me/test2_7bot");
				else {
					$text = $grid[$x][$y]->text;
					$grid[$x][$y] = array("text" => $text, "url" => "https://t.me/test2_7bot");
				}
			}
		}
		$grid[$row][$col] = array("text" => "\xf0\x9f\x92\xa5", "url" => "https://t.me/test2_7bot");
		$grid[sizeof($grid) - 1] = array(
			array("text" => "آخ! بازی رو باختی! \xf0\x9f\x98\x94", "url" => "https://t.me/test2_7bot")
		);
		$data = (sizeof($grid) - 1) . sizeof($grid[0]) . "i" . $mines . "nps";
		$grid[sizeof($grid)] = array(
			array("text" => "\xf0\x9f\x8e\xae بازی یک نفره جدید", "callback_data" => $data)
		);
	} elseif ($item == "e") {
		$grid[$row][$col]->text = " ";
		$grid[$row][$col]->callback_data = $game_id . "i" . $key_num . "nes";
		$grid = updateGameKeyEmpty($grid, $row, $col);
	} elseif ($item > 0 && $item <= 8) {
		$grid[$row][$col]->text = convertNum2Emo($item);
		$grid[$row][$col]->callback_data = $game_id . "i" . $key_num . "n" . $item . "s";
		$show = 0;
		$mine = 0;
		for ($x = 0; $x < sizeof($grid) - 1; $x++) {
			for ($y = 0; $y < sizeof($grid[0]); $y++) {
				$item = substr($grid[$x][$y]->callback_data, strpos($grid[$x][$y]->callback_data, "n") + 1, 1);
				if (strpos($grid[$x][$y]->callback_data, "s") !== false) {
					if (($item > 0 && $item <= 8) || $item == "e")
						$show++;
				} elseif ($item == "b")
					$mine++;
			}
		}
		if (((sizeof($grid) - 1) * sizeof($grid[0])) == ($show + $mine)) {
			$player = $user_id;
			$gameWin = 1;
			for ($x = 0; $x < sizeof($grid) - 1; $x++) {
				for ($y = 0; $y < sizeof($grid[0]); $y++) {
					$text = $grid[$x][$y]->text;
					$grid[$x][$y] = array("text" => $text, "url" => "https://t.me/test2_7bot");
				}
			}
			$grid[sizeof($grid) - 1] = array(
				array("text" => "آفرین! این بازی رو بردی \xf0\x9f\x8e\x89\xf0\x9f\x91\x8f\xf0\x9f\x8f\xbb", "url" => "https://t.me/test2_7bot")
			);
			$data = (sizeof($grid) - 1) . sizeof($grid[0]) . "i" . $mine . "nps";
			$grid[sizeof($grid)] = array(
				array("text" => "\xf0\x9f\x8e\xae بازی یک نفره جدید", "callback_data" => $data)
			);
		}
	} elseif ($item == "c") {
		$grid[sizeof($grid) - 1][0]->text = "تغییر حالت به \xf0\x9f\x92\xa3";
		$data = $grid[sizeof($grid) - 1][0]->callback_data;
		$grid[sizeof($grid) - 1][0]->callback_data = str_replace("c", "j", $data);
		$grid[sizeof($grid) - 1][1]->text = "\xe2\x9d\x93 راهنمای حالت \xf0\x9f\x9a\xa9";
		$data = $grid[sizeof($grid) - 1][1]->callback_data;
		$grid[sizeof($grid) - 1][1]->callback_data = str_replace("d", "k", $data);
		$currentState = "حالت فعلی: \xf0\x9f\x9a\xa9 (هر جا که میدونی مینه پرچم \xf0\x9f\x9a\xa9 بگذار)";
	} else {
		$grid[sizeof($grid) - 1][0]->text = "تغییر حالت به \xf0\x9f\x9a\xa9";
		$data = $grid[sizeof($grid) - 1][0]->callback_data;
		$grid[sizeof($grid) - 1][0]->callback_data = str_replace("j", "c", $data);
		$grid[sizeof($grid) - 1][1]->text = "\xe2\x9d\x93 راهنمای حالت \xf0\x9f\x92\xa3";
		$data = $grid[sizeof($grid) - 1][1]->callback_data;
		$grid[sizeof($grid) - 1][1]->callback_data = str_replace("k", "d", $data);
		$currentState = "حالت فعلی: \xf0\x9f\x92\xa3 (اشتباه \xf0\x9f\x98\xa7 = انفجار \xf0\x9f\x92\xa5)";
	}
	if (!$gameLost && !$gameWin) {
		$data = $grid[sizeof($grid) - 1][1]->callback_data;
		$mines = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
		$data = $grid[sizeof($grid) - 1][0]->callback_data;
		$falg = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
	}

	if (!$gameLost && !$gameWin) {
		$message_text = "مین‌های باقی مانده: *" . ($mines - $falg)
			. "*\n" . $currentState
			. "\n@mine";
	} elseif ($gameLost) {
		$message_text = "آخ! بازی رو باختی! \xf0\x9f\x98\x94\n"
			. "\xe2\x9e\x95 امتیاز این بازی: *5*\n"
			. "\xf0\x9f\x92\xb0 امتیاز کل: *1030*\n"
			. "\xf0\x9f\x93\xa2 @mine";
	} else {
		$message_text = "\xf0\x9f\x8f\x85 تبریک! همه‌ی مین‌ها رو درست تشخیص دادی \xf0\x9f\x8e\xaf\n"
			. "\xe2\x9e\x95 امتیاز این بازی: *10*\n"
			. "\xf0\x9f\x92\xb0 امتیاز کل: *1030*\n"
			. "\xf0\x9f\x93\xa2 @mine";
	}

	if ($gameLost || $gameWin)
		$is_End = 1;
	else
		$is_End = 0;

	$key_game = array(
		"inline_keyboard" => $grid
	);
	$json_key_game = json_encode($key_game);
	$json_key_game_save = str_replace("\\", "\\\\", $json_key_game);
	if ($gameWin) {
		$sql = "UPDATE game SET json_key='" . $json_key_game_save . "', player_last='" . $player . "', game_is_over='" . $is_End . "'
	        WHERE id=" . $game_id;
	} else {
		$sql = "UPDATE game SET json_key='" . $json_key_game_save . "', game_is_over='" . $is_End . "'
				WHERE id=" . $game_id;
	}
	$conn->query($sql);

	var_dump(
		request("editMessageText", [
			"chat_id" => $user_id,
			"message_id" => $message_id,
			"text" => $message_text,
			"parse_mode" => "markdown",
			"reply_markup" => $json_key_game
		])
	);

	$result->free();
	$conn->close();
}

function getGameState($game_id)
{
	$conn = connectDB();
	$sql = "SELECT json_key FROM game
	        WHERE id=" . $game_id;
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	$json_key = json_decode($row["json_key"]);
	$grid = $json_key->inline_keyboard;
	$result->free();
	$conn->close();
	return substr($grid[sizeof($grid) - 1][0]->callback_data, strpos($grid[sizeof($grid) - 1][0]->callback_data, "n") + 1, 1);
}

function getGameMulti($game_id)
{
	$conn = connectDB();
	$sql = "SELECT player_guest FROM game
	        WHERE id=" . $game_id;
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	$result->free();
	$conn->close();
	if ($row["player_guest"] != NULL)
		return true;
	else
		return false;
}

function getGameTurn($game_id)
{
	$conn = connectDB();
	$sql = "SELECT player_last FROM game
	        WHERE id=" . $game_id;
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	$result->free();
	$conn->close();
	return $row["player_last"];
}

function getGameHost($game_id)
{
	$conn = connectDB();
	$sql = "SELECT player_host FROM game
	        WHERE id=" . $game_id;
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	$result->free();
	$conn->close();
	return $row["player_host"];
}

function getUserInlineName($from_id)
{
	$conn = connectDB();
	$sql = "SELECT inline_name FROM user
	        WHERE id=" . $from_id;
	$result = $conn->query($sql);
	$row = $result->fetch_assoc();
	$result->free();
	$conn->close();
	return $row["inline_name"];
}



$update = json_decode(file_get_contents("php://input"));


if (isset($update->callback_query)) {
	$id = $update->callback_query->id;
	$from_id = $update->callback_query->from->id;
	$message_id = $update->callback_query->message->message_id;
	$message_text = $update->callback_query->message->text;
	$data = $update->callback_query->data;

	if (strpos($data, "u") !== false) {
		$get_user_id = substr($data, 0, strpos($data, "i"));
		var_dump(
			request("answerCallbackQuery", [
				"callback_query_id" => $id,
				"text" => $get_user_id,
				"show_alert" => true
			])
		);
	} elseif (strpos($data, "v") !== false) {
		$friend_id = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
		var_dump(
			request("answerCallbackQuery", [
				"callback_query_id" => $id,
				"text" => "بازی جدید ساخته میشه برای\n\n" . $from_id . "\n" . $friend_id,
				"show_alert" => true
			])
		);
	} else {
		$game_id = substr($data, 0, strpos($data, "i"));
		if (!getGameMulti($game_id)) {
			if (strpos($data, "p") !== false) {
				$row = substr($game_id, 0, 1);
				$col = substr($game_id, 1);
				$mines = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
				createNewGameSingle($from_id, $row, $col, $mines);
				var_dump(
					request("answerCallbackQuery", [
						"callback_query_id" => $id
					])
				);
			} elseif (strpos($data, "f") !== false) {
				$state = getGameState($game_id);
				if ($state == "c")
					$str = "اینجا پرچم " . "\xf0\x9f\x9a\xa9" . " گذاشتی!";
				else {
					$str = "پرچم \xf0\x9f\x9a\xa9 برداشته شد!";
					updateGameKeySingle($from_id, $message_id, $data);
				}
				var_dump(
					request("answerCallbackQuery", [
						"callback_query_id" => $id,
						"text" => $str
					])
				);
			} elseif (strpos($data, "h") !== false) {
				$state = getGameState($game_id);
				updateGameKeySingle($from_id, $message_id, $data);
				$item = substr($data, strpos($data, "n") + 1, 1);
				if ($state == "j" && $item != "j")
					$str = "پرچم \xf0\x9f\x9a\xa9 گذاشته شد!";
				elseif ($item == "e")
					$str = "باز شد!";
				elseif ($item == "b")
					$str = "";
				elseif ($item > 0 && $item <= 8)
					$str = convertNum2Emo($item) . " مین دور این خونه است!";
				elseif ($item == "c")
					$str = "حالت فعلی \xf0\x9f\x9a\xa9";
				else
					$str = "حالت فعلی \xf0\x9f\x92\xa3";
				var_dump(
					request("answerCallbackQuery", [
						"callback_query_id" => $id,
						"text" => $str
					])
				);
			} else {
				$item = substr($data, strpos($data, "n") + 1, 1);
				if ($item == "e")
					$str = "اینجا قبلا باز شده!";
				elseif ($item > 0 && $item <= 8)
					$str = convertNum2Emo($item) . " مین دور این خونه است!";
				elseif ($item == "d")
					$str = "\xf0\x9f\x94\xb8 عدد \x33\xe2\x83\xa3 درون یک خونه باز شده یعنی اطراف این خونه سه تا مین مخفی کاشته شده:\n"
						. "\xe2\xac\x9c\xf0\x9f\x92\xa3\xe2\xac\x9c\n"
						. "\xe2\xac\x9c\x33\xe2\x83\xa3\xf0\x9f\x92\xa3\n"
						. "\xe2\xac\x9c\xe2\xac\x9c\xf0\x9f\x92\xa3\n"
						. "\xf0\x9f\x94\xb9 در حالت \xf0\x9f\x92\xa3 روی خونه‌هایی که فکر میکنی مین نیستند بزن تا بهت راهنمایی بدن و بازی رو ببری!";
				else
					$str = "در حالت \xf0\x9f\x9a\xa9 میتونی خونه‌هایی که مطمئنی مین هستن رو پرچم گذاری کنی:\n"
						. "\xf0\x9f\x9a\xa9\xf0\x9f\x9a\xa9\xf0\x9f\x9a\xa9\n"
						. "\xf0\x9f\x9a\xa9\x34\xe2\x83\xa3\xe2\xac\x9c\n"
						. "\xe2\xac\x9c\xe2\xac\x9c\xe2\xac\x9c\n"
						. "اگر اشتباه کردی هم میتونی پرچم رو برداری!\n"
						. "برای سرعت دادن به بازی روی عددهای برابر با تعداد پرچم‌های اطراف اون عدد بزن.";
				if ($item == "e" || $item == "b" || ($item > 0 && $item <= 8)) {
					var_dump(
						request("answerCallbackQuery", [
							"callback_query_id" => $id,
							"text" => $str
						])
					);
				} else {
					var_dump(
						request("answerCallbackQuery", [
							"callback_query_id" => $id,
							"text" => $str,
							"show_alert" => true
						])
					);
				}
			}
		} else {
			if (strpos($data, "g") !== false) {
				var_dump(
					request("answerCallbackQuery", [
						"callback_query_id" => $id,
						"text" => "آغاز چت با حریف!"
					])
				);
			} elseif (strpos($data, "w") !== false) {
				$saveTime = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
				$turn = getGameTurn($game_id);
				if (($saveTime - time() + 60) > 0) {
					if ($from_id == $turn)
						$str = "\xe2\x9a\xa0 اگر تا " . ($saveTime - time() + 60) . " ثانیه دیگه حرکتت رو انجام ندی "
							. "حریفت میتونه بازی رو تموم کنه و تو جریمه میشی! \xf0\x9f\x98\x95";
					else
						$str = "\xe2\x9c\x82\xef\xb8\x8f اگر تا " . ($saveTime - time() + 60) . " ثانیه دیگه حریفت حرکتش رو انجام نده "
							. "میتونی بازی رو تموم کنی! \xf0\x9f\x98\x8e";
				} else {
					if ($from_id == $turn)
						$str = "\xe2\x9a\xa0 الان حریفت میتونه بازی رو تموم کنه!";
					else
						updateGameKeyMulti($from_id, $message_id, $data);
				}
				var_dump(
					request("answerCallbackQuery", [
						"callback_query_id" => $id,
						"text" => $str,
						"show_alert" => true
					])
				);
			} elseif (strpos($data, "r") !== false) {
				$number = substr($data, strpos($data, "i") + 1, (strpos($data, "n") - strpos($data, "i")) - 1);
				$player_host = getGameHost($game_id);
				if (($from_id == $player_host && ($number == 1 || $number == 3))
					|| ($from_id != $player_host && ($number == 2 || $number == 3))
				)
					$str = "درخواست قبلا ارسال شده";
				else
					updateGameKeyMulti($from_id, $message_id, $data, $message_text);
				var_dump(
					request("answerCallbackQuery", [
						"callback_query_id" => $id,
						"text" => $str
					])
				);
			} elseif (strpos($data, "m") !== false) {
				var_dump(
					request("answerCallbackQuery", [
						"callback_query_id" => $id,
						"text" => "بازی دونفره جدید!"
					])
				);
				createNewGameMulti($from_id);
			} elseif (strpos($data, "h") !== false) {
				if (getGameTurn($game_id) != $from_id) {
					var_dump(
						request("answerCallbackQuery", [
							"callback_query_id" => $id,
							"text" => "منتظر بازی حریفت بمون!",
							"show_alert" => true
						])
					);
				} else {
					updateGameKeyMulti($from_id, $message_id, $data);
					$item = substr($data, strpos($data, "n") + 1, 1);
					if ($item == "e")
						$str = "باز شد!";
					elseif ($item == "b")
						$str = "یه مین پیدا کردی!";
					else
						$str = convertNum2Emo($item) . " مین دور این خونه است!";
					var_dump(
						request("answerCallbackQuery", [
							"callback_query_id" => $id,
							"text" => $str
						])
					);
				}
			} else {
				$item = substr($data, strpos($data, "n") + 1, 1);
				if ($item == "e")
					$str = "اینجا قبلا باز شده!";
				elseif ($item == "b")
					$str = "این مین پیدا شده!";
				else
					$str = convertNum2Emo($item) . " مین دور این خونه است!";
				var_dump(
					request("answerCallbackQuery", [
						"callback_query_id" => $id,
						"text" => $str
					])
				);
			}
		}
	}
} else {
	$from_id = $update->message->from->id;
	$text = $update->message->text;

	if ($text == "/start" || $text == "بیخیال \xf0\x9f\x94\x99") {
		if (!findUser($from_id)) {
			$first_name = $update->message->from->first_name;
			$last_name = $update->message->from->last_name;
			$username = $update->message->from->username;
			$data = array($from_id, $first_name, $last_name, $first_name, $username, date("Y-m-d"));
			newUser($data);
			var_dump(
				request("sendMessage", [
					"chat_id" => $from_id,
					"text" => "\xf0\x9f\x99\x8b\xf0\x9f\x8f\xbb سلام " . $first_name . " عزیز!\n"
						. "خوش اومدی..."
				])
			);
		}
		$key_menu = array(
			"keyboard" => array(
				array("\xf0\x9f\x8e\xae بازی یک نفره جدید", "\xe2\x9a\x94 بازی دو نفره جدید"),
				array("\xf0\x9f\x92\xa1 راهنما"),
				array("\xf0\x9f\x8c\x90 زبان (Language)", "\xf0\x9f\x8f\x86 امتیازات")
			),
			"resize_keyboard" => true,
			"one_time_keyboard" => true
		);
		$json_key_menu = json_encode($key_menu);
		var_dump(
			request("sendMessage", [
				"chat_id" => $from_id,
				"text" => "چه کاری برات انجام بدم؟",
				"reply_markup" => $json_key_menu
			])
		);
	} elseif ($text == "\xf0\x9f\x8e\xae بازی یک نفره جدید") {
		$key_menu = array(
			"keyboard" => array(
				array("\xf0\x9f\x98\xa0 سخت"),
				array("\xf0\x9f\x99\x82 ساده", "\xf0\x9f\x98\x90 متوسط"),
				array("بیخیال \xf0\x9f\x94\x99")
			),
			"resize_keyboard" => true,
			"one_time_keyboard" => true
		);
		$json_key_menu = json_encode($key_menu);
		var_dump(
			request("sendMessage", [
				"chat_id" => $from_id,
				"text" => "چه کاری برات انجام بدم؟",
				"reply_markup" => $json_key_menu
			])
		);
	} elseif ($text == "\xe2\x9a\x94 بازی دو نفره جدید") {
		createNewGameMulti($from_id);
	} elseif ($text == "\xf0\x9f\x98\xa0 سخت") {
		createNewGameSingle($from_id, 8, 7, 10);
	} elseif ($text == "\xf0\x9f\x98\x90 متوسط") {
		createNewGameSingle($from_id, 7, 6, 6);
	} elseif ($text == "\xf0\x9f\x99\x82 ساده") {
		createNewGameSingle($from_id, 6, 5, 4);
	} else {
		var_dump(
			request("sendMessage", [
				"chat_id" => $from_id,
				"text" => "متوجه نشدم :/"
			])
		);
	}
}
