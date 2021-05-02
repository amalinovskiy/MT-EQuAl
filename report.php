<?php
header("Content-type: text/html; charset=utf-8");
?>

<html>
<head>
<link href="css/mtequal.css" rel="styleSheet" type="text/css">
<script type="text/javascript" src="js/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="js/mtequal.js"></script>
<style>
/* Header/Logo Title */
.header {
  padding: 10px;
  text-align: center;
  background: #1abc9c;
  color: white;
  font-size: 30px;
}

.tdright {
  text-align: right;
}
</style>
</head>

<body>
<div class="header">
  <h1>Qualification report</h1>
</div>

<div>
<?php
include("config.php");
include("functions.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // collect value of input field
  $uid = $_POST['user_id'];
  $tid = $_POST['task_id'];
  $taskid = $tid;
}

if (isset($taskid)) {
	if (isset($mysession) && $mysession["taskid"] != $taskid) {
		$taskinfo = getTaskInfo($taskid);
		$mysession["taskid"] = $taskid;
		$mysession["tasknow"] = $taskinfo["name"];
		$mysession["tasksysnum"] = countTaskSystem($taskid);
		$mysession["tasktype"] = $taskinfo["type"];
		$mysession["taskistr"] = $taskinfo["instructions"];
		$mysession["taskranges"] = rangesJson2Array($taskinfo["ranges"]);
		if (isset($_SESSION)) {
			$_SESSION["mysession"] = $mysession;
		} else {
			session_register("mysession"); 
		}	
	}	
}

$userinfo = getUserInfo($uid);
$taskinfo = getTaskInfo($tid);

print "<table width=\"100%\"><tr><td text-align=\"left\">Annotator: <b>".$userinfo['username']."</b></td><td class=\"tdright\">Task: <b>".$taskinfo['name']."</b></td></tr></table>";

$annotations = getAnnotations($tid, $uid);
$ranges = rangesJson2Array($taskinfo["ranges"]);

$sentence_annotations = array();
foreach ($annotations as $annotation){
  if(!array_key_exists($annotation['num'], $sentence_annotations)){
    $sentence_annotations[$annotation['num']] = array();
  }
  array_push($sentence_annotations[$annotation['num']], $annotation);
}

$total_count=0;
$error_count=0;

foreach ($sentence_annotations as $sentence_id => $annotations){
    $sentence_hash = getTargetSentence(intval($sentence_id), intval($tid));
    $src_id = $sentence_hash["source"][3];
    $sentidx = $annotations[0]['id'];


    $annotation_types = array();
    $gold_annotation = $annotations[0]['gold_eval'];
    $gold_text = $annotations[0]['gold_text'];

    if ($gold_annotation != 8){
      foreach ($annotations as $annotation){
        array_push($annotation_types, $annotation['eval']);
      }
      
      $total_count += 1;

      if (in_array($gold_annotation, $annotation_types)){
        if (count($annotation_types) == 1){
          print "<p style=\"color:green;\"> Correct annotation. </p>";
        } else{
          $error_count +=1;
          print "<p style=\"color:orange;\"> Annotating extra error. </p>";
          print "<p> True error: <span style=\"background-color:".$ranges[$gold_annotation][1]."\">".$ranges[$gold_annotation][0]."</span> => True span: <span style=\"background-color:".$ranges[$gold_annotation][1]."\">".$gold_text."</span>";
        }
      } else{
        $error_count += 1;
        print "<p style=\"color:red;\"> Wrong annotation.</p>";
        if ($gold_annotation == 0 || $gold_annotation == 7){
          print "<p> True error: <span style=\"background-color:".$ranges[$gold_annotation][1]."\">".$ranges[$gold_annotation][0]."</span>";
        }else{
          print "<p> True error: <span style=\"background-color:".$ranges[$gold_annotation][1]."\">".$ranges[$gold_annotation][0]."</span> => True span: <span style=\"background-color:".$ranges[$gold_annotation][1]."\">".$gold_text."</span>";
        }
      }
      print "<p> <b>Your annotation: </b></p>";
      print "<div style='display: block; width: 100%; float: left; left: 0px;'><div class=label>SOURCE: </div>" . showSentence($sentence_hash["source"][0], $sentence_hash["source"][1], "source");
      
      
      print "<iframe src=\"errors_output_show.php?id=$src_id&taskid=$tid&userid=$uid&sentidx=$sentidx\" style=\"border: 0px; padding-left: 0px; margin-top: -10px; width:100%\"></iframe></div>";
    }
}

print "<h3>Wrong annotation number: ".$error_count."/".$total_count."</h3>";


?>
</div>
</body>
