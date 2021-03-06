<?php
/*
   Copyright 2007, 2008 Nicolás Gudiño

   This file is part of Asternic Call Center Stats.

    Asternic Call Center Stats is free software: you can redistribute it 
    and/or modify it under the terms of the GNU General Public License as 
    published by the Free Software Foundation, either version 3 of the 
    License, or (at your option) any later version.

    Asternic Call Center Stats is distributed in the hope that it will be 
    useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Asternic Call Center Stats.  If not, see 
    <http://www.gnu.org/licenses/>.
*/

function return_timestamp($date_string)
{
  list ($year,$month,$day,$hour,$min,$sec) = preg_split("/-|:| /",$date_string,6);
  $u_timestamp = mktime($hour,$min,$sec,$month,$day,$year);
  return $u_timestamp;
}

function check_queue($queue_name) {
	global $queuecache;

	if($queue_name=="") {
		return 0;
	}

	if(isset($queuecache["$queue_name"])) {
		return $queuecache["$queue_name"];
	}

	$query = "SELECT qname_id,queue FROM qname WHERE queue='$queue_name'";
	$res = consulta_db($query,0,0);

	if(db_num_rows($res)>0) {
		$row = db_fetch_row($res);
		return $row[0];
	} else {
		$query = "INSERT INTO qname (queue) VALUES ('$queue_name')";
		$res = consulta_db($query,0,0);
		$id = db_insert_id($res);
		$queuecache["$queue_name"]=$id;
		return $id;
	}
}

function check_agent($agent) {
	global $agentcache;
	global $argv;

	if($agent=="") {
		return 0;
	}

	$partes = explode("-",$agent);

	$agent = $partes[0];

	if($argv[1]=="convertlocal") {
		$agent = preg_replace("/^Local/","SIP",$agent);
		$agent = preg_replace("/@from/","",$agent);
	}

	if(isset($agentcache["$agent"])) {
		return $agentcache["$agent"];
	}

	$query = "SELECT agent_id,agent FROM qagent WHERE agent='$agent'";
	$res = consulta_db($query,0,0);

	if(db_num_rows($res)>0) {
		$row = db_fetch_row($res);
		return $row[0];
	} else {
		$query = "INSERT INTO qagent (agent) VALUES ('$agent')";
		$res = consulta_db($query,0,0);
		$id = db_insert_id($res);
		$agentcache["$agent"]=$id;
		return $id;
	}
}

function procesa($linea) {

	global $event_array;
	global $last_event_ts;

	$linea = rtrim($linea);

        $lin = explode("|",$linea);
        $date = $lin[0];
        $uniqueid = $lin[1];
        $queue_name = $lin[2];
        $agent = $lin[3];
        $event = $lin[4];
        $data1 = $lin[5];
        $data2 = $lin[6];
        $data3 = $lin[7];

    if ( ! preg_match('/[0-9]/', $date) ) {
        return;
    }
	if($date < $last_event_ts || $date == "") {
		return;
	}

	$date = strftime("%Y-%m-%d %H:%M:%S",$date);
	$queue_id = check_queue($queue_name);
	$agent_id = check_agent($agent);
	$event_id = $event_array["$event"];



	if($agent_id <> -1) {
	$query = "INSERT INTO queue_stats (uniqueid, datetime, qname, qagent, qevent, info1, info2, info3) ";
	$query.= "VALUES ('$uniqueid','$date','$queue_id','$agent_id','$event_id','$data1','$data2','$data3')";
	$res = consulta_db($query,0,0,1);
	}
}
?>
