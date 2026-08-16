<?php
// controllers/CharacterController.php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use app\models\Character;

class CharacterController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'index' => ['get'],
                'save' => ['post'],
            ],
        ];
        return $behaviors;
    }

    // GET /character – получить данные персонажа
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $character = Character::find()->where(['user_id' => $user->id])->one();
        if (!$character) {
            throw new BadRequestHttpException('Character not found');
        }
        return $character->toApiResponse();
    }

    // POST /character/save – обновить параметры
    public function actionSave()
{
    $user = Yii::$app->user->identity;
    $character = Character::find()->where(['user_id' => $user->id])->one();
    if (!$character) {
        throw new BadRequestHttpException('Character not found');
    }

    $request = Yii::$app->request;
    $fields = ['health', 'mana', 'max_health', 'max_mana', 'gold', 'level', 'experience', 'current_dungeon', 'difficulty'];
    foreach ($fields as $field) {
        if ($request->post($field) !== null) {
            $character->$field = $request->post($field);
        }
    }
    
    // Позиция в данже
    $posX = $request->post('last_dungeon_position_x');
    $posY = $request->post('last_dungeon_position_y');
    $posZ = $request->post('last_dungeon_position_z');
    if ($posX !== null) $character->last_dungeon_position_x = $posX;
    if ($posY !== null) $character->last_dungeon_position_y = $posY;
    if ($posZ !== null) $character->last_dungeon_position_z = $posZ;

    // ===== ИСПРАВЛЕНИЕ: используем $character, а не $char =====
    if ($request->post('health_potions') !== null) {
        $character->health_potions = (int) $request->post('health_potions');
    }
    if ($request->post('mana_potions') !== null) {
        $character->mana_potions = (int) $request->post('mana_potions');
    }

    if (!$character->save()) {
        return ['error' => $character->errors];
    }

    return ['success' => true, 'character' => $character->toApiResponse()];
}
}