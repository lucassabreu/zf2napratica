<?php

namespace Admin\Service;

use Admin\Model\User;
use Core\Test\ServiceTestCase;
use Zend\Authentication\AuthenticationService;

/**
 * Testes do serviço Auth
 * @category Admin
 * @package Service
 * @author  Elton Minetto<eminetto@coderockr.com>
 */

/**
 * @group Service
 */
class AuthTest extends ServiceTestCase {

    /**
     * Authenticação sem parâmetros
     * @expectedException \Exception
     * @return void
     */
    public function testAuthenticateWithoutParams() {
        $authService = $this->serviceManager->get('Admin\Service\Auth');

        $authService->authenticate();
    }

    /**
     * Authenticação sem parâmetros
     * @expectedException \Exception
     * @expectedExceptionMessage Parâmetros inválidos
     * @return void
     */
    public function testAuthenticateEmptyParams() {
        $authService = $this->serviceManager->get('Admin\Service\Auth');

        $authService->authenticate(array());
    }

    /**
     * Teste da autenticação inválida
     * @expectedException \Exception
     * @expectedExceptionMessage Login ou senha inválidos
     * @return void
     */
    public function testAuthenticateInvalidParameters() {
        $authService = $this->serviceManager->get('Admin\Service\Auth');

        $authService->authenticate(array('username' => 'invalid', 'password' => 'invalid'));
    }

    /**
     * Teste da autenticação Inválida
     * @expectedException \Exception
     * @expectedExceptionMessage Login ou senha inválidos
     * @return void
     */
    public function testAuthenticateInvalidPassord() {
        $authService = $this->serviceManager->get('Admin\Service\Auth');
        $user = $this->addUser();

        $authService->authenticate(array('username' => $user->username, 'password' => 'invalida'));
    }

    /**
     * Teste da autenticação Válida
     * @return void
     */
    public function testAuthenticateValidParams() {
        $authService = $this->serviceManager->get('Admin\Service\Auth');
        $user = $this->addUser();

        $result = $authService->authenticate(
                array('username' => $user->username, 'password' => 'apple')
        );
        $this->assertTrue($result);

        //testar a se a authenticação foi criada
        $auth = new AuthenticationService();
        $this->assertEquals($auth->getIdentity(), $user->username);

        //verica se o usuário foi salvo na sessão
        $session = $this->serviceManager->get('Session');
        $savedUser = $session->offsetGet('user');
        $this->assertEquals($user->id, $savedUser->id);
    }

    /**
     * Limpa a autenticação depois de cada teste
     * @return void
     */
    public function tearDown() {
        parent::tearDown();
        $auth = new AuthenticationService();
        $auth->clearIdentity();
    }

    /**
     * Teste do logout
     * @return void
     */
    public function testLogout() {
        $authService = $this->serviceManager->get('Admin\Service\Auth');
        $user = $this->addUser();

        $result = $authService->authenticate(
                array('username' => $user->username, 'password' => 'apple')
        );
        $this->assertTrue($result);

        $result = $authService->logout();
        $this->assertTrue($result);

        //verifica se removeu a identidade da autenticação
        $auth = new AuthenticationService();
        $this->assertNull($auth->getIdentity());

        //verifica se o usuário foi removido da sessão
        $session = $this->serviceManager->get('Session');
        $savedUser = $session->offsetGet('user');
        $this->assertNull($savedUser);
    }

//    /**
//     * Teste da autorização
//     * @return void
//     */
//    public function testAuthorize() {
//        $authService = $this->getService('Admin\Service\Auth');
//
//        $result = $authService->authorize();
//        $this->assertFalse($result);
//
//        $user = $this->addUser();
//
//        $result = $authService->authenticate(
//                array('username' => $user->username, 'password' => 'apple')
//        );
//        $this->assertTrue($result);
//
//        $result = $authService->authorize();
//        $this->assertTrue($result);
//    }

    public function testAuthorize() {
        $authService = $this->getService('Admin\Service\Auth');

        $admin = $this->addUser();

        //adiciona visitante
        $visitante = new User();
        $visitante->username = 'bill';
        $visitante->password = md5('ms');
        $visitante->name = 'Bill Gates';
        $visitante->valid = 1;
        $visitante->role = 'visitante';

        $saved = $this->getTable('Admin\Model\User')->save($visitante);

        //cria novas configurações de acl
        $config = $this->serviceManager->get('Config');
        $config['acl']['roles']['visitante'] = null;
        $config['acl']['roles']['admin'] = 'visitante';

        $config['acl']['resources'] = array(
            'Application\Controller\Index.index',
            'Admin\Controller\Index.save'
        );

        $config['acl']['privilege']['visitante']['allow'] = array('Application\Controller\Index.index');
        $config['acl']['privilege']['admin']['allow'] = array('Admin\Controller\Index.save');

        //atualiza a configuração
        $this->serviceManager->setService('Config', $config);

        //authentica com o visitante
        $result = $authService->authenticate(
                array('username' => $visitante->username, 'password' => 'ms')
        );

        $result = $authService->authorize('application', 'Application\Controller\Index', 'index');
        $this->assertTrue($result);
        $result = $authService->authorize('admin', 'Admin\Controller\Index', 'save');
        $this->assertFalse($result);

        //authentica com o admin
        $result = $authService->authenticate(
                array('username' => $admin->username, 'password' => 'apple')
        );

        $result = $authService->authorize('application', 'Application\Controller\Index', 'index');
        $this->assertTrue($result);
        $result = $authService->authorize('admin', 'Admin\Controller\Index', 'save');
        $this->assertTrue($result);
    }

    private function addUser() {
        $user = new User();
        $user->username = 'steve';
        $user->password = md5('apple');
        $user->name = 'Steve <b>Jobs</b>';
        $user->valid = 1;
        $user->role = 'admin';

        $saved = $this->getTable('Admin\Model\User')->save($user);
        return $saved;
    }

}