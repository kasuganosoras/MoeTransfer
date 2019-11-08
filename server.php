<?php
use Swoole\Http\Server;

$clients = new Swoole\Table(1024);
$clients->column('fd', swoole_table::TYPE_INT, 4);
$clients->column('opened', swoole_table::TYPE_INT, 4);
$clients->column('length', swoole_table::TYPE_INT, 8);
$clients->column('buffer', swoole_table::TYPE_STRING, 8192);
$clients->create();
$server = new Server("0.0.0.0", 812);
$server->set([
	'worker_num'         => 16,
	'http_parse_post'    => false,
	'package_max_length' => 1024 * 1024 * 40,
]);
$server->clients = $clients;

$server->on('request', function ($request, $response) {
	$uri = $request->server['request_uri'];
	$cip = $request->header['x-real-ip'] ?? $request->server['remote_addr'];
	$exp = explode("/", $uri);
	if($exp && count($exp) == 3) {
		switch($exp[1]) {
			case "close":
				$opened = getData($exp[2], "opened");
				if($opened) {
					$response->status(200);
					setData($exp[2], ["opened" => 0]);
					$response->end("200 OK");
				} else {
					$response->status(404);
					$response->end("404 Client Not Found");
				}
				break;
			case "send":
				$opened = getData($exp[2], "opened");
				if($opened) {
					if(strtolower($request->server['request_method']) == "post") {
						$data = $request->rawContent();
					} else {
						$data = $request->get['data'] ?? "";
					}
					if(empty($data)) {
						$response->status(400);
						$response->end("400 Empty Data");
					} else {
						consoleLog("IP {$cip} Transfer data to {$exp[2]}, length: " . strlen($data));
						$success = true;
						for($i = 0;$i < ceil(strlen($data) / 200000);$i++) {
							if(!$success) break;
							$buffer = getData($exp[2], "buffer");
							$retry = 0;
							while($buffer !== "") {
								if($retry > 20) {
									$success = false;
									break;
								}
								usleep(50000);
								$retry++;
							}
							$buffer = setData($exp[2], ["buffer" => base64_encode(substr($data, 200000 * $i, 200000))]);
						}
						if($success) {
							$response->status(200);
							$response->end("200 OK");
						} else {
							$response->status(502);
							$response->end("502 Client Closed");
						}
					}
				} else {
					$response->status(404);
					$response->end("404 Client Not Found");
				}
				break;
			default:
				$response->status(400);
				$response->end("400 Bad Request");
		}
	} else {
		if($uri == "/get") {
			if(getData($cip, "fd")) {
				delData($cip);
				$response->status(503);
				$response->end("503 Another Connection Still Online");
			} else {
				consoleLog("Client {$cip} connected to server");
				$response->status(200);
				$response->header('X-Accel-Buffering', 'no');
				$response->header('Content-Type', 'text/plain');
				setData($cip, [
					"fd"     => $request->fd,
					"length" => 0,
					"opened" => 1,
					"buffer" => "",
				]);
				$fdtime = time();
				$length = 0;
				$opened = 1;
				while(time() - $fdtime < 120 && $length < 1024 * 1024 * 32 && Intval($opened) === 1) {
					$length = getData($cip, "length");
					$buffer = getData($cip, "buffer");
					$opened = getData($cip, "opened");
					if(!empty($buffer)) {
						$buffer = base64_decode($buffer) ?? "";
						$response->write($buffer);
						setData($cip, [
							"buffer" => "",
							"length" => $length + strlen($buffer),
						]);
					}
					usleep(50000);
				}
				delData($cip);
				if($length == 0) {
					$response->end("");
				} else {
					$response->end();
				}
			}
		} else {
			$response->status(400);
			$response->end("Moe Transfer");
		}
	}
});

$server->on('close', function ($server, $fd, $rid) {
	
});

$server->start();

function getData($key, $field) {
	$redis = new Redis();
	$redis->connect('127.0.0.1', 6379);
	$rs = $redis->get("moetransfer-{$key}");
	if($rs) {
		$rs = json_decode($rs, true);
		return $rs[$field] ?? false;
	} else {
		return false;
	}
}

function setData($key, $data) {
	$redis = new Redis();
	$redis->connect('127.0.0.1', 6379);
	$rs = json_decode($redis->get("moetransfer-{$key}"), true);
	if($rs) {
		foreach($data as $k => $v) {
			$rs[$k] = $v;
		}
		$redis->set("moetransfer-{$key}", json_encode($rs));
	} else {
		$redis->set("moetransfer-{$key}", json_encode($data));
	}
}

function delData($key) {
	$redis = new Redis();
	$redis->connect('127.0.0.1', 6379);
	$redis->del("moetransfer-{$key}");
}

function consoleLog($data) {
	echo date("[Y-m-d H:i:s] ") . $data . "\n";
}
