<div style='margin: 10px; vertical-align: top; top: 0px; display: inline-block'>
<form action="admin.php?section=stats" method=GET>
<input type=hidden name=section value="stats" />
Choose a task: <select onChange="submit()" name='id'><option value=''>
<?php
if (isset($mysession)) { 
	$tasks = getTasks($mysession["userid"]);
	
	$ttype = "";
	while (list ($tid,$tarr) = each($tasks)) {
		if ($tarr[1] != $ttype) {
			$ttype = $tarr[1];
			print "<option value='' disabled='disabled'>--- ".ucfirst($ttype) ." tasks --- \n";
		}
		print "<option value='$tid'";
		if (isset($id) && $id == $tid) {
			print " selected";
		} 
		print "> &nbsp;".$tarr[0]."\n";
	}	
}
?>
</select>
</form>

<?php
$anntot=array();
$annid="";
$annotators=array();
$statsTable = "";
if (isset($id)) {
	$taskinfo = getTaskInfo($id);
	if ($taskinfo['type'] == 'errors_test'){
		$statsTable = "<div style='margin:5px; display: inline-block; padding: 4px; border: 1px solid #000'><table cellspacing=2	 cellpadding=0 border=0><tr bgcolor=#ccc><td align=center>Annotators</td><td align=center># annotations</td><td align=center># annotated words</td><td align=center width='100' bgcolor=#fff></td></tr>";
		print $statsTable;
		$hash_report = getAnnotatorReport($id);
		foreach ($hash_report as $line){
			print "<tr><td>".$line['username']."</td><td>".$line['ann_count']."</td><td>".$line['wrd_count']."</td><td>";
			print "<form action=\"report.php\" method=\"post\">";
			print "<input type=\"hidden\" name=\"user_id\" value=\"".$line['user_id']."\"/>";
			print "<input type=\"hidden\" name=\"task_id\" value=\"".$id."\"/>";
			print "<input type=\"submit\" value=\"export\"></input>";
			print "</form>";
			print "</td></tr>";
		}
	}
	if (count($taskinfo) > 0) {
	$hash_report = getAnnotationReport($id);
	$systems = array();
	$statsTable = "<div style='margin:5px; display: inline-block; padding: 4px; border: 1px solid #000'><table cellspacing=1	 cellpadding=0 border=0><tr bgcolor=#ccc><td align=center>Annotation type</td><td colspan=4 align=center>Total</td></tr><tr><td></td><td bgcolor='#000'><img width=1></td>";
	while (list ($eval,$counters) = each($hash_report)) {
		$values = explode(",", $counters);
		foreach ($values as $val) {
			$items = explode(" ", $val);
			if (!in_array($items[1],$annotators)) {
				array_push($annotators, $items[1]);
			}
			if (!isset($systems[$items[0]])) {
				$systems[$items[0]]=0;
				#$statsTable .= "<th>&nbsp;&nbsp;".$items[0]."</th>";
			}

			if (isset($anntot[$eval."-".$items[0]])) {
				$anntot[$eval."-".$items[0]] += $items[2];
			} else {
				$anntot[$eval."-".$items[0]] = $items[2];
			}
			$systems[$items[0]] += $items[2];
		}					
	}
	$statsTable .= "</tr>\n";			
	reset($hash_report);
	$colorValue=array();
	$ranges = rangesJson2Array($taskinfo["ranges"]);
	while (list ($evalid, $attrs) = each($ranges)) {
		$statsTable .= "<tr align=right><th bgcolor='".$attrs[1]."'>".$attrs[0]."&nbsp;&nbsp;</th><td bgcolor='#000'><img width=1></td>\n";
		$colorValue[$attrs[1]]=$attrs[0];
		reset($systems);
		while (list ($system,$tot) = each($systems)) {
			$statsTable .= "<td>";
			if (isset($anntot[$evalid."-".$system])) {
				$counters = $hash_report[$evalid];
				$values = explode(",", $counters);
				$annotationdetail = "";
				foreach ($values as $val) {
					$items = explode(" ", $val);
					if ($items[0] == "$system") {
						$annotationdetail .= "user ".$items[1].": ".$items[2]."\n";
					}
				}
				$statsTable .= "&nbsp;&nbsp;".$anntot[$evalid."-".$system]."<a href='#' title='".$annotationdetail."'>[#]</a></td>";
			} else {
				$statsTable .= "-&nbsp;</td>";
			}
		}
		$statsTable .= "</tr>";
	}
	$statsTable .= "<tr><td></td><td colspan=".(count($systems)+1)." height=1 bgcolor='#000'><img width=1></td></tr>";
	$statsTable .= "<tr align=right><td><td width=1 bgcolor='#000'><img width=1></td></td>";
	reset($systems);
	while (list ($system,$tot) = each($systems)) {
		$statsTable .= "<th>$tot</th>";
	}
	
	$statsTable .= "</tr></table>";	
	print "<strong>&nbsp;&nbsp;This task has been annotated by <b>".count($annotators) ."</b> users</strong></br>";
	
	if (count($annotators) > 0) {	
		$sentid = "";
		$sourceid="";
		$userid="";
		$sentence="";
		$user_annotations=array();
		$sentence_records = getAgreementSentences($id);
		$outputNum = 0;
		$count=0;
		$num=0;
		$max=50;
		if (!isset($from)) {
			$from=0;
		}
		
		$outputText = "";
		#$hashusers = getUserStats($mysession["userid"],$mysession["status"]);
		while ($row = mysql_fetch_array($sentence_records)) {
			if ($userid != "" && ($userid != $row["user_id"] || $sentid != $row["output_id"] || $sourceid != $row["linkto"])) {
				if (isDone($row["linkto"],$row["user_id"]) != 1) {
					continue;
				}
				if (count($user_annotations) > 0) {
					$auser = "<tr heigth=2>";
					for ($i=0; $i < count($tokens); $i++) {
						if (isset($user_annotations["-1"])) {
							$auser .= "<td><table width=100% border=0 cellspacing=0 cellpadding=3><td bgcolor=".$user_annotations["-1"]." title='User ".$userid.": ".$colorValue[$user_annotations["-1"]]."'></td></table>";
						} else if (isset($user_annotations[$i+1])) {
							#$auser .= "<td bgcolor=".$user_annotations[$i+1]."></td>";
							$auser .= "<td><table width=100% border=0 cellspacing=0 cellpadding=3>";
							$cols = explode(" ", trim($user_annotations[$i+1]));
							foreach ($cols as $col) {
								$auser .= "<td bgcolor=".$col." title='User ".$userid.": ".$colorValue[$col]."'></td>";
							}
							$auser .= "</table></td>";
						} else {
							$auser .= "<td></td>";
						}
					}
					$auser .= "<td><a href='errors.php?id=".$sourceid."&userid=".$userid."&taskid=".$id."'><p style='font-size:5px'>#</p></a></td>";
					$auser .= "</tr>";
					$sentence .= "\n".$auser;
				}
				$user_annotations=array();
			} 
				
			if ($sentid != "" && $sentid != $row["output_id"]) {
				if ($num>=$from) {
					$outputText .= "OUTPUT $outputNum:<br><table cellspacing=0 cellpadding=0 border=1>".$sentence."</table><br>\n";
				}
				$sentence="";				
			}
			
			if ($sourceid != $row["linkto"]) {
				$outputNum = 0;
				$count++;
				if ($count > $max) {
					break;
				}
				$num++;
				if ($num>=$from) {
					$outputText .= "<hr><a href='".$taskinfo["type"].".php?id=".$row["linkto"]."&taskid=".$id ."'><i>sentence n.$num</i></a><br>";					
				}
				$sourceid = $row["linkto"];			
			}
			if ($sentid != $row["output_id"]) {
				$outputNum++;
				$tokens = getTokens($row["lang"], $row["text"], $row["tokenization"]);
			}	
			
			if (trim($row["evalids"]) == "") {
				$user_annotations["-1"]=$ranges[$row["eval"]][1];
			} else {
				$tokenids = preg_split("[ |,]", trim($row["evalids"]));
				if (count($tokenids) > 0) {
					foreach ($tokenids as $tid) {
						if (strpos($tid,'-') !== false) {
							$tid = preg_replace('/-.*$/',"",$tid); 
						}
						
						#print  $sentid  ."# " .$tid ." ## ". $ranges[$row["eval"]][0] ."<br>";	
						if (!isset($user_annotations[$tid])) {
							$user_annotations[$tid]=$ranges[$row["eval"]][1];
						} else {					
							$user_annotations[$tid].=" ".$ranges[$row["eval"]][1];
						}
					}
				}
			} 
				
			if ($sentid != $row["output_id"]) {
				$sentence .= "<tr bgcolor=#fff><td style='padding: 1px'>".join("</td><td style='padding: 2px'>", $tokens)."</td></tr>\n";
				$sentid = $row["output_id"];
				$userid = "";
			}
			$userid = $row["user_id"];
			
		}
		//end while
		
		if (count($user_annotations) > 0) {
					$auser  = "<tr heigth=2 title='User ".$userid."'>";
					for ($i=0; $i < count($tokens); $i++) {
						if (isset($user_annotations["-1"])) {
							$auser .= "<td><table width=100% border=0 cellspacing=0 cellpadding=2><td bgcolor=".$user_annotations["-1"]."></td></table>";
						} else if (isset($user_annotations[$i+1])) {
							#$auser .= "<td bgcolor=".$user_annotations[$i+1]."></td>";
							$auser .= "<td><table width=100% border=0 cellspacing=0 cellpadding=2>";
							$cols = explode(" ", trim($user_annotations[$i+1]));
							foreach ($cols as $col) {
								$auser .= "<td bgcolor=".$col."></td>";
							}
							$auser .= "</table></td>";
						} else {
							$auser .= "<td></td>";
						}
					}
					
					$auser .= "</tr>";
					                       
					$sentence .= "\n".$auser;

			if ($num>=$from) {
				$outputText .= "OUTPUT $outputNum:<br><table cellspacing=0 cellpadding=0 border=1>".$sentence."</table><br>\n";
			}
		}	
		print "$statsTable<br>";
	  }
		
		$source_count = getTaskSourceWordCount($id);
		print "Task source words: ".$source_count."<br>";  

		// Disagreement section
		// Error Rate
		print "<strong>Error Rate:</strong><hr>";
		print "<table cellspacing=1      cellpadding=2 border=1><tr><th></th>";
		foreach($annotators as $annotatorid)
		{
				$annotator_info = getUserInfo($annotatorid);
				print "<th>".$annotator_info['username']."</th>";
		}
		print "<tr><td># annotations</td>";
		foreach($annotators as $annotatorid)
		{
				$error_rate = getErrorRate($id, $annotatorid);
				print "<td>".$error_rate[0]."</td>";
		}

		print "<tr><td># source words</td>";
		foreach($annotators as $annotatorid)
		{
				$word_count = getSourceWordCount($id, $annotatorid);
				print "<td>".$word_count."</td>";
		}


		print "<tr><td>error rate</td>";
		foreach($annotators as $annotatorid)
		{
				$error_rate = getErrorRate($id, $annotatorid);
				print "<td>".$error_rate[1]."</td>";
		}
		print "</tr></table><br>";

		// Error Num
		print "<strong>Number of Errors:</strong><hr>";
		print "<table cellspacing=1      cellpadding=2 border=1><tr><th></th>";
		foreach($annotators as $annotatorid)
		{
				$annotator_info = getUserInfo($annotatorid);
				print "<th colspan='2'>".$annotator_info['username']."</th>";
		}
		

		$keys = array(0, 1, 2, '>=3', 'many');
		foreach($keys as $key){
			print "<tr>";
			print "<th>".$key." error(s)</th>";
			foreach($annotators as $annotatorid){
				$error_count = getErrorNum($id, $annotatorid);
				$total = array_sum($error_count);
				print "<td>".$error_count[$key]."</td>";
				$percentage = round($error_count[$key] * 100 / $total, 2);
				print "<td>".$percentage."%</td>";
			}
			print "</tr>";
		}
		print "</table><br>";

		print "<strong>Error Types:</strong><hr>";
		print "<table cellspacing=1      cellpadding=2 border=1><tr><th></th>";
		// write Annotator names
		foreach($annotators as $annotatorid)
		{
				$annotator_info = getUserInfo($annotatorid);
				print "<th colspan='2'>".$annotator_info['username']."</th>";
		}
		
		// get error counts
		foreach($ranges as $errorid => $errorname){
			print "<tr><th align=right bgcolor=".$errorname[1].">".$errorname[0]."</th>";
			foreach($annotators as $annotatorid){
				$types = getErrorTypeCounts($id, $annotatorid);
				if (!array_key_exists($errorid, $types)){
					$type_cnt = 0;
				}else{
					$type_cnt = $types[$errorid];
				}
				print "<td>".$type_cnt."</td>";

				$total = array_sum($types);
				$percentage = round($type_cnt * 100 / $total, 2);
				print "<td>".$percentage."%</td>";
			}
			print "</tr>";
		}
		print "</table><br>";
		
		// Consistency
		print "<strong>Consistency:</strong><hr>";
		print "<table cellspacing=1      cellpadding=2 border=1><tr><th></th>";
		foreach($annotators as $annotatorid)
		{
				$annotator_info = getUserInfo($annotatorid);
				print "<th>".$annotator_info['username']."</th>";
		}

		print "<tr><th>Type Consistency</th>";
		foreach($annotators as $annotatorid)
		{
			$repeated_sentences = getRepeatedSentence($id, $annotatorid);
			$total = count($repeated_sentences);
			$type_consistency = 0;
			foreach($repeated_sentences as $row){
				$types1 = explode("||", $row[1]);
				$types2 = explode("||", $row[4]);
				if (computeOverlapScore($types1, $types2) == 1){
					$type_consistency += 1;
				}
			}
			print "<td>".$type_consistency."/".$total."</td>";
		}
		print "</tr>";

		print "<tr><th>Span Consistency</th>";
		foreach($annotators as $annotatorid)
		{
			$repeated_sentences = getRepeatedSentence($id, $annotatorid);
			$total = count($repeated_sentences);
			$span_consistency = 0;
			foreach($repeated_sentences as $row){
				$spans1 = explode("||", $row[2]);
				$spans2 = explode("||", $row[5]);
				if (computeOverlapScore($spans1, $spans2) == 1){
					$span_consistency += 1;
				}
			}
			print "<td>".$span_consistency."/".$total."</td>";
		}
		print "</tr>";
		
		print "<tr><th>Inconsist ann</th>";
		foreach($annotators as $annotatorid)
		{
			print "<td>";
			$repeated_sentences = getRepeatedSentence($id, $annotatorid);
			
			foreach($repeated_sentences as $row){
				$types1 = explode("||", $row[1]);
				$types2 = explode("||", $row[4]);

				$sentspans1 = explode("||", $row[2]);
				$sentspans2 = explode("||", $row[5]);

				$spans1 = array();
				$spans2 = array();

				foreach($sentspans1 as $spans){
					$cut_spans = explode("__BR__", $spans);
					$spans1 = array_merge($spans1, $cut_spans);
				}

				foreach($sentspans2 as $spans){
					$cut_spans = explode("__BR__", $spans);
					$spans2 = array_merge($spans2, $cut_spans);
				}

				#print_r($spans1);
				#print_r($spans2);
				#print "<br>";
				if (task_with_batching($id)){
					$sentidx1 = getBatchedPrevNext($id, $row[0], $annotatorid);
					$sentidx2 = getBatchedPrevNext($id, $row[3], $annotatorid);

					if (computeOverlapScore($types1, $types2) != 1 || computeOverlapScore($spans1, $spans2) != 1){
						print "<a target = '_blank' href=errors.php?id=".$row[0]."&userid=".$annotatorid."&taskid=".$id.">sent1</a>: #<span style='color:red'>".$sentidx1[2]."</span>\t";
						print "<a target = '_blank' href=errors.php?id=".$row[3]."&userid=".$annotatorid."&taskid=".$id.">sent2</a>: #<span style='color:red'>".$sentidx2[2]."</span><br>";
					}
				}else{
					$sentidx1 = getPrevNext($id, $row[0]);
					$sentidx2 = getPrevNext($id, $row[3]);
					if (computeOverlapScore($types1, $types2) != 1 || computeOverlapScore($spans1, $spans2) != 1){
						//print "<a href=errors.php?id=".$row[0]."&userid=".$annotatorid."&taskid=".$id.">sent1</a>\t";
						//print "<a href=errors.php?id=".$row[3]."&userid=".$annotatorid."&taskid=".$id.">sent2</a><br>";
						print "<a target = '_blank' href=errors.php?id=".$row[0]."&userid=".$annotatorid."&taskid=".$id.">sent1</a>: #<span style='color:red'>".$sentidx1[2]."</span>\t";
						print "<a target = '_blank' href=errors.php?id=".$row[3]."&userid=".$annotatorid."&taskid=".$id.">sent2</a>: #<span style='color:red'>".$sentidx2[2]."</span><br>";
					}
				}
			}
		}
		print "</tr>";

		print "</table><br>";

		// Existence Kappa
		print "<strong>Existence Kappa:</strong><hr>";
		print "<table cellspacing=1      cellpadding=2 border=1><tr>";
		print "<th>annotator1</th><th>annotator2</th><th># annotations</th><th>Kappa</th><th>Kappa-approx</th><th>P(agreement)</th></tr>";

		foreach ($annotators as $annotatorid1){
			foreach ($annotators as $annotatorid2){
				if ($annotatorid1 < $annotatorid2){
					$annotator_info1 = getUserInfo($annotatorid1);
					$annotator_info2 = getUserInfo($annotatorid2);
					$name1 = $annotator_info1['username'];
					$name2 = $annotator_info2['username'];
					$kappa_info = getExistenceKappa($id, $annotatorid1, $annotatorid2);
					print "<tr><td>".$name1."</td><td>".$name2."</td><td>".$kappa_info[0]."</td><td>".$kappa_info[1]."</td><td>".$kappa_info[2]."</td><td>".$kappa_info[3]."</td></tr>";
				}
			}
		}
		print "</table><br>";

		// Type overlap
		print "<strong>Type overlap:</strong><hr>";
		print "<table cellspacing=1      cellpadding=2 border=1><tr>";
		print "<th>annotator1</th><th>annotator2</th><th># annotations</th><th>Overlap</th></tr>";

		foreach ($annotators as $annotatorid1){
			foreach ($annotators as $annotatorid2){
				if ($annotatorid1 < $annotatorid2){
					$annotator_info1 = getUserInfo($annotatorid1);
					$annotator_info2 = getUserInfo($annotatorid2);
					$name1 = $annotator_info1['username'];
					$name2 = $annotator_info2['username'];

					$overlap_info = getTypeOverlap($id, $annotatorid1, $annotatorid2);
					print "<tr><td>".$name1."</td><td>".$name2."</td><td>".$overlap_info[0]."</td><td>".$overlap_info[1]."</td></tr>";
				}
			}
		}
		print "</table><br>";

		// Span overlap
		print "<strong>Span overlap:</strong><hr>";
		print "<table cellspacing=1      cellpadding=2 border=1><tr>";
		print "<th>annotator1</th><th>annotator2</th><th># annotations</th><th>Overlap</th></tr>";

		foreach ($annotators as $annotatorid1){
			foreach ($annotators as $annotatorid2){
				if ($annotatorid1 < $annotatorid2){
					$annotator_info1 = getUserInfo($annotatorid1);
					$annotator_info2 = getUserInfo($annotatorid2);
					$name1 = $annotator_info1['username'];
					$name2 = $annotator_info2['username'];

					$overlap_info = getSpanOverlap($id, $annotatorid1, $annotatorid2);
					print "<tr><td>".$name1."</td><td>".$name2."</td><td>".$overlap_info[0]."</td><td>".$overlap_info[1]."</td></tr>";
				}
			}
		}
		print "</table><br>";

		// Annotated sentences
		if ($outputText != "") {
			print "<strong>Annotated sentences: </strong>";
			if ($from > 0) {
				print "<button onclick=\"location.href='admin.php?section=stats&id=$id&from=".($from-$max)."'\">prev</button> |";
			}	
			if ($count > $max+1) {
				print " <button onclick=\"location.href='admin.php?section=stats&id=$id&from=".($from+$max+1)."'\">next</button>";
			}
			print $outputText;
		}
	} else {
		print "WARNING! This task is not valid.";
	}	
	
}
?>
</div>
