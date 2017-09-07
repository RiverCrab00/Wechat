<?php
require './wechat.class.php';
$wechat=new Wechat();
if($_GET['echostr']){
	$wechat->valid();
}else{
	$wechat->responseMsg();
}

