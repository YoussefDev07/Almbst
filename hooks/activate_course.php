<?php
require_once "../master/connect.php";

$user_id = $_GET["user_id"];
$course_id = $_GET["course_id"];
$activate_code = $connect -> query("SELECT code FROM activate_codes WHERE code = '".$_GET["code"]."'") -> fetchAll(PDO::FETCH_COLUMN);

function course_expire() {
  $connect = $GLOBALS["connect"];
  $course_id = $GLOBALS["course_id"];

  $course_expire = $connect -> query("SELECT duration FROM courses WHERE id = $course_id") -> fetchAll(PDO::FETCH_COLUMN); $course_expire = $course_expire[0];

  if (str_contains($course_expire, "m")) {
	$course_expire = str_replace("m", "", $course_expire);
	$course_expire = intval($course_expire);
  }
  elseif (str_contains($course_expire, "y")) {
	$course_expire = str_replace("y", "", $course_expire);
	$course_expire = intval($course_expire);
	$course_expire = $course_expire * 12;
  }

  return $course_expire;
}

if (isset($activate_code[0])) {
  $activate_code = $activate_code[0];
  try {
    $activate_course = $connect -> prepare("INSERT INTO subscriptions (course_id, user_id, subscription_date, expire_date) VALUES (?, ?, ?, ?)");
    $activate_course -> execute([$course_id, $user_id, date("Y-m-d"), date("Y-m-d", time() + ((86400  * 30) * course_expire()))]);
    $connect -> exec("DELETE FROM activate_codes WHERE code = '$activate_code'");

	echo 200;
  }
  catch(Exception $e) {
    echo $e->getMessage();
  }
}
else {
  echo "رمز التفعيل غير صحيح!";
}