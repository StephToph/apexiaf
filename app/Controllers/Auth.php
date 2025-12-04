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
    public function submit_waitlist()
    {
        $request = service('request');
        $db = db_connect();

        $id = $request->getPost("waitlist_id");

        if (!$id) {
            return $this->response->setJSON([
                "status" => false,
                "msg" => "Invalid session. Please restart the process."
            ]);
        }

        // Fetch record
        $row = $db->table("waitlist")->where("id", $id)->get()->getRow();

        if (!$row) {
            return $this->response->setJSON([
                "status" => false,
                "msg" => "Error locating your application. Please start again."
            ]);
        }

        // ----------------------------
        // BLOCKED & REVIEW STATUSES
        // ----------------------------

        if ($row->status === "banned") {
            return $this->response->setJSON([
                "status" => false,
                "msg" => "This email is not permitted to submit an application."
            ]);
        }

        if ($row->status === "rejected") {
            return $this->response->setJSON([
                "status" => false,
                "msg" => "This application was rejected. Contact support for review."
            ]);
        }

        if ($row->status === "pending_review") {
            return $this->response->setJSON([
                "status" => false,
                "msg" => "Your application is already under review."
            ]);
        }

        if ($row->status === "complete") {
            return $this->response->setJSON([
                "status" => false,
                "msg" => "Your application has already been completed."
            ]);
        }

        // ----------------------------
        // VALIDATE FIELDS
        // ----------------------------
        $validation = \Config\Services::validation();
        $validation->setRules([
            "full_name" => "required|max_length[255]",
            "profession" => "required|max_length[255]",
            "net_worth" => "required|max_length[100]",
            "consent" => "required"
        ]);

        if (!$validation->withRequest($request)->run()) {
            return $this->response->setJSON([
                "status" => false,
                "msg" => implode("\n", $validation->getErrors())
            ]);
        }

        // ----------------------------
        // UPDATE RECORD WITH FULL DATA
        // ----------------------------
        $data = [
            "full_name" => trim($request->getPost("full_name")),
            "phone" => trim($request->getPost("phone")),
            "profession" => trim($request->getPost("profession")),
            "net_worth" => trim($request->getPost("net_worth")),
            "assets" => json_encode($request->getPost("assets") ?? []),
            "referral" => trim($request->getPost("referral")),
            "referral_other" => trim($request->getPost("referral_other")),
            "why" => trim($request->getPost("why")),
            "consent" => ($request->getPost("consent") === "on") ? 1 : 0,
            "status" => "complete",
            "updated_at" => date('Y-m-d H:i:s'),
            "user_agent" => $request->getUserAgent()->getAgentString(),
            "ip_address" => $request->getIPAddress(),
        ];

        $db->table("waitlist")->where("id", $id)->update($data);

        return $this->response->setJSON([
            "status" => true,
            "msg" => "Your application has been received. Apexia will review it manually."
        ]);
    }



    public function submit_email_first()
    {
        $request = service('request');
        $db = db_connect();

        $email = trim($request->getPost('email'));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON([
                "status" => false,
                "type" => "invalid_email",
                "msg" => "Invalid email provided."
            ]);
        }

        // Check if email exists
        $existing = $db->table("waitlist")
            ->where("email", $email)
            ->get()
            ->getRow();

        if ($existing) {

            // 🔥 SECURITY FLAGS
            if ($existing->status === "banned") {
                return $this->response->setJSON([
                    "status" => false,
                    "type" => "banned",
                    "msg" => "This email is not allowed to join Apexia."
                ]);
            }

            if ($existing->status === "rejected") {
                return $this->response->setJSON([
                    "status" => false,
                    "type" => "rejected",
                    "msg" => "This application was denied. Contact support for reconsideration."
                ]);
            }

            // 🔥 USER HAS COMPLETED THE FORM BEFORE
            if ($existing->status === "complete") {
                return $this->response->setJSON([
                    "status" => true,
                    "type" => "completed",
                    "id" => $existing->id,
                    "msg" => "Your application is already complete."
                ]);
            }

            // 🔥 APPLICATION IS UNDER REVIEW
            if ($existing->status === "pending_review") {
                return $this->response->setJSON([
                    "status" => true,
                    "type" => "under_review",
                    "id" => $existing->id,
                    "msg" => "Your application is currently under review."
                ]);
            }

            // 🔥 USER HAS EMAIL-ONLY OR INCOMPLETE APPLICATION → CONTINUE
            if ($existing->status === "email_only" || $existing->status === "incomplete") {
                return $this->response->setJSON([
                    "status" => true,
                    "type" => "continue",
                    "id" => $existing->id,
                    "msg" => "Continue your application."
                ]);
            }
        }

        // ------------------------
        // NEW USER (Save email only)
        // ------------------------
        $data = [
            "email" => $email,
            "status" => "email_only",
            "created_at" => date('Y-m-d H:i:s')
        ];

        $db->table("waitlist")->insert($data);

        return $this->response->setJSON([
            "status" => true,
            "type" => "new",
            "id" => $db->insertID(),
            "msg" => "Email saved successfully."
        ]);
    }

}
