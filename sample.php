<?php

// jpeg2swf Version 0.1
//
// by Andreas Windischer
// email: andreas_@gmx.net
// http://jpeg2swf.sourceforge.net

  include("jpeg2swf.php");
  
  $filename="cat.jpg";
  $jpg=fopen($filename, "rb");
  $jpeg=fread($jpg, filesize($filename));

  header('Content-type: application/x-shockwave-flash');
  header('Content-Disposition: attachment; filename=cat.swf');
  echo $jpeg;
  //echo jpeg2swf($jpeg);
?>