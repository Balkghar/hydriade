/**
 * Fonction permettant de faire la transition entre none to block et inversements
 * @param {Id du champ à changer la classe} value 
 */
function showOrHide(value) {
  
  var x = document.getElementById(value);
  var y = document.getElementById('pitch'.concat(value));
  var v = document.getElementById('buPitch'.concat(value));
  
  setInterval(function(){ 
    if (x.classList.contains('displayBlock') && x.maxHeight != x.scrollHeight+'px') {
      x.style.transitionDuration = '0.5s';
      x.style.maxHeight = x.scrollHeight+'px';
    }else{
      x.style.transitionDuration = '1s';
    }
  }, 500);

  if (x.classList.contains('displayNone')) {
    x.style.maxHeight = x.scrollHeight+'px';
    x.className  = "displayBlock";
  } else {
    x.style.maxHeight = '0px';
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
}
jQuery(document).ready(function() {
  jQuery(".language").change(function() {
    var vLanguage = new Array();
    var C = jQuery('.languageForm').serializeArray();
    jQuery.each(C, function(i, field) { 
      vLanguage.push(field.value);
    });
    jQuery.ajax({
      url : frontend_ajax_object.ajax_url,
      type : 'post',
      data : {
          action : 'showParties',
          language : vLanguage,
      },
      success : function( response ) {
        jQuery('#answer').html(response);
      }
    });
  });
  jQuery.ajax({
    url : frontend_ajax_object.ajax_url,
    type : 'post',
    data : {
        action : 'showParties',
        language : Array('Anglais','Allemand','French'),
    },
    success : function( response ) {
      jQuery('#answer').html(response);
    }
  });
});
