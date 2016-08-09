<?php
namespace ThomasWoehlke\Gtd\Controller;

/***
 *
 * This file is part of the "Getting Things Done" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2016 Thomas Woehlke <thomas@woehlke.org>, faktura gGmbH
 *
 ***/
use ThomasWoehlke\Gtd\Domain\Model\Project;
use ThomasWoehlke\Gtd\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;

/**
 * TaskController
 */
class TaskController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * taskRepository
     *
     * @var \ThomasWoehlke\Gtd\Domain\Repository\TaskRepository
     * @inject
     */
    protected $taskRepository = null;

    /**
     * userAccountRepository
     *
     * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $userAccountRepository = null;

    /**
     * projectRepository
     *
     * @var \ThomasWoehlke\Gtd\Domain\Repository\ProjectRepository
     * @inject
     */
    protected $projectRepository = null;

    /**
     * contextService
     *
     * @var \ThomasWoehlke\Gtd\Service\ContextService
     * @inject
     */
    protected $contextService = null;

    protected $taskStates = array(
        'inbox' => 0, 'today' => 1, 'next' => 2, 'waiting' => 3, 'scheduled' => 4, 'someday' => 5, 'completed' => 6 , 'trash' => 7
    );

    private $extName = 'gtd';

    /**
     * action show
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function showAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task)
    {
        $ctx = $this->contextService->getCurrentContext();
        $this->view->assign('task', $task);
        $this->getTaskEnergyAndTaskTime();
        $this->view->assign('contextList',$this->contextService->getContextList());
        $this->view->assign('currentContext',$ctx);
        $this->view->assign('rootProjects',$this->projectRepository->getRootProjects($ctx));
    }

    public function initializeEditAction()
    {
        $this->setTypeConverterConfigurationForImageUpload('task');
        $this->arguments['task']
            ->getPropertyMappingConfiguration()
            ->forProperty('dueDate')
            ->setTypeConverterOption('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter',
                \TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT, 'Y-m-d');
    }

    /**
     * action edit
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @ignorevalidation $task
     * @return void
     */
    public function editAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task)
    {
        $ctx = $this->contextService->getCurrentContext();
        $this->view->assign('task', $task);
        $this->getTaskEnergyAndTaskTime();
        $this->view->assign('contextList',$this->contextService->getContextList());
        $this->view->assign('currentContext',$ctx);
        $this->view->assign('rootProjects',$this->projectRepository->getRootProjects($ctx));
    }

    public function initializeUpdateAction()
    {
        $this->setTypeConverterConfigurationForImageUpload('task');
        $this->arguments['task']
            ->getPropertyMappingConfiguration()
            ->forProperty('dueDate')
            ->setTypeConverterOption('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter',
                \TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT, 'Y-m-d');
    }

    /**
     * action update
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function updateAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task)
    {
//        $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);

//        $logger->error('$task update');

//        $logger->error('$task '.$task);

        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $persistentTask = $this->taskRepository->findByUid($task->getUid());
        $persistentTask->setTitle($task->getTitle());
        $persistentTask->setText($task->getText());
        $persistentTask->setTaskEnergy($task->getTaskEnergy());
        $persistentTask->setTaskTime($task->getTaskTime());
        $persistentTask->setDueDate($task->getDueDate());
        $persistentTask->setImage($task->getImage());
        $persistentTask->setImageCollection($task->getImageCollection());
        if($task->getDueDate() != NULL){
            $persistentTask->changeTaskState($this->taskStates['scheduled']);
            $maxTaskStateOrderId = $this->taskRepository->getMaxTaskStateOrderId($userObject,$currentContext,$this->taskStates['scheduled']);
            $persistentTask->setOrderIdTaskState($maxTaskStateOrderId);
        } else {
            if($persistentTask->getTaskState() == $this->taskStates['scheduled']){
                $persistentTask->changeTaskState($this->taskStates['inbox']);
            }
            $maxTaskStateOrderId = $this->taskRepository->getMaxTaskStateOrderId($userObject,$currentContext,$persistentTask->getTaskState());
            $persistentTask->setOrderIdTaskState($maxTaskStateOrderId);
        }
        if($this->request->hasArgument('file')){
            $persistentTask->setFiles(str_replace('uploads/tx_gtd/', '',$this->request->getArgument('file')));
        }
//        $logger->error('$persistentTask '.$persistentTask);
//        try {
            $this->taskRepository->update($persistentTask);
//        } catch (\TYPO3\CMS\Extbase\Property\Exception\InvalidSourceException $e) {
//            $logger->error('update failed: '.$e->getMessage());
//        }
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.updated', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->getRedirectFromTask($persistentTask);
    }

    private function getRedirectFromTask(\ThomasWoehlke\Gtd\Domain\Model\Task $task){
        switch($task->getTaskState()){
            case $this->taskStates['inbox']:
                $this->redirect('inbox');
                break;
            case $this->taskStates['today']:
                $this->redirect('today');
                break;
            case $this->taskStates['next']:
                $this->redirect('next');
                break;
            case $this->taskStates['waiting']:
                $this->redirect('waiting');
                break;
            case $this->taskStates['scheduled']:
                $this->redirect('scheduled');
                break;
            case $this->taskStates['someday']:
                $this->redirect('someday');
                break;
            case $this->taskStates['completed']:
                $this->redirect('completed');
                break;
            case $this->taskStates['trash']:
                $this->redirect('trash');
                break;
            default:
                $this->redirect('list');
                break;
        }
    }

    /**
     * action inbox
     *
     * @return void
     */
    public function inboxAction()
    {
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $tasks = $this->taskRepository->findByUserAccountAndTaskState($userObject,$currentContext,$this->taskStates['inbox']);
        $this->view->assign('tasks', $tasks);
        $this->view->assign('contextList',$this->contextService->getContextList());
        $this->view->assign('currentContext',$currentContext);
        $this->view->assign('rootProjects',$this->projectRepository->getRootProjects($currentContext));
    }

    /**
     * action today
     *
     * @return void
     */
    public function todayAction()
    {
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $tasks = $this->taskRepository->findByUserAccountAndTaskState($userObject,$currentContext,$this->taskStates['today']);
        $this->view->assign('tasks', $tasks);
        $this->view->assign('contextList',$this->contextService->getContextList());
        $this->view->assign('currentContext',$currentContext);
        $this->view->assign('rootProjects',$this->projectRepository->getRootProjects($currentContext));
    }

    /**
     * action next
     *
     * @return void
     */
    public function nextAction()
    {
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $tasks = $this->taskRepository->findByUserAccountAndTaskState($userObject,$currentContext,$this->taskStates['next']);
        $this->view->assign('tasks', $tasks);
        $this->view->assign('contextList',$this->contextService->getContextList());
        $this->view->assign('currentContext',$currentContext);
        $this->view->assign('rootProjects',$this->projectRepository->getRootProjects($currentContext));
    }

    /**
     * action waiting
     *
     * @return void
     */
    public function waitingAction()
    {
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $tasks = $this->taskRepository->findByUserAccountAndTaskState($userObject,$currentContext,$this->taskStates['waiting']);
        $this->view->assign('tasks', $tasks);
        $this->view->assign('contextList',$this->contextService->getContextList());
        $this->view->assign('currentContext',$currentContext);
        $this->view->assign('rootProjects',$this->projectRepository->getRootProjects($currentContext));
    }

    /**
     * action scheduled
     *
     * @return void
     */
    public function scheduledAction()
    {
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $tasks = $this->taskRepository->findByUserAccountAndTaskState($userObject,$currentContext,$this->taskStates['scheduled']);
        $this->view->assign('tasks', $tasks);
        $this->view->assign('contextList',$this->contextService->getContextList());
        $this->view->assign('currentContext',$currentContext);
        $this->view->assign('rootProjects',$this->projectRepository->getRootProjects($currentContext));
    }

    /**
     * action someday
     *
     * @return void
     */
    public function somedayAction()
    {
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $tasks = $this->taskRepository->findByUserAccountAndTaskState($userObject,$currentContext,$this->taskStates['someday']);
        $this->view->assign('tasks', $tasks);
        $this->view->assign('contextList',$this->contextService->getContextList());
        $this->view->assign('currentContext',$currentContext);
        $this->view->assign('rootProjects',$this->projectRepository->getRootProjects($currentContext));
    }

    /**
     * action completed
     *
     * @return void
     */
    public function completedAction()
    {
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $tasks = $this->taskRepository->findByUserAccountAndTaskState($userObject,$currentContext,$this->taskStates['completed']);
        $this->view->assign('tasks', $tasks);
        $this->view->assign('contextList',$this->contextService->getContextList());
        $this->view->assign('currentContext',$currentContext);
        $this->view->assign('rootProjects',$this->projectRepository->getRootProjects($currentContext));
    }

    /**
     * action trash
     *
     * @return void
     */
    public function trashAction()
    {
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $tasks = $this->taskRepository->findByUserAccountAndTaskState($userObject,$currentContext,$this->taskStates['trash']);
        $this->view->assign('tasks', $tasks);
        $this->view->assign('contextList',$this->contextService->getContextList());
        $this->view->assign('currentContext',$currentContext);
        $this->view->assign('rootProjects',$this->projectRepository->getRootProjects($currentContext));
    }

    /**
     * action focus
     *
     * @return void
     */
    public function focusAction()
    {
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $tasks = $this->taskRepository->findByUserAccountAndHasFocus($userObject,$currentContext);
        $this->view->assign('tasks', $tasks);
        $this->view->assign('contextList',$this->contextService->getContextList());
        $this->view->assign('currentContext',$currentContext);
        $this->view->assign('rootProjects',$this->projectRepository->getRootProjects($currentContext));
    }

    /**
     * action emptyTrash
     *
     * @return void
     */
    public function emptyTrashAction()
    {
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $tasks = $this->taskRepository->findByUserAccountAndTaskState($userObject,$currentContext,$this->taskStates['trash']);
        foreach($tasks as $task){
            $this->taskRepository->remove($task);
        }
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.trash_emptied', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->redirect('trash');
    }

    /**
     * action transformTaskIntoProject
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function transformTaskIntoProjectAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task)
    {
        $parentProject = $task->getProject();
        $newProject = new Project();
        $newProject->setContext($task->getContext());
        $newProject->setUserAccount($task->getUserAccount());
        $newProject->setParent($parentProject);
        $newProject->setName($task->getTitle());
        $newProject->setDescription($task->getText());
        if($parentProject != null){
            $parentProject->addChild($newProject);
            $this->projectRepository->update($parentProject);
        }
        $this->projectRepository->add($newProject);
        $this->taskRepository->remove($task);
        $args = array("project" => $parentProject);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.task2project', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->redirect('show',"Project",null,$args);
    }

    /**
     * action completeTask
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function completeTaskAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task)
    {
        $task->changeTaskState($this->taskStates['completed']);
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $maxTaskStateOrderId = $this->taskRepository->getMaxTaskStateOrderId($userObject,$currentContext,$this->taskStates['completed']);
        $task->setOrderIdTaskState($maxTaskStateOrderId);
        $this->taskRepository->update($task);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.completed', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->getRedirectFromTask($task);
    }

    /**
     * action undoneTask
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function undoneTaskAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task)
    {
        $task->setToLastTaskState();
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $maxTaskStateOrderId = $this->taskRepository->getMaxTaskStateOrderId($userObject,$currentContext,$task->getTaskState());
        $task->setOrderIdTaskState($maxTaskStateOrderId);
        $this->taskRepository->update($task);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.notcompleted', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->getRedirectFromTask($task);
    }

    /**
     * action setFocus
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function setFocusAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task)
    {
        $task->setFocus(true);
        $this->taskRepository->update($task);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.focus', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->getRedirectFromTask($task);
    }

    /**
     * action unsetFocus
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function unsetFocusAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task)
    {
        $task->setFocus(false);
        $this->taskRepository->update($task);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.notfocus', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->getRedirectFromTask($task);
    }

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $ctx = $this->contextService->getCurrentContext();
        $tasks = $this->taskRepository->findAll();
        $this->view->assign('tasks', $tasks);
        $this->view->assign('contextList',$this->contextService->getContextList());
        $this->view->assign('currentContext',$ctx);
        $this->view->assign('rootProjects',$this->projectRepository->getRootProjects($ctx));
    }

    private function getTaskEnergyAndTaskTime(){
        $taskEnergy = array(
            0 => 'none',
            1 => 'low',
            2 => 'mid',
            3 => 'high'
        );
        $taskTime = array(
            0 => 'none',
            1 => '5 min',
            2 => '10 min',
            3 => '15 min',
            4 => '30 min',
            5 => '45 min',
            6 => '1 hours',
            7 => '2 hours',
            8 => '3 hours',
            9 => '4 hours',
            10 => '6 hours',
            11 => '8 hours',
            12 => 'more'
        );
        $this->view->assign('taskEnergy',$taskEnergy);
        $this->view->assign('taskTime',$taskTime);
    }

    /**
     * action new
     *
     * @return void
     */
    public function newAction()
    {
        $ctx = $this->contextService->getCurrentContext();
        $this->getTaskEnergyAndTaskTime();
        $this->view->assign('contextList',$this->contextService->getContextList());
        $this->view->assign('currentContext',$ctx);
        $this->view->assign('rootProjects',$this->projectRepository->getRootProjects($ctx));
    }

    public function initializeCreateAction()
    {
        $this->arguments['newTask']
            ->getPropertyMappingConfiguration()
            ->forProperty('dueDate')
            ->setTypeConverterOption('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter',
                \TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT, 'Y-m-d');
        $this->setTypeConverterConfigurationForImageUpload('newTask');
    }

    /**
     * action create
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $newTask
     * @return void
     */
    public function createAction(\ThomasWoehlke\Gtd\Domain\Model\Task $newTask)
    {
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $newTask->setContext($currentContext);
        $newTask->setUserAccount($userObject);
        $newTask->setTaskState($this->taskStates['inbox']);
        $projectOrderId = $this->taskRepository->getMaxProjectOrderId(null);
        $newTask->setOrderIdProject($projectOrderId);
        $maxTaskStateOrderId = $this->taskRepository->getMaxTaskStateOrderId($userObject,$currentContext,$this->taskStates['inbox']);
        $newTask->setOrderIdTaskState($maxTaskStateOrderId);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.new', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        if($newTask->getDueDate() != NULL){
            $newTask->setTaskState($this->taskStates['scheduled']);
            $this->taskRepository->add($newTask);
            $this->redirect('scheduled');
        } else {
            $newTask->setTaskState($this->taskStates['inbox']);
            $this->taskRepository->add($newTask);
            $this->redirect('inbox');
        }
    }

    /**
     * action moveToInbox
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function moveToInboxAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task){
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $maxTaskStateOrderId = $this->taskRepository->getMaxTaskStateOrderId($userObject,$currentContext,$this->taskStates['inbox']);
        $task->setOrderIdTaskState($maxTaskStateOrderId);
        $task->changeTaskState($this->taskStates['inbox']);
        $this->taskRepository->update($task);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.moved_inbox', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->redirect('inbox');
    }

    /**
     * action moveToToday
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function moveToTodayAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task){
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $maxTaskStateOrderId = $this->taskRepository->getMaxTaskStateOrderId($userObject,$currentContext,$this->taskStates['today']);
        $task->setOrderIdTaskState($maxTaskStateOrderId);
        $task->changeTaskState($this->taskStates['today']);
        $this->taskRepository->update($task);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.moved_today', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->redirect('today');
    }

    /**
     * action moveToNext
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function moveToNextAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task){
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $maxTaskStateOrderId = $this->taskRepository->getMaxTaskStateOrderId($userObject,$currentContext,$this->taskStates['next']);
        $task->setOrderIdTaskState($maxTaskStateOrderId);
        $task->changeTaskState($this->taskStates['next']);
        $this->taskRepository->update($task);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.moved_next', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->redirect('next');
    }

    /**
     * action moveToWaiting
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function moveToWaitingAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task){
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $maxTaskStateOrderId = $this->taskRepository->getMaxTaskStateOrderId($userObject,$currentContext,$this->taskStates['waiting']);
        $task->setOrderIdTaskState($maxTaskStateOrderId);
        $task->changeTaskState($this->taskStates['waiting']);
        $this->taskRepository->update($task);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.moved_waiting', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->redirect('waiting');
    }

    /**
     * action moveToSomeday
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function moveToSomedayAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task){
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $maxTaskStateOrderId = $this->taskRepository->getMaxTaskStateOrderId($userObject,$currentContext,$this->taskStates['someday']);
        $task->setOrderIdTaskState($maxTaskStateOrderId);
        $task->changeTaskState($this->taskStates['someday']);
        $this->taskRepository->update($task);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.moved_someday', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->redirect('someday');
    }

    /**
     * action moveToCompleted
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function moveToCompletedAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task){
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $maxTaskStateOrderId = $this->taskRepository->getMaxTaskStateOrderId($userObject,$currentContext,$this->taskStates['completed']);
        $task->setOrderIdTaskState($maxTaskStateOrderId);
        $task->changeTaskState($this->taskStates['completed']);
        $this->taskRepository->update($task);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.moved_completed', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->redirect('completed');
    }

    /**
     * action moveToTrash
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $task
     * @return void
     */
    public function moveToTrashAction(\ThomasWoehlke\Gtd\Domain\Model\Task $task){
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $maxTaskStateOrderId = $this->taskRepository->getMaxTaskStateOrderId($userObject,$currentContext,$this->taskStates['trash']);
        $task->setOrderIdTaskState($maxTaskStateOrderId);
        $task->changeTaskState($this->taskStates['trash']);
        $this->taskRepository->update($task);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.moved_trash', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->redirect('trash');
    }

    /**
     * action moveAllCompletedToTrash
     *
     * @return void
     */
    public function moveAllCompletedToTrashAction(){
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $tasks = $this->taskRepository->findByUserAccountAndTaskState($userObject,$currentContext, $this->taskStates['completed']);
        $maxTaskStateOrderId = $this->taskRepository->getMaxTaskStateOrderId($userObject,$currentContext,$this->taskStates['trash']);
        foreach($tasks as $task){
            $task->changeTaskState($this->taskStates['trash']);
            $task->setOrderIdTaskState($maxTaskStateOrderId);
            $this->taskRepository->update($task);
            $maxTaskStateOrderId++;
        }
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.moved_completed2trash', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->redirect('trash');
    }

    /**
     * action moveTaskOrder
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $srcTask
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $targetTask
     * @return void
     */
    public function moveTaskOrderAction(\ThomasWoehlke\Gtd\Domain\Model\Task $srcTask,
                                        \ThomasWoehlke\Gtd\Domain\Model\Task $targetTask){
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $destinationTaskOrderId = $targetTask->getOrderIdTaskState();
        if($srcTask->getOrderIdTaskState()<$targetTask->getOrderIdTaskState()){
            $tasks = $this->taskRepository->getTasksToReorderByOrderIdTaskState($userObject, $currentContext, $srcTask, $targetTask, $srcTask->getTaskState());
            foreach ($tasks as $task){
                $task->setOrderIdTaskState($task->getOrderIdTaskState()-1);
                $this->taskRepository->update($task);
            }
            $targetTask->setOrderIdTaskState($targetTask->getOrderIdTaskState()-1);
            $this->taskRepository->update($targetTask);
            $srcTask->setOrderIdTaskState($destinationTaskOrderId);
            $this->taskRepository->update($srcTask);
        } else {
            $tasks = $this->taskRepository->getTasksToReorderByOrderIdTaskState($userObject, $currentContext, $targetTask, $srcTask, $srcTask->getTaskState());
            foreach ($tasks as $task){
                $task->setOrderIdTaskState($task->getOrderIdTaskState()+1);
                $this->taskRepository->update($task);
            }
            $srcTask->setOrderIdTaskState($destinationTaskOrderId+1);
            $this->taskRepository->update($srcTask);
        }
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.ordering', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->getRedirectFromTask($srcTask);
    }

    /**
     * action moveTaskOrderInsideProject
     *
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $srcTask
     * @param \ThomasWoehlke\Gtd\Domain\Model\Task $targetTask
     * @return void
     */
    public function moveTaskOrderInsideProjectAction(\ThomasWoehlke\Gtd\Domain\Model\Task $srcTask,
                                                     \ThomasWoehlke\Gtd\Domain\Model\Task $targetTask){
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $project = $srcTask->getProject();
        $destinationProjectOrderId = $targetTask->getOrderIdProject();
        if($srcTask->getOrderIdProject()<$targetTask->getOrderIdProject()){
            $tasks = $this->taskRepository->getTasksToReorderByOrderIdProject($userObject, $currentContext, $srcTask, $targetTask, $project);
            foreach ($tasks as $task){
                $task->setOrderIdProject($task->getOrderIdProject()-1);
                $this->taskRepository->update($task);
            }
            $targetTask->setOrderIdProject($targetTask->getOrderIdProject()-1);
            $this->taskRepository->update($targetTask);
            $srcTask->setOrderIdProject($destinationProjectOrderId);
            $this->taskRepository->update($srcTask);
        } else {
            $tasks = $this->taskRepository->getTasksToReorderByOrderIdProject($userObject, $currentContext, $targetTask, $srcTask, $project);
            foreach ($tasks as $task){
                $task->setOrderIdProject($task->getOrderIdProject()+1);
                $this->taskRepository->update($task);
            }
            $srcTask->setOrderIdProject($destinationProjectOrderId+1);
            $this->taskRepository->update($srcTask);
        }
        $args = array('project'=>$project);
        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_gtd_flash.task.ordering', $this->extName, null);
        $this->addFlashMessage($msg, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->redirect('show','Project',null,$args);
    }

    /**
     *
     */
    protected function setTypeConverterConfigurationForImageUpload($argumentName) {
        $uploadConfiguration = array(
            UploadedFileReferenceConverter::CONFIGURATION_ALLOWED_FILE_EXTENSIONS => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
            UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/content/',
        );
//        $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
//        $logger->error('setTypeConverterConfigurationForImageUpload: '.$argumentName);
        /** @var PropertyMappingConfiguration $newExampleConfiguration */
        $newExampleConfiguration = $this->arguments[$argumentName]->getPropertyMappingConfiguration();
        $newExampleConfiguration->forProperty('image')
            ->setTypeConverterOptions(
                'ThomasWoehlke\\Gtd\\Property\\TypeConverter\\UploadedFileReferenceConverter',
                $uploadConfiguration
            );
        $newExampleConfiguration->forProperty('imageCollection.0')
            ->setTypeConverterOptions(
                'ThomasWoehlke\\Gtd\\Property\\TypeConverter\\UploadedFileReferenceConverter',
                $uploadConfiguration
            );
//        $logger->error('setTypeConverterConfigurationForImageUpload: DONE');
    }
}
