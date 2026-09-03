<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Пополнение баланса';
?>
<div class="payment-shop">
    <h1>Пополнение баланса</h1>

    <?php foreach ($products as $id => $product): ?>
        <div class="shop-item" style="margin-bottom: 20px;">
            <h3><?= Html::encode($product['name']) ?></h3>
            <p><?= (int) $product['price'] ?> ₽</p>

            <form method="post" action="<?= Url::to(['payment/buy']) ?>">
                <?= Html::hiddenInput('token', $token) ?>
                <?= Html::hiddenInput('product', $id) ?>
                <?= Html::submitButton('Купить', ['class' => 'btn btn-primary']) ?>
            </form>
        </div>
    <?php endforeach; ?>
</div>