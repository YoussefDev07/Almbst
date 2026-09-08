$(document).ready(function(){

  // print-page

  $("#printPage").click(function(){
    $(".noPrint").hide();
    $("#scoresChart").css("width", "100%");
    window.print();
    $(".noPrint").show();
  });

});