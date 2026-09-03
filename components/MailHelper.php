<?php
namespace app\components;
use Yii;

require_once Yii::getAlias('@vendor') . '/phpmailer/src/Exception.php';
require_once Yii::getAlias('@vendor') . '/phpmailer/src/PHPMailer.php';
require_once Yii::getAlias('@vendor') . '/phpmailer/src/SMTP.php';

// Импортируем классы после ручной загрузки
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailHelper
{
    public static function sendPasswordReset($toEmail, $username, $resetLink)
    {
        // Создаем экземпляр PHPMailer
        $mail = new PHPMailer(true);
        
        try {
            // Настройки сервера
            $mail->SMTPDebug = SMTP::DEBUG_OFF;                     // Отключаем отладку
            $mail->isSMTP();                                        // Отправляем через SMTP
            $mail->Host       = 'smtp.gmail.com';                   // SMTP сервер Gmail
            $mail->SMTPAuth   = true;                               // Включить аутентификацию
            $mail->Username   = Yii::$app->params['supportEmail'] ?? 'your-email@gmail.com';
            $mail->Password   = Yii::$app->params['mailerPassword'] ?? 'your-app-password';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;        // SSL шифрование
            $mail->Port       = 465;                                // Порт SSL
            
            // Отправитель и получатель
            $mail->setFrom($mail->Username, Yii::$app->name . ' Robot');
            $mail->addAddress($toEmail, $username);
            
            // Содержимое письма
            $mail->isHTML(true);
            $mail->Subject = 'Сброс пароля для ' . Yii::$app->name;
            $mail->Body    = self::getHtmlBody($username, $resetLink);
            $mail->AltBody = self::getTextBody($username, $resetLink);
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            Yii::error('Mail error: ' . $mail->ErrorInfo, 'mail');
            return false;
        }
    }
    
    private static function getHtmlBody($username, $resetLink)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Сброс пароля</title>
        </head>
        <body>
            <h2>Сброс пароля</h2>
            <p>Здравствуйте, <strong>{$username}</strong>!</p>
            <p>Для сброса пароля перейдите по ссылке:</p>
            <p><a href='{$resetLink}'>{$resetLink}</a></p>
            <p>Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо.</p>
            <hr>
            <small>Это письмо отправлено автоматически. Пожалуйста, не отвечайте на него.</small>
        </body>
        </html>
        ";
    }
    
    private static function getTextBody($username, $resetLink)
    {
        return "Здравствуйте, {$username}!\n\n"
             . "Для сброса пароля перейдите по ссылке:\n"
             . "{$resetLink}\n\n"
             . "Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо.";
    }
}