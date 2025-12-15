<?php
namespace service;
loadFile('class.phpmailer', LIB_PATH . 'Mail' . DS);

/**
 * mail 类的 封装
 * 
 * @author
 */
class Mail extends PHPMailer 
{
    var $From        = "ubunto@sina.cn";
    var $FromName    = "伟哥";              	// 发送者名称
    var $Host        = "smtp.sina.com.cn";
    var $Mailer      = "smtp";              // Alternative to IsSMTP()
    var $SMTPAuth    = true;                // turn on SMTP authentication
    var $CharSet     = "UTF-8";
    var $Username    = "ubunto@sina.cn";    // SMTP username
    var $Password    = "295012469";         // SMTP password
    var $WordWrap    = 75;
    var $ContentType = "text/html";
    
    /**
     * 添加附件
     * 
     * @see Lib/Mail/service.PHPMailer::AddAttachment()
     */
    public function AddAttachment($path, $name = "", $disposition = "attachment", $encoding = "base64", $type = "application/octet-stream")
    {
        if (!@is_file($path)) {
            $this->SetError($this->Lang("file_access") . $path);
            return false;
        }

        $filename = basename($path);
        if ($name == "") {
            $name = $filename;
        }
        $cur = count($this->attachment);
        $this->attachment[$cur]['0'] = $path;
        $this->attachment[$cur]['1'] = $filename;
        $this->attachment[$cur]['2'] = $name;
        $this->attachment[$cur]['3'] = $encoding;
        $this->attachment[$cur]['4'] = $type;
        $this->attachment[$cur]['5'] = false; // isStringAttachment
        $this->attachment[$cur]['6'] = ($disposition=='attachment') ? 'attachment' : 'inline' ;
        $this->attachment[$cur]['7'] = $cur;

        return true;
    }
    
    /**
     * 添加收信人
     * 
     * @param   array   $addressees  收信人列表
     *
     * @return bool
     */
    public function addressee($addressees)
    {
    	if (is_iteratable($addressees)) foreach ($addressees as $addressee) {
    	    $this->AddAddress("{$addressee}");
    	}
        return true;
    }

}