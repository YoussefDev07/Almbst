<?php
include "../config.php";
$exam_id = $_GET["id"];

$connect -> exec("DELETE FROM exams WHERE id = $exam_id");

header("Location:index.php");
exit();