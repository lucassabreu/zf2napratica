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

}