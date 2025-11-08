<?php
function convert_duration_to_arabic($duration) {
  if ($duration == "1m") {
	return "شهر";
  }
  elseif ($duration == "2m") {
	return "شهرين";
  }
  elseif ($duration == "11m") {
	return str_replace("m", " شهر", $duration)
  }
  elseif ($duration == "12m" || $duration == "1y") {
	return "سنة";
  }
  else {
	return str_replace("m", " شهور", $duration);
  }
}