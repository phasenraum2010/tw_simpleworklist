<?php
namespace ThomasWoehlke\TwSimpleworklist\Controller;

use \ThomasWoehlke\TwSimpleworklist\Domain\Model\Project;

/***
 *
 * This file is part of the "SimpleWorklist" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2016 Thomas Woehlke <woehlke@faktura-berlin.de>, faktura gGmbH
 *
 ***/

/**
 * ProjectController
 */
class ProjectController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * projectRepository
     * 
     * @var \ThomasWoehlke\TwSimpleworklist\Domain\Repository\ProjectRepository
     * @inject
     */
    protected $projectRepository = null;

    /**
     * contextService
     *
     * @var \ThomasWoehlke\TwSimpleworklist\Service\ContextService
     * @inject
     */
    protected $contextService = null;

    /**
     * userAccountRepository
     *
     * @var \ThomasWoehlke\TwSimpleworklist\Domain\Repository\UserAccountRepository
     * @inject
     */
    protected $userAccountRepository = null;

    /**
     * action show
     * 
     * @param \ThomasWoehlke\TwSimpleworklist\Domain\Model\Project $project
     * @return void
     */
    public function showAction(\ThomasWoehlke\TwSimpleworklist\Domain\Model\Project $project)
    {
        $this->view->assign('project', $project);
    }
    
    /**
     * action edit
     * 
     * @param \ThomasWoehlke\TwSimpleworklist\Domain\Model\Project $project
     * @ignorevalidation $project
     * @return void
     */
    public function editAction(\ThomasWoehlke\TwSimpleworklist\Domain\Model\Project $project)
    {
        $this->view->assign('project', $project);
    }
    
    /**
     * action update
     * 
     * @param \ThomasWoehlke\TwSimpleworklist\Domain\Model\Project $project
     * @return void
     */
    public function updateAction(\ThomasWoehlke\TwSimpleworklist\Domain\Model\Project $project)
    {
        $this->addFlashMessage('The object was updated. Please be aware that this action is publicly accessible unless you implement an access check. See http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        $this->projectRepository->update($project);
        $this->redirect('list');
    }
    
    /**
     * action delete
     * 
     * @param \ThomasWoehlke\TwSimpleworklist\Domain\Model\Project $project
     * @return void
     */
    public function deleteAction(\ThomasWoehlke\TwSimpleworklist\Domain\Model\Project $project)
    {
        $this->addFlashMessage('The object was deleted. Please be aware that this action is publicly accessible unless you implement an access check. See http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        $this->projectRepository->remove($project);
        $this->redirect('list');
    }
    
    /**
     * action addNewRootProject
     * 
     * @return void
     */
    public function addNewRootProjectAction()
    {
        
    }
    
    /**
     * action addNewChildProject
     * 
     * @return void
     */
    public function addNewChildProjectAction()
    {
        
    }
    
    /**
     * action moveProject
     * 
     * @return void
     */
    public function moveProjectAction()
    {
        
    }
    
    /**
     * action getAllProjects
     * 
     * @return void
     */
    public function getAllProjectsAction()
    {
        
    }
    
    /**
     * action getRootProjects
     * 
     * @return void
     */
    public function getRootProjectsAction()
    {
        
    }
    
    /**
     * action list
     * 
     * @return void
     */
    public function listAction()
    {
        $projects = $this->projectRepository->findAll();
        $this->view->assign('projects', $projects);
    }
    
    /**
     * action new
     * 
     * @return void
     */
    public function newAction()
    {
        
    }
    
    /**
     * action create
     * 
     * @param \ThomasWoehlke\TwSimpleworklist\Domain\Model\Project $newProject
     * @return void
     */
    public function createAction(\ThomasWoehlke\TwSimpleworklist\Domain\Model\Project $newProject)
    {
        $this->addFlashMessage('The object was created. Please be aware that this action is publicly accessible unless you implement an access check. See http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        $this->projectRepository->add($newProject);
        $this->redirect('list');
    }

    /**
     * action createTestData
     * @return void
     */
    public function createTestDataAction()
    {
        $userObject = $this->userAccountRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        $currentContext = $this->contextService->getCurrentContext();
        $testProject1 = new Project();
        $testProject1->setContext($currentContext);
        $testProject1->setUserAccount($userObject);
        $testProject1->setName("Project 1");
        $testProject1->setDescription("Description 1");
        $testProject1->setParent(null);
        $this->projectRepository->add($testProject1);

        $testProject2 = new Project();
        $testProject2->setContext($currentContext);
        $testProject2->setUserAccount($userObject);
        $testProject2->setName("Project 2");
        $testProject2->setDescription("Description 2");
        $testProject2->setParent(null);
        $this->projectRepository->add($testProject2);

        $testProject3 = new Project();
        $testProject3->setContext($currentContext);
        $testProject3->setUserAccount($userObject);
        $testProject3->setName("Project 3");
        $testProject3->setDescription("Description 3");
        $testProject3->setParent(null);
        $this->projectRepository->add($testProject3);

        $testProject1_1 = new Project();
        $testProject1_1->setContext($currentContext);
        $testProject1_1->setUserAccount($userObject);
        $testProject1_1->setName("Project 11");
        $testProject1_1->setDescription("Description 11");
        $testProject1_1->setParent($testProject1);
        $this->projectRepository->add($testProject1_1);

        $testProject1_2 = new Project();
        $testProject1_2->setContext($currentContext);
        $testProject1_2->setUserAccount($userObject);
        $testProject1_2->setName("Project 12");
        $testProject1_2->setDescription("Description 12");
        $testProject1_2->setParent($testProject1);
        $this->projectRepository->add($testProject1_2);

        $testProject1_3 = new Project();
        $testProject1_3->setContext($currentContext);
        $testProject1_3->setUserAccount($userObject);
        $testProject1_3->setName("Project 13");
        $testProject1_3->setDescription("Description 13");
        $testProject1_3->setParent($testProject1);
        $this->projectRepository->add($testProject1_3);

        $testProject1_3_1 = new Project();
        $testProject1_3_1->setContext($currentContext);
        $testProject1_3_1->setUserAccount($userObject);
        $testProject1_3_1->setName("Project 131");
        $testProject1_3_1->setDescription("Description 131");
        $testProject1_3_1->setParent($testProject1_3);
        $this->projectRepository->add($testProject1_3_1);

        $testProject1_3_2 = new Project();
        $testProject1_3_2->setContext($currentContext);
        $testProject1_3_2->setUserAccount($userObject);
        $testProject1_3_2->setName("Project 132");
        $testProject1_3_2->setDescription("Description 132");
        $testProject1_3_2->setParent($testProject1_3);
        $this->projectRepository->add($testProject1_3_2);

        $this->redirect('inbox',"Task");
    }
}
