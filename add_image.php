<?php
header("content-type:text/html;charset=utf8");
require "./wechat.class.php";
$wechat=new Wechat();
//添加图片
$wechat->add_image();
//获取图片
$wechat->getImage();