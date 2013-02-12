<?php

namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Core\Controller\ActionController;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as PaginatorDbSelectAdapter;

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
        $post = $this->getTable('Application\Model\Post');
        $sql = $post->getSql();
        $select = $sql->select();

        $paginatorAdapter = new PaginatorDbSelectAdapter($select, $sql);
        $paginator = new Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($this->params()->fromRoute('page'));
        $paginator->setItemCountPerPage(10);

        return new ViewModel(array(
                    'posts' => $paginator
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

    /**
     * Retorna os comentários de um post
     * @return Zend\Http\Response 
     */
    public function commentsAction() {
        $id = (int) $this->params()->fromRoute('id', 0);

        if ($id == 0) {
            throw new \Exception('Código necesário');
        }

        $where = array('post_id' => $id);
        $comments = $this->getTable('Application\Model\Comment')
                ->fetchAll(null, $where)
                ->toArray();

        /*$response = $this->getResponse();
        $response->setStatusCode(200);
        $response->setContent(json_encode($comments));
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        return $response;*/
        
        $view = new \Zend\View\Model\JsonModel($comments);
        
        return $view;
    }

}