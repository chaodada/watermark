<?php

class Tools
{


    /**
     * GET curl函数
     * @param $url 表示请求地址
     * @param string $cookie 表示header的cookie
     * @param $headers 表示header的一些自定义属性
     * @return bool|string
     */
    public function getcurl($url, $cookie = '', $headers = [])
    {
        // 启动一个CURL会话
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $info = curl_exec($curl);
        curl_close($curl);
        return $info;
    }


}

class Wx extends Tools
{

    protected $config = [];
    private $token_file_path;
    private $token;
    private $appid;
    private $secret;



    public function __construct($config = [])
    {
        $this->config = $config;
        $this->token = $config['token'];
        # 存放access_token的目录
        $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wx';

// 	 file_put_contents('post.txt', date('Y-m-d H:i:s')."\r\n" .json_encode($config));
        $this->is_dir($dir);
        $this->token_file_path = $dir;
        $this->appid = $config['appid'];
        $this->secret = $config['secret'];

        // file_put_contents('post.txt', date('Y-m-d H:i:s')."\r\n" . $this->secret);
        $this->access_token = $this->get_accessToken();
        // file_put_contents('post.txt', date('Y-m-d H:i:s')."2222\r\n" .$dir);


    }

    protected function is_dir($path)
    {
        if (!is_dir($path)) {
            @mkdir($path);
        }
    }


    /**
     * 关注后事件
     * @param $postObj
     * @param $content
     */
    public function _doSubscribe($postObj, $content)
    {
        # 处理该关注事件，向用户发送关注信息
        $this->_msgText($postObj['FromUserName'], $postObj['ToUserName'], time(), $content);
    }


    /**
     * 回复文本消息
     * @param $to 接收方帐号（收到的OpenID）
     * @param $from 开发者微信号
     * @param $time 消息创建时间
     * @param $content 回复的消息内容（换行：在content中能够换行，微信客户端就支持换行显示）
     */
    public function _msgText($to, $from, $time, $content)
    {
        # 定义模板
        $_msg_template = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>';
        $response = sprintf($_msg_template, $to, $from, $time, $content);
        die($response);
    }


    /**
     * 回复图文消息
     * @param $to 接收方帐号（收到的OpenID）
     * @param $from 开发者微信号
     * @param $time 时间
     * @param $articlecount 图文消息信息，注意，如果图文数超过限制，则将只发限制内的条数
     * @param $title 图文消息标题
     * @param $description 图文消息描述
     * @param $picurl 图片链接，支持JPG、PNG格式，较好的效果为大图360*200，小图200*200
     * @param $url 点击图文消息跳转链接
     */
    public function _msgGraphic($to, $from, $time, $articlecount, $title, $description, $picurl, $url)
    {
        $_msg_template = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>%s</ArticleCount><Articles><item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item></Articles></xml>';
        $response = sprintf($_msg_template, $to, $from, $time, $articlecount, $title, $description, $picurl, $url);
        die($response);
    }


    /**
     * 回复图片
     * @param $to
     * @param $from
     * @param $time
     * @param $media_id
     */
    public function _msgImg($to, $from, $time, $media_id)
    {
        $_msg_template = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[image]]></MsgType><Image><MediaId><![CDATA[%s]]></MediaId></Image></xml>';
        $response = sprintf($_msg_template, $to, $from, $time, $media_id);
        die($response);
    }


    /**
     * 获取accessToken 超过两小时自动重新获取
     * @return string
     */
    public function get_accessToken()
    {
        if (file_exists($this->token_file_path . '/access_token.json')) {
            $token = file_get_contents($this->token_file_path . '/access_token.json');
            if (strlen($token) < 10) {
            } else {
                $tokenarr = json_decode($token, true);
                if (strtotime(date('Y-m-d H:i:s', strtotime('now'))) < $tokenarr['end']) {
                    return $tokenarr['access_token'];
                }
            }
        }
        # 请求接口
        $output = $this->getcurl("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->secret}");
        $jsoninfo = json_decode($output, true);
        $access_token = $jsoninfo["access_token"];
        if ($access_token) {
            //写入文件 2小时内有效
            $token_json = '{"access_token":"' . $access_token . '","expires_in":7200,"start":"' . strtotime(date('Y-m-d H:i:s', strtotime('now'))) . '","end":"' . strtotime(date('Y-m-d H:i:s', strtotime('+2hour'))) . '"}';
            file_put_contents($this->token_file_path . '/access_token.json', $token_json);
            return $access_token;
        } else {
            return 'api return error';
        }
    }


    /**
     * 获取素材列表
     * @param $type
     * @param $offset
     * @param $count
     * @return mixed
     */
    public function get_article_list($type, $offset, $count)
    {
        //https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=ACCESS_TOKEN

        $url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=" . $this->access_token;
        $data = '{"type":"' . $type . '","offset":"' . $offset . '","count":"' . $count . '"}';
        //返回的数据
        $response = $this->postcurl($url, $data);
//        echo $response;die;
        //echo strip_tags($response);
        $res = json_decode($response, true);
        return $res;
    }


    /**
     * 创建菜单
     * @param $data
     * @return mixed
     */
    public function create_menu($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->access_token;
        $response = $this->postcurl($url, $data);
        $res = json_decode($response, true);
        return $res;

    }

    public function get_menu()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token=' . $this->access_token;
        $response = $this->getcurl($url);
        $res = json_decode($response, true);
        return $res;
    }


    public function upimg($filePath)
    {

        $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=" . $this->access_token . "&type=image";


        $curl = curl_init();
        if (class_exists('\CURLFile')) {
            curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
            $data = array('media' => new \CURLFile(realpath($filePath)));//>=5.5
        } else {
            if (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
            }
            $data = array('media' => '@' . realpath($filePath));//<=5.5
        }
        //   	$post_type = 'toutiao';
        //     $data['type'] = $post_type;
        //     $data['token'] = '5645156e1386da446fab2db259ded22a';
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_USERAGENT, "TEST");
        $result = curl_exec($curl);
        curl_close($curl);
        // 		$result = json_decode($result, true);
        return $result;
        // 		if($result['code']==200 && $result['data']['url'][$post_type]){
        // 		  unlink($newname);
        // 		  $img_url = $result['data']['url'][$post_type];
        //                       #$img_url = $result['data']['url'][$post_type].'?imgurl='.$url.'/'.$newname;
        // 		  echo '{"code":"success","data":{"size":"'.$size.'","path":"'.$newname.'","timestamp":'.$time.',"url":"'.$img_url.'"}}';
        // 		  exit;
        // 		}else{
        // 			#echo '{"code":"success","data":{"size":"'.$size.'","path":"'.$newname.'","timestamp":'.$time.',"url":"'.$url.'/'.$newname.'"}}';
        // 			unlink($newname);
        //                   	   echo '{"status":"0","msg":"上传失败，请稍候再试或者配置自己的第三方OSS"}';
        // 			exit;
        // 		}


    }


    /**
     * 检测微信返回的 signature  跟本地生成的是否一致
     * @return bool
     */
    private function checkSignature()
    {
        //接受数据
        //微信加密签名，signature结合了开发者填写的token参数和请求中的timestamp参数、nonce参数。
        $signature = $_GET["signature"];
        $signature = empty($signature) ? '' : $signature;
        $timestamp = $_GET["timestamp"];
        $timestamp = empty($timestamp) ? '' : $timestamp;
        $nonce = $_GET["nonce"];
        $nonce = empty($nonce) ? '' : $nonce;
        $tmpArr = array($this->token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            file_put_contents('2.txt', json_encode($tmpStr));

            return true;
        } else {
            return false;
        }
    }


    /**
     * 开发者通过检验signature对请求进行校验。若确认此次GET请求来自微信服务器，请原样返回echostr参数内容，则接入生效，成为开发者成功，否则接入失败。
     * @param bool $return
     * @return bool|mixed|string
     */
    public function tokenValid($return = false)
    {
        //接受微信echostr参数
        $echoStr = $_GET["echostr"];
        $echoStr = empty($echoStr) ? '' : $echoStr;
        if ($return) {
            if ($echoStr) {
                if ($this->checkSignature()) {
                    return $echoStr;
                } else {
                    return false;
                }
            } else {
                return $this->checkSignature();
            }
        } else {
            if ($echoStr) {
                if ($this->checkSignature()) {
                    //符合这个条件就通过
                    die($echoStr);
                } else {
                    die('no access1');
                }
            } else {
                if ($this->checkSignature()) {
                    return true;
                } else {
                    die('404');
                }
            }
        }
        return false;
    }

}

$config = ["appid" => "xxxxxx", "secret" => "xxxxxx", "token" => "xxxxxx"];

# 微信对象
$wxobj = new Wx($config);

# 验证token
$wxobj->tokenValid();
# 获取accessToken
$wxobj->get_accessToken();

$postStr = file_get_contents('php://input');

if (!empty($postStr)) {
    $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
    $jsonStr = json_encode($postObj);
    $data = json_decode($jsonStr, true);
    switch ($data['MsgType']) {
        case 'event':
            # 判断具体的事件类型（关注、取消、点击）
            // if ($Event == 'subscribe') {
            // } elseif ($Event == 'CLICK') {
            // } elseif ($Event == 'VIEW') {
            // }
            break;
        case 'text':
            //文本消息
            $wxobj->_msgText($data['FromUserName'], $data['ToUserName'], time(), "123");
            break;
        case 'image':
            //图片消息
            if (!empty($data["PicUrl"])) {

                $savePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wx' . DIRECTORY_SEPARATOR . "save";
                # 水印
                $watermarkPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wx' . DIRECTORY_SEPARATOR . 'watermarks' . DIRECTORY_SEPARATOR . "Watermark1.JPG";

                $fileName = md5($data["PicUrl"]);

                $filePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wx' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $fileName;

                CurlGet($data["PicUrl"], $filePath);
                //   sleep(3);


                # 图片信息
                $imgInfo = getimagesize($filePath);
                $i_width = $imgInfo[0];
                $i_height = $imgInfo[1];


                # 水印信息
                $watermarkInfo = getimagesize($watermarkPath);
                $w_width = $watermarkInfo[0];
                $w_height = $watermarkInfo[1];


                if (($w_width / $w_height) > ($i_width / $i_height)) {
                    $rate = $i_width / $w_width;
                } else {
                    $rate = $i_height / $w_height;
                }
                $rate = number_format($rate, 1);

                // 新水印宽高
                $new_w_width = (int)($w_width * $rate);
                $new_w_height = (int)($w_height * $rate);


                # 创建新水印资源
                $new_watermark = imagecreatetruecolor($new_w_width, $new_w_height);
                # 获取原水印资源
                $watermark = imagecreatefromjpeg($watermarkPath);
                # 放大或缩小水印
                imagecopyresampled($new_watermark, $watermark, 0, 0, 0, 0, $new_w_width, $new_w_height, $w_width, $w_height);

                # 用户图
                $user_image = imagecreatefromjpeg($filePath);


                # 合成图片的高度
                $new_i_height = (int)($i_height + $new_w_height);

                # 创建真彩画布
                $new_user_image = imagecreatetruecolor($i_width, $new_i_height);

                $black = imagecolorallocate($new_user_image, 0, 0, 0); //创建黑色
                $white = imagecolorallocate($new_user_image, 255, 255, 255); //创建白色
                $yellow = imagecolorallocate($new_user_image, 255, 83, 0); //创建黄色
                $gray = imagecolorallocate($new_user_image, 180, 180, 180); //创建灰色

                # 为画布填充白色
                imagefill($new_user_image, 0, 0, $white);

                # 拼接用户图片
                imagecopyresampled($new_user_image, $user_image, 0, 0, 0, 0, imagesx($user_image), imagesy($user_image), imagesx($user_image), imagesy($user_image));
                # 拼接新水印资源
                imagecopyresampled($new_user_image, $new_watermark, 0, $i_height, 0, 0, $new_w_width, $new_w_height, imagesx($new_watermark), imagesy($new_watermark)); //将二维码和画布拼合


                $saveFilePath = $savePath . "/" . $fileName . ".jpg";
                /* 将缩放后的图片$image_p保存， 100（最佳质量，文件最大) */
                imagejpeg($new_user_image, $saveFilePath, 100);

                imagedestroy($new_user_image);        //销毁拼接后的图片资源
                # 销毁用户图片资源
                imagedestroy($user_image);
                # 销毁新水印资源
                imagedestroy($new_watermark);
                # 销毁原水印资源
                imagedestroy($watermark);


                $res = $wxobj->upimg($saveFilePath);
                $resData = json_decode($res, true);
                if (isset($resData["media_id"]) && !empty($resData["media_id"])) {
                    $wxobj->_msgImg($data['FromUserName'], $data['ToUserName'], time(), $resData["media_id"]);
                } else {
                    $wxobj->_msgText($data['FromUserName'], $data['ToUserName'], time(), "哎呀,合成失败了呢");
                }

                // file_put_contents('12.txt', $res);


                // $file = file_get_contents($data["PicUrl"]);
                // $ext = exif_imagetype($file)
                //                     file_put_contents('12.txt', $ext);

                // // file_put_contents($this->token_file_path . '/access_token.json', );
                //             $wxobj->_msgText($data['FromUserName'], $data['ToUserName'], time(), $ext);

            }


            file_put_contents('12.txt', json_encode($data));


            break;
    }
}
// //            file_put_contents('2.txt', json_encode($this->postObj));
// //            die;
//             $this->getCode(); # 获取推广码
//            
//         } else {
//             @error_log("没有收到消息", 3, './empty.log');
//             exit;
//         }


function GetUrlFileName($url)
{
    $urlArray = parse_url($url);
    $pathInfoData = pathinfo($urlArray['path']);
//        print_r($pathInfoData);
    return $pathInfoData["filename"];
}

function CurlGet($url, $file)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $file_content = curl_exec($ch);
    curl_close($ch);
    $downloaded_file = fopen($file, 'w');
    fwrite($downloaded_file, $file_content);
    fclose($downloaded_file);
}

function GetFileType($path)
{
    $finfo = finfo_open(FILEINFO_MIME); // 返回 mime 类型
    $filename = $path;
    $fileType = finfo_file($finfo, $filename);
    finfo_close($finfo);
    return $fileType;
}



// 	 file_put_contents('post.txt', date('Y-m-d H:i:s')."\r\n" .json_encode($wxobj));


// 	 file_put_contents('post.txt', date('Y-m-d H:i:s')."\r\n" ."request_headers"."\r\n". print_r(getallheaders(),true) ."\r\n"."POST_DATA" ."\r\n".file_get_contents('php://input'). "\r\n".var_export($_GET,true). "\r\n".var_export($_POST,true) , FILE_APPEND);







