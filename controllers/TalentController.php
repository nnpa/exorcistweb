<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use app\models\Character;
use app\models\CharacterTalent;

class TalentController extends Controller
{
    // Конфигурация всех талантов (дублируем с клиента)
    private $talentConfig = [];

    public function init()
    {
        parent::init();
        // Здесь нужно определить те же таланты, что и на клиенте
        // Для краткости я приведу только пример, полный список нужно скопировать из клиентского TalentManager.initializeTalents()
        $this->talentConfig = $this->getTalentConfig();
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];
        return $behaviors;
    }

    private function getTalentConfig()
{
    return [
        // ==================== DEFENSE ====================
        'def_1' => ['maxLevel' => 5, 'cost' => 1, 'prerequisites' => []],
        'def_2' => ['maxLevel' => 3, 'cost' => 1, 'prerequisites' => []],
        'def_3' => ['maxLevel' => 3, 'cost' => 1, 'prerequisites' => ['def_1']],
        'def_4' => ['maxLevel' => 3, 'cost' => 1, 'prerequisites' => ['def_2']],
        'def_5' => ['maxLevel' => 2, 'cost' => 1, 'prerequisites' => ['def_3']],
        'def_6' => ['maxLevel' => 3, 'cost' => 1, 'prerequisites' => ['def_4']],
        'def_7' => ['maxLevel' => 2, 'cost' => 1, 'prerequisites' => []],
        'def_8' => ['maxLevel' => 1, 'cost' => 1, 'prerequisites' => []],
        'def_9' => ['maxLevel' => 1, 'cost' => 1, 'prerequisites' => ['def_7']],
        'def_10' => ['maxLevel' => 2, 'cost' => 1, 'prerequisites' => ['def_8']],

        // ==================== LIGHT ====================
        'light_1' => ['maxLevel' => 5, 'cost' => 1, 'prerequisites' => []],
        'light_2' => ['maxLevel' => 3, 'cost' => 1, 'prerequisites' => []],
        'light_3' => ['maxLevel' => 3, 'cost' => 1, 'prerequisites' => ['light_1']],
        'light_4' => ['maxLevel' => 2, 'cost' => 1, 'prerequisites' => ['light_2']],
        'light_5' => ['maxLevel' => 3, 'cost' => 1, 'prerequisites' => ['light_3']],
        'light_6' => ['maxLevel' => 2, 'cost' => 1, 'prerequisites' => ['light_4']],
        'light_7' => ['maxLevel' => 3, 'cost' => 1, 'prerequisites' => []],
        'light_8' => ['maxLevel' => 2, 'cost' => 1, 'prerequisites' => []],
        'light_9' => ['maxLevel' => 2, 'cost' => 1, 'prerequisites' => ['light_7']],
        'light_10' => ['maxLevel' => 1, 'cost' => 1, 'prerequisites' => ['light_8']],

        // ==================== ATTACK ====================
        'attack_1' => ['maxLevel' => 5, 'cost' => 1, 'prerequisites' => []],
        'attack_2' => ['maxLevel' => 3, 'cost' => 1, 'prerequisites' => []],
        'attack_3' => ['maxLevel' => 3, 'cost' => 1, 'prerequisites' => ['attack_1']],
        'attack_4' => ['maxLevel' => 2, 'cost' => 1, 'prerequisites' => ['attack_2']],
        'attack_5' => ['maxLevel' => 4, 'cost' => 1, 'prerequisites' => ['attack_3']],
        'attack_6' => ['maxLevel' => 3, 'cost' => 1, 'prerequisites' => ['attack_4']],
        'attack_7' => ['maxLevel' => 5, 'cost' => 1, 'prerequisites' => []],
        'attack_8' => ['maxLevel' => 3, 'cost' => 1, 'prerequisites' => []],
        'attack_9' => ['maxLevel' => 3, 'cost' => 1, 'prerequisites' => ['attack_7']],
        'attack_10' => ['maxLevel' => 2, 'cost' => 1, 'prerequisites' => ['attack_8']],
    ];
}

    private function getCharacter()
    {
        $user = Yii::$app->user->identity;
        $char = Character::find()->where(['user_id' => $user->id])->one();
        if (!$char) throw new BadRequestHttpException('Character not found');
        return $char;
    }

    /**
     * GET /talents – получить состояние талантов персонажа
     */
public function actionIndex()
{
    $char = $this->getCharacter();
    $talents = $char->getTalents()->all();
    $result = [];
    foreach ($talents as $t) {
        $result[$t->talent_id] = $t->level;
    }

    // ===== ВАЖНО: вычитаем потраченные очки! =====
    $availablePoints = $this->calculatePoints($char->level) - $this->countSpentPoints($char->id);

    return [
        'success' => true,
        'talents' => $result,
        'availablePoints' => $availablePoints,
    ];
}

    /**
     * POST /talents/learn – улучшить талант
     */
    public function actionLearn()
    {
        $char = $this->getCharacter();
        $talentId = Yii::$app->request->post('talentId');
        if (!$talentId) {
            throw new BadRequestHttpException('Missing talentId');
        }

        // Проверяем существование таланта в конфиге
        if (!isset($this->talentConfig[$talentId])) {
            throw new BadRequestHttpException('Unknown talent');
        }
        $config = $this->talentConfig[$talentId];

        // Получаем текущий уровень
        $talentRecord = CharacterTalent::findOne([
            'character_id' => $char->id,
            'talent_id' => $talentId
        ]);
        if (!$talentRecord) {
            $talentRecord = new CharacterTalent();
            $talentRecord->character_id = $char->id;
            $talentRecord->talent_id = $talentId;
            $talentRecord->level = 0;
        }
        $currentLevel = $talentRecord->level;

        // Проверка максимального уровня
        if ($currentLevel >= $config['maxLevel']) {
            throw new BadRequestHttpException('Talent already at max level');
        }

        // Проверка доступных очков
        $availablePoints = $this->calculatePoints($char->level) - $this->countSpentPoints($char->id);
        if ($availablePoints < $config['cost']) {
            throw new BadRequestHttpException('Not enough talent points');
        }

        // Проверка пререквизитов
        foreach ($config['prerequisites'] as $prereqId) {
            $prereqRecord = CharacterTalent::findOne([
                'character_id' => $char->id,
                'talent_id' => $prereqId
            ]);
            if (!$prereqRecord || $prereqRecord->level == 0) {
                throw new BadRequestHttpException('Prerequisite ' . $prereqId . ' not learned');
            }
        }

        // Увеличиваем уровень
        $talentRecord->level = $currentLevel + 1;
        if (!$talentRecord->save()) {
            throw new BadRequestHttpException('Failed to save talent');
        }

        // Возвращаем обновлённое состояние
        return $this->actionIndex();
    }

    /**
     * POST /talents/reset – сброс всех талантов
     */
    public function actionReset()
    {
        $char = $this->getCharacter();
        // Удаляем все записи
        CharacterTalent::deleteAll(['character_id' => $char->id]);

        return $this->actionIndex();
    }

    // Вспомогательные методы
    private function calculatePoints($level)
    {
        // По вашей логике: 1 очко за каждый 5-й уровень (level % 5 == 0)
        return intdiv($level, 2);
    }

    private function countSpentPoints($characterId)
    {
        $records = CharacterTalent::find()
            ->where(['character_id' => $characterId])
            ->all();
        $total = 0;
        foreach ($records as $rec) {
            if (isset($this->talentConfig[$rec->talent_id])) {
                $total += $this->talentConfig[$rec->talent_id]['cost'] * $rec->level;
            }
        }
        return $total;
    }
}