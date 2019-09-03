<?php
namespace czt\unipush;

require_once(dirname(__FILE__) . '/' . 'config.php');
require_once(dirname(__FILE__) . '/' . 'igetui/IGt.APNPayload.php');
require_once(dirname(__FILE__) . '/' . 'IGt.Push.php');
require_once(dirname(__FILE__).'/'.'igetui/template/notify/IGt.Notify.php');

class PushService
{
    private $cid = '';          //clientid,单独发送时必填
    //推送配置
    private $appid;         // 个推平台申请应用的AppID
    private $appkey;        //个推平台申请应用的AppKey
    private $package;       //应用包名，修改为自己应用的包名
    private $mastersecrect; //个推平台申请应用的MasterSecret
    private $host;          //个推推送平台服务器地址

    public $igt;            //IGeTui类实例

    public function __construct(array $options = [])
    {
        $this->package = PACKAGENAME;
        $this->appid = APPID;
        $this->appkey = APPKEY;
        $this->mastersecrect = MASTERSECRET;
        $this->host = HOST;
        if(!empty($options)) {
            !empty($options['package']) && $this->package = $options['package'];
            !empty($options['appid']) && $this->appid = $options['appid'];
            !empty($options['appkey']) && $this->appkey = $options['appkey'];
            !empty($options['mastersecrect']) && $this->mastersecrect = $options['mastersecrect'];
            !empty($options['host']) && $this->host = $options['host'];
        }

        $this->igt = new \IGeTui($this->host, $this->appkey,$this->mastersecrect);
    }

    /**
     * 设置clientid，不为空时将采用单独推送，为空时则是全部推送
     * @param $cid
     */
    public function setCid($cid)
    {
        $this->cid = $cid;
    }

    /**
     * 获取IGeTui类实例
     * @return \IGeTui
     */
    public function getIgt()
    {
        return $this->igt;
    }

    /**
     * 创建通知
     * @return \IGtNotificationTemplate
     */
    public function createNotificationTemplate()
    {
        $template = new \IGtNotificationTemplate();
        $template->set_appId($this->appid);     //应用appid
        $template->set_appkey($this->appkey);   //应用appkey
        $template->set_transmissionType(1);//透传消息类型，Android平台控制点击消息后是否启动应用
        /*if(!empty($p)){
            $template->set_transmissionContent($p);//透传内容，点击消息后触发透传数据
        }*/
//        $template->set_title('');//通知栏标题
//        $template->set_text('');//通知栏内容
//    $template->set_logo("http://wwww.igetui.com/logo.png");//通知栏logo，不设置使用默认程序图标
        $template->set_isRing(true);//是否响铃
        $template->set_isVibrate(true);//是否震动
        $template->set_isClearable(true);//通知栏是否可清除
        return $template;
    }

    /**
     * 创建点击跳转通知内容
     * @return \IGtLinkTemplate
     */
    public function createLinkTemplate()
    {
        $template = new \IGtLinkTemplate();
        $template ->set_appId($this->appid);//应用appid
        $template ->set_appkey($this->appkey);//应用appkey
//        $template ->set_title($t);//通知栏标题
//        $template ->set_text($c);//通知栏内容
//    $template->set_logo("http://wwww.igetui.com/logo.png");//通知栏logo，不设置使用默认程序图标
        $template ->set_isRing(true);//是否响铃
        $template ->set_isVibrate(true);//是否震动
        $template ->set_isClearable(true);//通知栏是否可清除
//        $template ->set_url($l);//打开连接地址
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息
        return $template;
    }

    /**
     * 创建支持厂商通道的透传消息
     * @return \IGtTransmissionTemplate
     */
    public function createTransmissionTemplate($title, $content, $p)
    {
        $intent = "intent:#Intent;action=android.intent.action.oppopush;launchFlags=0x14000000;component={$this->package}/io.dcloud.PandoraEntry;S.UP-OL-SU=true;S.title={$title};S.content={$content};S.payload={$p};end";
        $template =  new \IGtTransmissionTemplate();
        $template->set_appId($this->appid);//应用appid
        $template->set_appkey($this->appkey);//应用appkey
        $template->set_transmissionType(2);//透传消息类型:1为激活客户端启动

        //为了保证应用切换到后台时接收到个推在线推送消息，转换为{title:'',content:'',payload:''}格式数据，UniPush将在系统通知栏显示
        //如果开发者不希望由UniPush处理，则不需要转换为上述格式数据（将触发receive事件，由应用业务逻辑处理）
        //注意：iOS在线时转换为此格式也触发receive事件
        $payload = array('title'=>$title, 'content'=>$content);
        $pj = json_decode($p, TRUE);
        $payload['payload'] = is_array($pj)?$pj:$p;
        $template->set_transmissionContent(json_encode($payload));//透传内容

        //兼容使用厂商通道传输
        $notify = new \IGtNotify();
        $notify->set_title($title);
        $notify->set_content($content);
        $notify->set_intent($intent);
        $notify->set_type(\NotifyInfo_type::_intent);
        $template->set3rdNotifyInfo($notify);


        //iOS平台设置APN信息，如果应用离线（不在前台运行）则通过APNS下发推送消息
        $apn = new \IGtAPNPayload();
        $apn->alertMsg = new \DictionaryAlertMsg();
        $apn->alertMsg->body = $content;
        $apn->add_customMsg('payload', is_array($pj)?json_encode($pj):$p);//payload兼容json格式字符串
        $template->set_apnInfo($apn);
        //个推老版本接口: $template ->set_pushInfo($actionLocKey,$badge,$message,$sound,$payload,$locKey,$locArgs,$launchImage);
        //$template->set_pushInfo('', 0, $c, '', $p, '', '', '');

        return $template;
    }

    /**
     * 创建透传通知
     * @param $p  推送数据内容
     * @return IGtNotificationTemplate
     */
    function createTransMessage($p) {
        $template =  new \IGtTransmissionTemplate();
        $template->set_appId($this->appid);//应用appid
        $template->set_appkey($this->appkey);//应用appkey
        $template->set_transmissionType(2);//透传消息类型:1为激活客户端启动
        $template->set_transmissionContent($p);//透传内容
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息

        //当透传消息满足格式{title:'',content:'',payload:''}时兼容支持厂商推送通道
        $jp = json_decode($p, true);
        if(!empty($jp) && !empty($jp['title']) && !empty($jp['content'])){
            $title = $jp['title'];
            $content = $jp['content'];
            $payload = empty($jp['payload'])?'':$jp['payload'];
            $pn = $this->package;

            $notify = new \IGtNotify();
            $notify->set_title($jp['title']);
            $notify->set_content($jp['content']);
            $notify->set_intent("intent:#Intent;action=android.intent.action.oppopush;component={$pn}/io.dcloud.PandoraEntry;S.UP-OL-SU=true;S.title={$title};S.content={$content};S.payload={$payload};end");
            $notify->set_type(\NotifyInfo_type::_intent);

            $template->set3rdNotifyInfo($notify);
        }

        return $template;
    }

    /**
     * 创建下载通知
     * @return IGtNotyPopLoadTemplate
     */
    public function createDownTemplate(){
        $template =  new \IGtNotyPopLoadTemplate();

        $template ->set_appId($this->appid);//应用appid
        $template ->set_appkey($this->appkey);//应用appkey
        //通知栏
//        $template ->set_notyTitle($t);//通知栏标题
//        $template ->set_notyContent($c);//通知栏内容
//        $template ->set_notyIcon("");//通知栏logo
        $template ->set_isBelled(true);//是否响铃
        $template ->set_isVibrationed(true);//是否震动
        $template ->set_isCleared(true);//通知栏是否可清除
        //弹框
//        $template ->set_popTitle($pt);//弹框标题
//        $template ->set_popContent($pc);//弹框内容
//    $template ->set_popImage("");//弹框图片
        $template ->set_popButton1("下载");//左键
        $template ->set_popButton2("取消");//右键
        //下载
//    $template ->set_loadIcon("");//弹框图片
//        $template ->set_loadTitle($dt); //下载标题
//        $template ->set_loadUrl($dl); //下载链接
        $template ->set_isAutoInstall(false);
        $template ->set_isActived(true);
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息

        return $template;
    }

    /**
     * 创建发送消息对象
     * @param $template
     * @param $cid
     * @return \IGtAppMessage|\IGtSingleMessage
     */
    public function createMessage($template, $cid = '')
    {
        if(!empty($cid)) {
            $this->cid = $cid;
        }

        if(!empty($this->cid)) {
            if(is_array($this->cid)) {
                $message = new \IGtListMessage();
            } else {
                $message = new \IGtSingleMessage();
            }
        } else {
            $message = new \IGtAppMessage();
            $appid = $this->appid;
            if(!is_array($this->appid)) {
                $appid = [$this->appid];
            }
            $message->set_appIdList($appid);
        }

        $message->set_isOffline(true);//是否离线
        $message->set_offlineExpireTime(3600*12*1000);//离线时间
        $message->set_data($template);//设置推送消息类型
//                	$message->set_PushNetWorkType(0);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        return $message;
    }

    /**
     * 发送
     * @param $message
     * @return array|void
     */
    public function send($message)
    {
        $igt = $this->igt;
        //个推信息体

        if(!empty($this->cid)) {
            //接收方
            if(!is_array($this->cid)) {
                $target = new \IGtTarget();
                $target->set_appId($this->appid);
                $target->set_clientId($this->cid);
                //    $target->set_alias(Alias);

                try {
                    $rep = $igt->pushMessageToSingle($message, $target);
                } catch(\RequestException $e) {
                    $requstId = $e->getRequestId();
                    $rep = $igt->pushMessageToSingle($message, $target, $requstId);
                }
                return $rep;
            } else {
                $target_list = [];
                foreach ($this->cid as $id) {
                    $target = new \IGtTarget();
                    $target->set_appId($this->appid);
                    $target->set_clientId($id);
                    $target_list[] = $target;
                }
                try {
                    $contentid = $igt->getContentId($message);
                    $rep = $igt->pushMessageToList($contentid, $target_list);
                    return $rep;
                } catch(\RequestException $e) {
                    return $e->getRequestId();
                }
            }
        }

        try {
            $rep = $igt->pushMessageToApp($message);
        } catch(\RequestException $e) {
            $requstId = $e->getRequestId();
            $rep = $igt->pushMessageToApp($message, $requstId);
        }
        return $rep;
    }

}