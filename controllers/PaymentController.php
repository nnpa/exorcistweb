<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use app\models\User;
use app\models\Character;
use app\models\Payment;

class PaymentController extends Controller
{
    // ЗАМЕНИ на реальные данные из личного кабинета ЮKassa
    private const SHOP_ID = '1455733';
    private const SECRET_KEY = 'test_ef29bfO1jHZflroLUZ1dTqVjPCE4PeWQPy0HxkEq51w';

    public $enableCsrfValidation = false;

    private function getProducts()
    {
        return require Yii::getAlias('@app/config/shop.php');
    }

    private function findUserByToken($token)
    {
        if (!$token) {
            return null;
        }
        return User::findOne(['auth_token' => $token]);
    }

    private function yooKassaRequest($method, $path, $body = null, $idempotenceKey = null)
    {
        $ch = curl_init('https://api.yookassa.ru/v3' . $path);

        $headers = ['Content-Type: application/json'];

        if ($idempotenceKey) {
            $headers[] = 'Idempotence-Key: ' . $idempotenceKey;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERPWD, self::SHOP_ID . ':' . self::SECRET_KEY);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \Exception('YooKassa request failed: ' . $error);
        }

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new \Exception('YooKassa API error (' . $httpCode . '): ' . $response);
        }

        return $data;
    }

    // GET /payment/shop?token=...
    public function actionShop($token = null)
    {
        $user = $this->findUserByToken($token);

        if (!$user) {
            throw new NotFoundHttpException('Пользователь не найден. Войдите в игру заново.');
        }

        return $this->render('shop', [
            'products' => $this->getProducts(),
            'token' => $token,
        ]);
    }

    // POST /payment/buy
    public function actionBuy()
    {
        $token = Yii::$app->request->post('token');
        $productId = Yii::$app->request->post('product');

        $user = $this->findUserByToken($token);
        if (!$user) {
            throw new NotFoundHttpException('Пользователь не найден.');
        }

        $products = $this->getProducts();
        if (!isset($products[$productId])) {
            throw new NotFoundHttpException('Товар не найден.');
        }

        $product = $products[$productId];

        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->product_id = $productId;
        $payment->gold_amount = $product['gold'];
        $payment->price_rub = $product['price'];
        $payment->status = 'pending';
        $payment->save(false);

        $idempotenceKey = 'payment_' . $payment->id . '_' . uniqid();

        try {
            $result = $this->yooKassaRequest('POST', '/payments', [
                'amount' => [
                    'value' => number_format($product['price'], 2, '.', ''),
                    'currency' => 'RUB',
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => Yii::$app->urlManager->createAbsoluteUrl(['payment/success']),
                ],
                'capture' => true,
                'description' => $product['name'],
                'metadata' => ['payment_id' => $payment->id],
            ], $idempotenceKey);

        } catch (\Exception $e) {
            Yii::error('YooKassa create payment failed: ' . $e->getMessage(), 'payment');
            $payment->status = 'failed';
            $payment->save(false);
            throw new ServerErrorHttpException('Не удалось создать платёж.');
        }

        $payment->yookassa_payment_id = $result['id'];
        $payment->save(false, ['yookassa_payment_id']);

        return $this->redirect($result['confirmation']['confirmation_url']);
    }

    // GET — страница после возврата с оплаты (информационная, золото тут НЕ начисляется)
    public function actionSuccess()
    {
        return $this->render('success');
    }

    // POST — webhook от ЮKassa
    public function actionYookassaNotify()
    {
        $raw = Yii::$app->request->getRawBody();
        $data = json_decode($raw, true);

if (!$data || !isset($data['object']['id'])) {
        // Может быть проверочный пинг от ЮKassa при сохранении URL —
        // отвечаем 200, чтобы форма в кабинете не ругалась на "опечатку".
        return 'OK';
    }

        $yookassaPaymentId = $data['object']['id'];

        // Не доверяем телу webhook напрямую — перепроверяем
        // статус платежа прямым запросом к API ЮKassa.
        try {
            $actual = $this->yooKassaRequest('GET', '/payments/' . $yookassaPaymentId);
        } catch (\Exception $e) {
            Yii::error('YooKassa verify failed: ' . $e->getMessage(), 'payment');
            return $this->response->statusCode = 502;
        }

        if (($actual['status'] ?? '') !== 'succeeded' || empty($actual['paid'])) {
            return 'OK';
        }

        $payment = Payment::findOne(['yookassa_payment_id' => $yookassaPaymentId]);

        if (!$payment) {
            Yii::error('YooKassa: payment not found for id ' . $yookassaPaymentId, 'payment');
            return $this->response->statusCode = 404;
        }

        if ($payment->status === 'completed') {
            return 'OK'; // идемпотентность
        }

        $paidAmount = (float) ($actual['amount']['value'] ?? 0);

        if ($paidAmount < (float) $payment->price_rub) {
            Yii::error('YooKassa: amount mismatch for payment ' . $payment->id, 'payment');
            $payment->status = 'failed';
            $payment->save(false);
            return $this->response->statusCode = 400;
        }

        $character = Character::find()->where(['user_id' => $payment->user_id])->one();

        if (!$character) {
            Yii::error('YooKassa: character not found for user ' . $payment->user_id, 'payment');
            return $this->response->statusCode = 404;
        }

        $character->gold += $payment->gold_amount;
        $character->save(false, ['gold']);

        $payment->status = 'completed';
        $payment->save(false);

        return 'OK';
    }
}