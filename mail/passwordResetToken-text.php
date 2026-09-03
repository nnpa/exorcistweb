<?php
/* @var $user \app\models\User */

$link = Yii::$app->urlManager->createAbsoluteUrl(
    ['site/reset-password', 'token' => $user->password_reset_token]
);
?>
Здравствуйте, <?= $user->login ?>!

Чтобы сбросить пароль, перейдите по ссылке:
<?= $link ?>

Ссылка действительна 1 час. Если вы не запрашивали сброс — проигнорируйте это письмо.