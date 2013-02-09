<?php

namespace Admin\Service;

use Core\Service\Service;
use Exception;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\Adapter;

/**
 * Serviço responsável pela autenticação da aplicação
 * 
 * @category Admin
 * @package Service
 * @author  Elton Minetto<eminetto@coderockr.com>
 */
class Auth extends Service {

    /**
     * Adapter usado para a autenticação
     * @var Adapter
     */
    private $dbAdapter;

    /**
     * Construtor da classe
     *
     * @return void
     */
    public function __construct($dbAdapter = null) {
        $this->dbAdapter = $dbAdapter;
    }

    /**
     * Faz a autenticação dos usuários
     * 
     * @param array $params
     * @return array
     */
    public function authenticate($params) {
        if (!isset($params['username']) || !isset($params['password'])) {
            throw new Exception("Parâmetros inválidos");
        }

        $password = md5($params['password']);
        $auth = new AuthenticationService();

        $authAdapter = new AuthAdapter($this->dbAdapter);
        $authAdapter
                ->setTableName('users')
                ->setIdentityColumn('username')
                ->setCredentialColumn('password')
                ->setIdentity($params['username'])
                ->setCredential($password);

        $authAdapter->setIdentity($params['username'])
                ->setCredential($password);

        $result = $auth->authenticate($authAdapter);
        if (!$result->isValid()) {
            throw new Exception("Login ou senha inválidos");
        }

        //salva o user na sessão
        $session = $this->getServiceManager()->get('Session');
        $session->offsetSet('user', $authAdapter->getResultRowObject());

        return true;
    }

    /**
     * Faz o logout do sistema
     *
     * @return void
     */
    public function logout() {
        $auth = new AuthenticationService();
        $session = $this->getServiceManager()->get('Session');
        $session->offsetUnset('user');
        $auth->clearIdentity();
        return true;
    }

}