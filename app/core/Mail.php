<?php
namespace Core;


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require CORE_DIR . 'Mail/Exception.php';
require CORE_DIR . 'Mail/PHPMailer.php';
require CORE_DIR . 'Mail/SMTP.php';

class Mail {
    protected $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        //服务器配置
        $this->mail->CharSet ="UTF-8";                     //设定邮件编码
        $this->mail->SMTPDebug = 0;                        // 调试模式输出
        $this->mail->isSMTP();                             // 使用SMTP
        $this->mail->Host = 'smtp.ym.163.com';         // SMTP服务器
        $this->mail->SMTPAuth = true;                      // 允许 SMTP 认证
        $this->mail->Username = 'script@stepcounter.cn';      // SMTP 用户名  即邮箱的用户名
        $this->mail->Password = 'Jingyun8629';             // SMTP 密码  部分邮箱是授权码(例如163邮箱)
        $this->mail->SMTPSecure = 'ssl';                    // 允许 TLS 或者ssl协议
        $this->mail->Port = 994;                            // 服务器端口 25 或者465 具体要看邮箱服务器支持
        //994

        $this->mail->setFrom('script@stepcounter.cn', '景赟脚本');  //发件人
        //$this->mail->addAddress('ellen@example.com');  // 可添加多个收件人
        $this->mail->addReplyTo('script@stepcounter.cn'); //回复的时候回复给哪个邮箱 建议和发件人一致
        //$this->mail->addCC('cc@example.com');                    //抄送
        //$this->mail->addBCC('bcc@example.com');                    //密送
        //发送附件
        // $this->mail->addAttachment('../xy.zip');         // 添加附件
        // $this->mail->addAttachment('../thumb-1.jpg', 'new.jpg');    // 发送附件并且重命名
        //Content
    }

    public function send($email, $subject, $body, $isHtml = TRUE) {
        if (is_array($email)) {
            foreach ($email as $e) {
                $this->mail->addAddress($e);  // 收件人
            }
        } else {
            $this->mail->addAddress($email);  // 收件人
        }

        $this->mail->isHTML($isHtml);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
        $this->mail->Subject = $subject;
        $this->mail->Body    = $body;
//        $this->mail->AltBody = '如果邮件客户端不支持HTML则显示此内容';
        $this->mail->send();
    }
}