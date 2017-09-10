<?php
require './wechat.cfg.php';
class Wechat{
	private $token;
	private $textTpl;
	public function __construct($token){
		$this->token=TOKEN;
		$this->appid= APPID;
		$this->appsecret= APPSECRET;
		$this->textTpl="<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
						</xml>";
        $this->newTpl="<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[news]]></MsgType>
                <ArticleCount>%s</ArticleCount>
                <Articles>%s</Articles>
                        </xml>";
        $this->itemTpl="<item>
                    <Title><![CDATA[%s]]></Title> 
                    <Description><![CDATA[%s]]></Description>
                    <PicUrl><![CDATA[%s]]></PicUrl>
                    <Url><![CDATA[%s]]></Url>
                </item>";
        $this->imageTpl="<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[image]]></MsgType>
                    <Image>
                        <MediaId><![CDATA[%s]]></MediaId>
                    </Image>
                </xml>";
        $this->musicTpl="
            <xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[music]]></MsgType>
                <Music>
                    <Title><![CDATA[%s]]></Title>
                    <Description><![CDATA[%s]]></Description>
                    <MusicUrl><![CDATA[%s]]></MusicUrl>
                    <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                    <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
                </Music>
            </xml>";
    }
	public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        file_get_contents('data.xml',$postStr);
        //extract post data
        if (!empty($postStr)) {
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
              the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            switch ($postObj->MsgType) {
            	case 'text':
            		$this->doText($postObj);
            		break;
            	case 'image':
            		$this->doImage($postObj);
            		break;
            	case 'voice':
            		$this->doVoice($postObj);
            		break;
            	case 'location':
            		$this->doLocation($postObj);
            		break;
                case 'event';
                    $this->doEvent($postObj);
                    break;
            	default:
            		# code...
            		break;
            }
            
        } else {
            echo "";
            exit;
        }
    }

    private function checkSignature()
    {
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
    private function doText($postObj,$keyword){
            $keyword=isset($keyword)?$keyword:trim($postObj->Content);
            //$keyword = trim($postObj->Content);
            if (!empty($keyword)) {
                if($keyword=='图片'){
                    $MediaId='uW4ABoWPLlFPUF3KxnRDgzyykptAR3cJEIDiSVKYr-R0kRlhkAolX4x_sS7NLrGa';
                    $resultStr = sprintf($this->imageTpl,$postObj->FromUserName,$postObj->ToUserName,time(),$MediaId);
                    echo $resultStr;
                    die;
                }elseif($keyword=='歌曲'){
                    $this->sendMusic($postObj);
                    exit();
                }elseif(mb_substr($keyword,0,2)=='快递'){
                    $this->sendExpress($keyword);
                    exit();
                }
                $msgType = "text";
                //$contentStr = "Welcome to wechat world!";
                $url="https://way.jd.com/turing/turing?info={$keyword}&loc=北京市海淀区信息路28号&userid=222&appkey=a1998b3a8ab71832c6ffc855631db6d1";
                $contentStr=json_decode($this->request($url))->result->text;
                /*$url="http://api.qingyunke.com/api.php?key=free&appid=0&msg={$keyword}";
                $contentStr=str_replace('{br}', "\r\n", json_decode($this->request($url,false))->content);*/
                $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text', $contentStr);
                echo $resultStr;
            } else {
                echo "Input something...";
            }
    }
    private function doImage($postObj){
        $MediaId=$postObj->MediaId;
    	$resultStr = sprintf($this->imageTpl,$postObj->FromUserName,$postObj->ToUserName,time(),$MediaId);
    	echo $resultStr;
    }
    private function doVoice($postObj){
        $keyword=$postObj->Recognition;
        $this->doText($postObj,$keyword);
    	/*$MediaId=$postObj->MediaId;
    	$resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text',"语音消息无法识别,MediaId:{$MediaId}");
    	 echo $resultStr;*/
    }
    private function doLocation($postObj){
    	$location=$postObj->Location_X.','.$postObj->Location_Y;
        $contentStr=$this->amapLBS($location);
    	$resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text',$contentStr);
    	echo $resultStr;
    }
    private function amapLBS($location){
        $url="http://restapi.amap.com/v3/place/around?key=0a523e64ec1d397af8223b516a0b2aec&location={$location}&types=餐厅&radius=10000";
        $res=$this->request($url,false,'get');
        $arr=json_decode($res)->pois;
        $data=array_slice($arr,0,5);
        $text="推荐餐厅为:\n";
        foreach ($data as $key => $value) {
            $text.="\t".$value->name."\r类型:".$value->type."\r地址:".$value->address."\r\n";
        }
        return $text;
    }
    private function sendMusic($postObj){
        $MusicUrl=$HQMusicUrl='http://47.94.23.12/wechat/intro.mp3';
        $MediaId='M1ujBy98X_FUH_G3A7SD4cI94QrN4vkQ0UAvmwH5XG4rsPJa3zjJT6NJkM9SrRLm';
        $title='intro';
        $description='The xx';
        $resultStr = sprintf($this->musicTpl,$postObj->FromUserName,$postObj->ToUserName,time(),$title,$description,$MusicUrl,$HQMusicUrl,$MediaId);
        echo $resultStr;
    }
    private function sendExpress($keyword){
        $num=mb_substr($keyword,2);
        $host = "http://jisukdcx.market.alicloudapi.com";
        $path = "/express/query";
        $method = "GET";
        $appcode = "6201e188b681489f92c4113d17575d40";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "number={$num}&type=auto";
        $bodys = "";
        $url = $host . $path . "?" . $querys;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($curl, CURLOPT_FAILONERROR, false);
        //以文件流的形式
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //文件头以文件流的形式
        //curl_setopt($curl, CURLOPT_HEADER, true);
        $res=json_decode(curl_exec($curl));
        $contentStr="物流信息:\n";
        foreach ($res->result->list as $value) {
            $contentStr.=$value->time."\t".$value->status;
        }
        file_put_contents('express.txt',$contentStr);
        $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text', $contentStr);
        echo $resultStr;
    }
    public function request($url,$https=true,$method='get',$data=null){
    	//1.初始化
    	$ch=curl_init($url);
        //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。 
    	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    	if($https===true){
            //禁用后cURL将终止从服务端进行验证
    		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
            //检查服务器SSL证书
    		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
    	}
    	if($method==='post'){
            //启用时会发送一个常规的POST请求
    		curl_setopt($ch,CURLOPT_POST,true);
            //全部数据使用HTTP协议中的"POST"操作来发送。
    		curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
    	}
    	$content=curl_exec($ch);
    	return $content;
    }
    public function getAccessToken(){
    	$mem=new Memcache();
    	$mem->connect('127.0.0.1',11211);
    	$access_token=$mem->get('access_token');
    	if($access_token===false){
    		$url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
	    	$content=$this->request($url);
	    	$access_token=json_decode($content)->access_token;
	    	//file_put_contents('./access_token.text',$access_token);
	    	$mem->set('access_token',$access_token,0,7200);
    	}
    	return $access_token;
    }
    public function getTicket($tmp=true){
    	$url='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$this->getAccessToken();
    	if($tmp===true){
    		$data='{"expire_seconds": 604800, "action_name": "QR_STR_SCENE", "action_info": {"scene": {"scene_str": "test"}}}';
    	}else{
    		$data='{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "test"}}}';
    	}
    	$content=$this->request($url,true,'post',$data);
    	$ticket=json_decode($content)->ticket;
    	return $ticket;
    }
    public function getQRCode(){
    	$url='https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$this->getTicket();
    	$content=$this->request($url);
        /*header('content-type:image/jpg');
    	echo $content;*/
        file_put_contents(time().'.jpg',$content);
    }
    public function doEvent($postObj){
        switch ($postObj->Event) {
            case 'subscribe':
                $this->doSubsribe($postObj);
                break;
            case 'unsubscribe':
                $this->doUnsubscribe($postObj);
                break;
            case 'SCAN':
                $this->doScan($postObj);
                break;
            case 'LOCATION':
                $this->doLocation($postObj);
                break; 
            case 'CLICK':
                $this->doClick($postObj);
                break;    
            default:
                # code...
                break;
        }
    }
    public function doSubsribe($postObj){
        $scene_id='您参加的活动代号为:'.$postObj->EventKey;
        $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text',$scene_id);
        echo $resultStr;
    }
    public function doScan($postObj){
        $scene_id='已经关注,您参加的活动代号为:'.$postObj->EventKey;
        $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text',$scene_id);
        echo $resultStr;
    }
    public function doUnsubscribe($postObj){
        $data=$postObj->FromUserName.'在'.date('Y-m-d H-i-s',time()).'取消了关注';
        file_put_contents('list.text',$data,FILE_APPEND);
    }
    public function doClick($postObj){
        $EventKey=$postObj->EventKey;
        if($EventKey=='TODAY_NEWS'){
            $url='http://v.juhe.cn/toutiao/index?type=top&key=453f3e8bd8355e0d6ebca0147e4f256b';
            $content=json_decode($this->request($url,false,'get'));
            $data=$content->result->data;
            $data=array_slice($data,0,5);
            $article='';
            foreach($data as $v){
                $article.=sprintf($this->itemTpl,$v->title,$v->title,$v->thumbnail_pic_s,$v->url);
            }
            $resultStr = sprintf($this->newTpl,$postObj->FromUserName,$postObj->ToUserName,time(),count($data),$article);
            //file_put_contents('article.xml',$article);
            //file_put_contents('news.xml',$resultStr);
            echo $resultStr;
        }elseif($EventKey=='HELP'){
            $contentStr="快递查询方式:快递+单号\r\n例:快递3920912053409";
            $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text', $contentStr);
            echo $resultStr;
        }elseif($EventKey=='TODAY_WECHAT'){
            $url="http://v.juhe.cn/weixin/query?pno=&ps=&dtype=&key=ebc3901bd180d1c0a0dc4872e99f8f93";
            $content=json_decode($this->request($url,false,'get'));
            $arr=$content->result->list;
            $data=array_slice($arr,0,5);
            $article='';
            foreach($data as $v){
                $article.=sprintf($this->itemTpl,$v->title,$v->source,$v->firstImg,$v->url);
            }
            $resultStr = sprintf($this->newTpl,$postObj->FromUserName,$postObj->ToUserName,time(),count($data),$article);
            echo $resultStr;
        }elseif($EventKey=='TODAY_CALENDAR'){
            $date=date('Y-n-j',time());
            $url="http://v.juhe.cn/calendar/day?date={$date}&key=b20d6555a91c7c678cb0919edff9c997";
            $content=$this->request($url,false,'get');
            $res=json_decode($content)->result->data;
            $contentStr="{$res->lunarYear}\n属相:{$res->animalsYear}\n农历:{$res->lunar}\n宜:{$res->suit}\n忌:{$res->avoid}\n日期:{$res->date}";
            //file_put_contents('date.xml',$content);
            $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text', $contentStr);
            echo $resultStr;
        }else{
            $contentStr='测试中';
            $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text', $contentStr);
            echo $resultStr;
        }     
    }
    public function getUserList(){
        $url="https://api.weixin.qq.com/cgi-bin/user/get?access_token=".$this->getAccessToken();
        $res=$this->request($url);
        $content=json_decode($res);
        //var_dump($content);
        echo '关注用户数:'.$content->total.'<br>';
        echo '本此次数:'.$content->total.'<br>';
        foreach($content->data->openid as $key => $value){
            echo ($key+1).'###'.$value.'<br>';
        }
    }
    public function getUserInfo(){
        $url="https://api.weixin.qq.com/cgi-bin/user/get?access_token=".$this->getAccessToken().'$openid=';    
    }
    public function createMenu(){
        $url='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->getAccessToken();
        $data= '{
            "button":[
            {
                "name": "新闻热点", 
                "sub_button": [
                    { 
                      "type":"click",
                      "name":"今日头条",
                      "key":"TODAY_NEWS",
                      "sub_button": [ ]
                    },
                    { 
                      "type":"click",
                      "name":"微信热点",
                      "key":"TODAY_WECHAT",
                      "sub_button": [ ]
                    }
                ]
            },
            {
                "name": "扫码", 
                "sub_button": [
                    {
                        "type": "scancode_waitmsg", 
                        "name": "扫码提示", 
                        "key": "rselfmenu_0_0", 
                        "sub_button": [ ]
                    }, 
                    {
                        "type": "scancode_push", 
                        "name": "商品信息查询", 
                        "key": "rselfmenu_0_1", 
                        "sub_button": [ ]
                    }
                ]
            },     
            {
                "name":"查询",
                "sub_button":[
                    {
                        "type":"click",
                        "name":"万年历",
                        "key":"TODAY_CALENDAR",
                        "sub_button": [ ]
                    },
                    {
                        "type":"view",
                        "name":"快递",
                        "url":"http://m.kuaidi100.com//",
                        "sub_button": [ ]
                    },
                    {
                        "type":"click",
                        "name":"帮助",
                        "key":"HELP",
                        "sub_button": [ ]
                    }
                ]
            }]
        }';
        $res=$this->request($url,true,'post',$data);
        $res=json_decode($res);
        if($res->errcode==0){
            echo '菜单创建成功';
        }else{
            echo '错误代码为:'.$res->errcode.'<br>';
            echo '错误信息为:'.$res->errmsg.'<br>';
        }
    }
    public function delMenu(){
        $url='https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$this->getAccessToken();
        $res=$this->request($url,true,'get');
        $res=json_decode($res);
        if($res->errcode==0){
            echo '菜单删除成功';
        }else{
            echo '错误代码为:'.$res->errcode.'<br>';
            echo '错误信息为:'.$res->errmsg.'<br>';
        }
    }

}