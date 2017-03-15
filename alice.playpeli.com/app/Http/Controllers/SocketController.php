<?php

namespace App\Http\Controllers;

use App\Helpers\SocketHelper;
use App\Helpers\ServiceHelper;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;

class SocketController extends Controller
{
	const TIME_OUT = 10;			//超时
	
	function anyListen(){
		$address = Input::get('address',"alice.playpeli.com");
		$port = Input::get('port',8888);
		SocketHelper::run($address,$port);
		$response = Response::make("Socket Listen[$address:$port] has closed!", 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function anyClose(){
		$address = Input::get('address',"alice.playpeli.com");
		$port = Input::get('port',8888);
		SocketHelper::RemoveSocket($address,$port);
		$response = Response::make("Socket [$address:$port] has closed", 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function anyAvailables(){		
		$socketList = SocketHelper::GetSocketList();

		if(is_array($socketList))
		{
			$currentTime = time();
			$changed = false;
			foreach ($socketList as $key => $socketInfo) {
				$time = ServiceHelper::GetServiceTime("SOCKET.".$socketInfo->address.".".$socketInfo->port);
				$elaspe = $currentTime-$time;
				if($elaspe>self::TIME_OUT)
				{
					unset($socketList[$key]);
					$changed = true;
				}
			}
			if($changed)
				$socketList = array_filter($socketList);
			
			shuffle($socketList);
		}
		
		$result = json_encode($socketList, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	
	function anyTestString(){
		echo strlen("你");
	}
	
	
	function anyTest1()
	{
		$hosts = array("host1.sample.com", "host2.sample.com", "host3.sample.com");
		$timeout = 15;
		$status = array();
		foreach ($hosts as $host) {
			$errno = 0;
			$errstr = "";
			$s = fsockopen($host, 80, $errno, $errstr, $timeout);
			if ($s) {
				$status[$host] = "Connectedn";
				fwrite($s, "HEAD / HTTP/1.0rnHost: $hostrnrn");
				do {
					$data = fread($s, 8192);
					if (strlen($data) == 0) {
						break;
					}
					$status[$host] .= $data;
				} while (true);
				fclose($s);
			} else {
				$status[$host] = "Connection failed: $errno $errstrn";
			}
		}
		print_r($status);
	}
	
	
	function anyTest2()
	{
		$hosts = array("host1.sample.com", "host2.sample.com", "host3.sample.com");
		$timeout = 15;
		$status = array();
		$sockets = array();
		/* Initiate connections to all the hosts simultaneously */
		foreach ($hosts as $id => $host) {
			$s = stream_socket_client("$host:80", $errno, $errstr, $timeout,
					STREAM_CLIENT_ASYNC_CONNECT|STREAM_CLIENT_CONNECT);
			if ($s) {
				$sockets[$id] = $s;
				$status[$id] = "in progress";
			} else {
				$status[$id] = "failed, $errno $errstr";
			}
		}
		/* Now, wait for the results to come back in */
		while (count($sockets)) {
			$read = $write = $sockets;
			/* This is the magic function - explained below */
			$n = stream_select($read, $write, $e = null, $timeout);
			if ($n > 0) {
				/* readable sockets either have data for us, or are failed
				 * connection attempts */
				foreach ($read as $r) {
					$id = array_search($r, $sockets);
					$data = fread($r, 8192);
					if (strlen($data) == 0) {
						if ($status[$id] == "in progress") {
							$status[$id] = "failed to connect";
						}
						fclose($r);
						unset($sockets[$id]);
					} else {
						$status[$id] .= $data;
					}
				}
				/* writeable sockets can accept an HTTP request */
				foreach ($write as $w) {
					$id = array_search($w, $sockets);
					fwrite($w, "HEAD / HTTP/1.0rnHost: "
							. $hosts[$id] .  "rnrn");
					$status[$id] = "waiting for response";
				}
			} else {
				/* timed out waiting; assume that all hosts associated
				 * with $sockets are faulty */
				foreach ($sockets as $id => $s) {
					$status[$id] = "timed out " . $status[$id];
				}
				break;
			}
		}
		foreach ($hosts as $id => $host) {
			echo "Host: $hostn";
			echo "Status: " . $status[$id] . "nn";
		}
	}
}