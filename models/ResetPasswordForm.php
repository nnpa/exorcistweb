<?php
namespace app\models;

use Yii;
use yii\base\InvalidArgumentException;
use yii\base\Model;

/**
 * Форма ввода нового пароля — используется на странице,
 * куда попадает пользователь по ссылке из письма.
 */
class ResetPasswordForm extends Model
{
    public $password;

    private $_user;

    private const TOKEN_LIFETIME_SECONDS = 3600;

    /**
     * @throws InvalidArgumentException если токен некорректен/истёк
     */
    public function __construct($token, $config = [])
    {
        if (empty($token) || !is_string($token)) {
            throw new InvalidArgumentException('Password reset token cannot be blank.');
        }

        $this->_user = User::findOne(['password_reset_token' => $token]);

        if (!$this->_user) {
            throw new InvalidArgumentException('Wrong password reset token.');
        }

        $parts = explode('_', $token);
        $timestamp = (int) end($parts);

        if ($timestamp + self::TOKEN_LIFETIME_SECONDS < time()) {
            throw new InvalidArgumentException('Password reset token has expired.');
        }

        parent::__construct($config);
    }

    public function rules()
    {
        return [
            ['password', 'required'],
            ['password', 'string', 'min' => 6],
        ];
    }

    public function attributeLabels()
    {
        return [
            'password' => 'Новый пароль',
        ];
    }

    /**
     * Устанавливает новый пароль и сжигает токен.
     */
    public function resetPassword()
    {
        $user = $this->_user;

        $user->password_hash =
            Yii::$app->security->generatePasswordHash($this->password);

        $user->password_reset_token = null;

        return $user->save(false, ['password_hash', 'password_reset_token']);
    }
}