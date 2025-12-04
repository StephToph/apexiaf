<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\Crud;
use CodeIgniter\Session\Session;
use CodeIgniter\Email\Email;

class BaseController extends Controller
{
    /** @var CLIRequest|IncomingRequest */
    protected $request;

    protected Crud $Crud;
    protected Session $session;
    protected Email $email;

    /** @var array */
    protected $helpers = ['array', 'file', 'form', 'date', 'cookie', 'char_helper', 'text'];

    /**
     * Initialization for every controller that extends BaseController.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // ✅ Only load Crud if DB is configured
        if (!empty(env('database.default.DBDriver'))) {
            $this->Crud = new Crud();
        }

        $this->session = \Config\Services::session();
        $this->email = \Config\Services::email();

        $view = service('renderer');
        $view->setVar('Crud', $this->Crud ?? null);
        $view->setVar('session', $this->session);
        $view->setVar('email', $this->email);
    }

}
