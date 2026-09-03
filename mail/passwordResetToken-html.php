<?php
use yii\helpers\Html;

/* @var $user \app\models\User */
?>
<div class="password-reset">
    <p>Здравствуйте, <?= Html::encode($user->login) ?>!</p>

    <p>Чтобы сбросить пароль, перейдите по ссылке ниже:</p>

    <p>
        <?= Html::a(
            'Сбросить пароль',
            Yii::$app->urlManager->createAbsoluteUrl(
                ['site/reset-password', 'token' => $user->password_reset_token]
            )
        ) ?>
    </p>

    <p>Ссылка действительна 1 час. Если вы не запрашивали сброс — просто проигнорируйте это письмо.</p>
</div>