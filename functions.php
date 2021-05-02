<?php
/*
MT-EQuAl: a Toolkit for Human Assessment of Machine Translation Output

Copyright 2014, Christian Girardi (cgirardi@fbk.eu)

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

# get user annotation statistics
function getUserStats($user_id, $user_status) {
	$hash = array();
	if ($user_status == "root" || $user_status == "admin" || $user_status == "advisor") {
		//get the tasks of the admin
		$admintasks = getUserTasks($user_id);
		$adminTaskIDs = array_keys($admintasks);
		if ($user_status == "advisor") {
			$query = "select id,username,name,activated,status,refuser from user RIGHT JOIN (select team from user where id=$user_id) as b on user.team=b.team order by status,username;";
		} else if ($user_status == "admin") {
			$query = "select id,username,name,activated,status,refuser from user RIGHT JOIN (select distinct user_id from usertask left join task on task.id=usertask.task_id where task.owner=$user_id) AS b ON b.user_id=user.id WHERE id IS NOT NULL order by status,username;";
		} else {
			$query = "select id,username,name,activated,status,refuser from user order by status,username;";
		}
		#$query = "select id,username,name,count(*),activated,status,refuser from user left join annotation on annotation.user_id=user.id group by id order by status,username;";
		$result = safe_query($query);	
		if (mysql_num_rows($result) > 0) {
			while($row = mysql_fetch_row($result)) {
				/*$counter = $row[3];
				if ($counter == 1) {
					$counter = 0;
				}*/
				#if ($row[0] == $user_id || $row[6] == $user_id) {
				#if ($row[0] == $user_id) {
					#$hash[$row[0]] = array($row[1],$row[2],$counter,$row[4],$row[5]);
					$hash[$row[0]] = array($row[1],$row[2],$row[3],$row[4]);
				#} else if ($row[4] != "admin") {
				#	$usertasks = getUserTasks($row[0]);
					#print $row[0] . " ". count(array_intersect(array_keys($usertasks),array_keys($admintasks)))."</br>";
					#if ($user_status == "advisor" && $row[6] == $user_id) {
					#}
				#	if (count(array_intersect(array_keys($usertasks),$adminTaskIDs)) > 0) {
				#		$hash[$row[0]] = array($row[1],$row[2],$row[3],$row[4]);
				#		#$hash[$row[0]] = array($row[1],$row[2],$counter,$row[4],$row[5]);
				#	}
				#}	
			}
		}
	} 
	
	return $hash;
}

function getUserTasks($userid) {
	$query = "SELECT task_id,name,type FROM usertask LEFT JOIN task ON usertask.task_id=task.id WHERE user_id='$userid'";
	$result = safe_query($query);
	$hash = array();	
	while ($row = mysql_fetch_row($result)) {
		$hash[$row[0]] = array($row[1], $row[2]);
	}
	return $hash;
}

function getUserInfo($userid) {
	$query ="SELECT * FROM user WHERE id='$userid'";
	$result = safe_query($query);
	if (mysql_num_rows($result) == 1) {
		return mysql_fetch_array($result);
	}
	return array();
}

#remove user info, and all down annotations 
function removeUser($userid) {
	$query ="DELETE FROM annotation WHERE user_id=$userid";
	if (safe_query($query) == 1) {
		$query ="DELETE FROM comment WHERE user_id=$userid";
		if (safe_query($query) == 1) {
			$query ="DELETE FROM done WHERE user_id=$userid";
			if (safe_query($query) == 1) {
				$query ="DELETE FROM usertask WHERE user_id=$userid";
				if (safe_query($query) == 1) {
					$query ="DELETE FROM user WHERE id=$userid";
					if (safe_query($query) == 1) {
						return 1;
					}
				}
			}
		}	
	}
	return 0;
}

function getTaskInfo($taskid) {
	$query ="SELECT * FROM task WHERE id='$taskid'";
	$result = safe_query($query);
	if (mysql_num_rows($result) == 1) {
		return mysql_fetch_array($result);
	}
	return array();
}
	
#remove task info, sentences belongs to it and all user annotations 
function removeTask($taskid) {
	$query ="DELETE annotation FROM annotation left join sentence ON annotation.sentence_num=sentence.num WHERE task_id=$taskid";
	if (safe_query($query) == 1) {
		$query ="DELETE comment FROM comment left join sentence ON comment.sentence_num=sentence.num WHERE task_id=$taskid";
		if (safe_query($query) == 1) {
			$query ="DELETE done FROM done left join sentence ON done.sentence_num=sentence.num WHERE task_id=$taskid";
			if (safe_query($query) == 1) {
				$query ="DELETE FROM sentence WHERE task_id=$taskid";
				if (safe_query($query) == 1) {
					$query ="DELETE FROM usertask WHERE task_id=$taskid";
					if (safe_query($query) == 1) {
						$query ="DELETE FROM task where id=$taskid";
						if (safe_query($query) == 1) {
							$query ="DELETE FROM usertasksent where task_id=$taskid";
							if (safe_query($query) == 1) {
								return 1;
							}
						}
					}
				}
			}
		}	
	}
	return 0;
}
	
function removeUserTask($userid, $taskid) {
	if ($userid > 0) {
		$query ="DELETE FROM usertask WHERE user_id=$userid";
		if (safe_query($query) == 1) {
			return 1;
		}
	} else if ($taskid > 0) {
		$query ="DELETE FROM usertask WHERE task_id=$taskid";
		if (safe_query($query) == 1) {
			return 1;
		}
	}
	return 0;
}

function addUserTask($userid, $taskid) {
	if (!empty($userid) && !empty($taskid)) {
		$query ="INSERT INTO usertask VALUES ($userid,$taskid)";
		if (safe_query($query) == 1) {
			return 1;
		}
	}
	return 0;
}

function getAnnotationTaskStats() {
	$query = "select task_id,count(*) from annotation left join sentence on annotation.sentence_num=sentence.num group by task_id";
	
	$result = safe_query($query);	
	$hash = array();
	if (mysql_num_rows($result) > 0) {
		while($row = mysql_fetch_row($result)) {
			$hash[$row[0]] = $row[1];
		}
	}
	return $hash;
}

#check if there are some annotation for a particular task
function isAnnotatedTask ($taskid) {
	$query="select distinct sentence_num,user_id from annotation left join sentence on annotation.sentence_num=sentence.num WHERE task_id=$taskid";
	$result = safe_query($query);	
	if (mysql_num_rows($result) > 0) {
		return 1;
	}
	return 0;
}

function getDoneTaskStats() {
	$query = "select task_id,count(*) from done left join sentence on done.sentence_num=sentence.num group by task_id";
	
	$result = safe_query($query);	
	$hash = array();
	if (mysql_num_rows($result) > 0) {
		while($row = mysql_fetch_row($result)) {
			$hash[$row[0]] = $row[1];
		}
	}
	return $hash;
}

function getErrorRate ($taskid, $annotatorid){
        $query = "select eval from annotation left join sentence on annotation.output_id=sentence.num where task_id=$taskid AND user_id=$annotatorid group by output_id";
        $result = safe_query($query);


        while( $row = mysql_fetch_assoc( $result)){
                $annotations[] = $row['eval']; // Inside while loop
        }
        //print_r($annotations);

        $total = count($annotations);
        $error_count = array_count_values($annotations);
		
		if (array_key_exists(0,$error_count)){
			$noerror = $error_count[0];
		}else{
			$noerror = 0;
		}
        
        $error_rate = ($total - $noerror) / $total;
        return array($total, round($error_rate, 3));
}

function getSourceWordCount($taskid, $annotatorid){
	$query = "select text from annotation left join sentence on annotation.sentence_num=sentence.num where task_id=$taskid AND user_id=$annotatorid group by output_id;";
        $result = safe_query($query);


        while( $row = mysql_fetch_assoc( $result)){
                $sourcetexts[] = $row['text']; // Inside while loop
        }
        //print_r($annotations);

        $total = 0;
		
		foreach($sourcetexts as $text){
			$total += str_word_count($text);
		}
        
        return $total;
}

function getTaskSourceWordCount($taskid){
	$query = "select text from sentence where task_id=$taskid and type='source';";
        $result = safe_query($query);

	$total = 0;
        while( $row = mysql_fetch_assoc( $result)){
                //$sourcetexts[] = $row['text']; // Inside while loop
		$total += str_word_count($row['text']);
        }
        //print_r($annotations);

        //$total = 0;

	
	//foreach($sourcetexts as $text){
	//	$total += str_word_count($text);
	//}
        
        return $total;
}

function getErrorNum($taskid, $annotatorid){
		$query = "select output_id, eval from annotation left join sentence on annotation.output_id=sentence.num where task_id=$taskid and user_id=$annotatorid";
		$result = safe_query($query);
		while( $row = mysql_fetch_assoc( $result)){
	                $annotations[] = $row;
		}
		
		$error_num_counter = array();
		foreach($annotations as $annotation){
			if (!array_key_exists($annotation['output_id'], $error_num_counter)){
				$error_num_counter[$annotation['output_id']] = 0;
			}
			if ($annotation['eval'] == 1){
				$error_num_counter[$annotation['output_id']] = 'many';
			} elseif ($annotation['eval'] != 0){
				$error_num_counter[$annotation['output_id']] += 1;
			}
		}

		// print_r($error_num_counter);
		$error_counter[0]=0;
		$error_counter[1]=0;
		$error_counter[2]=0;
		$error_counter['>=3']=0;
		$error_counter['many']=0;
		foreach($error_num_counter as $num => $count)
		{
			if ($count === 'many'){
					$error_counter['many'] += 1;
			}else{
				if ($count >= 3){
						$error_counter['>=3'] += 1;
				}else{
						$error_counter[$count] += 1;
				}
			}
		}

		return $error_counter;
}

function getErrorTypeCounts($taskid, $annotatorid){
	$query = "select eval, count(*) as cnt from annotation left join sentence on annotation.output_id=sentence.num where task_id=$taskid and user_id=$annotatorid group by eval";
	$result = safe_query($query);
	while( $row = mysql_fetch_assoc( $result)){
		$types[] = $row;
	}

	$type_counter = array();
	foreach($types as $type){
		$type_counter[$type['eval']] = $type['cnt'];
	}

	return $type_counter;
}

function sum_index($arr, $col_name){
    $sum = 0;
    foreach ($arr as $item) {
        $sum += $item[$col_name];
    }
    return $sum;
}

function getErrorExistence($taskid, $annotatorid){
	$query = "select output_id, eval from annotation left join sentence on annotation.output_id=sentence.num where task_id=$taskid and user_id=$annotatorid";
	$result = safe_query($query);
	while( $row = mysql_fetch_assoc( $result)){
			$annotations[] = $row; // Inside while loop
	}

	$existence = array();
	foreach ($annotations as $ann){
		if ($ann['eval'] == 0){
			$existence[$ann['output_id']] = 0;
		}else{
			$existence[$ann['output_id']] = 1;
		}
	}

	return $existence;
}


function getExistenceKappa($taskid, $annotatorid1, $annotatorid2){
	$existence1 = getErrorExistence($taskid, $annotatorid1);
	$existence2 = getErrorExistence($taskid, $annotatorid2);

	$table = array("0,0" => 0,
				   "0,1" => 0,
				   "1,0" => 0,
				   "1,1" => 0
	);

	// add counts for co-ocurred annotations
	foreach ($existence1 as $output_id => $haserror1){
		if (array_key_exists($output_id, $existence2)){
			$haserror2 = $existence2[$output_id];
			$compare_key = strval($haserror1).",".strval($haserror2);
			$table[$compare_key] += 1;
		}
	}
	
	$total = array_sum($table);
	// compute Po
	$Po = ($table['0,0'] + $table['1,1']) / $total;
	// compute Pe
	$Pe = ($table['0,0'] + $table['0,1']) * ($table['0,0'] + $table['1,0']) / ($total * $total) + 
		  ($table['1,1'] + $table['0,1']) * ($table['1,1'] + $table['1,0']) / ($total * $total);
	
	$Pe_approx = 1/2;
	
	//print_r($table);
	//print $Pe;
	$kappa_approx = 1 - (1 - $Po) / (1 - $Pe_approx);
	if (($Po == 1) && ($Pe == 1)){
		return array($total, NULL, round($kappa_approx, 4), round($Po, 4));
	}else{
		$kappa = 1 - (1 - $Po) / (1 - $Pe);
		return array($total, round($kappa, 4), round($kappa_approx, 4), round($Po, 4));
	}
}

function getTypeOverlap($taskid, $annotatorid1, $annotatorid2){
	$query1 = "select output_id, group_concat(eval) as evals from annotation left join sentence on annotation.output_id=sentence.num where task_id=$taskid and user_id=$annotatorid1 group by output_id";
	$result1 = safe_query($query1);
	while( $row = mysql_fetch_assoc( $result1)){
			$types1[] = $row;
	}

	$query2 = "select output_id, group_concat(eval) as evals from annotation left join sentence on annotation.output_id=sentence.num where task_id=$taskid and user_id=$annotatorid2 group by output_id";
	$result2 = safe_query($query2);
	while( $row = mysql_fetch_assoc( $result2)){
			$types2[] = $row;
	}

	$id_types1 = array();
	foreach ($types1 as $type){
		$id_types1[$type['output_id']] = $type['evals'];
	}

	$id_types2 = array();
	foreach ($types2 as $type){
		$id_types2[$type['output_id']] = $type['evals'];
	}

	$total_overlap_score = 0;
	$total = 0;

	foreach ($id_types1 as $output_id => $type_list1){
		if (array_key_exists($output_id, $id_types2)){
			// compute overlap score for single annotation
			$type_list2 = $id_types2[$output_id];
			$type_list1 = explode(',', $type_list1);
			$type_list2 = explode(',', $type_list2);

			$len1 = count($type_list1);
			$len2 = count($type_list2);
			$max_len = max($len1, $len2);

			// iteratively remove elements from the second list
			$count = 0;
			foreach ($type_list1 as $type1){
				$result = removeFirstOccurrence($type1, $type_list2);
				if ($result[0] == true){
					$count += 1;
					$type_list2 = $result[1];
				}
			}

			$score = $count / $max_len;
			$total_overlap_score += $score;
			$total += 1;
		}
	}

	return array($total, round($total_overlap_score/$total, 4));
}

function getSpanOverlap($taskid, $annotatorid1, $annotatorid2){
	$query1 = "select output_id, group_concat(evaltext SEPARATOR '||') as spans from annotation left join sentence on annotation.output_id=sentence.num where task_id=$taskid and user_id=$annotatorid1 group by output_id";
	$result1 = safe_query($query1);
	while( $row = mysql_fetch_assoc( $result1)){
			$spans1[] = $row;
	}

	$query2 = "select output_id, group_concat(evaltext SEPARATOR '||') as spans from annotation left join sentence on annotation.output_id=sentence.num where task_id=$taskid and user_id=$annotatorid2 group by output_id";
	$result2 = safe_query($query2);
	while( $row = mysql_fetch_assoc( $result2)){
			$spans2[] = $row;
	}

	$id_spans1 = array();
	foreach ($spans1 as $span){
		if (!empty($span['spans']))
			$id_spans1[$span['output_id']] = $span['spans'];
	}

	$id_spans2 = array();
	foreach ($spans2 as $span){
		if (!empty($span['spans']))
			$id_spans2[$span['output_id']] = $span['spans'];
	}

	$total_overlap_score = 0;
	$total = 0;

	foreach ($id_spans1 as $output_id => $span_list1){
		if (array_key_exists($output_id, $id_spans2)){
			// compute overlap score for single annotation
			$span_list2 = $id_spans2[$output_id];
			$span_list1 = explode('||', $span_list1);
			$span_list2 = explode('||', $span_list2);

			$len1 = count($span_list1);
			$len2 = count($span_list2);
			$max_len = max($len1, $len2);

			// iteratively remove elements from the second list
			$count = 0;
			foreach ($span_list1 as $span1){
				$result = removeFirstOccurrenceFuzzy($span1, $span_list2);
				if ($result[0] == true){
					$count += 1;
					$span_list2 = $result[1];
				}
			}

			$score = $count / $max_len;
			$total_overlap_score += $score;
			$total += 1;
		}
	}
	
	if($total == 0){
		return array(0, 0);
	}else{
		return array($total, round($total_overlap_score/$total, 4));
	}
}

function computeOverlapScore($span_list1, $span_list2){
	$total_overlap_score = 0;
	$total = 0;


	$len1 = count($span_list1);
	$len2 = count($span_list2);
	$max_len = max($len1, $len2);

	// iteratively remove elements from the second list
	$count = 0;
	foreach ($span_list1 as $span1){
		$result = removeFirstOccurrenceFuzzy($span1, $span_list2);
		if ($result[0] == true){
			$count += 1;
			$span_list2 = $result[1];
		}
	}

	$score = $count / $max_len;

	return $score;
}


function removeFirstOccurrence($item, $arr){
	$success = false;
	if (($index = array_search($item, $arr)) !== false){
		unset($arr[$index]);
		$success = true;
	}
	return array($success, $arr);
}

function get_longest_common_subsequence($string_1, $string_2)
{
	$string_1_length = strlen($string_1);
	$string_2_length = strlen($string_2);
	$return          = '';
	
	if ($string_1_length === 0 || $string_2_length === 0)
	{
		// No similarities
		return $return;
	}
	
	$longest_common_subsequence = array();
	
	// Initialize the CSL array to assume there are no similarities
	$longest_common_subsequence = array_fill(0, $string_1_length, array_fill(0, $string_2_length, 0));
	
	$largest_size = 0;
	
	for ($i = 0; $i < $string_1_length; $i++)
	{
		for ($j = 0; $j < $string_2_length; $j++)
		{
			// Check every combination of characters
			if ($string_1[$i] === $string_2[$j])
			{
				// These are the same in both strings
				if ($i === 0 || $j === 0)
				{
					// It's the first character, so it's clearly only 1 character long
					$longest_common_subsequence[$i][$j] = 1;
				}
				else
				{
					// It's one character longer than the string from the previous character
					$longest_common_subsequence[$i][$j] = $longest_common_subsequence[$i - 1][$j - 1] + 1;
				}
				
				if ($longest_common_subsequence[$i][$j] > $largest_size)
				{
					// Remember this as the largest
					$largest_size = $longest_common_subsequence[$i][$j];
					// Wipe any previous results
					$return       = '';
					// And then fall through to remember this new value
				}
				
				if ($longest_common_subsequence[$i][$j] === $largest_size)
				{
					// Remember the largest string(s)
					$return = substr($string_1, $i - $largest_size + 1, $largest_size);
				}
			}
			// Else, $CSL should be set to 0, which it was already initialized to
		}
	}
	
	// Return the list of matches
	return $return;
}

function removeFirstOccurrenceFuzzy($item, $arr){
	$THRESHOLD = 0.6;

	$success = false;
	// compare each element with item
	foreach($arr as $idx => $str){
		$substr = get_longest_common_subsequence($item, $str);

		$len1 = strlen($substr);
		$len2 = strlen($item);
		$max_len = max($len1, $len2);

		if (strlen($substr) >= 0.6 * $max_len){
			unset($arr[$idx]);
			$success = true;
		}
	}
	return array($success, $arr);
}

function getDoneUserStats() {
	$query = "select user_id,count(*) from done group by user_id";
	
	$result = safe_query($query);	
	$hash = array();
	if (mysql_num_rows($result) > 0) {
		while($row = mysql_fetch_row($result)) {
			$hash[$row[0]] = $row[1];
		}
	}
	return $hash;
}



#get last N sentence annotated by the users
# foreach sentence returns the sentence ID (as key of the hash), and the task ID and the last modified time (as array)
function getUserLastAnnotations($user_id, $limit = 2) {
	$query = "SELECT DISTINCT sentence_num,task_id,annotation.lasttime from annotation LEFT JOIN sentence ON annotation.sentence_num=sentence.num WHERE user_id=$user_id and task_id!='' group by sentence_num order by annotation.lasttime desc limit $limit;";
	
	$result = safe_query($query);	
	$hash = array();
	if (mysql_num_rows($result) > 0) {
		while($row = mysql_fetch_row($result)) {
			$hash[$row[0]] = array($row[1], $row[2]);
		}
	}
	return $hash;
}

function getUserLastDone($user_id) {
	$query = "SELECT count(lasttime), max(lasttime) from done where user_id=$user_id order by lasttime desc";
	
	$result = safe_query($query);	
	return mysql_fetch_row($result);
}

# get sentence mapping between the source ID and the internal MySQL one
function getSourceSentenceIdMapping($task_id) {
	$query = "SELECT id,num FROM sentence WHERE task_id=$task_id AND type='source'";
	$result = safe_query($query);	
	$hash = array();
	if (mysql_num_rows($result) > 0) {
		while($row = mysql_fetch_row($result)) {
			$hash[$row[0]] = $row[1];
		}
	}
	return $hash;
}


# get info about a source sentence: text, reference, ..
function getSentence($num, $taskid) {
	$query = "SELECT type,lang,text,tokenization FROM sentence WHERE (num='$num' OR (linkto='$num' AND type='reference')) AND task_id='$taskid';";
	$result = safe_query($query);	
	$hash =array();
	if (mysql_num_rows($result) > 0) {
		while($row = mysql_fetch_array($result)) {
			$hash[$row["type"]] = array($row["lang"],$row["text"], $row["tokenization"]);
		}
	}
	return $hash;
}

#get annotaion task: quality
function getQuality($sentence_num,$output_id,$user_id) {
	$query = "SELECT eval FROM annotation WHERE sentence_num='$sentence_num' AND output_id='$output_id' AND user_id=$user_id;";
	$result = safe_query($query);	
	#print mysql_num_rows($result)  . " -- ".$query;
	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_array($result);
		return $row["eval"];
	}
	return -1;
}

#get annotaion task: errors
function getErrors($sentence_num,$output_id,$user_id) {
	$query = "SELECT eval,evalids,evaltext FROM annotation WHERE sentence_num=$sentence_num AND output_id=$output_id AND user_id=$user_id order by eval;";
	$result = safe_query($query);	
	//print mysql_num_rows($result)  . " -- ".$query;
	$hash_error = array();
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_array($result)) {
			$hash_error[$row["eval"]] = array($row["evalids"],$row["evaltext"]);
		}
	}
	return $hash_error;
}

#count annotations for a target
function countAnnotations($sentence_num,$output_id,$user_id) {
	$query = "SELECT LENGTH(evalids) - LENGTH(REPLACE(evalids,',',''))+1 FROM annotation WHERE sentence_num=$sentence_num AND output_id=$output_id AND user_id=$user_id order by eval;";
	$result = safe_query($query);	
	if (mysql_num_rows($result) > 0) {
		$sum=0;
		while ($row = mysql_fetch_row($result)) {
			$sum += $row[0];
		} 
		return $sum;
	}
	return 0;
}

#count the annotated targets
function countAnnotatedSentences($sentence_num,$user_id = null) {
	$query = "SELECT distinct output_id FROM annotation WHERE sentence_num='$sentence_num' AND eval >= 0";
	if ($user_id != null) {
		$query .= " AND user_id=$user_id;";
	}
	return mysql_num_rows(safe_query($query));
}
	
function getUsedValues ($taskid) {
	$values = array();
	$query = "SELECT DISTINCT eval FROM annotation LEFT JOIN sentence ON annotation.sentence_num=sentence.num WHERE task_id=$taskid";
	$result = mysql_query($query);	
	if (mysql_num_rows($result) > 0) {
		while($row = mysql_fetch_row($result)) {
			array_push($values, $row[0]);
		}
	}
	return $values;
}

#duplicate annotations: copy the annotations from taskid and fromuserid to the current userid (=curruserid)
function copyAnnotations ($curruserid, $taskid, $fromuserid) {
	$query="INSERT INTO annotation SELECT sentence_num, output_id, $curruserid, eval, evalids, evaltext, now() FROM annotation LEFT JOIN sentence ON annotation.sentence_num=sentence.num WHERE user_id=$fromuserid AND task_id=$taskid";
	mysql_query($query) or print ("ERROR! Records copying failed. (" . mysql_error() .")");	
	return mysql_affected_rows();
}
	
function getAnnotatedTasks ($userid) { 
	$hash = array();
	#$query="select distinct task_id, count(*) from annotation left join sentence on annotation.sentence_num=sentence.num where user_id=$userid group by task_id";
	$query="SELECT task_id,sum(LENGTH(evalids) - LENGTH(REPLACE(evalids, ',', ''))+1) FROM annotation LEFT JOIN sentence ON annotation.sentence_num=sentence.num WHERE user_id=$userid group by task_id";
	$result = safe_query($query);	
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_row($result)) {
			if (!empty($row[0])) {
				$hash[$row[0]] = $row[1];
			}
		}
	}    
	return $hash;
}

#get uniq pair task,user for annotation	except the user user_id
function getTaskAndUsers ($user_id) { 
	$hash = array();
	$query="select distinct task_id,user_id from annotation,sentence where annotation.sentence_num=sentence.num and user_id != $user_id order by task_id, user_id";
	$result = safe_query($query);	
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_array($result)) {
			if (array_key_exists($row["task_id"], $hash)) {
				$hash[$row["task_id"]] .= " " .$row["user_id"];
			} else {
				$hash[$row["task_id"]] = $row["user_id"];
			}
		}
	}
	return $hash ;
}

#get all annotation divided by task/user key
# task/user id | count(*)
#     1-22     |   2505
function getTaskAndUserAnnotation () { 
	$hash = array();
	$query="select concat(task_id,'-',user_id) as tu, count(*) as count from annotation, sentence where annotation.sentence_num=sentence.num group by tu order by tu";
	$result = safe_query($query);	
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_array($result)) {
			$hash[$row["tu"]] = $row["count"];
		}
	}
	return $hash ;
}

#save annotation task: quality
function saveQuality($source_id,$output_id,$user_id,$eval,$action="") {
	$query="";
	if ($action == "remove") {
		$evalclause="";
		if ($eval > -1) {
			$evalclause="AND eval=$eval";
		}
		$query = "DELETE FROM annotation WHERE sentence_num='$source_id' AND output_id='$output_id' AND user_id=$user_id $evalclause";		
	} else if (getQuality($source_id,$output_id,$user_id) < 0) {	
		$query = "INSERT INTO annotation (sentence_num,output_id,user_id,eval,lasttime) VALUES ('$source_id','$output_id',$user_id,$eval,now())";		
	} else {
		$query = "UPDATE annotation SET eval='$eval',lasttime=now() WHERE sentence_num='$source_id' AND output_id='$output_id' AND user_id=$user_id;";
	}
	
	safe_query("UPDATE done SET completed='N',lasttime=now() WHERE sentence_num='$source_id' AND user_id=$user_id;");
	return safe_query($query);		
}

#save annotation task: error
function removeError($id,$targetid,$user_id,$eval,$item) {
	$query = "SELECT evalids,evaltext FROM annotation WHERE sentence_num=$id AND output_id='$targetid' AND user_id=$user_id AND eval=$eval";
	$result = safe_query($query);	
	//print mysql_num_rows($result)  . " -- ".$query;
	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_row($result);
		
		$ids = explode(",",$row[0]);
		$texts = explode("__BR__",$row[1]);
		for ($i=0; $i<count($ids); $i++) {
			if ($ids[$i] == $item || empty($ids[$i])) {
				unset($ids[$i]);
				unset($texts[$i]);				
			}	
		}
		
		#saveLog("#".join("__BR__",array_filter($texts)) ."<br>!! ".join(",",array_filter($ids)));
		$ids = array_filter($ids);
		$texts = array_filter($texts);
		
		if (count($ids) == count($texts)) {
			if(count($ids) == 0) {
				$query = "DELETE FROM annotation WHERE sentence_num=$id AND output_id='$targetid' AND user_id=$user_id AND eval=$eval;";
			} else {
				$query = "UPDATE annotation SET evalids='".join(",",$ids)."',evaltext=\"".addslashes(join("__BR__",$texts))."\",lasttime=now() WHERE sentence_num=$id AND output_id='$targetid' AND user_id=$user_id AND eval=$eval;";
			}
			safe_query($query);
			
			safe_query("UPDATE done SET completed='N',lasttime=now() WHERE sentence_num='$id' AND user_id=$user_id");
	
		} else {
			return 0;
		}
	}
	return 1;
}

//reset all annotations of an error category type 
function resetErrors($id,$targetid,$user_id,$check) {
	$query = "DELETE FROM annotation WHERE sentence_num=$id AND output_id='$targetid' AND user_id=".$user_id." AND eval=$check";
	safe_query($query);
	
	safe_query("UPDATE done SET completed='N',lasttime=now() WHERE sentence_num=$id AND user_id=$user_id");
}

#save annotation task: error
function saveErrors($source_id,$output_id,$user_id,$eval,$evalids="",$evaltext="") {
	$query="";
	if ($eval == -1) {
		$query = "DELETE FROM annotation WHERE sentence_num='$source_id' AND output_id='$output_id' AND user_id=$user_id";
		safe_query($query);
	} else {
		$hash = getErrors($source_id,$output_id,$user_id);
		if (!isset($hash[$eval])) {
			$evaltext = str_replace("\"","&quot;",stripslashes(trim($evaltext)));	
			$query = "INSERT INTO annotation (sentence_num,output_id,user_id,eval,evalids,evaltext,lasttime) VALUES ('$source_id','$output_id',$user_id,$eval,'$evalids',\"$evaltext\",now())";
		} else {
			$list_ids = explode(",", $evalids);
			$texts = explode("__BR__",$evaltext);
		
			$evalids="";
			$evaltext="";
			$list_saved_ids = explode(',', $hash[$eval][0]);
			$t=0;
			foreach ($list_ids as $sid) {
				if (!in_array($sid, $list_saved_ids)) {
					$evalids .= ",$sid";
					$evaltext .="__BR__".$texts[$t];
				}
				$t++;
			}
			if ($evalids != "") {
				$evaltext = str_replace("\"","&quot;",stripslashes(trim($hash[$eval][1]."$evaltext")));	
				$query = "UPDATE annotation SET evalids='".$hash[$eval][0].trim($evalids)."',evaltext=\"$evaltext\",lasttime=now() WHERE sentence_num='$source_id' AND output_id='$output_id' AND user_id=$user_id AND eval=$eval;";
			}
		}
		#print "Q: $query<br>";
		safe_query($query);
		
		$query = "DELETE FROM annotation WHERE sentence_num='$source_id' AND output_id='$output_id' AND user_id=$user_id AND (eval<2 OR eval=7)";
		#print "Q2: $query<br>";
		safe_query($query);	
	}
	
	safe_query("UPDATE done SET completed='N',lasttime=now() WHERE sentence_num='$source_id' AND user_id=$user_id");
	return 1;
}

#save annotation task: alignment
function saveAligment($source_id,$output_id,$user_id,$eval,$evalids="") {
	$query="";
	$evalids=trim($evalids);
	if ($evalids == "") {
		$query = "DELETE FROM annotation WHERE sentence_num='$source_id' AND output_id='$output_id' AND user_id=$user_id AND eval=$eval";
		safe_query($query);
	} else {
		$hash = getErrors($source_id,$output_id,$user_id);
		if (!isset($hash[$eval])) {
			$query = "INSERT INTO annotation (sentence_num,output_id,user_id,eval,evalids,lasttime) VALUES ('$source_id','$output_id',$user_id,$eval,'$evalids',now())";
		} else {	
			$query = "UPDATE annotation SET evalids='$evalids',lasttime=now() WHERE sentence_num='$source_id' AND output_id='$output_id' AND user_id=$user_id AND eval=$eval;";
		}
		safe_query($query);
	}
	
	safe_query("UPDATE done SET completed='N',lasttime=now() WHERE sentence_num='$source_id' AND user_id=$user_id");
	return 1;
}

#save comment
function saveComment($sentence_num,$user_id,$comment) {
	$query = "DELETE FROM comment WHERE sentence_num=$sentence_num AND user_id=$user_id;";
	safe_query($query);
	$comment = str_replace("\"","&quot;",stripslashes(trim($comment)));
	#if (!empty(trim($comment))) {
		$query = "INSERT INTO comment VALUES ($sentence_num, $user_id, \"$comment\")";
		#saveLog($query);
		safe_query($query);	
	#}	
	safe_query("UPDATE done SET completed='N',lasttime=now() WHERE sentence_num='$source_id' AND user_id=$user_id");
	
}

#get comment
function getComment($sentence_num,$user_id) {
	$query = "SELECT comment FROM comment WHERE sentence_num=$sentence_num AND user_id=$user_id;";
	$result = safe_query($query);	
	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_row($result);
		return $row[0];
	}
	return "";
}

function getComments($taskid, $user_id) {
	$hash = array();
	$query = "SELECT sentence_num,id,type,comment FROM comment LEFT JOIN sentence ON sentence_num=sentence.num WHERE task_id=$taskid AND comment.user_id=$user_id";
	$result = safe_query($query);	
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_array($result)) {
			$hash[$row["sentence_num"]] = $row["id"]."\t".$row["type"]."\t".$row["comment"];
		}
	}
	return $hash;
}

# check if the user click for a sentence
function isDone($sentence_num,$user_id) {
	$query = "SELECT completed FROM done WHERE sentence_num=$sentence_num AND user_id=$user_id;";
	$result = safe_query($query);	
	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_row($result);
		if ($row[0] == "Y") {
			return 1;
		} else {
			return 0;
		}
	}
	return -1;
}

#save "Y" when a user click the "DONE" button in the sentence page
function saveDone($sentence_num,$user_id,$completed) {
	$query="";
	if (isDone($sentence_num,$user_id) < 0) {
		$query = "INSERT INTO done (sentence_num,user_id,completed,lasttime) VALUES ($sentence_num,$user_id,'Y',now())";
	} else {
		$query = "UPDATE done SET completed='Y',lasttime=now() WHERE sentence_num=$sentence_num AND user_id=$user_id";
	}
	return safe_query($query);
}

# get info about the target sentences
function getSystemSentences($id,$taskid) {
	$query = "SELECT num,lang,text,tokenization FROM sentence WHERE linkto='$id' AND task_id=$taskid AND type != 'reference' order by type";
	$result = safe_query($query);
	$hash = array();
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_array($result)) {
			$hash[$row["num"]] = array($row["lang"], $row["text"], $row["tokenization"]);
		}
	}
	
	$taskinfo = getTaskInfo($taskid);
	if ($taskinfo['randout'] == "Y") {
		#randomize and return
		return shuffle_assoc($hash,$id);
	}
	return $hash;

}

function getOutputSentence($sourceId, $type) {
	$query = "SELECT num from sentence where linkto='".$sourceId. "' and type='".$type ."'";
	$result = safe_query($query);	
	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_row($result);
		return $row[0];
	}
	return -1;
}

# get hash with uniq info about the available sentences for a task: sentence_num, text
function getSourceSentences($taskid) {
	$query = "SELECT num,lang,text,id FROM sentence WHERE task_id='$taskid' AND linkto is null order by num;";
	$result = safe_query($query);	
	$hash = array();
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_row($result)) {
			$hash[$row[0]] = array($row[1],$row[2],$row[3]);
        }
	}
	return $hash;
}

function getBatchedSourceSentences($taskid, $userid) {
	$query = "SELECT num,s.lang,s.text,s.id FROM usertasksent t left join sentence s on t.sent_num = s.num WHERE t.task_id='$taskid' AND t.user_id='$userid';";
	$result = safe_query($query);	
	$hash = array();
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_row($result)) {
			$hash[$row[0]] = array($row[1],$row[2],$row[3]);
        }
	}
	return $hash;
}

# get an array with done sentece by a user
function getDoneSentences($taskid,$userid) {
	$query = "SELECT sentence_num,done.lasttime FROM done LEFT JOIN sentence on done.sentence_num=sentence.num WHERE done.user_id=$userid AND sentence.task_id=$taskid AND completed ='Y'";
	$result = safe_query($query);	
	$arr = array();
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_row($result)) {
			array_push($arr, $row[0]);
		}
	}
	return $arr;
}


# get an array with done sentece by a user
function getErrorSentences($taskid) {
	$query = "SELECT linkto FROM sentence WHERE text LIKE '% %' AND task_id=$taskid";
	$result = safe_query($query);	
	$arr = array();
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_row($result)) {
			array_push($arr, $row[0]);
		}
	}
	return $arr;
}

#get the counter about sentence types of a task
function countTaskSentences ($taskid) {
	$query="SELECT type,count(*) as num FROM sentence WHERE task_id=$taskid group by type order by type;";
	$result = safe_query($query);	
	$hash = array();
	if (mysql_num_rows($result) > 0) {
		while($row = mysql_fetch_array($result)) {
			$hash[$row["type"]] = $row["num"];
		}
	}
	return $hash;
}

#get the counter about sentence types of a task
function countTaskSystem ($taskid) {
	if (!empty($taskid)) {
		if (getTaskType($taskid) == "docann") {
			return 1;
		} else {
			$query="SELECT distinct type FROM sentence WHERE task_id=$taskid AND type != 'source' AND type != 'reference';";
			$result = safe_query($query);	
			return mysql_num_rows($result);
		}
	}
	return 0;
}

#get the list of sentence types
function getSentenceType () {
	$query="SELECT distinct type FROM sentence ORDER BY type";
	$result = safe_query($query);	
	$arr = array();
	if (mysql_num_rows($result) > 0) {
		while($row = mysql_fetch_row($result)) {
			array_push($arr, $row[0]);
		}
	}
	return $arr;
}

# get task ID by the task name
function getTaskID($taskname) {
	$query = "SELECT id FROM task WHERE name='$taskname';";
	$result = safe_query($query);	
	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_row($result);
		return $row[0];
	}
	return 0;
}

function rangesJson2Array($ranges) {
	$array = array();
	$dec = json_decode(stripslashes($ranges));
	for($idx=0;$idx<count($dec);$idx++){
    	$obj = (Array) $dec[$idx];
    	$array[$obj["val"]] = array($obj["label"], $obj["color"]);
    }
    return $array;
}

# get task type by a task name
function getTaskType($taskid) {
	$query = "SELECT type FROM task WHERE id=$taskid;";
	$result = safe_query($query);	
	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_row($result);
		return $row[0];
	}
	return "";
}

# get task name by a task id
function getTaskName($taskid) {
	$query = "SELECT name FROM task WHERE id=$taskid;";
	$result = safe_query($query);	
	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_row($result);
		return $row[0];
	}
	return "";
}

# get an array with tasks name
function getTasks($userid) {
	global $taskTypes;
	$hash = array();
	//if you are root
	if (isset($userid)) {
		$annotationCount=getAnnotationTaskStats();
		if (intval($userid) == 0) {
			$query = "SELECT id,name,type FROM task ORDER BY type, name";
			$result = safe_query($query);	
			if (mysql_num_rows($result) > 0) {
				while ($row = mysql_fetch_row($result)) {
					#print $row[0] ."= array(".$row[1].", ".$row[2].")<br>";
					$count=0;
					if (isset($annotationCount[$row[0]])) {
						$count = $annotationCount[$row[0]];	
					}
					if (isset($taskTypes[$row[2]])) {
						$hash[$row[0]] = array($row[1], $row[2],$count);
					}
				}
			}
		} else { 
			$query = "SELECT id,name,type FROM task LEFT JOIN usertask ON task.id=usertask.task_id WHERE user_id='$userid' OR owner='$userid' order by type, name";
			$result = safe_query($query);	
			while ($row = mysql_fetch_row($result)) {
				$count=0;
				if (isset($annotationCount[$row[0]])) {
					$count = $annotationCount[$row[0]];	
				}
				if (isset($taskTypes[$row[2]])) {
					$hash[$row[0]] = array($row[1], $row[2],$count);
				}
			}
			//add also own task
			//$query = "SELECT id,name,type FROM task WHERE owner='$userid'";
			//$result = safe_query($query);	
			//while ($row = mysql_fetch_row($result)) {
			//	$hash[$row[0]] = array($row[1], $row[2]);
			//}	
		}	
	} 
	return $hash;
}

# add data
function addFileData ($taskid,$type,$tokenization,$filepath,$filename) {
	$errmsg="";
	$insert=0;
   	$mappingsID2NUM = getSourceSentenceIdMapping($taskid);
#	print "TASK: $taskid, TYPE: $type, mappingsID2NUM: ". join(",",$mappingsID2NUM). " ($tokenization,$filepath,$filename)<br>";
	#skip the file that start with dot
	if (preg_match("/^\..+$/",basename($filename))) {
		return $errmsg;
	}	 
	
	$handle = fopen($filepath, "r");
	if ($handle) {
		$tasktype = getTaskType($taskid);
		$linenum = 0;
		$fileType ="csv";
		$fileName = $filename;
		$txpText="";
		$tokenstartId = -1;
		$currentPosition=0;
		while (($line = fgets($handle)) !== false) {
    		$linenum++;
 	   		if (preg_match("/^# FILE:/",$line)) {
    			$fileType="txp";
    			#$fileName=trim(preg_replace("/^# FILE:/","",$line));
    		} else if (preg_match("/^# FIELDS:/",$line)) {
    			$fields = preg_split("/\t/",preg_replace("/^# FIELDS:\s*/","", $line));
    			for ($i=0; $i< count($fields); $i++) {
    				if ($fields[$i] == "tokenstart") {
    					$tokenstartId=$i;
    				}
    			}
    		}
    		if ($tasktype == "docann") {
    			if ($fileType == "txp") {
    				if (preg_match("/^# /",$line)) {
    					continue;
    				} else {
    					if (trim($line) == "" && strlen($txpText) >0) {
    						$txpText .= "\n";
    						$currentPosition++;
    					} else {
    						$items = explode("\t", trim(htmlentities2utf8($line)));
    						if ($tokenstartId != -1) {
    							for ($i=$currentPosition;$i<intval($items[$tokenstartId]); $i++) {
    								$txpText .= " ";
    							}
    						} 
    						$txpText .= $items[0];
    						if ($tokenstartId != -1) {
    							$currentPosition = intval($items[$tokenstartId]) + strlen($items[0]);
    						} else {
    							$txpText .= " ";
    						} 	
    					}
    				}
    			} else {
    				$txpText .= htmlentities2utf8($line);
    			}
    		} else {
    			$query = "";
       			#print $line ."<br><pre>".htmlentities2utf8($line)."</pre><br>----<br>";
    			$items = explode("\t", htmlentities2utf8($line));
    			if (count($items) < 3 || empty($items[1])) {
    				$errmsg = "<small>WARNING! Parse error on file $filename: language is missing [line: $linenum]</small><br>\n";
    				break;
    			}
    			if ($type == "source") {
       				$query = "INSERT INTO sentence (id, type, lang, task_id, text, lasttime, tokenization) VALUES ('".$items[0] ."','".$type."','" .$items[1]."',$taskid,'". addslashes($items[2]) ."', now(), $tokenization);";
				} else {   			
       				if (!empty($mappingsID2NUM[$items[0]])) {
       					if ($type == "reference") {
       						$query = "INSERT INTO sentence (id, type, lang, task_id, linkto, text, lasttime, tokenization) VALUES ('".$items[0] ."','".$type."','" .$items[1]."',$taskid,'". $mappingsID2NUM[$items[0]] ."','" .addslashes($items[2]) ."', now(),0);";
       					} else {
						# qualification for error annotation task
							if ($tasktype == "errors_test"){
								if (count($items) < 5){
									$errmsg = "<small>WARNING! Parse error on file $filename: for qualification testing gold annotation is needed [line: $linenum]</small><br>\n";
									break;
								}
								$query = "INSERT INTO sentence (id, type, lang, task_id, linkto, text, lasttime, tokenization, gold_eval, gold_text) VALUES ('".$items[0] ."','".$type."','" .$items[1]."',$taskid,'". $mappingsID2NUM[$items[0]] ."','" .addslashes($items[2]) ."', now(),$tokenization, $items[3], '".$items[4]."');";
							}else{
								$query = "INSERT INTO sentence (id, type, lang, task_id, linkto, text, lasttime, tokenization) VALUES ('".$items[0] ."','".$type."','" .$items[1]."',$taskid,'". $mappingsID2NUM[$items[0]] ."','" .addslashes($items[2]) ."', now(),$tokenization);";
							}
					}
       				} else {
       					$errmsg = "WARNING! The source of sentence ".$items[0]." is missing. Add the source sentence aligned to this output sentence.<br>";
       				}
       			}
				if (strlen($query) > 0) {
					$insert += safe_query($query);
				}
			}
    	}
    	fclose($handle);
	if ($type == 'source'){
		split_task_data($taskid);
	}
		
		if (strlen(trim($txpText)) > 0) {
    		if (strlen(trim($fileName)) > 0) {
    			$query = "INSERT INTO sentence (id, type, lang, task_id, text, lasttime, tokenization) VALUES ('$fileName','".$type."','',$taskid,'".trim(addslashes($txpText)) ."', now(),1);";
    		#print $query;
    			$insert += safe_query($query);
    		} else {
    			$errmsg = "WARNING! The filename is missing.";
    		}
    	}
    		
    	
    	//add info about used file
    	/*$query = "DELETE FROM file WHERE type='$type' AND task_id=$taskid";
    	safe_query($query);
		$query = "INSERT INTO file VALUES ('$filename','$type',$taskid)";
    	safe_query($query);
		*/
	} else {
    	// error opening the file.
    	$errmsg = "ERROR! Some problems occured opening the file $filename.";
	}
	#if ($insert == count($mappingsID2NUM)) {
    #	$errmsg = "DONE! $insert resources have been inserted";
    #}
    return $errmsg;
}

# get system ids and labels
function getSystems() {
	$query = "SELECT DISTINCT type FROM sentence";
	$result = safe_query($query);	
	$hash = array();
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_row($result)) {
			array_push($hash, $row[0]);
		}
	}
	return $hash;
}

#delete the annotations from a task and user
function deleteAnnotations ($taskid,$userid) {	
	$query="DELETE annotation from annotation LEFT JOIN sentence on annotation.sentence_num=sentence.num where user_id=$userid and task_id=$taskid";
	mysql_query($query) or print ("ERROR! Annotations deleting failed. (" . mysql_error() .")");	
	$deleted = mysql_affected_rows();
	
	#delete done records
	$query="DELETE done from done LEFT JOIN sentence on done.sentence_num=sentence.num where user_id=$userid and task_id=$taskid";
	safe_query($query);
	return $deleted;
}

function deleteSentences($taskid,$type) {	
	#delete all annotations
    $query = "DELETE FROM annotation where output_id IN (
    SELECT num FROM sentence WHERE sentence.task_id=$taskid AND type='$type');";
	#delete all sentences
	$query = "DELETE FROM sentence WHERE task_id=$taskid AND type='$type';";
	#$result = safe_query($query);
	$query2 = "DELETE FROM usertasksent where task_id=$taskid";
	safe_query($query2);
	return safe_query($query);
}

		
#controllo che i done abbiamo tutti lo stesso numero di output controllati: se ce ne sono in numero diverso probabilmente c'e` stato qualche erore dell'interfaccia: -1 non è stato trovato nessun done, 1 tutto OK!, 0 c'è qualche errore
#select annotation.sentence_num, count(*) as count from annotation LEFT JOIN done ON annotation.sentence_num=done.sentence_num WHERE completed="Y" AND done.user_id=1 group by annotation.sentence_num;
function getCheckAndDone($userid) {	
	$query = "select distinct count(*) as count from annotation LEFT JOIN done ON annotation.sentence_num=done.sentence_num AND completed='Y' AND annotation.user_id=done.user_id WHERE done.user_id=$userid group by annotation.sentence_num;";
	$result = safe_query($query);	
	if (mysql_num_rows($result) > 1) {
		return 0;
	} else {
		if (mysql_num_rows($result) == 0) {
			return -1;
		}
	}
	
	return 1;
}

function getDBInconsistency($userid, $tasks) {
	$hash_error= array();
	$array_sentencenum = array();
	//get annotation about removed sentences (TODO REMOVE THIS PATCH ASAP!!) 
	$query = "select output_id from annotation left join sentence on annotation.output_id=sentence.num where num is null AND user_id=$userid;";
	$result = safe_query($query);
	while ($row = mysql_fetch_row($result)) {
		array_push($array_sentencenum, $row[0]);
	}
	$query = "select output_id from annotation left join sentence on annotation.sentence_num=sentence.num where num is null AND user_id=$userid;";
	$result = safe_query($query);
	while ($row = mysql_fetch_row($result)) {
		array_push($array_sentencenum, $row[0]);
	}		
	//end get
	
	
	//check if all evaluated sentence has full annotated output
	foreach ($tasks as $taskid) {
		$tasksyscount=countTaskSystem($taskid);
			
		#if ($tasksyscount > 0) {
			$query="SELECT count(*) FROM done LEFT JOIN sentence ON done.sentence_num=sentence.num WHERE user_id=$userid AND completed='Y' AND task_id=$taskid;";
			$result = safe_query($query);
			$row = mysql_fetch_row($result);
			$num_done = $row[0];
				
			$query = "SELECT sentence_num,output_id,count(*) AS count FROM annotation LEFT JOIN sentence ON sentence.num=annotation.sentence_num where task_id=$taskid AND user_id=$userid group by sentence_num,output_id order by sentence_num;";
			$result = safe_query($query);
			$hash = array();
			if (mysql_num_rows($result) > 0) {
				while ($row = mysql_fetch_row($result)) {
					if (!in_array($row[1], $array_sentencenum)) {
						if (isset($hash[$row[0]])) {
							$hash[$row[0]] += 1;
						} else {
							$hash[$row[0]] = 1;
						}
					}	
				}
				while (list ($sentence_num, $countann) = each($hash)) {
					if ($countann < $tasksyscount) {
						$hash_error[$sentence_num] = array($taskid,"");
					}
				}
					
				if ($num_done != count($hash)) {
					$msg = "";
					if ($num_done == 1) {
						$msg = "there is ".$num_done." confirmed annotation on ";
					} else {
						$msg = "there are ".$num_done." confirmed annotations on ";
					}
					$msg .= count($hash) ."!";
					$hash_error["DONE!".$taskid] = array($taskid, $msg);
				}
			}
		#}
	}
	
	$query = "select distinct sentence_num FROM annotation where user_id=$userid";
	$result_annotation = safe_query($query);
	$hash = array();
	while ($row = mysql_fetch_row($result_annotation)) {
		$hash[$row[0]] = 1;
	}
	$query = "select distinct sentence_num,task_id,done.lasttime FROM done LEFT JOIN sentence ON done.sentence_num=sentence.num WHERE user_id=$userid AND completed='Y'";
	$result_done = safe_query($query);	
	while ($row = mysql_fetch_row($result_done)) {
		if (!isset($hash[$row[0]])) {
			$hash_error[$row[0]] = array($row[1],$row[2]);
		} 
	}
	
	return 	$hash_error;
}

function getAnnotationReport ($taskid) {
	$hash_report = array();
	$query = "select eval,type,user_id,count(*) from annotation left join sentence on annotation.output_id=sentence.num where task_id=$taskid group by eval,type,user_id order by eval,type,user_id";
	$result_done = safe_query($query);	
	while ($row = mysql_fetch_row($result_done)) {
		if (isset($hash_report[$row[0]])) {
			$hash_report[$row[0]] .= ",".$row[1]." ".$row[2]." ".$row[3];
		} else {
			$hash_report[$row[0]] = $row[1]." ".$row[2]." ".$row[3];
		} 
	}
	return $hash_report;
}

function getNextUndone($task_id, $user_id){
	if (task_with_batching($task_id)){
		$query = "SELECT sent_num as num from usertasksent where task_id=$task_id AND user_id=$user_id and sent_num not in (SELECT sentence_num from done where user_id=$user_id and completed='Y') order by num limit 1";
	}else{
		$query = "SELECT num from sentence where task_id=$task_id and num not in (SELECT sentence_num from done where user_id=$user_id and completed='Y') order by num limit 1";
	}
	
	$nextid = 0;
	$result = safe_query($query);
	while( $row = mysql_fetch_assoc( $result)){
		$nextid = $row['num'];
	}
	
	if (task_with_batching($task_id)){
		$sentid = getBatchedPrevNext($task_id, $nextid, $user_id);
	}else{
		$sentid = getPrevNext($task_id, $nextid);
	}

	return array($nextid, $sentid[2]);
}


function task_with_batching($task_id){
	$sizes = array();
	$query = "select batch_size from task where id=$task_id";
	$result = safe_query($query);
	while( $row = mysql_fetch_assoc( $result)){
			$sizes['batch_size'] = $row['batch_size'];
	}

	$batch_size = $sizes['batch_size'];

	if($batch_size != 0){
		return True;
	}else{
		return False;
	}
}

function split_task_data($task_id) {
	$sizes = array();
	$query = "select batch_size, overlap_size from task where id=$task_id";
	$result = safe_query($query);
	while( $row = mysql_fetch_assoc( $result)){
			$sizes['batch_size'] = $row['batch_size'];
			$sizes['overlap_size'] = $row['overlap_size'];
	}

	$batch_size = $sizes['batch_size'];
	$overlap_size = $sizes['overlap_size'];
	

	# split sentences into batches if batch_size is set
	if($batch_size != 0){
		# retrieve all source sentence id
		$sentence_ids = array();
		$query = "select num from sentence where task_id=$task_id and linkto is null order by num";
		$result = safe_query($query);
		while( $row = mysql_fetch_assoc( $result)){
			$sentence_ids[] = $row['num'];
		}

		# retrieve all user ids in this task
		$user_ids = array();
		$query = "select user_id from usertask ut left join user u on ut.user_id = u.id where task_id=$task_id and u.status = 'annotator'";
		$result = safe_query($query);
		while( $row = mysql_fetch_assoc( $result)){
			$user_ids[] = $row['user_id'];
		}
		
		if (count($sentence_ids) != ($batch_size - $overlap_size)*count($user_ids) + $overlap_size){

			print('Error when spliting batched data! This may cause a fatal error when batching the data!! Make sure N = (B - O) * U + O. N: number of sentences, B: batch size, O: overlap size, U: user number.');
			return;
		}
		
		# insert overlapped sentence
		$overlapped_ids = array_slice($sentence_ids, 0, $overlap_size);
		$rest_ids = array_slice($sentence_ids, $overlap_size);
		
		foreach($overlapped_ids as $sent_num){
			foreach($user_ids as $user_id){
				$query = "INSERT INTO usertasksent (user_id, task_id, sent_num) VALUES ($user_id, $task_id, $sent_num);";
				safe_query($query);
			}
		}
		# insert non-overlapped sentence
		$rest_count = 0;
		$user_num = 0;
		foreach ($rest_ids as $sent_num){
			if ($rest_count < $batch_size-$overlap_size){
				$rest_count += 1;
			}else{
				$rest_count = 1;
				$user_num += 1;
			}
			$query = "INSERT INTO usertasksent (user_id, task_id, sent_num) VALUES ($user_ids[$user_num], $task_id, $sent_num);";
			safe_query($query);
		}
	}
}

function getRepeatedSentence($task_id, $user_id){
	$hash_report = array();
	if (task_with_batching($task_id)){
		$query = "select dup.num1, a1.evals as eval1, a1.evaltexts as evaltext1, dup.num2, a2.evals as eval2, a2.evaltexts as evaltext2 from (select s1.num as num1, s2.num as num2 from (select s.num as num, s.text as text from usertasksent uts LEFT JOIN sentence s ON uts.sent_num = s.num WHERE uts.task_id=$task_id AND uts.user_id=$user_id) as s1, (select s.num as num, s.text as text from usertasksent uts LEFT JOIN sentence s ON uts.sent_num = s.num WHERE uts.task_id=$task_id AND uts.user_id=$user_id) as s2 where s1.num < s2.num and s1.text = s2.text) as dup left join (select sentence_num, group_concat(eval SEPARATOR '||') as evals, group_concat(evaltext SEPARATOR '||') as evaltexts from annotation left join sentence on annotation.output_id=sentence.num where task_id=$task_id and user_id=$user_id group by output_id) as a1 on dup.num1 = a1.sentence_num left join (select sentence_num, group_concat(eval SEPARATOR '||') as evals, group_concat(evaltext SEPARATOR '||') as evaltexts from annotation left join sentence on annotation.output_id=sentence.num where task_id=$task_id and user_id=$user_id group by output_id) as a2 on dup.num2 = a2.sentence_num";
	}else{
		$query = "select dup.num1, a1.evals as eval1, a1.evaltexts as evaltext1, dup.num2, a2.evals as eval2, a2.evaltexts as evaltext2 from (select s1.num as num1, s2.num as num2 from (select s.num as num, s.text as text from sentence s LEFT JOIN usertask ut ON ut.task_id = s.task_id WHERE s.task_id=$task_id AND ut.user_id=$user_id AND s.linkto is null) as s1, (select s.num as num, s.text as text from sentence s LEFT JOIN usertask ut ON ut.task_id = s.task_id WHERE s.task_id=$task_id AND ut.user_id=$user_id AND s.linkto is null) as s2 where s1.num < s2.num and s1.text = s2.text) as dup left join (select sentence_num, group_concat(eval SEPARATOR '||') as evals, group_concat(evaltext SEPARATOR '||') as evaltexts from annotation left join sentence on annotation.output_id=sentence.num where task_id=$task_id and user_id=$user_id group by output_id) as a1 on dup.num1 = a1.sentence_num left join (select sentence_num, group_concat(eval SEPARATOR '||') as evals, group_concat(evaltext SEPARATOR '||') as evaltexts from annotation left join sentence on annotation.output_id=sentence.num where task_id=$task_id and user_id=$user_id group by output_id) as a2 on dup.num2 = a2.sentence_num";
	}

	$result_done = safe_query($query);	
	while ($row = mysql_fetch_row($result_done)) {
		$hash_report[] = $row;
	}
	return $hash_report;
}

function getAnnotatorReport ($taskid) {
	$hash_report = array();
	$query = "select user_id, username, count(*) as ann_count, sum(LENGTH(text) - LENGTH(replace(text, ' ', '')) + 1) as wrd_count from (select distinct user_id, sentence_num, text from annotation left join sentence on sentence.num = annotation.sentence_num where task_id = $taskid) as annotated_sent left join user on user.id = user_id group by user_id";
	$result = safe_query($query);	
	while( $row = mysql_fetch_assoc( $result)){
		$hash_report[] = $row;
	}
	return $hash_report;
}

function getAnnotations($taskid, $userid){
	$query = "select num, id, eval, gold_eval, gold_text from annotation left join sentence on annotation.output_id=sentence.num where task_id=$taskid and user_id=$userid order by id";
	$result = safe_query($query);
	while( $row = mysql_fetch_assoc( $result)){
		$annotations[] = $row;
	}
	return $annotations;
}

function getAgreementSentences ($taskid) {
	$query = "select lang,linkto,output_id,user_id,eval,evalids,text,tokenization from annotation left join sentence on annotation.output_id=sentence.num where task_id=$taskid AND type != 'source' AND type != 'reference' order by linkto,output_id,user_id,eval";
	#print $query;
	return safe_query($query);	
}

# reverse of getSentence, the $num is the id for target sentence
# values returned will still be source sentence information
function getTargetSentence($num, $taskid){
	$query = "SELECT s1.num, s1.type, s1.lang, s1.text, s1.tokenization FROM sentence as s1 left join sentence as s2 on s2.linkto=s1.num WHERE s2.num=$num AND s2.task_id=$taskid;";
	$result = safe_query($query);	
	$hash =array();
	if (mysql_num_rows($result) > 0) {
		while($row = mysql_fetch_array($result)) {
			$hash[$row["type"]] = array($row["lang"],$row["text"], $row["tokenization"], $row["num"]);
		}
	}
	return $hash;
}

function getTargetIds($sourceid, $taskid){
	$query = "SELECT s2.num FROM sentence as s1 left join sentence as s2 on s2.linkto=s1.num WHERE s1.num=$sourceid AND s1.task_id=$taskid;";
	$result = safe_query($query);	
	$hash =array();
	if (mysql_num_rows($result) > 0) {
		while($row = mysql_fetch_assoc($result)) {
			$hash[] = $row["num"];
		}
	}
	return $hash;
}

#this function return the previous and next ids and the counter of a sentence
function getPrevNext ($taskid, $id) {
	$prevnext = array("","");
	$source_sentences = getSourceSentences($taskid);
	if (count($source_sentences) > 0) {
		$prev="";
		$i = 1;
		while (list($k,$arr) = each($source_sentences)) {
			if ($k == $id) {
				if (list($next,$arr1) = each($source_sentences)) {
					return array($prev, $next, $i);
				}
			}
			$prev=$k;
			$i++;
		}
		return array($prev,"", $i);
	}
	return array("","", 0);
}

function getBatchedPrevNext ($taskid, $id, $userid) {
	$prevnext = array("","");
	$source_sentences = getBatchedSourceSentences($taskid, $userid);
	if (count($source_sentences) > 0) {
		$prev="";
		$i = 1;
		while (list($k,$arr) = each($source_sentences)) {
			if ($k == $id) {
				if (list($next,$arr1) = each($source_sentences)) {
					return array($prev, $next, $i);
				}
			}
			$prev=$k;
			$i++;
		}
		return array($prev,"", $i);
	}
	return array("","", 0);
}
	
### EXPORT FUNCTIONS ###

function saveCSVFile ($intDir, $taskid, $userid="") {
	$taskinfo = getTaskInfo($taskid);	
	$taskname=$taskinfo["name"];
	$tasktype=$taskinfo["type"];
	$taskranges = rangesJson2Array($taskinfo["ranges"]);
	
	$tasksyscount=countTaskSystem ($taskid);
	$query_clause = "";
	if (isset($userid) && $userid != "") {
		$query_clause = "AND user_id=$userid";
	}
	
	//count the number of annotators for the current task
	$query = "select distinct user_id from annotation LEFT JOIN sentence ON annotation.output_id=sentence.num WHERE task_id=$taskid $query_clause";
	$result_annotators = safe_query($query);
	$annotators_count = mysql_num_rows($result_annotators);
	#print "TASK: ". $taskid . " ($query) annotations:".$annotators_count."<br>";
		
	if ($annotators_count > 0) {					
		//loop on users
		while ($row = mysql_fetch_row($result_annotators)) {
			$userid=$row[0];
			$sentence_done = getDoneSentences($taskid,$userid);
			$filecsv = $intDir.$taskname."_ann".$userid.".csv";
			$fh=fopen($filecsv,"w"); 
			
			//save comments
			$comments = getComments($taskid,$userid);	
			if (count($comments) > 0) {
				$filecsv_comment = $intDir.$taskname."_comment".$userid.".csv";
				$fh_comment=fopen($filecsv_comment,"w"); 
				fwrite($fh_comment,"ID\ttype\tcomment\n");
				while (list ($sentnum,$comment) = each($comments)) {
					if (in_array($sentnum,$sentence_done)) {
						fwrite($fh_comment,	str_replace("&quot;","\"",$comment)."\n");
					}
				}
				fclose($fh_comment); 				
			}
				
			
			#print "FILE : $fh $filecsv <br>";
			$count_ann=0;
			#print "<h3>USER: ".$row[0]."</h3> $filecsv [$tasktype]";
				
			if (preg_match("/quality/i", $tasktype)) {
				fwrite($fh,"ID\tlanguage_pair\tsystem\tscore\ttarget_tok_num\ttarget_text\tsource_tok_num\tsource_text\tlasttime\n");
			} else if (preg_match("/errors/i", $tasktype)) {
			fwrite($fh,"ID\tlanguage_pair\tsystem\tannotation_typeid\tannotation_label\ttokenIDs\ttarget_tok_num\ttokenized_target_text\tsource_tok_num\tsource_text\tlasttime\n");
			} else if (preg_match("/wordaligner/i", $tasktype)) {
				fwrite($fh,"ID\tlanguage_pair\tsystem\tannotation_typeid\tannotation_label\ttokenIDs\ttarget_tok_num\ttokenized_target_text\tsource_tok_num\ttokenized_source_text\tlasttime\n");
			} else {
				fwrite($fh,"ID\tlanguage_pair\tsystem\tannotation_typeid\tannotation_label\ttokenIDs\ttarget_tok_num\ttarget_text\tsource_tok_num\tsource_text\tlasttime\n");
			}	

			$query = "SELECT output_id,id,type,eval,evalids,sentence_num,lang,text,tokenization,annotation.lasttime FROM annotation LEFT JOIN sentence ON annotation.output_id=sentence.num WHERE user_id=".$userid." AND task_id=".$taskid." order by id;";
				
			$result_annotation = safe_query($query);
			saveLog($taskid . " " . $taskname . " " . mysql_num_rows($result_annotation) . " " . $query);
			$last_id = "";
			$src_text="";
			while ($row_annotation = mysql_fetch_row($result_annotation)) {
				if (in_array($row_annotation[5],$sentence_done)) {
					if ($last_id != $row_annotation[1]) {
						$last_id = $row_annotation[1];
							
						#get source data
						$query = "SELECT lang,text,tokenization FROM sentence WHERE task_id=$taskid AND id='".$row_annotation[1]."' AND type='source'";
						$result_source = safe_query($query);
						$row_source = mysql_fetch_row($result_source);
						
						$src_text = preg_replace("/[\n|\r]/","",preg_replace("/\t+/"," ",$row_source[1]));
					}
					$label = $taskranges[$row_annotation[3]][0];
								
					$text = preg_replace("/[\n|\r]/","",preg_replace("/\t+/"," ",$row_annotation[7]));
					$trg_tokens = getTokens(preg_replace("/.*_/","", $row_annotation[6]), $text,$row_annotation[8]);
					#if (isset($hash_common_taskanns[$row_annotation[5]])) { 
					$src_tokens = getTokens($row_source[0],$src_text,$row_source[2]);
							
					if (preg_match("/quality/i", $tasktype)) {
						fwrite($fh,$row_annotation[1] ."\t". $row_source[0]."_".$row_annotation[6] ."\t" . $row_annotation[2] ."\t". $row_annotation[3] ."\t".count($trg_tokens)."\t$text\t".count($src_tokens)."\t".$src_text."\n");
					} else if (preg_match("/errors/i", $tasktype)) {
						$splitted_ids = explode(",",preg_replace("/^,/","",$row_annotation[4]));
						$cleaned_ids = array();
						#$savelog=0;
						#if ($row_annotation[1] == "MI009_GSPPA-I_13_REP_en-11" && $taskid=4 && $row_annotation[2] == sys1 && $label == "Lexicon") { 
						#	$savelog =1;
						#}
						foreach ($splitted_ids as $item_ids) {
							$ids = explode(" ", trim($item_ids));
							#if ($savelog == 1) {
							#	saveLog("=>>>>> "  . trim($item_ids));
							#}
							$spaceIDs = array();
							$tokenIDs = array();
							foreach ($ids as $id) {
								if ($id != "") {
									if (preg_match('/-/',$id)) { 
										if (!in_array($id, $spaceIDs)) {
											array_push($spaceIDs, $id);
										}
									} else {
										if (!in_array($id, $tokenIDs)) {
											array_push($tokenIDs, $id);
										}
									}
								}
							}
							
							if (count($tokenIDs) > 0) {
								array_push($cleaned_ids, join(" ",$tokenIDs));
							} else if (count($spaceIDs) == 1) {
								array_push($cleaned_ids, join(" ",$spaceIDs));
							}
						}
						$strids = trim(join(",", $cleaned_ids));
						#if ($savelog == 1) {
						#	saveLog($row_annotation[4] . ">>>> " . $strids);
						#}
						if ($row_annotation[3] > 1 && $strids == "") {
							continue;
						}
													
						fwrite($fh,$row_annotation[1] ."\t". $row_source[0]."_".$row_annotation[6] ."\t". $row_annotation[2] ."\t".$row_annotation[3]."\t".$label ."\t". $strids ."\t". count($trg_tokens)."\t".join(" ", $trg_tokens)."\t".count($src_tokens)."\t".$src_text."\t".$row_annotation[9]."\n"); 	
					} else if (preg_match("/wordaligner/i", $tasktype)) {
						fwrite($fh,$row_annotation[1] ."\t". $row_source[0]."_".$row_annotation[6] ."\t". $row_annotation[2] ."\t".$row_annotation[3] ."\t".$label."\t". $row_annotation[4] ."\t". count($trg_tokens)."\t".join(" ", $trg_tokens)."\t".count($src_tokens)."\t".join(" ", $src_tokens)."\n"); 	
					} else {
						fwrite($fh,$row_annotation[1] ."\t". $row_source[0]."_".$row_annotation[6] ."\t". $row_annotation[2] ."\t". $row_annotation[3] ."\t".$label."\t". $row_annotation[4] ."\t". count($trg_tokens)."\t$text\t".count($src_tokens)."\t".$src_text."\n"); 	
					}
					$count_ann++;
					#print $row_annotation[0] ."\t". $row_annotation[1] ."\t". $row_annotation[2] ."\t". $row_annotation[3] ."\n";
				}
			}
			fclose($fh); 
			saveLog("SAVED FILE $filecsv: $count_ann $annotators_count $taskname " . mysql_num_rows($result_annotation));
			if ($count_ann == 0) {
				unlink($filecsv);
			}
			#print "Saved $count_ann annotations."; 
		}
	}	
}

function saveXMLFile ($intDir, $taskid, $userid="") {
	$taskinfo = getTaskInfo($taskid);	
	$taskname = $taskinfo["name"];
	$tasktype = $taskinfo["type"];
	$taskranges = rangesJson2Array($taskinfo["ranges"]);
	$tasksyscount=countTaskSystem ($taskid);
	
	$query_clause = "";
	if (isset($userid) && $userid != "") {
		$query_clause = "AND user_id=$userid";
	}
	
	//count the number of annotators for the current task
	$query = "select distinct user_id from annotation LEFT JOIN sentence ON annotation.output_id=sentence.num WHERE task_id=$taskid $query_clause";
	$result_annotators = safe_query($query);
	$annotators_count = mysql_num_rows($result_annotators);
	#print "TASK: ". $taskid . " ($query) annotations:".$annotators_count."<br>";
		
	if ($annotators_count > 0) {
		//loop on users
		while ($row = mysql_fetch_row($result_annotators)) {
			$userid=$row[0];
			$sentence_done = getDoneSentences($taskid,$userid);
			
			$filecsv = $intDir.$taskname."_ann".$userid.".xml";
			$fh=fopen($filecsv,"w"); 
			fwrite($fh,"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<".$tasktype."_task>\n");
			#print "FILE : $fh $filecsv <br>";
			$count_ann=0;
			#print "<h3>USER: ".$row[0]."</h3> $filecsv";
			
			$query = "SELECT output_id,id,type,eval,evalids,sentence_num,lang,text,tokenization FROM annotation LEFT JOIN sentence ON annotation.output_id=sentence.num WHERE user_id=".$userid." AND task_id=".$taskid." order by id,type,eval;";
			$result_annotation = safe_query($query);
			saveLog($taskid . " " . $taskname . " " . mysql_num_rows($result_annotation) . " " . $query);
			$last_id = "";
			$system_id = "";
			$tokens=array();
			$sourcetokens=array();
				
			//get comments
			$comments = getComments($taskid,$userid);	
						
			while ($row_annotation = mysql_fetch_row($result_annotation)) {				
				if (in_array($row_annotation[5],$sentence_done)) {
					$label = $taskranges[$row_annotation[3]][0];
			
					if ($last_id != $row_annotation[1]) {
						if ($last_id != "") {
							fwrite($fh,"    </target>\n");	
							fwrite($fh,"  </system>\n </eval_item>\n");
						}
						$last_id = $row_annotation[1];
						$system_id = "";
						
						#get source data
						$query = "SELECT lang,text,tokenization FROM sentence WHERE task_id=$taskid AND id='".$row_annotation[1]."' AND type='source'";
						$result_source = safe_query($query);
						$row_source = mysql_fetch_row($result_source);
						fwrite($fh," <eval_item ID='".$last_id."' language_pair='".$row_source[0]."_".$row_annotation[6]."'>\n");
				
						$src_text = preg_replace("/[\n|\r]/","",preg_replace("/\t+/"," ",$row_source[1]));
						if ($src_text != "") {
							$sourcetokens = getTokens($row_source[0], $src_text, $row_source[2]);
							fwrite($fh,"  <source tok_num='".count($sourcetokens)."'>\n    <text>".xml_escape($src_text)."</text>\n");
								
							#add tokens
							if (preg_match("/wordaligner/i", $tasktype)) {
								$i=1;
								fwrite($fh,"    <tokens>\n");
								foreach ($sourcetokens as $token) {
									fwrite($fh,"      <token id='$i'>".xml_escape($token)."</token>\n");
									$i++;
								}
								fwrite($fh,"    </tokens>\n");								
							}
							fwrite($fh,"  </source>\n");
						}	
					}	
					if ($system_id != $row_annotation[2]) {
					
						if ($system_id != "") {
							fwrite($fh,"    </target>\n");	
							fwrite($fh,"  </system>\n");
						}
						$system_id = $row_annotation[2];
				
						$text = preg_replace("/[\n|\r]/","",preg_replace("/\t+/"," ",$row_annotation[7]));
						fwrite($fh,"  <system name='".$system_id."'");
						if (preg_match("/quality/i", $tasktype)) {
							fwrite($fh," score='".$row_annotation[3]."'");
						}
						fwrite($fh,">\n");
						if (count($comments) > 0) {
							if (array_key_exists($row_annotation[0], $comments)) {
								fwrite($fh,"    <comment>".preg_replace("/.+\t/","",$comments{$row_annotation[0]}) ."</comment>\n");
							}									
						}
						$tokens = getTokens(preg_replace("/.*_/","",$row_annotation[6]), $text, $row_annotation[8]);
						fwrite($fh,"    <target tok_num='".count($tokens)."'>\n      <text>".xml_escape($text)."</text>\n");	
						
						#add tokens
						$i=1;
						fwrite($fh,"      <tokens>\n");
						foreach ($tokens as $token) {
							fwrite($fh,"        <token id='$i'>".xml_escape($token)."</token>\n");
							$i++;
						}
						fwrite($fh,"      </tokens>\n");								
					}
					
					if (preg_match("/errors/i", $tasktype)) {
						$splitted_ids = explode(",",preg_replace("/^,/","",$row_annotation[4]));
						$cleaned_ids = array();
							
						foreach ($splitted_ids as $item_ids) {
							$ids = explode(" ", trim($item_ids));
							#if ($savelog == 1) {
							#	saveLog("=>>>>> "  . trim($item_ids));
							#}
							$spaceIDs = array();
							$tokenIDs = array();
							foreach ($ids as $id) {
								if ($id != "") {
									if (preg_match('/-/',$id)) { 
										if (!in_array($id, $spaceIDs)) {
											array_push($spaceIDs, $id);																						
										}
									} else {
										if (!in_array($id, $tokenIDs)) {
											array_push($tokenIDs, $id);
										}
									}
								}
							}
							
							if (count($tokenIDs) > 0) {
								array_push($cleaned_ids, join(" ",$tokenIDs));
							} else if (count($spaceIDs) == 1) {
								array_push($cleaned_ids, join(" ",$spaceIDs));
							}
						}
						$strids = trim(join(",", $cleaned_ids));
						
						#if ($savelog == 1) {
						#	saveLog($row_annotation[4] . ">>>> " . $strids);
						#}
						if ($row_annotation[3] > 1 && $strids == "") {
							continue;
						}
						fwrite($fh,"      <annotation type='error' typeid='".$row_annotation[3]."' label='".$label."'>\n");
						foreach ($cleaned_ids as $ids) {
							fwrite($fh,"        <span>\n");
							foreach (explode(" ", $ids) as $id) {
								fwrite($fh,"          <token id='$id'");
								if (preg_match('/-/',$id)) {
									fwrite($fh,"/>\n");
								} else {
									fwrite($fh,">".xml_escape($tokens[($id-1)])."</token>\n");
								} 	
							}
							fwrite($fh,"        </span>\n");								
						}							
						fwrite($fh,"      </annotation>\n");
					
					} else if (preg_match("/wordaligner/i", $tasktype)) {
						fwrite($fh,"      <annotation type='wordalign' typeid='".$row_annotation[3]."' label='$label'>\n");
						$splitted_ids = explode(" ",$row_annotation[4]);
							
						foreach ($splitted_ids as $id) {
							$xy = explode("-", $id);
							if (isset($sourcetokens[$xy[0]-1]) && isset($tokens[$xy[1]-1])) {
								fwrite($fh,"        <span>\n");
								fwrite($fh,"          <token from='source' id='".$xy[0]."'>".xml_escape($sourcetokens[$xy[0]-1])."</token>\n"); 	
								fwrite($fh,"          <token from='target' id='".$xy[1]."'>".xml_escape($tokens[$xy[1]-1])."</token>\n"); 	
								fwrite($fh,"        </span>\n");
							}
						}
						fwrite($fh,"      </annotation>\n");										
					}
				}
				$count_ann++;					  	
				#print $row_annotation[0] ."\t". $row_annotation[1] ."\t". $row_annotation[2] ."\t". $row_annotation[3] ."\n";	
			}
						
			if (mysql_num_rows($result_annotation) > 0 && count($sentence_done) > 0) {
				fwrite($fh,"    </target>\n");	
				fwrite($fh,"  </system>\n</eval_item>\n");
			}
			fwrite($fh,"</".$tasktype."_task>\n");				
			fclose($fh); 
				
			saveLog("SAVED FILE $filecsv: $count_ann $annotators_count $taskname " . mysql_num_rows($result_annotation));
			if ($count_ann == 0) {
				unlink($filecsv);
			}
			#print "Saved $count_ann annotations."; 
		}
	}
}

function saveIOB2File ($intDir, $taskid, $userid="") {
	$taskinfo = getTaskInfo($taskid);	
	$taskname=$taskinfo["name"];
	$tasktype=$taskinfo["type"];
	$taskranges = rangesJson2Array($taskinfo["ranges"]);
	
	$tasksyscount=countTaskSystem ($taskid);
	$query_clause = "";
	if (isset($userid) && $userid != "") {
		$query_clause = "AND user_id=$userid";
	}
	
	//count the number of annotators for the current task
	$query = "select distinct user_id from annotation LEFT JOIN sentence ON annotation.output_id=sentence.num WHERE task_id=$taskid $query_clause";
	$result_annotators = safe_query($query);
	$annotators_count = mysql_num_rows($result_annotators);
	#print "TASK: ". $taskid . " ($query) annotations:".$annotators_count."<br>";
		
	if ($annotators_count > 0) {	
		//loop on users
		while ($row = mysql_fetch_row($result_annotators)) {
			$userid=$row[0];
			$sentence_done = getDoneSentences($taskid,$userid);
			$filecsv = $intDir.$taskname."_ann".$userid.".iob2";
			$fh=fopen($filecsv,"w"); 
			
			//save comments
			$comments = getComments($taskid,$userid);	
			if (count($comments) > 0) {
				$filecsv_comment = $intDir.$taskname."_comment".$userid.".csv";
				$fh_comment=fopen($filecsv_comment,"w"); 
				fwrite($fh_comment,"ID\ttype\tcomment\n");
				while (list ($sentnum,$comment) = each($comments)) {
					if (in_array($sentnum,$sentence_done)) {
						fwrite($fh_comment,	str_replace("&quot;","\"",$comment)."\n");
					}
				}
				fclose($fh_comment); 				
			}
			
			$query = "SELECT output_id,id,type,eval,evalids,sentence_num FROM annotation LEFT JOIN sentence ON annotation.output_id=sentence.num WHERE user_id=".$userid." AND task_id=".$taskid." order by id,type,eval;";
			$result_annotation = safe_query($query);
			$last_id="";		
			$tot_row = mysql_num_rows($result_annotation);
			$num_row = 1;
			$annotatedTokens = array();
			while ($row_annotation = mysql_fetch_row($result_annotation)) {	
				if ($num_row == $tot_row) {
					$label = $taskranges[$row_annotation[3]][0];
					$evalitems = preg_split("/,/", $row_annotation[4]);
					foreach($evalitems as $item) {
						$tokenitems = preg_split("/ /", $item);
						for ($l = 0; $l < count($tokenitems); $l++) {
							if ($l==0) {
								if (isset($annotatedTokens{$tokenitems[$l]})) {
									$annotatedTokens{$tokenitems[$l]} .= " B-".strtoupper(substr($label,0,3));
								} else {
									$annotatedTokens{$tokenitems[$l]} = " B-".strtoupper(substr($label,0,3));
								}
							} else {
								if (isset($annotatedTokens{$tokenitems[$l]})) {
									$annotatedTokens{$tokenitems[$l]} .= " I-".strtoupper(substr($label,0,3));
								} else {
									$annotatedTokens{$tokenitems[$l]} = "I-".strtoupper(substr($label,0,3));
								}
							}
						} 
					} 
				}
					
				if ($last_id != $row_annotation[1] || $num_row == $tot_row) {
					if (count($annotatedTokens) > 0) {
						#get source data
						$query = "SELECT lang,text,tokenization FROM sentence WHERE task_id=$taskid AND id='".$last_id."' AND type='source'";
						$result_source = safe_query($query);
						$row_source = mysql_fetch_row($result_source);
				
						$tokens = getTokens($row_source[0], $row_source[1], $row_source[2]);	
						fwrite($fh, "# FILE: ".$last_id."\n");
						
						$i=1;
						foreach ($tokens as $token) {
							fwrite($fh,xml_escape($token)."\t");
							if (isset($annotatedTokens{$i})) {
								fwrite($fh,$annotatedTokens{$i}."\n");
							} else {
								fwrite($fh,"O\n");
							}
							$i++;
						}
						fwrite($fh,"\n\n");
						$annotatedTokens = array();
					}
					$last_id = $row_annotation[1];
				}
				
				if (!in_array($row_annotation[5],$sentence_done)) {
					$annotatedTokens = array();
					continue;
				}
			
				$label = $taskranges[$row_annotation[3]][0];
				$evalitems = preg_split("/,/", $row_annotation[4]);
				foreach($evalitems as $item) {
					$tokenitems = preg_split("/ /", $item);
					for ($l = 0; $l < count($tokenitems); $l++) {
						if ($l==0) {
							if (isset($annotatedTokens{$tokenitems[$l]})) {
								$annotatedTokens{$tokenitems[$l]} .= " B-".strtoupper(substr($label,0,3));
							} else {
								$annotatedTokens{$tokenitems[$l]} = " B-".strtoupper(substr($label,0,3));
							}
						} else {
							if (isset($annotatedTokens{$tokenitems[$l]})) {
								$annotatedTokens{$tokenitems[$l]} .= " I-".strtoupper(substr($label,0,3));
							} else {
								$annotatedTokens{$tokenitems[$l]} = "I-".strtoupper(substr($label,0,3));
							}
						}
					} 
				} 
					
				$num_row++;	
			}
		}	
	}
}


function exportTaskCSV ($taskid) {
	$intDir="/tmp";
	if (!is_dir($intDir)) {
		mkdir($intDir, 0777);
	}
	$date = date('Ymd_his', time());
	
	$intDir .= "/mtequal_".$taskid."_".$date."/";
	if (!is_dir($intDir)) {
		mkdir($intDir, 0777);
	}

	saveCSVFile($intDir, $taskid);
	
	$filezip = "/tmp/mtequal-CSV_$date.zip";
	
	$zip = new ZipArchive();
	if($zip->open($filezip, ZIPARCHIVE::CREATE)!==TRUE){
		print "ERROR! Sorry ZIP creation failed.";
	}
	$files= scandir($intDir);
	//var_dump($files);
	//unset($files[0],$files[1]);
	foreach ($files as $file) {
		#print "ADD to zip: $file<br>";
		if ($file != "." && $file != "..") { 
			if (isset($userid) && $userid != null) {
				$zip->addFile($intDir.$file,"mtequal_CSV_".$userid."-".$date."/".$file);
			} else {
  				$zip->addFile($intDir.$file,"mtequal_CSV-".$date."/".$file);
  			}
  		}    
	}
	$zip->close();

	if (file_exists($filezip)) {
		#print $filezip . " (" . file_exists($filezip) .")";
		readfile($filezip);
		unlink($filezip);
	}
	deleteDirectory($intDir);
	exit(0);
}

function exportTaskXML ($taskid) {
	$intDir="/tmp";
	if (!is_dir($intDir)) {
		mkdir($intDir, 0777);
	}
	$date = date('Ymd_his', time());
	#$intDir =$_SERVER['DOCUMENT_ROOT'] ."/mtequal_".$date."/";
	$intDir .= "/mtequal";
	
	$query_clause = " status='annotator'";
	if (isset($userid) && $userid != null) {
		$query_clause = " user.id='".$userid."'";
		$intDir .= "_".$userid;	
	}
	$intDir .= "_".$date."/";	
	if (!is_dir($intDir)) {
		mkdir($intDir, 0777);
	}
	
	saveXMLFile($intDir, $taskid);
	
	$filezip = "/tmp/mtequal-XML_$date.zip";
	
	$zip = new ZipArchive();
	if($zip->open($filezip, ZIPARCHIVE::CREATE)!==TRUE){
		print "ERROR! Sorry ZIP creation failed.";
	}
	$files= scandir($intDir);
	//var_dump($files);
	//unset($files[0],$files[1]);
	foreach ($files as $file) {
		#print "ADD to zip: $file<br>";
		if ($file != "." && $file != "..") { 
			if (isset($userid) && $userid != null) {
				$zip->addFile($intDir.$file,"mtequal_XML_".$userid."-".$date."/".$file);
			} else {
  				$zip->addFile($intDir.$file,"mtequal_XML-".$date."/".$file);
  			}
  		}    
	}
	$zip->close();

	if (file_exists($filezip)) {
		#print $filezip . " (" . file_exists($filezip) .")";
		readfile($filezip);
		
		unlink($filezip);
	}
	deleteDirectory($intDir);
	exit(0);	
}

function exportTaskIOB2 ($taskid) {
	$intDir="/tmp";
	if (!is_dir($intDir)) {
		mkdir($intDir, 0777);
	}
	$date = date('Ymd_his', time());
	#$intDir =$_SERVER['DOCUMENT_ROOT'] ."/mtequal_".$date."/";
	$intDir .= "/mtequal";
	
	#$query_clause = " status='annotator'";
	if (isset($taskid) && $taskid != "") {
		#$query_clause = " user.id='".$userid."'";
		$intDir .= "_".$taskid;	
	}
	$intDir .= "_".$date."/";
	if (!is_dir($intDir)) {
		mkdir($intDir, 0777);
	}

	saveIOB2File($intDir, $taskid);
	$filezip = "/tmp/mtequal-IOB2_$date.zip";
	
	$zip = new ZipArchive();
	if($zip->open($filezip, ZIPARCHIVE::CREATE)!==TRUE){
		print "ERROR! Sorry ZIP creation failed.";
	}
	$files= scandir($intDir);
	//var_dump($files);
	//unset($files[0],$files[1]);
	foreach ($files as $file) {
		#print "ADD to zip: $file<br>";
		if ($file != "." && $file != "..") { 
			if (isset($userid) && $userid != null) {
				$zip->addFile($intDir.$file,"mtequal_IOB2_".$userid."-".$date."/".$file);
			} else {
  				$zip->addFile($intDir.$file,"mtequal_IOB2-".$date."/".$file);
  			}
  		}    
	}
	$zip->close();

	if (file_exists($filezip)) {
		#print $filezip . " (" . file_exists($filezip) .")";
		readfile($filezip);
		unlink($filezip);
	}
	deleteDirectory($intDir);
	exit(0);	
}

function exportCSV ($userid) {
	$intDir="/tmp";
	if (!is_dir($intDir)) {
		mkdir($intDir, 0777);
	}
	$date = date('Ymd_his', time());
	#$intDir =$_SERVER['DOCUMENT_ROOT'] ."/mtequal_".$date."/";
	$intDir .= "/mtequal_".$userid."_".$date."/";
	if (!is_dir($intDir)) {
		mkdir($intDir, 0777);
	}
	
	$tasks = getTasks($userid);
	while (list ($taskid,$arrinfo) = each($tasks)) {
		saveCSVFile($intDir, $taskid, $userid);
	} 
				
	$filezip = "/tmp/mtequal-CSV_$date.zip";
	
	$zip = new ZipArchive();
	if($zip->open($filezip, ZIPARCHIVE::CREATE)!==TRUE){
		print "ERROR! Sorry ZIP creation failed.";
	}
	$files= scandir($intDir);
	//var_dump($files);
	//unset($files[0],$files[1]);
	foreach ($files as $file) {
		#print "ADD to zip: $file<br>";
		if ($file != "." && $file != "..") { 
			if (isset($userid) && $userid != null) {
				$zip->addFile($intDir.$file,"mtequal_CSV_".$userid."-".$date."/".$file);
			} else {
  				$zip->addFile($intDir.$file,"mtequal_CSV-".$date."/".$file);
  			}
  		}    
	}
	$zip->close();

	if (file_exists($filezip)) {
		#print $filezip . " (" . file_exists($filezip) .")";
		readfile($filezip);
		unlink($filezip);
	}
	deleteDirectory($intDir);
	exit(0);
	
}

#save XML files
function exportXML ($userid) {
	$intDir="/tmp";
	if (!is_dir($intDir)) {
		mkdir($intDir, 0777);
	}
	$date = date('Ymd_his', time());
	#$intDir =$_SERVER['DOCUMENT_ROOT'] ."/mtequal_".$date."/";
	$intDir .= "/mtequal";
	
	$query_clause = " status='annotator'";
	if (isset($userid) && $userid != null) {
		$query_clause = " user.id='".$userid."'";
		$intDir .= "_".$userid;	
	}
	$intDir .= "_".$date."/";	
	if (!is_dir($intDir)) {
		mkdir($intDir, 0777);
	}
	
	$tasks = getTasks($userid);
	while (list ($taskid,$arrinfo) = each($tasks)) {
			saveXMLFile($intDir, $taskid, $userid);
	} 	
			
	$filezip = "/tmp/mtequal-XML_$date.zip";
	
	$zip = new ZipArchive();
	if($zip->open($filezip, ZIPARCHIVE::CREATE)!==TRUE){
		print "ERROR! Sorry ZIP creation failed.";
	}
	$files= scandir($intDir);
	//var_dump($files);
	//unset($files[0],$files[1]);
	foreach ($files as $file) {
		#print "ADD to zip: $file<br>";
		if ($file != "." && $file != "..") { 
			if (isset($userid) && $userid != null) {
				$zip->addFile($intDir.$file,"mtequal_XML_".$userid."-".$date."/".$file);
			} else {
  				$zip->addFile($intDir.$file,"mtequal_XML-".$date."/".$file);
  			}
  		}    
	}
	$zip->close();

	if (file_exists($filezip)) {
		#print $filezip . " (" . file_exists($filezip) .")";
		readfile($filezip);
		
		unlink($filezip);
	}
	deleteDirectory($intDir);
	exit(0);	
}

### PRESENTATION FUNCTION ###
function showFixedSentence ($lang, $text, $type = "", $tokenize = 0, $idx = "", $hashErrors = array(), $colorRange = array()) {
	global $languages;
	$spacebg = " class=token";
	$tokenbg = " class=token";
	if ($type == "output") {
		$tokens = getTokens($lang, $text, $tokenize);
		$text = "";
		$hashTokenidErrortype = array();
		if (count($tokens) > 0) {
			for ($i=0; $i<count($tokens); $i++) {
				#print "$i. ".$tokens[$i]."<br>";
				$hashTokenidErrortype{($i+1)} = array();
				$hashTokenidErrortype{($i+1)."-".($i+2)} = array();
			}
			$level=0;
			while (list ($errID, $errARRAY) = each($hashErrors)) {
			  if (!empty($errARRAY[0])) {
			  	$tokids = preg_split("/,/", $errARRAY[0]);
				//check the max number of lenght of the current segment of tokens
				$loop = 0;
				while (count($tokids) > 0) {
					$catched_tids = array();
					foreach ($tokids as $tids) {
						if (trim($tids) == "") {
							continue;
						}
						$span = preg_split("/ /", $tids);
						if ($level > 0) {
							for ($l=0; $l < count($hashTokenidErrortype{1}); $l++) {
								$freePos=0;
								foreach ($span as $tid) {
									if (!isset($hashTokenidErrortype{$tid})) {
										array_push($catched_tids, $tids);
										break;
									}
									if (isset($hashTokenidErrortype{$tid}[$l]) && $hashTokenidErrortype{$tid}[$l]=="FFF") {
										$freePos++;
									}	
								
								}
								if ($freePos == count($span)) {
									foreach ($span as $tid) {
										$hashTokenidErrortype{$tid}[$l] = $colorRange{$errID}[1];
									}
									array_push($catched_tids, $tids);
									break;
								}
							}	
							if (in_array($tids, $catched_tids)) {
								continue;
							}
							
						}
						$freePos=0;
						foreach ($span as $tid) {
							#check if all token position are free
							if (isset($hashTokenidErrortype{$tid}) && count($hashTokenidErrortype{$tid}) == $loop) {
								$freePos++;
							}
						}
						if ($freePos == count($span)) {
							foreach ($span as $tid) {
								if (isset($hashTokenidErrortype{$tid})) {
									array_push($hashTokenidErrortype{$tid},$colorRange{$errID}[1]);
								}
							}
							array_push($catched_tids, $tids);
						}
					}
					$tokids = array_diff($tokids, $catched_tids);
				
					//put #FFF foreach remain empty value
					foreach($hashTokenidErrortype as $tid => $val) {
						if (count($hashTokenidErrortype{$tid}) == $loop) {
							array_push($hashTokenidErrortype{$tid}, "FFF");
						}
					}
					$loop++;
				  }
				}
				$level++;
			}
		}
	
		$id=1;
		foreach ($tokens as $token) {
			if ($token == "__BR__") {
				$text .= "<br>";
			} else {
				$text .= "<div id='$idx.$id' $tokenbg>$token";
				if (isset($hashTokenidErrortype{$id})) {
					foreach ($hashTokenidErrortype{$id} as $col) {
						$text .= "<nobr><div style='font-size: 1px;
  background: #". $col .";border-bottom: 1px solid ";
						if ($col != "FFF") {
							$text .= "#888;";
						} else {
							$text .= "#FFF;";
						}
						$text .= "height: 4px'>&nbsp;</div></nobr>";
					}
				}
				$text .= "</div>";
				if ($tokenize != 3) {
					$spaceId=$id."-".($id+1);
					$text .= "<div id='$idx.".$spaceId."' $spacebg>&nbsp;";
					if (isset($hashTokenidErrortype{$spaceId})) {
						foreach ($hashTokenidErrortype{$spaceId} as $col) {
							$text .= "<nobr><div style='font-size: 5px;
  background: #". $col ."; border-bottom: 1px solid ";
							if ($col != "FFF") {
								$text .= "#888;";
							} else {
								$text .= "#FFF;";
							}
							$text .= "height: 4px'>&nbsp;</div></nobr>";
						}
					}
					$text .= "</div>";
				}
				$id++;
			}
		}
	}

	$html="<div class='cell $type";
  if($type == "context") {
    $html.="'><a href='https://en.wikipedia.org/wiki/".$text."' target='_blank'>".$text."</a></div>";
    return $html;
  }
	if (isset($languages[$lang][2]) && $languages[$lang][2] == "rtl") {
		$html.=" rtl";
	}
	$html.="'>$text</div>";
	return $html;
}


### PRESENTATION FUNCTION ###
function showSentence ($lang, $text, $type = "", $tokenize = 0, $idx = "", $hashErrors = array(), $colorRange = array()) {
	global $languages;
	$spacebg = " class=token onmouseover=\"this.className='orangebg orangeborderb'\" onmouseout=\"this.className='whitebg whiteborderb'\"";
	$tokenbg = " class=token onmouseover=\"this.className='orangeborderb'\" onmouseout=\"this.className='whiteborderb'\"";
	if ($type == "output") {
		$tokens = getTokens($lang, $text, $tokenize);
		$text = "";
		$hashTokenidErrortype = array();
		if (count($tokens) > 0) {
			for ($i=0; $i<count($tokens); $i++) {
				#print "$i. ".$tokens[$i]."<br>";
				$hashTokenidErrortype{($i+1)} = array();
				$hashTokenidErrortype{($i+1)."-".($i+2)} = array();
			}
			$level=0;
			while (list ($errID, $errARRAY) = each($hashErrors)) {
			  if (!empty($errARRAY[0])) {
			  	$tokids = preg_split("/,/", $errARRAY[0]);
				//check the max number of lenght of the current segment of tokens
				$loop = 0;
				while (count($tokids) > 0) {
					$catched_tids = array();
					foreach ($tokids as $tids) {
						if (trim($tids) == "") {
							continue;
						}
						$span = preg_split("/ /", $tids);
						if ($level > 0) {
							for ($l=0; $l < count($hashTokenidErrortype{1}); $l++) {
								$freePos=0;
								foreach ($span as $tid) {
									if (!isset($hashTokenidErrortype{$tid})) {
										array_push($catched_tids, $tids);
										break;
									}
									if (isset($hashTokenidErrortype{$tid}[$l]) && $hashTokenidErrortype{$tid}[$l]=="FFF") {
										$freePos++;
									}	
								
								}
								if ($freePos == count($span)) {
									foreach ($span as $tid) {
										$hashTokenidErrortype{$tid}[$l] = $colorRange{$errID}[1];
									}
									array_push($catched_tids, $tids);
									break;
								}
							}	
							if (in_array($tids, $catched_tids)) {
								continue;
							}
							
						}
						$freePos=0;
						foreach ($span as $tid) {
							#check if all token position are free
							if (isset($hashTokenidErrortype{$tid}) && count($hashTokenidErrortype{$tid}) == $loop) {
								$freePos++;
							}
						}
						if ($freePos == count($span)) {
							foreach ($span as $tid) {
								if (isset($hashTokenidErrortype{$tid})) {
									array_push($hashTokenidErrortype{$tid},$colorRange{$errID}[1]);
								}
							}
							array_push($catched_tids, $tids);
						}
					}
					$tokids = array_diff($tokids, $catched_tids);
				
					//put #FFF foreach remain empty value
					foreach($hashTokenidErrortype as $tid => $val) {
						if (count($hashTokenidErrortype{$tid}) == $loop) {
							array_push($hashTokenidErrortype{$tid}, "FFF");
						}
					}
					$loop++;
				  }
				}
				$level++;
			}
		}
	
		$id=1;
		foreach ($tokens as $token) {
			if ($token == "__BR__") {
				$text .= "<br>";
			} else {
				$text .= "<div id='$idx.$id' $tokenbg>$token";
				if (isset($hashTokenidErrortype{$id})) {
					foreach ($hashTokenidErrortype{$id} as $col) {
						$text .= "<nobr><div style='font-size: 1px;
  background: #". $col .";border-bottom: 1px solid ";
						if ($col != "FFF") {
							$text .= "#888;";
						} else {
							$text .= "#FFF;";
						}
						$text .= "height: 4px'>&nbsp;</div></nobr>";
					}
				}
				$text .= "</div>";
				if ($tokenize != 3) {
					$spaceId=$id."-".($id+1);
					$text .= "<div id='$idx.".$spaceId."' $spacebg>&nbsp;";
					if (isset($hashTokenidErrortype{$spaceId})) {
						foreach ($hashTokenidErrortype{$spaceId} as $col) {
							$text .= "<nobr><div style='font-size: 5px;
  background: #". $col ."; border-bottom: 1px solid ";
							if ($col != "FFF") {
								$text .= "#888;";
							} else {
								$text .= "#FFF;";
							}
							$text .= "height: 4px'>&nbsp;</div></nobr>";
						}
					}
					$text .= "</div>";
				}
				$id++;
			}
		}
	}
		$html="<div class='cell $type";
  if($type == "context") {
    $html.="'><a href='https://en.wikipedia.org/wiki/".$text."' target='_blank'>".$text."</a></div>";
    return $html;
  }
	if (isset($languages[$lang][2]) && $languages[$lang][2] == "rtl") {
		$html.=" rtl";
	}
	$html.="'>$text</div>";
	return $html;
}
# tokenization values:
# 0: NO
# 1: YES, using spaces only
# 2: YES, using spaces and punctuations
# 3: YES, character by character	
function getTokens  ($lang, $text, $tokenization = 2) {
	$tokens=array();
	if ($tokenization == 0) {
		array_push($tokens, trim($text));
	} else {
		preg_match_all('/./u', trim($text), $tokenized_text);
		if ($tokenization == 3) {
			foreach ($tokenized_text[0] as $ch) {
				if (trim($ch) != "") {
					array_push($tokens, $ch);
				}
			}
		} else {
			$token="";
			foreach ($tokenized_text[0] as $ch) {
				#print " ($ch)" . chr($ch);
				#other special character
				#ord($ch)=194 (No-break space) U+00A0 &#160; 
				if (($tokenization==1 && ($ch == " " || ord($ch) == 194)) || 
					($tokenization==2 && ($ch == " " || ord($ch) == 194 || preg_match("/[\!|\?|\"|\'|\-|\/|\$|,|:|;|\.|\(|\)|\[|\]|\{|\}]/",$ch)))) {
					if ($ch == ";" and $token == "&nbsp"){
						array_push($tokens, "&nbsp;");
						$token="";
					} else{
						if (strlen($token) > 0) {
							array_push($tokens, $token);
							$token="";
						}
						if (trim($ch) != "") {
							array_push($tokens, $ch);
						}
					}
				} else {
					$token .="$ch";
				}	
			}
			if (strlen($token) > 0) {
				array_push($tokens, $token);					
			}
		}
	}
	return $tokens;
}	
	

#$date = "2009-03-04 17:45";
#$result = nicetime($date); // 2 days ago
function niceTime($date) {
	if(empty($date)) {
        return "No date provided";
    }
    
    $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
    $lengths = array("60","60","24","7","4.35","12","10");
    
    $now = time();
    $unix_date = strtotime($date);
    
       // check validity of date
    if(empty($unix_date)) {    
        return "Bad date";
    }

    // is it future date or past date
    if($now > $unix_date) {    
        $difference = $now - $unix_date;
        $tense = "ago";
        
    } else {
        $difference = $unix_date - $now;
        $tense = "from now";
    }
    
    for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
        $difference /= $lengths[$j];
    }
    
    $difference = round($difference);
    
    if($difference != 1) {
        $periods[$j].= "s";
    }
    
    return "$difference $periods[$j] {$tense}";
}

/*
 * return an array whose elements are shuffled in random order.
 */
function shuffle_assoc($list,$id) { 
  if (!is_array($list)) return $list; 

  $keys = array_keys($list); 
  //shuffle($keys); 
  //each user will be a difference random output ordered
  seoShuffle($keys,$id);
  $random = array(); 
  foreach ($keys as $key) 
    $random[$key] = $list[$key]; 

  return $random; 
} 

#the same randomized result each time that list is generated.
function seoShuffle(&$items,$seed) { 
    mt_srand($seed); 
    for ($i = count($items) - 1; $i > 0; $i--){ 
        $j = @mt_rand(0, $i); 
        $tmp = $items[$i]; 
        $items[$i] = $items[$j]; 
        $items[$j] = $tmp; 
    } 
} 

/*
 * Logging
 */
#saveLog("PASSO! $eval $source_id,$target_id,$user_id,$action\n");
function saveLog($line) {
	if (SAVELOG == 1) {
		$time = date( "d/m/Y H:m:s", time() );

		$myFile = "/tmp/mtequal.log";
		$fh = fopen($myFile, 'a') or die("can't open file");
		fwrite($fh, "$time\t$line\n");
		fclose($fh);
	}
}

#generic function for Mysql query
function safe_query ($query = "") {
    global $mysession;
    if (empty($query)) {
		return FALSE;
    }
	$errorno=0;
    if (QUERY_LOG == "yes" && substr(strtolower(trim($query)),0, 6) != "select") {
		$querylog = addslashes($query);
    	$querylog = "INSERT INTO log (user_id, query, error, lasttime) VALUES (".$mysession['userid'].",\"$querylog\",\"$errorno\",now());";
		mysql_query($querylog) or die ("Error! " . mysql_error());
    }    
    	
    $result = mysql_query($query) or $errorno= mysql_errno();
    if ($errorno != 0) {
		if (QUERY_DEBUG == "no") {
		    print ("<BR>Query failed: please contact the webmaster " . SYSADMIN . ".");
		} else {
			$error = mysql_error();	
	    	print ("</td></tr></table></td></tr></table><BR>Query failed:" 
					      . "<li> errorno=" . $errorno
					      . "<li> error=" . $error
					      . "<b><li> query=" . $query
					      . "<p><a href=\"javascript:history.go(-1)\"> Back</a>");
			return 0;
		}	
    } 
    return $result;
}

//Delete folder function 
function deleteDirectory($dir) { 
    if (!file_exists($dir)) return true; 
    if (!is_dir($dir) || is_link($dir)) return unlink($dir); 
    foreach (scandir($dir) as $item) { 
    if ($item == '.' || $item == '..') continue; 
		if (!deleteDirectory($dir . "/" . $item)) { 
        	chmod($dir . "/" . $item, 0777); 
	  		if (!deleteDirectory($dir . "/" . $item)) return false; 
    	}; 
    }
    return rmdir($dir); 
} 
    
function xml_escape($s) {
	$s = str_replace("&quot;","\"",$s);
    $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
    $s = htmlspecialchars($s, ENT_QUOTES, 'UTF-8', false);
    return trim($s);
}

function chr_utf8($code) { 
        if ($code < 0) return false; 
        elseif ($code < 128) return chr($code); 
        elseif ($code < 160) // Remove Windows Illegals Cars 
        { 
            if ($code==128) $code=8364; 
            elseif ($code==129) $code=160; // not affected 
            elseif ($code==130) $code=8218; 
            elseif ($code==131) $code=402; 
            elseif ($code==132) $code=8222; 
            elseif ($code==133) $code=8230; 
            elseif ($code==134) $code=8224; 
            elseif ($code==135) $code=8225; 
            elseif ($code==136) $code=710; 
            elseif ($code==137) $code=8240; 
            elseif ($code==138) $code=352; 
            elseif ($code==139) $code=8249; 
            elseif ($code==140) $code=338; 
            elseif ($code==141) $code=160; // not affected 
            elseif ($code==142) $code=381; 
            elseif ($code==143) $code=160; // not affected 
            elseif ($code==144) $code=160; // not affected 
            elseif ($code==145) $code=8216; 
            elseif ($code==146) $code=8217; 
            elseif ($code==147) $code=8220; 
            elseif ($code==148) $code=8221; 
            elseif ($code==149) $code=8226; 
            elseif ($code==150) $code=8211; 
            elseif ($code==151) $code=8212; 
            elseif ($code==152) $code=732; 
            elseif ($code==153) $code=8482; 
            elseif ($code==154) $code=353; 
            elseif ($code==155) $code=8250; 
            elseif ($code==156) $code=339; 
            elseif ($code==157) $code=160; // not affected 
            elseif ($code==158) $code=382; 
            elseif ($code==159) $code=376; 
        } 
        if ($code < 2048) return chr(192 | ($code >> 6)) . chr(128 | ($code & 63)); 
        elseif ($code < 65536) return chr(224 | ($code >> 12)) . chr(128 | (($code >> 6) & 63)) . chr(128 | ($code & 63)); 
        else return chr(240 | ($code >> 18)) . chr(128 | (($code >> 12) & 63)) . chr(128 | (($code >> 6) & 63)) . chr(128 | ($code & 63)); 
    } 

// Callback for preg_replace_callback('~&(#(x?))?([^;]+);~', 'html_entity_replace', $str); 
function html_entity_replace($matches) { 
	if ($matches[2]) { 
        return chr_utf8(hexdec($matches[3])); 
    } elseif ($matches[1]) { 
        return chr_utf8($matches[3]); 
    } 
    switch ($matches[3]) { 
        case "nbsp": return chr_utf8(160); 
    	case "iexcl": return chr_utf8(161); 
        case "cent": return chr_utf8(162); 
        case "pound": return chr_utf8(163); 
        case "curren": return chr_utf8(164); 
        case "yen": return chr_utf8(165); 
        //... etc with all named HTML entities 
    } 
    return false; 
} 
    
function htmlentities2utf8_old ($string) { // because of the html_entity_decode() bug with UTF-8 
    $string = preg_replace_callback('~&(#(x?))?([^;]+);~', 'html_entity_replace', $string); 
	return $string; 
} 

function htmlentities2utf8 ($string) {
	return str_replace("\xc2\xa0",' ',$string);
	#return utf8_decode(mb_convert_encoding(str_replace("\xc2\xa0",' ',$string), 'UTF-8', 'HTML-ENTITIES'));
}

?>
