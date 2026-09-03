<?php
namespace app\models;

use Yii;
use yii\base\Model;

/**
 * Форма запроса на сброс пароля — используется на странице
 * "Забыли пароль?" (ввод email).
 */
class PasswordResetRequestForm extends Model
{
    public $email;

    private const TOKEN_LIFETIME_SECONDS = 3600; // 1 час на переход по ссылке

    public function rules()
    {
        return [
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'email' => 'Email',
        ];
    }

    /**
     * Отправляет письмо со ссылкой для сброса пароля,
     * если пользователь с таким email существует.
     *
     * Намеренно возвращает true даже если пользователя нет —
     * чтобы не палить существование email в базе перебором.
     */
    public function sendEmail()
{
    $user = User::findOne(['email' => $this->email]);

    if (!$user) {
        return true;
    }

    $user->password_reset_token =
        Yii::$app->security->generateRandomString() . '_' . time();

    if (!$user->save(false, ['password_reset_token'])) {
        return false;
    }

    // Подключаем PHPMailer напрямую, без Composer autoload
    $vendorPath = Yii::getAlias('@vendor');

    require_once $vendorPath . '/PHPMailer/src/Exception.php';
    require_once $vendorPath . '/PHPMailer/src/PHPMailer.php';
    require_once $vendorPath . '/PHPMailer/src/SMTP.php';

    $link = Yii::$app->urlManager->createAbsoluteUrl(
        ['site/reset-password', 'token' => $user->password_reset_token]
    );

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ibaleksandrov1988@gmail.com';
        $mail->Password   = 'kafs rloj qvhb dnjv';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('ibaleksandrov1988@gmail.com', Yii::$app->name);
        $mail->addAddress($this->email, $user->login);

        $mail->isHTML(true);
        $mail->Subject = 'Восстановление пароля — ' . Yii::$app->name;
        $mail->Body    =
            "<p>Здравствуйте, " . htmlspecialchars($user->login) . "!</p>"
            . "<p>Чтобы сбросить пароль, перейдите по ссылке:</p>"
            . "<p><a href=\"{$link}\">{$link}</a></p>"
            . "<p>Ссылка действительна 1 час.</p>";

        $mail->send();
        return true;

    } catch (\PHPMailer\PHPMailer\Exception $e) {
        Yii::error('PHPMailer error: ' . $mail->ErrorInfo, 'mail');
        return false;
    }
}
}