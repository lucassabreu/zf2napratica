<?php

namespace Application\Controller;

use Core\Test\ControllerTestCase;
use Application\Controller\IndexController;
use Application\Model\Post;
use Zend\Http\Request;
use Zend\Stdlib\Parameters;
use Zend\View\Renderer\PhpRenderer;

/**
 * @group Controller
 */
class IndexControllerTest extends ControllerTestCase {

    /**
     * Namespace completa do Controller
     * @var string
     */
    protected $controllerFQDN = 'Application\Controller\IndexController';

    /**
     * Nome da rota. Geralmente o nome do módulo
     * @var string
     */
    protected $controllerRoute = 'application';

    /**
     * Testa o acesso a uma action que não existe
     */
    public function test404() {
        $this->routeMatch->setParam('action', 'action_nao_existente');
        $result = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * Testa a página inicial, que deve mostrar os posts
     */
    public function testIndexAction() {
        // Cria posts para testar
        $postA = $this->addPost();
        $postB = $this->addPost();

        // Invoca a rota index
        $this->routeMatch->setParam('action', 'index');
        $result = $this->controller->dispatch($this->request, $this->response);

        // Verifica o response
        $response = $this->controller->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // Testa se um ViewModel foi retornado
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);

        // Testa os dados da view
        $variables = $result->getVariables();

        $this->assertArrayHasKey('posts', $variables);

        // Faz a comparação dos dados
        $controllerData = $variables["posts"];
        $this->assertEquals($postA->title, $controllerData[0]['title']);
        $this->assertEquals($postB->title, $controllerData[1]['title']);
    }

    /**
     * Adiciona um post para os testes
     */
    private function addPost() {
        $post = new Post();
        $post->title = 'Apple compra a Coderockr';
        $post->description = 'A Apple compra a <b>Coderockr</b><br> ';
        $post->post_date = date('Y-m-d H:i:s');

        $saved = $this->getTable('Application\Model\Post')->save($post);

        return $saved;
    }

}