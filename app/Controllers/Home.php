<?php

namespace App\Controllers;

class Home extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {

        // check login
        $log_id = $this->session->get('plx_id');




        $data['title'] = app_name;
        $data['page_active'] = 'home';
        return view('home/land', $data);
    }

   

}
