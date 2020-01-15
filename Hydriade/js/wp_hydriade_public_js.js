function showOrHide(value) {
  
  var x = document.getElementById(value);
  var y = document.getElementById('pitch'.concat(value));
  
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
}
