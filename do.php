<?php
header("content-type:text/html;charset=utf8");
require "./wechat.class.php";
$wechat=new Wechat();
//删除自定义菜单
$wechat->delMenu();
//生成自定义菜单
$wechat->createMenu();
//生成token值
//echo $wechat->getAccessToken();
echo "<br>";