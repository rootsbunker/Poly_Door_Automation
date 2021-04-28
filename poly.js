function openTab(evt, tabName) {
  // Declare all variables
  var i, tabcontent, tablinks;

  // Get all elements with class="tabcontent" and hide them
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }

  // Get all elements with class="tablinks" and remove the class "active"
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }

  // Show the current tab, and add an "active" class to the button that opened the tab
  document.getElementById(tabName).style.display = "block";
  evt.currentTarget.className += " active";
}

// countdown timer east

function timedRefreshEast(timeoutPeriod) {
   var timer = setInterval(function() {
   if (timeoutPeriod > 0) {
       timeoutPeriod -= 1;

       document.getElementById("east_countdown").innerHTML = timeoutPeriod + "&nbsp;";
   } else {
       clearInterval(timer);
            window.location.href = window.location.href;
       };
   }, 1000);
};

// countdown timer west

function timedRefreshWest(timeoutPeriod) {
   var timer = setInterval(function() {
   if (timeoutPeriod > 0) {
       timeoutPeriod -= 1;

       document.getElementById("west_countdown").innerHTML = timeoutPeriod + "&nbsp;";
   } else {
       clearInterval(timer);
            window.location.href = window.location.href;
       };
   }, 1000);
};


// end countdown timer


	
	