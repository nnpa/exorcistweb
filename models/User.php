<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName() { return 'users'; }

    public function rules()
    {
        return [
            [['email', 'login', 'password_hash'], 'required'],
            ['email', 'email'],
            ['login', 'string', 'max' => 64],
            ['email', 'unique'],
            ['login', 'unique'],
        ];
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_token' => $token]);
    }

    public function getId() { return $this->id; }
    public function getAuthKey() { return $this->auth_token; }
    public function validateAuthKey($authKey) { return $this->auth_token === $authKey; }

    public function getCharacter()
    {
        return $this->hasOne(Character::class, ['user_id' => 'id']);
    }
}