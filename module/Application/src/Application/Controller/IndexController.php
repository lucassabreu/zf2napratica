<?php

namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Core\Controller\ActionController;

/**
 * Controlador que gerencia os posts
 * 
 * @category Application
 * @package Controller
 * @author  Elton Minetto <eminetto@coderockr.com>
 */
class IndexController extends ActionController {

    /**
     * Mostra os posts cadastrados
     * @return Zend\View\Model\ViewModel
     */
    public function indexAction() {
        return new ViewModel(array(
                    'posts' => $this->getTable('Application\Model\Post')
                            ->fetchAll()
                            ->toArray()
                ));
    }

    public function postAction() {

        $post_id = $this->params()->fromRoute('id', null);

        if (is_null($post_id))
            throw new \Exception('É preciso informar um ID para visualizar o post.');

        $post = $this->getTable('Application\Model\Post')->get($post_id);

        $where = new \Zend\Db\Sql\Predicate\Predicate();
        $comments = $this->getTable('Application\Model\Comment')->fetchAll(null, $where->equalTo('post_id', $post->id));

        return new ViewModel(array(
                    'post' => $post->toArray(),
                    'comments' => $comments->toArray(),
                ));
    }

}