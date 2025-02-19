<html><body>
<?php
require "auth.php";
require "password.php";
check_cors();

if($auth)
{
  if(strlen($pwhash) > 0)
  {
    require_once("../../vendor/qrcode.php");
    $qr = QRCode::getMinimumQRCode($pwhash, QR_ERROR_CORRECT_LEVEL_Q);
    $qr->printHTML("10px");
    echo "<br>原始 API 令牌：<code>" . $pwhash . "</code>";
  }
  else
  {
  ?><p>未设置密码<?php
  }
}
else
{
?><p>未授权！</p><?php
}
?>
</body>
</html>
