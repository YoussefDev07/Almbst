<?php
require_once "../master/connect.php";

$today = date("Y-m-d");
$expired_subscriptions = $connect -> query("SELECT id FROM subscriptions WHERE expire_date = '$today'") -> fetchAll(PDO::FETCH_COLUMN);

foreach ($expired_subscriptions as $subscription_index => $subscription_id) {
  $connect -> exec("UPDATE subscriptions SET expired = true WHERE id = $subscription_id");
}