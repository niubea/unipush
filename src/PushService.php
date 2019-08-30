<?php
namespace czt\unipush;

use think\Exception;

require_once(dirname(__FILE__).'/'.'push.php');

class PushService
{
    private $cid = '';          //clientid,单独发送时必填
    private $title = '';        //通知标题
    private $content = '';      //通知内容
    private $payload = '';      //附带数据
    private $package = '';      //包名
    private $url = '';          //链接，如果有则是跳转通知
    private $popupTitle = '';   //下载通知点击后弹框标题
    private $popupContent = ''; //下载通知点击通知后弹框内容
    private $downloadTitle = '';//下载进度框标题
    private $downloadUrl = '';  //下载链接

    public function __construct()
    {
        $this->package = PACKAGENAME;
    }

    public function setCid($cid)
    {
        $this->cid = $cid;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setPopupTitle($popupTitle)
    {
        $this->popupTitle = $popupTitle;
    }

    public function setPopupContent($popupContent)
    {
        $this->popupContent = $popupContent;
    }

    public function setDownloadTitle($downloadTitle)
    {
        $this->downloadTitle = $downloadTitle;
    }

    public function setDownloadUrl($downloadUrl)
    {
        $this->downloadUrl = $downloadUrl;
    }

    /**
     * 普通透传消息
     * @return array|void
     */
    public function pushMessage()
    {
        $intent = "intent:#Intent;action=android.intent.action.oppopush;launchFlags=0x14000000;component={$this->package}/io.dcloud.PandoraEntry;S.UP-OL-SU=true;S.title={$this->title};S.content={$this->content};S.payload={$this->payload};end";
        $template = createPushMessage($this->payload, $intent, $this->title, $this->content);
        return $this->send($template);
    }

    /**
     * 跳转链接消息
     * @return array|void
     */
    public function linkMessage()
    {
        if(empty($this->title)) {
            return 'title参数缺失';
        }
        if(empty($this->content)) {
            return 'content参数缺失';
        }
        if(empty($this->url)) {
            return 'url参数缺失';
        }

        $template = createLinkMessage($this->title, $this->content, $this->url);
        return $this->send($template);
    }

    /**
     * 下载通知
     * @return array|void
     */
    public function downMessage()
    {
        if(empty($this->title)) {
            return 'title参数缺失';
        }
        if(empty($this->content)) {
            return 'content参数缺失';
        }
        if(empty($this->popupTitle)) {
            return 'popupTitle参数缺失';
        }
        if(empty($this->popupContent)) {
            return 'popupContent参数缺失';
        }
        if(empty($this->downloadTitle)) {
            return 'downloadTitle参数缺失';
        }
        if(empty($this->downloadUrl)) {
            return 'downloadUrl参数缺失';
        }

        $template = createDownMessage($this->title, $this->content, $this->popupTitle, $this->popupContent, $this->downloadTitle, $this->downloadUrl);
        return $this->send($template);
    }

    /**
     *
     * @param $template
     * @return array|void
     */
    public function send($template)
    {
        try {
            if(!empty($this->cid)) {
                return pushMessageToSingle($template, $this->cid);
            }
            return pushMessageToApp($template);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /*public function pushMessageToSingle()
    {
        if(empty($this->cid)){
            return false;
        }
        if(empty($this->title)){
            return false;
        }
        if(empty($this->content)){
            return false;
        }

        if(empty($this->url)) {
            $intent = "intent:#Intent;action=android.intent.action.oppopush;launchFlags=0x14000000;component={$this->package}/io.dcloud.PandoraEntry;S.UP-OL-SU=true;S.title={$this->title};S.content={$this->content};S.payload={$this->payload};end";
            $template = createPushMessage($this->payload, $intent, $this->title, $this->content);
        } elseif(!empty($this->url)) {
            $template = createLinkMessage($this->title, $this->content, $this->url);
        } elseif(!empty($this->downloadUrl)) {
            $template = createDownMessage($this->title, $this->content, $this->popupTitle, $this->popupContent, $this->downloadTitle, $this->downloadUrl);
        }
        return pushMessageToSingle($template, $this->cid);
    }*/

    /*public function pushMessageToApp()
    {
        if(empty($this->title)){
            return false;
        }

        if(empty($this->content)){
            return false;
        }

        if(empty($this->url)) {
            $intent = "intent:#Intent;action=android.intent.action.oppopush;launchFlags=0x14000000;component={$this->package}/io.dcloud.PandoraEntry;S.UP-OL-SU=true;S.title={$this->title};S.content={$this->content};S.payload={$this->payload};end";
            $template = createPushMessage($this->payload, $intent, $this->title, $this->content);
        } elseif(!empty($this->url)) {
            $template = createLinkMessage($this->title, $this->content, $this->url);
        } elseif(!empty($this->downloadUrl)) {
            $template = createDownMessage($this->title, $this->content, $this->popupTitle, $this->popupContent, $this->downloadTitle, $this->downloadUrl);
        }
        return pushMessageToApp($template);
    }*/


}