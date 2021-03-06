<?php
namespace user\controllers;

use app\components\Alert;
use app\components\AuthControl;
use Exception;
use user\controllers\actions\VcsBindingsAction;
use user\models\UserForm;
use user\models\UserSearch;
use user\UserModule;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * Controller to manage users:
 *
 * - create new users and send notification;
 * - update users and send notification;
 * - delete or lock exists users.
 */
class UserManagerController extends Controller
{
    /**
     * @var UserModule
     */
    protected $userModule;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->userModule = Yii::$app->getModule('user');
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'vcs-bindings' => [
                'class' => VcsBindingsAction::className(),
                'userId' => Yii::$app->request->get('id'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'accessControl' => [
                'class' => AuthControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['createUser', 'updateUser', 'deleteUser'],
                        'actions' => ['index'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['createUser'],
                        'actions' => ['create'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['updateUser'],
                        'actions' => ['update', 'vcs-bindings'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['deleteUser'],
                        'actions' => ['lock', 'activate'],
                    ]
                ],
            ]
        ]);
    }

    /**
     * Users list
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new UserSearch();
        $dataProvider = $model->search(Yii::$app->request->get());

        return $this->render('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Create new user
     *
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new UserForm();
        $model->setScenario('create');

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model->load(Yii::$app->request->post());
            return ActiveForm::validate($model);
        }

        /* @var $systemAlert Alert */
        $systemAlert = Yii::$app->systemAlert;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            try {
                if ($this->userModule->createUser($model)) {
                    $systemAlert->setMessage(Alert::SUCCESS, Yii::t('user', 'User successfully created'));
                    return $this->redirect(['index']);
                }
                else {
                    $systemAlert->setMessage(Alert::DANGER, Yii::t('user', 'Creation user error'));
                }
            }
            catch (Exception $ex) {
                $systemAlert->setMessage(Alert::DANGER, Yii::t('app', 'System error: {message}', [
                    'message' => $ex->getMessage(),
                ]));
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Update exits user model.
     *
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $model->setScenario('update');

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model->load(Yii::$app->request->post());
            return ActiveForm::validate($model);
        }

        /* @var $systemAlert Alert */
        $systemAlert = Yii::$app->systemAlert;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            try {
                if ($this->userModule->updateUser($model)) {
                    $systemAlert->setMessage(Alert::SUCCESS, Yii::t('user', 'User successfully updated'));
                    return $this->redirect(['index']);
                }
                else {
                    $systemAlert->setMessage(Alert::DANGER, Yii::t('user', 'User update error'));
                }
            }
            catch (Exception $ex) {
                $systemAlert->setMessage(Alert::DANGER, Yii::t('app', 'System error: {message}', [
                    'message' => $ex->getMessage(),
                ]));
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Users activation
     *
     * @param integer $id User's id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionActivate($id)
    {
        $model = $this->findModel($id);

        /* @var $systemAlert Alert */
        $systemAlert = Yii::$app->systemAlert;
        try {
            if ($this->userModule->activateUser($model)) {
                $systemAlert->setMessage(Alert::SUCCESS, Yii::t('user', 'User successfully activated'));
            }
            else {
                $systemAlert->setMessage(Alert::SUCCESS, Yii::t('user', 'Error activation a user'));
            }
        } catch (Exception $ex) {
            $systemAlert->setMessage(Alert::DANGER, Yii::t('app', 'System error: {message}', [
                'message' => $ex->getMessage(),
            ]));
        }

        return $this->redirect(['index']);
    }

    /**
     * Users locking
     *
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionLock($id)
    {
        $model = $this->findModel($id);

        /* @var $systemAlert Alert */
        $systemAlert = Yii::$app->systemAlert;
        try {
            if ($this->userModule->lockUser($model)) {
                $systemAlert->setMessage(Alert::SUCCESS, Yii::t('user', 'User successfully blocked'));
            }
            else {
                $systemAlert->setMessage(Alert::SUCCESS, Yii::t('user', 'Error locking a user'));
            }
        } catch (Exception $ex) {
            $systemAlert->setMessage(Alert::DANGER, Yii::t('app', 'System error: {message}', [
                'message' => $ex->getMessage(),
            ]));
        }

        return $this->redirect(['index']);
    }

    /**
     * Find user model by id and generate 404 if model is not found.
     *
     * @param integer $id User id
     *
     * @return UserForm
     *
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $id = is_scalar($id) ? (int) $id : 0;

        $model = UserForm::find()->andWhere(['id' => $id])->one();

        if (!$model instanceof UserForm) {
            throw new NotFoundHttpException();
        }

        return $model;
    }
}
