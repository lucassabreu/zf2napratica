<?php

namespace Application\Controller;

use Application\Model\Post;
use Application\Model\Comment;
use Core\Test\ControllerTestCase;

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
        $controllerData = $variables["posts"]->getCurrentItems()->toArray();
        $this->assertEquals($postA->title, $controllerData[0]['title']);
        $this->assertEquals($postB->title, $controllerData[1]['title']);
    }

    /**
     * Testa a página inicial, que deve mostrar os posts com paginador
     */
    public function testIndexActionPaginator() {
        // Cria posts para testar
        $post = array();
        for ($i = 0; $i < 25; $i++) {
            $post[] = $this->addPost();
        }

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

        //testa o paginator
        $paginator = $variables["posts"];
        $this->assertEquals('Zend\Paginator\Paginator', get_class($paginator));
        $posts = $paginator->getCurrentItems()->toArray();
    
        $this->assertEquals(10, count($posts));
        $this->assertEquals($post[0]->id, $posts[0]['id']);
        $this->assertEquals($post[1]->id, $posts[1]['id']);

        //testa a terceira página da paginação
        $this->routeMatch->setParam('action', 'index');
        $this->routeMatch->setParam('page', 3);
        $result = $this->controller->dispatch($this->request, $this->response);
        $variables = $result->getVariables();
        $controllerData = $variables["posts"]->getCurrentItems()->toArray();
        $this->assertEquals(5, count($controllerData));
    }

    /**
     * Testa o funcionamento da tela de detalhe de um Post
     */
    public function testPostAction() {
        $post = $this->addPost();

        $comment0 = $this->addComment($post->id);
        $comment1 = $this->addComment($post->id);

        $this->routeMatch->setParam('action', 'post');
        $this->routeMatch->setParam('id', 1);

        $result = $this->controller->dispatch($this->request, $this->response);

        // Verifica o Response
        $response = $this->controller->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // Verifica o resultado do dispatch
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $result);

        $variables = $result->getVariables();

        // Verifica se foi enviada a variavel 'post'
        $this->assertArrayHasKey('post', $variables);

        // Verifica se foi enviada a variável 'comments'
        $this->assertArrayHasKey('comments', $variables);

        $this->assertEquals($post->title, $variables['post']['title']);

        $this->assertEquals(2, count($variables['comments']));

        $this->assertEquals($comment0->id, $variables['comments'][0]['id']);
        $this->assertEquals($comment0->post_id, $variables['comments'][0]['post_id']);
        $this->assertEquals($comment0->description, $variables['comments'][0]['description']);

        $this->assertEquals($comment1->id, $variables['comments'][1]['id']);
        $this->assertEquals($comment1->post_id, $variables['comments'][1]['post_id']);
        $this->assertEquals($comment1->description, $variables['comments'][1]['description']);
    }

    /**
     * Testa se existe tratamento para a inexistencia de um id no parametro.
     * @expectedException Exception
     * @expectedExceptionMessage É preciso informar um ID para visualizar o post.
     */
    public function testIfNotInformAId() {
        $this->routeMatch->setParam('action', 'post');
        $this->controller->dispatch($this->request, $this->response);
    }

    /**
     * Testa se é lançado erro ao informar ID inválido.
     * @expectedException Exception
     * @expectedExceptionMessage Could not find row 2
     */
    public function testThrowExceptionOnInvalidIdInformed() {
        $this->routeMatch->setParam('action', 'post');
        $this->routeMatch->setParam('id', '2');
        $this->controller->dispatch($this->request, $this->response);
    }

    /**
     * Testa se esta trazendo os comentários corretamente.
     */
    public function testCorrectLoadOfComments() {
        $post0 = $this->addPost();
        $post1 = $this->addPost();

        $comment0 = $this->addComment($post0->id);
        $comment1 = $this->addComment($post0->id);

        $this->routeMatch->setParam('action', 'post');
        $this->routeMatch->setParam('id', '2');
        $result = $this->controller->dispatch($this->request, $this->response);

        $variables = $result->getVariables();

        $this->assertArrayHasKey('comments', $variables);

        $this->assertEquals(0, count($variables['comments']));
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

    private function addComment($post_id) {
        $comment = new Comment();
        $comment->post_id = $post_id;
        $comment->description = 'Comentário importante <script>alert("ok");</script> <br> ';
        $comment->name = 'Elton Minetto';
        $comment->email = 'eminetto@coderockr.com';
        $comment->webpage = 'http://www.eltonminetto.net';
        $comment->comment_date = date('Y-m-d H:i:s');

        return $this->getTable('Application\Model\Comment')->save($comment);
    }

}