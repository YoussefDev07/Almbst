new WOW().init();
const date = new Date();

// account

var content = document.createElement("div");
content.classList.add("login");
content.innerHTML = `<button type="button" class="google" onclick="location.assign('https://accounts.google.com/o/oauth2/v2/auth?response_type=code&access_type=online&client_id=159539750861-varjob8dg4gftcfl4rf6mnkfes4qvv43.apps.googleusercontent.com&redirect_uri=http%3A%2F%2Flocalhost%2Fwww%2F%25d8%25a7%25d9%2584%25d9%2585%25d8%25a8%25d8%25b3%25d8%25b7%2Faccount.php&state&scope=email%20profile&approval_prompt=auto')"><i class="fab fa-google"></i>التسجيل عبر قوقل</button>`;

function login() {
  swal({
    content: content,
    button: false,
    className: "loginBox"
  });
}

function activateCourse(courseId) {
  swal({
    title: "أدخل رمز التفعيل:",
    content: "input",
    button: {
      text: "تفعيل",
      closeModal: false
    }
  }).then(code => {
    if (!code) throw null;
    return fetch(`./hooks/activate_course.php?code=${code}&user_id=${$.cookie("id")}&course_id=${courseId}`);
  }).then(res => {
    return res.text();
  }).then(result => {
    console.log(result);
    if (result == 200) {
      swal({
        title: "تم التفعيل بنجاح!",
        icon: "success",
        button: "حسناً",
      }).then(() => {
        window.location.reload();
      });
    }
    else {
      swal({
       title: result,
       icon: "error",
       button: "إغلاق",
      });
    }
  }).catch(err => {
  if (err) {
    swal("حدث خطأ", `${err}`, "error");
  } else {
    swal.stopLoading();
    swal.close();
  }
});
}

// accounts

function logout() {
  location.assign("./hooks/logout.php");
}

$(window).ready(function(){

// slider

$("#slider").click(function(){
  $(".slider").css("display", "flex");
  $(".slider nav").css("animation-name", "slider-move-in");
});

$("#closeSlider").click(function(){
  $(".slider nav").css("animation-name", "slider-move-out");
  setTimeout(function(){
    $(".slider").css("display", "none");
  }, 599);
});

// contents

$(".contents aside details button").click(function(){
  var sessionID = $(".contents").attr("session-id");
  var videoID = $(this).attr("data-id");
  location.assign(`./course.php?session=${sessionID}&id=${videoID}`);
});

// copyrights 

$("#copyrightsYear").html(date.getFullYear());

});