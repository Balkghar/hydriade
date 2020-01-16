function showOrHide(value) {
  
  var x = document.getElementById(value);
  var y = document.getElementById('pitch'.concat(value));
  var v = document.getElementById('buPitch'.concat(value));
  
  if (x.classList.contains('displayNone')) {
    x.className  = "displayBlock";
  } else {
    x.className  = "displayNone";
  }
  if (y.style.backgroundColor === "rgb(255, 255, 255)") {
      y.style.backgroundColor  = "rgb(245, 245, 245)";
  } else {
    y.style.backgroundColor  = "rgb(255, 255, 255)";
  }
  if (v.classList.contains('buPitch2')) {
    v.className  = "buPitch";
  } else {
    v.className  = "buPitch2";
  }
  /*
  if(v.innerHTML.localeCompare('✛') == 0){
    v.innerHTML = '⎯';
  }
  else{
    v.innerHTML = '✛';
  }*/
}
