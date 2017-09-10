<?php
header("content-type:text/html;charset=utf8");
require "./wechat.class.php";
$wechat=new Wechat();
//生成token值
echo $wechat->getAccessToken();