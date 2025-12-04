<?php

namespace App\Controllers;

class Auth extends BaseController
{
    public function index()
    {
        return $this->login();
    }

    ///// LOGIN
    public function login()
    {
        // check login
        $log_id = $this->session->get('plx_id');
        if (!empty($log_id))
            return redirect()->to(site_url('dashboard'));

        if ($this->request->getMethod() == 'post') {
            $email = $this->request->getVar('email');
            $password = $this->request->getVar('password');

            if (!$email || !$password) {
                echo $this->Crud->msg('danger', 'Please provide Email and Password');
            } else {
                // Check if user exists with email
                $user = $this->Crud->read_single('email', $email, 'user'); // fetch user by email

                if (empty($user)) {
                    echo $this->Crud->msg('danger', 'Invalid Authentication!');
                } else {
                    $user = $user[0]; // assuming read_single returns an array
                    $hashedPassword = $user->password; // stored password hash in DB

                    if (!password_verify($password, $hashedPassword)) {
                        echo $this->Crud->msg('danger', 'Invalid Authentication!');
                    } else {
                        $ban = $user->activate;
                        $fullname = $user->fullname;
                        $id = $user->id;

                        // ✅ Check if user is banned
                        if ((int) $ban === 0) {
                            echo $this->Crud->msg('warning', 'Your account has been suspended. Please contact admin.');
                        } else {
                            ///// store activities
                            $code = $fullname;
                            $action = $code . ' logged in from the Web';
                            $this->Crud->activity('authentication', $id, $action);

                            echo $this->Crud->msg('success', 'Login Successful!');
                            $this->session->set('plx_id', $id);
                            echo '<script>window.location.replace("' . site_url('dashboard') . '");</script>';
                        }
                    }
                }
            }


            die;
        }


        $data['title'] = 'Log In | ' . app_name;
        return view('auth/login', $data);
    }

    ///// LOGOUT
    public function logout()
    {
        if (!empty($this->session->get('plx_id'))) {
            $user_id = $this->session->get('plx_id');
            ///// store activities
            $code = $this->Crud->read_field('id', $user_id, 'user', 'fullname');
            $action = $code . ' logged out';
            $this->Crud->activity('authentication', $user_id, $action);

            $this->session->remove('plx_id');
        }
        return redirect()->to(site_url());
    }
}
