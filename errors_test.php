<?php
header("Content-type: text/html; charset=utf-8");
?>

<html>
<head>
<link href="css/mtequal.css" rel="styleSheet" type="text/css">
<script type="text/javascript" src="js/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="js/mtequal.js"></script>
	
<?php
include("config.php");
include("functions.php");

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
if (!isset($mysession) || $mysession["taskid"]==0 || empty($mysession["status"])) {
	header("Location: index.php");
	#print "<script>window.open('index.php','_self');</script>";
}

$sentence_hash = getSentence($id, $taskid);
/* $context_hash = getPrevNext($taskid, $id);
$prev_sent_array = getSentence($context_hash[0], $taskid);
$next_sent_array = getSentence($context_hash[1], $taskid); */

if (!isset($sentence_hash["source"])) {
	header("Location: index.php#".($id-1)); 
	exit;
}
?>

<style>
html{height:100%}body{height:100%;min-width:980px;overflow:hidden;font-family:verdana,arial,helvetica;font-size:12px;margin:0;padding:0;}
</style>
</head>
<body>

<div style="background-color: #FFFFFF; z-index:9999; position: absolute; width: 100%; height: 100%; border-right: 1px solid #222; border-left: 1px solid #222">
<table cellpadding="0" cellspacing="0" height="100%" width="100%">
<tr height="1%">
<td style="top:0; width:100%">

<?php
include("menu_sentence.php");

$monitoring=0;
if (isset($userid) && $userid != $mysession['userid'] && ($mysession["status"] == "root" || $mysession["status"] == "admin" || $mysession["status"] == "advisor")) {
	$time = date( "d/m/Y H:m:s", time() );
	print "<div style='display: inline-block; background: yellow; border: dashed #777 1px; border-radius: 0px 0px 15px 15px;  padding: 9px; font-size:12px; position:absolute; top: 0px; margin-left: 320px; z-index:1000'>Monitoring... sentence <b>$id</b>, user: <b>$userid</b> ($time)<br><a href='admin.php?section=annotation#user$userid' style='float:right'>« Back to Admin</a></div><br>";
	$monitoring=1;
	$sentidx=-1;
} else {
	if (isset($mysession['userid'])) {
		$userid = $mysession['userid'];
		if (!isset($taskid)) {
 			$taskid = $mysession["taskid"];
 		}
		
	} else {
		print "<br><font color=red>Access denied!</font> You are an unregistered user or your session has expired. Please <a href='index.php' target='_top'>login</a> again!";
		return;
	} 
}


if ($mysession["taskistr"] != "") {
?>

<span style="float: right; padding-right: 20px; padding-top: 9px; width:20%;">
<div style='float: right; right: 0px; top:0px; display: inline-block; position: fixed;text-align: left; background: #eee; font-size: 12px; padding-top: 10px; padding-left: 10px; padding-right: 10px; padding-bottom: 10px; border: solid #999 1px; border-radius: 0px 0px 0px 15px; z-index: 1000'>
		<button href="#collapse1" class="nav-toggle" style='float: right; margin-top: -4px;'>read more</button><div style="float: right; margin-right: 20px">Task instructions<br></div>
		<div id="collapse1" style="display:none; font-size: 14px;">
		<br><br>
		<?php print $mysession["taskistr"]; ?>
		</div>	
</div>
</span>

<?php
}
  if(isset($prev_sent_array["source"])) {
    /* print "<div style='display: block; width: 100%; float: left; left: 0px; margin-top: 5px'><div class=label>PREV: </div>" . showSentence($sentence_hash["source"][0], $prev_sent_array["source"][1], "context")."<div>"; */
  }
    print "<div style='display: block; width: 100%; float: left; left: 0px;'><div class=label>SOURCE: </div>" . showSentence($sentence_hash["source"][0], $sentence_hash["source"][1], "source")."<div>";
  if(isset($next_sent_array["source"])) {
    /* print "<div style='display: block; width: 100%; float: left; left: 0px; margin-bottom: 5px'><div class=label>NEXT: </div>" . showSentence($sentence_hash["source"][0], $next_sent_array["source"][1], "context")."<div>"; */
  }
	if (isset($sentence_hash["reference"])) {
		print "<div class=labelref>CONTEXT: </div>".showSentence($sentence_hash["reference"][0], $sentence_hash["reference"][1], "context")."<div>";
		// print "<div class=labelref>REFERENCE: </div>".showSentence($sentence_hash["reference"][0], $sentence_hash["reference"][1], "reference")."<div>";
  }
  /* print "<div style='display: block; width: 100%; float: left; left: 0px; margin-bottom: 5px'><div class=label>SOURCE:</div>".showSentence($sentence_hash["source"][0], $sentence_hash["source"][1], "context").showSentence($sentence_hash["source"][0], $sentence_hash["source"][1], "source").showSentence($sentence_hash["source"][0], $sentence_hash["source"][1], "context")."<div>"; */
?>
 
  </div>
							
								</div>
							</td>
						</tr>
						<tr>
							<td valign=top>
							<div style='display: inline-block; box-shadow: 3px -5px 5px #888; position: relative;  margin-bottom: 5px; width: 100%; height: 6px; '>
							</div>
							<iframe src="errors_output.php?id=<?php echo $id; ?>&taskid=<?php echo $taskid; ?>&userid=<?php echo $userid; ?>&sentidx=<?php echo $sentidx; ?>&monitoring=<?php echo $monitoring; ?>" style="border: 0px; padding-left: 0px; margin-top: -10px; width:100%; height:100%"></iframe>
							</td>
						</tr>
					</table>
                    
				</div>

<?php
if (isset($userid) && $userid != $mysession['userid'] && ($mysession["status"] == "root" || $mysession["status"] == "admin" || $mysession["status"] == "advisor")) {
	print "<script>\n  setTimeout(\"window.open('errors.php?id=$id&userid=$userid&taskid=$taskid','_self')\", 5000);\n</script>\n";
}
?>			
<script>
$(document).ready(function() {
  	$('.nav-toggle').click(function() {
		//get collapse content selector
		var collapse_content_selector = $(this).attr('href');					
		//make the collapse content to be shown or hide
		var toggle_switch = $(this);
		$(collapse_content_selector).toggle(function(){
			if ($(this).css('display')=='none'){
				//change the button label to be 'Show'
				toggle_switch.html('read more');
				
			}else{
				//change the button label to be 'Hide'
				toggle_switch.html('close');
			}
		});
	});
});	
</script>
</body>
</html>
