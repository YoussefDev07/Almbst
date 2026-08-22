// Initialize optional page animation support when the vendor library is loaded.
if (typeof WOW !== "undefined") {
  new WOW().init();
}
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

// Copy an exam URL from the administration exam list.
function copyExamLink(examId) {
  const url = `${window.location.origin}/exam/take_exam.php?exam_id=${examId}`;
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url).then(() => alert("تم النسخ"));
  }
}

// Validate the library upload before showing its blocking progress state.
function setupLibraryUpload() {
  const form = document.getElementById("libraryUploadForm");
  const input = document.getElementById("pdf_file");
  const button = document.getElementById("libraryUploadBtn");
  const label = button ? button.querySelector(".library-submit-label") : null;
  const loading = document.getElementById("libraryLoading");
  const maxSize = 10 * 1024 * 1024;

  if (!form || !input || !button || !loading) return;

  form.addEventListener("submit", event => {
    const file = input.files && input.files[0];
    const extension = file ? file.name.split(".").pop().toLowerCase() : "";

    if (!file) {
      event.preventDefault();
      alert("يرجى اختيار ملف PDF أولاً.");
    } else if (extension !== "pdf" || file.type !== "application/pdf") {
      event.preventDefault();
      alert("مسموح برفع ملفات PDF فقط.");
      input.value = "";
    } else if (file.size >= maxSize) {
      event.preventDefault();
      alert("حجم الملف يجب أن يكون أقل من 10 ميجابايت.");
      input.value = "";
    } else {
      loading.classList.add("is-visible");
      loading.setAttribute("aria-hidden", "false");
      button.disabled = true;
      if (label) label.textContent = "جارٍ الرفع...";
    }
  });
}

$(window).ready(function(){

setupLibraryUpload();

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

$(".contents aside button").click(function(){
  var sessionID = $(".contents").attr("session-id");
  var videoID = $(this).attr("data-id");
  location.assign(`./course.php?session=${sessionID}&id=${videoID}`);
});

// whatsapp

$(".whatsapp").click(function(){
  window.open("https://wa.me/000000000000", "_blank");    
});

// copyrights 

$("#copyrightsYear").html(date.getFullYear());

});