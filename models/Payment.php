<?php
namespace app\models;

use yii\db\ActiveRecord;

class Payment extends ActiveRecord
{
    public static function tableName()
    {
        return 'payments';
    }

    public function rules()
    {
        return [
            [['user_id', 'gold_amount'], 'integer'],
            [['product_id', 'yookassa_payment_id'], 'string', 'max' => 64],
            [['price_rub'], 'number'],
            [['status'], 'string'],
            [['created_at'], 'safe'],
        ];
    }
}