<?php
// ============================================================
//  2. КОНТРОЛЛЕРЫ (controllers/)
// ============================================================

// controllers/AuthController.php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use app\models\User;
use app\models\Character;

class AuthController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // Отключаем CSRF для REST
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'register' => ['post'],
                'login' => ['post'],
                'check' => ['get', 'post'],
            ],
        ];
        return $behaviors;
    }

    // POST /auth/register
    public function actionRegister()
    {
        $request = Yii::$app->request;
        $email = $request->post('email');
        $login = $request->post('login');
        $password = $request->post('password');

        if (!$email || !$login || !$password) {
            throw new BadRequestHttpException('Missing required fields');
        }

        if (User::find()->where(['email' => $email])->exists()) {
            throw new BadRequestHttpException('Email already exists');
        }
        if (User::find()->where(['login' => $login])->exists()) {
            throw new BadRequestHttpException('Login already exists');
        }

        $user = new User();
        $user->email = $email;
        $user->login = $login;
        $user->password_hash = Yii::$app->security->generatePasswordHash($password);
        if (!$user->save()) {
            return ['error' => $user->errors];
        }

        // Создаём персонажа
        $char = new Character();
        $char->user_id = $user->id;
        $char->name = $login;
        $char->save();

        return ['success' => true, 'userId' => $user->id];
    }

    // POST /auth/login
    public function actionLogin()
    {
        $request = Yii::$app->request;
    $login = $request->post('login');
    $password = $request->post('password');
    Yii::info("Login attempt: $login, password: $password", 'auth');
    
        $request = Yii::$app->request;
        $login = $request->post('login');
        $password = $request->post('password');

        $user = User::findOne(['login' => $login]);
        if (!$user || !Yii::$app->security->validatePassword($password, $user->password_hash)) {
            throw new BadRequestHttpException('Invalid login or password');
        }

        // Генерация токена (JWT)
        $token = $this->generateJwt($user);
        $user->auth_token = $token;
        $user->save();

        $character = Character::find()->where(['user_id' => $user->id])->one();
        return [
            'success' => true,
            'token' => $token,
            'character' => $character ? $character->toApiResponse() : null,
        ];
    }

    // GET/POST /auth/check
    public function actionCheck()
    {
        // Токен передаётся через Bearer header – проверяем в фильтре
        $user = Yii::$app->user->identity;
        if (!$user) {
            throw new BadRequestHttpException('Invalid token');
        }
        $character = Character::find()->where(['user_id' => $user->id])->one();
        return [
            'success' => true,
            'character' => $character ? $character->toApiResponse() : null,
        ];
    }

    private function generateJwt($user)
    {
            return md5($user->id . 'exorcist_salt' . time());

    }
}