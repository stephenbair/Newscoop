<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Comments controller
 */

use Newscoop\Entity\Comment;

require_once($GLOBALS['g_campsiteDir'].'/include/captcha/php-captcha.inc.php');
require_once($GLOBALS['g_campsiteDir'].'/include/get_ip.php');

class CommentController extends Zend_Controller_Action
{
    public function init()
    {
		$this->getHelper('contextSwitch')->addActionContext('save', 'json')->initContext();
    }

    public function saveAction()
    {
		global $_SERVER;

		$this->_helper->layout->disableLayout();
		$parameters = $this->getRequest()->getParams();

		$errors = array();

		$auth = Zend_Auth::getInstance();

		$article = new Article($parameters['f_language'], $parameters['f_article_number']);
		$publication = new Publication($article->getPublicationId());

		if ($auth->getIdentity()) {
			$acceptanceRepository = $this->getHelper('entity')->getRepository('Newscoop\Entity\Comment\Acceptance');
			$user = new User($auth->getIdentity());

			$userIp = getIp();
			if ($acceptanceRepository->checkParamsBanned($user->m_data['Name'], $user->m_data['EMail'], $userIp, $article->getPublicationId())) {
				$errors[] = getGS('You have been banned from writing comments.');
			}
		}
		else {
			$errors[] = getGS('You are not logged in.');
		}

		if (!array_key_exists('f_comment_subject', $parameters) || empty($parameters['f_comment_subject'])) {
			$errors[] = getGS('The comment subject was not filled in.');
		}
		if (!array_key_exists('f_comment_content', $parameters) || empty($parameters['f_comment_content'])) {
			$errors[] = getGS('The comment content was not filled in.');
		}

		if (empty($errors)) {
			$commentRepository = $this->getHelper('entity')->getRepository('Newscoop\Entity\Comment');
			$comment = new Comment();

			$values = array(
				'user' => $auth->getIdentity(),
				'name' => $parameters['f_comment_nickname'],
				'subject' => $parameters['f_comment_subject'],
				'message' => $parameters['f_comment_content'],
				'language' => $parameters['f_language'],
				'thread' => $parameters['f_article_number'],
				'ip' => $_SERVER['REMOTE_ADDR'],
				'status' => 'approved',
				'time_created' => new DateTime()
			);

			$commentRepository->save($comment, $values);
			$commentRepository->flush();

            $current_user = $this->_helper->service('user')->getCurrentUser();
            $this->_helper->service->notifyDispatcher("comment.delivered", array('user' => $current_user));

			$this->view->response = 'OK';
		}
		else {
			$errors = implode('<br>', $errors);
			$errors = getGS('Following errors have been found:') . '<br>' . $errors;
			$this->view->response = $errors;
		}
    }

    public function indexAction()
    {
		$this->view->param = $this->_getParam('switch');
	}
}