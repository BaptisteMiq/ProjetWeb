/*
Module réalisé par Chamalotbis
*/

alerts = [];
alerts_close = [];
alerts_close_btn = [];
addTime = "";
aI = 0;

alerts_box = document.createElement('div');

function alert(content, type = 'null', time = 100) {

  if(type == 'info') {
    color = "#4d708e";
  }
  else if(type == 'error') {
    color = "#d32828";
  }
  else if(type == 'warning') {
    color = "#c9981e";
  }
  else if(type == 'success') {
    color = "#4a4";
  }
  else {
    color = "#a6acb2";
  }

  // console.log(content);

  alerts[aI] = document.createElement('div');
  alerts[aI].innerHTML = content;
  alerts[aI].classList.add('alerts');
  alerts[aI].lifeTime = 0;
  alerts[aI].maxTime = time;
  alerts[aI].style.width = "292px";
  if(content.length > 50) {
    alerts[aI].style.height = "42px";
  } else {
    alerts[aI].style.height = "30px";
  }
  alerts[aI].style.position = "absolute";
  alerts[aI].style.bottom = "0px";
  alerts[aI].style.backgroundColor = color;
  alerts[aI].style.opacity = "0.9";
  alerts[aI].style.borderRadius = "4px";
  alerts[aI].style.padding = "7px 4px 4px 4px";
  alerts[aI].style.fontSize = "16px";
  //alerts[aI].style.fontWeight = "900";
  alerts[aI].style.color = "#EEE";
  alerts[aI].style.overflow = "hidden";
  alerts[aI].style.lineHeight = "15px";
  alerts[aI].style.transition = "1s";

  for (var i = 0; i < alerts.length; i++) {
    alerts[i].style.bottom = parseInt(alerts[i].style.bottom) + 50 + "px";
  }

  alerts_close[aI] = document.createElement('div');
  alerts_close[aI].style.position = "absolute";
  alerts_close[aI].style.top = "0px";
  alerts_close[aI].style.right = "5px";
  alerts_close[aI].style.cursor = "pointer";

  alerts_close[aI].addEventListener("click", function(e) {
    element = alerts.indexOf(e.srcElement.parentElement.parentElement);
    if(element > -1) {
      alerts[element].lifeTime = alerts[element].maxTime;
    }
  });

  alerts_close_btn[aI] = document.createElement('i');
  alerts_close_btn[aI].classList.add('fa');
  alerts_close_btn[aI].classList.add('fa-times');

  alerts_close[aI].append(alerts_close_btn[aI]);
  alerts[aI].append(alerts_close[aI]);

  alerts_box.append(alerts[aI]);

  aI += 1;

  // Remove alerts with time
  clearInterval(addTime);
  addTime = setInterval(function () {
    for (var i = 0; i < alerts.length; i++) {

      try {
        prevBottom = alerts[i+1].style.bottom;
      } catch (e) {
        prevBottom = 0;
      }
      alerts[i].style.bottom = parseInt(prevBottom) + 50 + "px";

      alerts[i].lifeTime += 1;
      if(alerts[i].lifeTime > alerts[i].maxTime - 10) {
        alerts[i].style.opacity = "0";
      }
      if(alerts[i].lifeTime > alerts[i].maxTime) {
        alerts[i].remove();
        alerts.splice(i, 1)
        aI -= 1;
      }
    }
    alerts = cleanArray(alerts);
  }, 100);

}

function cleanArray(actual) {
  var newArray = new Array();
  for (var i = 0; i < actual.length; i++) {
    if (actual[i]) {
      newArray.push(actual[i]);
    }
  }
  return newArray;
}

alerts_box.style.width = "300px";
alerts_box.style.height = "500px";
alerts_box.style.position = "fixed";
alerts_box.style.bottom = "-40px";
alerts_box.style.right = "10px";
alerts_box.style.zIndex = "1000";
alerts_box.style.pointerEvents = "none";

document.body.appendChild(alerts_box);
