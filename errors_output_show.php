<html>
<head>
<link href="css/mtequal.css" rel="styleSheet" type="text/css">
<script type="text/javascript" src="js/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="js/mtequal.js"></script>
	
<?php
header("Content-type: text/html; charset=utf-8");
include("config.php");
include("functions.php");

if (!isset($userid)) {
	$userid = $mysession['userid'];
} 
	
if (!isset($taskid)) {
	$taskid = $mysession["taskid"];
}

if (isset($monitoring) && $monitoring == 1) {
	$sentidx=-1;
} else {
	$monitoring=0;
}
?>

<script type="text/javascript">
/* Default value of choice */
var selectionFrom = 0;
var selectionTo = 1;

function getColor (idx) {
	return "#ccc";
}

function goto(el,interval) {
	var leftEl = document.getElementById("LeftPane");
	var tokenEl = document.getElementById(el+"."+interval);
	
	if (leftEl != null && tokenEl != null) {
		if (tokenEl.offsetTop > 100) {
			leftEl.scrollTop = tokenEl.offsetTop-100;
		} else {
			leftEl.scrollTop = 0;
		}
	}
}

function showRange(el,sentid,range) {
	el.style.borderBottom = "solid #000 2px";
	el.style.cursor = "nw-resize";
	var ids = range.split(" ");
	for (var i in ids) {
    	var el = document.getElementById(sentid+"."+ids[i]);
    	if (el != null) {
    		if (el.style.backgroundColor == COLOR) {
				el.style.borderTop = "2px solid "+COLOR;
			}
			el.style.backgroundColor = "#bbb";
    	}
    }
}

function hideRange(el,sentid,range) {
	el.style.borderBottom = "none";
	el.style.cursor = "default";
	var ids = range.split(" ");
	for (var i in ids) {
    	var el = document.getElementById(sentid+"."+ids[i]);
    	if (el != null) {
    		if (el.style.borderTop == "2px solid "+COLOR) {
				el.style.backgroundColor = COLOR;
				el.style.borderTop = "none";
			} else {
				el.style.backgroundColor = WHITE;
			}
		}
    }
}

function removeAnnotation(id,targetid,ranges,errid) {
  //alert(id+","+ranges);
  if (confirm("Do you really want to cancel this annotation?")) {
  	$.ajax({
  		url: 'update.php',
  		type: 'GET',
      	data: "id="+id+"&targetid="+targetid+"&taskid=<?php echo $taskid;?>&userid=<?php echo $userid;?>&check="+errid+"&action=remove&tokenids="+ranges,
  		async: false,
  		cache:false,
  		crossDomain: true,
  		success: function(response) {
  			$("#output"+targetid).html(response);
  			$.ajax({
  				url: 'errors_type.php',
 				type: 'GET',
  				data: "id=<?php echo $id;?>&targetid="+targetid+"&userid=<?php echo $userid;?>",
  				async: false,
  				cache:false,
  				crossDomain: true,
  				success: function(response) {
  					$("#errors"+targetid).html(response);
				}
			});
  		},
  		error: function(response, xhr,err ) {
        	//alert(err+"\nreadyState: "+xhr.readyState+"\nstatus: "+xhr.status+"\nresponseText: "+xhr.responseText);
        	switch(xhr.status) {
				case 200: 
					alert("Sorry! Data has not been saved!");
			}
		}
  	});
	//window.open("errors_output.php?id="+id+"&sentidx=<?php echo $sentidx; ?>","_self");
                        
  }	
}

$(document).ready(function() {
	try {
		$(document).bind("contextmenu", function(e) {
			e.preventDefault();
			/*if (OUTPUTID != null && isSelected(OUTPUTID) == 1) {	
				$("#errortypes").css({ top: (e.pageY-2) + "px", left: (e.pageX-20) + "px" }).show(100);
			} else {
				$("#noselection").css({top: (e.pageY-2) + "px", left: (e.pageX-20) + "px" }).show(100);
				var el = document.getElementById("noselection");
    			el.style.visibility = "visible";

			}*/
		});
		$(document).mouseup(function(e) {
			var container = $("#errortypes");
			if (container.has(e.target).length == 0) {
				container.hide();
				//container.show();
				//hideErrorMenu();
				//alert(container.has(e.target).length);
			}
			//showErrorMenu();
		});
	} catch (err) {
		alert(err);
	}
});

</script>
</head>

<body>
<div id="errortypes" onclick="this.style.visibility='hidden';" style="font-size: 10px">
<table width=200 border=0 cellspacing=0 cellpadding=2 style='background-color: #ddd; color: #000; font-size: 16px; box-shadow: 3px 3px 3px #888; '>
<?php
	$ranges = $mysession["taskranges"];
	while (list ($val,$attrs) = each($ranges)) {
		if ($val > 1 && $val < 7) {
			print "<tr><td onclick=\"javascript:saveAnnotationRanges($val);\" onmouseover=\"this.className='yellow'\" onmouseout=\"this.className='whitebg'\">".$attrs[0]."</td></tr>";
		}
	}
?>	
</table>
</div>

<!--
<div id="noselection" onclick="this.style.visibility='hidden';" style="position: fixed; visibility: hidden; box-shadow: 2px 2px 2px #888; font-size: 14px; padding-left: 20px; width: 140px; border: 1px solid #000; background: lightyellow">
<img src='img/bullet_error.png'> No selection!
</div>
-->

<?php
$prevAndnextIDs = getPrevNext($taskid, $id);	
if ($sentidx != -1) {
 	$sentidx = $prevAndnextIDs[2];
}

if (empty($mysession["status"])) {
	print "<script>window.open('index.php','_self');</script>";
}

if (!isset($errorid)) {
	$errorid = "";
} 

if ($taskid > 0 && isset($id) && isset($userid)) {
	$hash_target = getSystemSentences($id,$taskid);
	$i = 0;
	$checked = 0;
	print "<div style='width: 100%; position: relative; height: 100%; margin-top:0px; margin-left: -28px;  padding-right: 46px; margin-bottom: auto; overflow-y: auto;'>";
	if (count($hash_target) > 0) {
	  while (list ($sentence_id, $sentence_item) = each($hash_target)) {
		$errors = getErrors($id,$sentence_id,$userid);
		if (count($errors) > 0) {
			$checked++;
		}
		print "<table cellpadding=0 cellspacing=2 border=0> <td valign=top>";
		print "<div>";
		//print "<div style='display: table-cell; float: left; width: 666px'>";
		
		//Add output row
		$sent = showFixedSentence ($sentence_item[0], $sentence_item[1], "output", $sentence_item[2], $sentence_id, $errors, $ranges);
		//ripristino eventuali errori nei carattri con lastring vuota se non sono stati fatte delle anotazioni
		#if(count($errors) == 0) {
		#	$sent = preg_replace("/<img src='img\/check_error.png' width=16>/","",$sent);
		#}
		print "<div class=row><div class=label>OUTPUT <b>".($i+1)."</b>: </div><div class=cell id='output".$sentence_id."'>$sent</div></div>";
		//end output row
		
		//end cell (output+comment)
		
		//start error cell 
		print "<td valign=top>";
		print "<div class='cell right' id='errors".$sentence_id."'>";
		
		reset($ranges);
		$checkid = 0;
		while (list ($val,$attrs) = each($ranges)) {
			if ($val <= 1 || $val == 7) {
				if (count($errors) == 0 || isset($errors[0]) || isset($errors[1]) || isset($errors[7])) {
					$color="#".$attrs[1];
					$bordercolor="4px solid ".$color;
					if (isset($errors[$val])) {
						$bordercolor="4px solid red";
					}
					if ($val == 0) {
						print "<table cellspacing=4>";
					} 
					print "<td style='padding: 1px; background: $color; border: $bordercolor; box-shadow: 2px 2px 2px #888; font-size:13px' id='check.$i.$checkid' align=center nowrap>".$attrs[0] ."</td></tr>";
					// if ($val == 1) {
					if ($val == 7) {
						print "</table>";
					}
				} 
			$checkid++;
			} 
		}
				
		while (list ($errID, $errARRAY) = each($errors)) {
			if ($errID > 1 && $errID != 7) {
			  	$tokenids = explode(",",$errARRAY[0]);
				$texts = explode("__BR__",$errARRAY[1]);
				$annotations = "";
				for($r=0; $r<count($tokenids); $r++) {
					$delicon = "";
					if (count($ranges) > 1) {
						if ($monitoring==0) {	
							$delicon = "<a href=\"javascript:removeAnnotation($id,$sentence_id,'".$tokenids[$r]."',$errID);\"><img src='img/delete.png' width=12></a>";
						}
					}
					$annotations .= "- <div style='display: inline; font-size:17px' onmouseover=\"javascript:showRange(this,$sentence_id,'".$tokenids[$r]."');\" onmouseout=\"javascript:hideRange(this,$sentence_id,'".$tokenids[$r]."');\">&nbsp;";
					if (trim($texts[$r]) == "") {
						$annotations .= "<small>_SPACE_</small>";
					} else {
						$annotations .= $texts[$r];
					}
					$annotations .="</div><br>";
				}
				print "<div style='background: #".$ranges[$errID][1]."; white-space: nowrap;'> ";
				print "&nbsp;<i><small><b>".$ranges[$errID][0].":</b></small></i></div>$annotations";
			}
		}	  
					
		print "</div></td>";
		//end error cell
		
		$i++;	
	  }
	
	  #print count($hash_target) ."!= $checked || ".isDone($id,$userid);
	  if (isDone($id,$userid) > 0) {
		print "<script>alreadyDone();</script>";
	  } else {
		if ($checked != count($hash_target)) {
	 		print "<script>notDoneYet();</script>";
		} else {
			print "<script>activateDone(".$monitoring.");</script>";
		}
	  } 
	} else {
		print "<h3><font color=red>No output found!</font></h3>";
	}
} 
?>
<div class=log id=log></div>       


<script type="text/javascript" src="js/errors_fix.js"></script>
<script type="text/javascript" charset="utf-8">
var ERRORID="<?php echo $errorid; ?>";
var ERRORCOLOR = "red";
var OUTPUTID = null;
 
function saveAnnotationRanges(errid) {
	ERRORID = errid;
	var elid = 1;
	var ranges = "";
	var entities = "";
	var prevcolor = null;
	while(true) {
		//alert(OUTPUTID+" "+elid);
		el = document.getElementById(OUTPUTID+"."+elid);
		//alert(ERRORID+" "+OUTPUTID+"."+elid + " " + el.style.backgroundColor);
		if (el == null) {
			break;
		} else {
			if (el.style.backgroundColor != WHITE) {
				if (prevcolor == null || prevcolor != el.style.backgroundColor) {
					//alert(el.id + " ## " +prevcolor + " ---- backgroundColor:" +el.style.backgroundColor);			
					//ranges += "("+getType(prevcolor)+"),";
					ranges += ","+elid;
					//entities += " ("+getType(prevcolor)+")__BR__";
					entities += "__BR__";
				} else {
					ranges += " "+elid;
					entities += " ";
				}
				entities += encodeURIComponent(el.innerHTML).replace(/%3Cnobr.*$/gi,"")
			}
			prevcolor = el.style.backgroundColor;					
		}
		
		//check spaces
		el = document.getElementById(OUTPUTID+"."+elid+"-"+(elid+ 1));
		if (el != null) {
			if (el.style.backgroundColor != WHITE) {
				if (prevcolor== null || prevcolor != el.style.backgroundColor) {
					//ranges += "("+getType(prevcolor)+"),";
					ranges += ","+elid+"-"+(elid+ 1);
					//entities += " ("+getType(prevcolor)+")__BR__";
					entities += "__BR___SPACE_";
				} else {
					ranges += " "+elid+"-"+(elid+ 1);
					entities += " ";
				}							
			}
			prevcolor = el.style.backgroundColor;	
		}
		
    	elid++;	
	}
	
	//alert("ID: <?php echo $id;?>, OUTPUTID: "+OUTPUTID+", ERRORID: "+ERRORID+", RANGES: "+ranges);
	ranges = ranges.replace(/^,\s*/,"");	
	entities = entities.replace(/^__BR__\s*/, "").replace(/&nbsp;/gi," ");
	
 //if (send) { // && trim(ranges) != "") { 	
 
 $.ajax({
	url: 'update.php',
	type: 'GET',
	data: "id=<?php echo $id;?>&targetid="+OUTPUTID+"&userid=<?php echo $userid;?>&check="+ERRORID+"&tokenids="+ranges+"&taskid=<?php echo $taskid;?>&words="+entities,
	async: false,
	cache:false,
	crossDomain: true,
	success: function(response) {
		if (response == "error") {
  			//$("#log").html("");
			alert("Warning! A problem occurred during saving the data. Try again later!");
		} else {
			//update list of annotated tokens		
			$("#output"+OUTPUTID).html(response);
			$.ajax({
  				url: 'errors_type.php',
 				type: 'GET',
  				data: "id=<?php echo $id;?>&targetid="+OUTPUTID+"&userid=<?php echo $userid;?>",
  				async: false,
  				cache:false,
  				crossDomain: true,
  				success: function(response) {
  					$("#errors"+OUTPUTID).html(response);
				}
			});
			//window.open("errors_output.php?id=<?php echo $id;?>&sentidx=<?php echo $sentidx; ?>","_self");			
		}
 	},
  	error: function(response, xhr,err ) {
        //alert(err+"\nreadyState: "+xhr.readyState+"\nstatus: "+xhr.status+"\nresponseText: "+xhr.responseText);
        //alert(ERRORID);
        switch(xhr.status) {
			case 200: 
				$("#log").html('<font color=gray>Data saved!</font>');
      			break;
    		case 404:
      			$("#log").html('<font color=red>Could not contact server.</font>');
      			break;
    		case 500:
      			$("#log").html('<font color=red>A server-side error has occurred.</font>');
      			break;
    		}   
    	setTimeout(function(){$("#log").html('')}, 3000);		
    }
});
  
 
ERRORID = "";
}

function getType (color) {
	if (color == ERRORCOLOR) {
		return ERRORID;
	} 
	return color;
}

function setColor(selectel, index, id)  {
	var color = selectel.value;
    if (selectel.id == "corefcolor" || selectel == "corefcolor") {
    	ERRORID = id;
    	COLOR = ERRORCOLOR;
    	color = COLOR;
    	//$("#color").prop("selectedIndex",0);
    } else {
    	//ERRORID = selectel.options[selectel.options.selectedIndex].text;
    	//ERRORID = ERRORID.substr(0,3).toUpperCase();
    	ERROR = null;
    	COLOR = color;
    	//$("#corefcolor").prop("selectedIndex",0);
    }
}

$(document).ready(function() {
  	$('.nav-toggle').click(function() {
		//get collapse content selector
		var collapse_content_selector = $(this).attr('href');					
		//make the collapse content to be shown or hide
		var toggle_switch = $(this);
		$(collapse_content_selector).toggle(function(){
			if ($(this).css('display')=='none'){
				//change the button label to be 'Show'
				if (this.id.indexOf("comm") == 0) {
					toggle_switch.html("<img src='img/addcomment.png' style='vertical-align: top; float: right;' width=80>");
					
					el = document.getElementById(this.id+"_text");
					if (el != null) {
						save_comment(this.id,<?php echo $userid ?>,el.value);
						$("#"+this.id+"_label").html(el.value);
						elComment = document.getElementById(this.id+"_label");
						elComment.style.visibility = "visible";
						//activateDone();
					} else {
						alert("Error while saving the comment! Please contact the administrator. (code: 1001)");
					}	
				} else {
					toggle_switch.html('read more');
				}
				
			}else{
				//change the button label to be 'Hide'
				if (this.id.indexOf("comm") == 0) {
					$("#"+this.id+"_text").focus();
					elabel = this.id.replace(/_label/,"");
					elComment = document.getElementById(elabel+"_label");
					//alert(el.id);
					if (elComment != null) {
						elComment.style.visibility = "hidden";
					}
					toggle_switch.html("<img src='img/savecomment.png' style='vertical-align: top; float: right;' width=40>");
				} else {
					toggle_switch.html('close');
				}
			}
		});
	});
});	


/*(function($){
    var methods = {
            disable: function() {
              return $(this)
                .attr('unselectable', 'on')
                .attr('disabled','disabled')
                .css({
                   '-user-select': 'none',
                   '-webkit-user-select': 'none',
                   '-khtml-user-select': 'none',
                   '-moz-user-select': 'none',
                   '-o-user-select': 'none'
                })
                .each(function() { 
                   this.onselectstart = function() { return false; };
                });
            }
        };
    
    $.fn.textSelect = function( method ) {
        // Method calling logic
        if ( methods[method] ) {
          return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else {
          $.error( 'Method ' +  method + ' does not exist on jQuery.textSelect' );
        }    
    }

        
})(jQuery);
$('.unselect').textSelect('disable');
*/

//if IE4+
//document.onselectstart=new Function ("return false")

//if NS6
//if (window.sidebar){
//document.onmousedown=disableselect
//document.onclick=reEnable
//}
</script>
<br></br>

</div>
</body>
</html>

