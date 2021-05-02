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
var BUTTONSELCOLOR = "4px solid red";
var BUTTONOVERCOLOR = "#999";

function fadeOut (el,color) {
	el.style.cursor="normal";
	if (el.style.border != BUTTONSELCOLOR) {
		//el.style.borderColor="#000";
		el.style.backgroundColor=color;	
	} 
}

function fadeIn (el) {
	el.style.cursor="pointer";
	if (el.style.border != BUTTONSELCOLOR) {
		el.style.borderColor=el.style.backgroundColor;
		el.style.backgroundColor=BUTTONOVERCOLOR;
	} 
}

function check(id,target_id,user_id,val,checkid,totcheck,outid,totout) {
	//alert("check() id:"+id+", target_id:"+target_id+", user_id:"+user_id+", val:"+val+", checkid:"+checkid+", totcheck:"+totcheck+ ", outid:"+outid+", totout:"+totout);
	var radioEl = document.getElementById("check."+outid+"."+checkid);
	var action="";
    if (radioEl.style.border == BUTTONSELCOLOR) {
    	checkid=-1;
    	action="remove";
    	radioEl.style.borderColor = radioEl.style.backgroundColor;
    	radioEl.style.backgroundColor=BUTTONOVERCOLOR;
    }
    		
    $.ajax({
  		url: 'update.php',
  		type: 'GET',
      	data: "id="+id+"&targetid="+target_id+"&userid="+user_id+"&check="+val+"&action="+action,
  		async: false,
  		cache:false,
  		crossDomain: true,
  		success: function(response) {
  			if (response != "1") {
  				//alert("update.php?id="+id+"&targetid="+target_id+"&userid="+user_id+"&check="+val+"&action="+action);
  			 			
  				alert(response + " Sorry but an error occured saving data. Try again, please!");
  			} else {
  				//controllo se sono stati attivati almeno un radio per ogni check, se cos`i attivo il bottone DONE! 
				for(var c=0; c<totcheck; c++) {
    				var checked = 0;
      				radioEl = document.getElementById("check."+outid+"."+c);
      				if (radioEl == null) {
      					break;
      				}
      				if (c == checkid) {	  
      					radioEl.style.backgroundColor = radioEl.style.borderColor;
      					radioEl.style.border = BUTTONSELCOLOR;
					} else {
						if (radioEl.style.border == BUTTONSELCOLOR) {
							radioEl.style.borderColor = radioEl.style.backgroundColor;
						}
					}
				}
  			}
  		},
  		error: function(response, xhr,err ) {
        	//alert(err+"\nreadyState: "+xhr.readyState+"\nstatus: "+xhr.status+"\nresponseText: "+xhr.responseText);
        	switch(xhr.status) {
				case 200: 
				alert("Data saved!");
			}
		}
  	});
   	
  	
  	//controllo se attivare bottone done se c'e` almeno un assegnamento per ogni sistema
 	var checked = 0;
 	for(var o=0; o<totout; o++) {	
   		for(var c=0; c<totcheck; c++) {
   			radioEl = document.getElementById("check."+o+"."+c);
   			if (radioEl != null && radioEl.style.border == BUTTONSELCOLOR) {
   				checked++;
   				break;
   			} else {
   				resetEl = document.getElementById("reset."+o+"."+c);
   				if (resetEl != null) {
   					checked++;
   					break;
   				}
   			}
   		}		
 	}
 	
 	if (checked == totout) {
		activateDone(0);
  	} else {
  		notDoneYet();
    }
}

function reset(id,targetid,taskid,userid,errid,totcheck,outid,totout) {
	if (confirm("Do you really want to cancel all the annotations in this error category?")) {
		$.ajax({
  			url: 'update.php',
	 		type: 'GET',
    	  	data: "id="+id+"&targetid="+targetid+"&taskid="+taskid+"&userid="+userid+"&check="+errid+"&action=reset",
  			async: false,
  			cache:false,
			crossDomain: true,
			success: function(response) {
  				//alert(id+","+targetid+", ("+response+")");
  				$("#output"+targetid).html(response);
				$.ajax({
  					url: 'errors_type.php',
 					type: 'GET',
  					data: "id="+id+"&targetid="+targetid+"&userid="+userid,
  					async: false,
					cache:false,
  					crossDomain: true,
  					success: function(response) {
  						$("#errors"+targetid).html(response);
					}
				});
				//window.open("errors.php?id="+id+"&taskid="+taskid+"&outid="+outid,"_top");
      		},
	  		error: function(response, xhr,err ) {
    	    	//alert(err+"\nreadyState: "+xhr.readyState+"\nstatus: "+xhr.status+"\nresponseText: "+xhr.responseText);
        		switch(xhr.status) {
					case 200: 
						alert("WARNING! Data has not been saved correctly");
				}
			}
  		});
  		//controllo se attivare bottone done se c'e` almeno un assegnamento per ogni sistema
 	var checked = 0;
 	for(var o=0; o<totout; o++) {	
   		for(var c=0; c<totcheck; c++) {
   			radioEl = document.getElementById("check."+o+"."+c);
   			if (radioEl != null && radioEl.style.border == BUTTONSELCOLOR) {
   				checked++;
   				break;
   			} else {
   				resetEl = document.getElementById("reset."+o+"."+c);
   				if (resetEl != null) {
   					checked++;
   					break;
   				}
   			}
   		}		
 	}
 	
 	if (checked == totout) {
		activateDone(0);
  	} else {
  		notDoneYet();
    }
	}	
}

function save_comment(id, userid, comment) {
	id = id.replace(/^comm/," ");
	//alert("Saving... sentid: " + id+" userid:"+userid + "comment: "+comment); //entities
	$.ajax({
		url: 'update.php',
  		type: 'GET',
		data: "id="+id+"&userid="+userid+"&comment="+comment,
  		async: false,
  		cache:false,
  		crossDomain: true
  	});
  	return true;
}

function next(page) {
	window.open(page,'_top');
}

function nextUndone(target_ids, user_id) {
	page = document.getElementById("nextundonepage").value;

	saveEditingComment(target_ids, user_id);
	window.open(page,'_top');
}

function notDoneYet()  {
	doneEl = document.getElementById("done");
 	if (doneEl != null) {
 		doneEl.innerHTML='Not completed yet.';
 		doneEl.style.background='#ddd';
 		doneEl.disabled=true;
 		doneEl.style.color='#666';
 	}
}

function alreadyDone()  {
	doneEl = document.getElementById("done");
 	if (doneEl != null) {
 		doneEl.innerHTML='Annotation confirmed!';
 		doneEl.style.background='lightgreen';
 		doneEl.disabled=true;
 		doneEl.style.color='black';
 	}
}

function activateDone(monitoring) {
	doneEl = document.getElementById("done");
 	if (doneEl != null) {
 		doneEl.innerHTML='Confirm annotation?';
 		
 		if (monitoring==0) {
 			doneEl.disabled=false;
 		}
 		doneEl.style.background='lightyellow';
 		doneEl.style.color='black';
 	}
}

function updateNextUndone(id, task_id, sent_id) {
	dataEl = document.getElementById('nextundonepage');
	if (dataEl != null) {
		dataEl.value = "errors.php?id="+id+"&taskid="+task_id+"&sentidx="+sent_id;
	}
}

function doneAndIndex(id, target_ids, user_id, task_id, button) {
	doneEl = document.getElementById("done");
	saveEditingComment(target_ids, user_id);
	if (doneEl.innerHTML == 'Confirm annotation?'){
		done(id,user_id, task_id, button);
	}
	// window.open('index.php#'+id,'_top');
}

function saveEditingComment(target_ids, user_id) {
	for (key in target_ids){
		targetid = target_ids[key];
		elid = "comm" + targetid + "_text";
		commentEl = document.getElementById(elid);
		
		if (commentEl != null) {
			save_comment(targetid, user_id, commentEl.value);
		} else {
			alert("Error while saving the comment! Please contact the administrator. (code: 1001)");
		}

	}
}


function done(id,user_id,task_id, button) {
	button.disabled=true;
   	$.ajax({
 		url: 'update.php',
  		type: 'GET',
      	data: "id="+id+"&userid="+user_id+"&completed=Y&taskid="+task_id,
  		async: false,
  		cache:false,
  		crossDomain: true,
  		success: function(response) {
			confirmed = response.split("|||")[0];
			nextUndoneId = response.split("|||")[1];
			nextUndoneSentId = response.split("|||")[2];
  			if (confirmed != true) {
  				alert("Sorry but an error occured saving data. Try again, please!");
  			} else {
				alreadyDone();
				updateNextUndone(nextUndoneId, task_id, nextUndoneSentId);
  			}
  		},
  		error: function(response, xhr,err ) {
        	switch(xhr.status) {
				case 200: 
					alert("Data saved!");
			}
   		}
	});
    	
}


function getObject(name) {
    if (document.getElementById) {
        //alert (document.getElementById(name).);
        return document.getElementById(name);
    } else if (document.getElementsByTagName) {
        var elements = document.getElementsByTagName("*");
        for(i=0; i < elements.length; i++)
            if(elements.item(i).getAttribute("name") == name || elements.item(i).getAttribute("id") == name)
                return elements.item(i);

    } else if (document.layers[name]) {
        // NN 4 DOM.. note: this won't find nested layers
        return document.layers[objectId];
    } else if (document.all) {
        return document.all[name];
        //return eval ('document.all.'+name);
    }
    return null;
}

function moveObject( obj, e ) {
    // step 1
    var tempX = 0;
    var tempY = 0;
    var offset = 2;

    // step 2
    obj = getObject( obj );

    if (obj==null) return;

    // step 3
    if (document.all) {
        tempX = event.clientX + document.body.scrollLeft;
        tempY = event.clientY + document.body.scrollTop;
    } else {
        tempX = e.pageX;
        tempY = e.pageY;
    }

    // step 4
    if (tempX < 0){tempX = 0}
    if (tempY < 0){tempY = 0}

    // step 5
    obj.style.top  = (tempY + offset) + 'px';
    obj.style.left = (tempX + offset) + 'px';
    obj.style.visibility = 'visible'; //hidden
    //obj.style.display = 'block';   //none

}

function hideErrorMenu() {
    var obj = getObject('errortypes');
    //obj.style.visibility = 'hidden';
    obj.style.display = 'none';
}

function showErrorMenu() {
	var obj = getObject('errortypes');
    obj.style.visibility = 'hidden';
}

function isSelected(id) {
	var i = 1;
	var el = document.getElementById(id+"."+i);
	while (el != null) {
    	if (el.style.backgroundColor != "") {
    		return 1;
    	}
    	el = document.getElementById(id+"."+i+"-"+(i+1));
    	if (el != null && el.style.backgroundColor != "") {
    		return 1;
    	}
    	i++;
    	el = document.getElementById(id+"."+i);
    }	
    
    return 0;
}	


	
