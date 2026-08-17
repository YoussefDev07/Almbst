<?php
  date_default_timezone_set("Asia/Riyadh");

  try {
      $connect = new PDO("mysql:host=localhost;dbname=almbst", "root", "");
      $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      
      $connect->exec("SET time_zone = '+03:00';");

  } catch (PDOException $e) {
      die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
  }

  $master_user__id = "";