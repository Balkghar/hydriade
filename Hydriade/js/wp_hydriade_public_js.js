function showOrHide(value) {
    var x = document.getElementById(value);
  if (x.classList.contains('displayNone')) {
    x.className  = "displayBlock";
  } else {
    x.className  = "displayNone";
  }
}