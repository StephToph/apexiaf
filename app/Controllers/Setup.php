<?php

namespace App\Controllers;

class Setup extends BaseController
{
	private $db;

	public function __construct()
	{
		$this->db = \Config\Database::connect();
	}

	public function index()
	{
		return $this->admin();
	}

	//// ADMIN
	public function admin($param1 = '', $param2 = '', $param3 = '')
	{
		$db = \Config\Database::connect();

		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'setup/admin', 'create');
		$role_r = $this->Crud->module($role_id, 'setup/admin', 'read');
		$role_u = $this->Crud->module($role_id, 'setup/admin', 'update');
		$role_d = $this->Crud->module($role_id, 'setup/admin', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'user';

		$form_link = site_url('setup/admin/');
		if ($param1) {
			$form_link .= $param1 . '/';
		}
		if ($param2) {
			$form_link .= $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_user_id');
						///// store activities
						$code = $this->Crud->read_field('id', $del_id, 'user', 'fullname');
						$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
						$action = $by . ' deleted Administrator ' . $code . ' Account';

						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							$this->Crud->activity('setup', $del_id, $action);

							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_fullname'] = $e->fullname;
								$data['e_email'] = $e->email;
								$data['e_role_id'] = $e->role_id;
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$user_id = $this->request->getVar('user_id');
					$fullname = $this->request->getVar('fullname');
					$email = $this->request->getVar('email');
					$role_id = $this->request->getVar('role_id');
					$password = $this->request->getVar('password');

					$p_data['fullname'] = $fullname;
					$p_data['email'] = $email;
					$p_data['role_id'] = $role_id;
					if ($this->Crud->read_field('id', $role_id, 'access_role', 'name') == 'Marketer') {
						$p_data['is_marketer'] = 1;
					}
					if (!empty($password))
						$p_data['password'] = md5($password);

					// check if already exist
					if (!empty($user_id)) {
						$upd_rec = $this->Crud->updates('id', $user_id, $table, $p_data);
						if ($upd_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $user_id, 'user', 'fullname');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' updated Administrator ' . $code . ' Record';
							$this->Crud->activity('setup', $user_id, $action);

							echo $this->Crud->msg('success', 'Record Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if ($this->Crud->check('email', $email, 'user') > 0) {
							echo $this->Crud->msg('danger', 'Record Already Exist');
						} else {
							$p_data['is_admin'] = 1;
							$p_data['reg_date'] = date(fdate);
							$ins_rec = $this->Crud->create($table, $p_data);
							if ($ins_rec > 0) {
								///// store activities
								$code = $this->Crud->read_field('id', $ins_rec, 'user', 'fullname');
								$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
								$action = $by . ' created Administrator ' . $code . ' Record';
								$this->Crud->activity('setup', $ins_rec, $action);

								echo $this->Crud->msg('success', 'Record Created');
								echo '<script>location.reload(false);</script>';
							} else {
								echo $this->Crud->msg('danger', 'Please try later');
							}
						}
					}

					die;
				}
			}
		}

		// all ages
		$admins = '';
		$query = $this->Crud->read_single_order('is_admin', 1, $table, 'id', 'ASC');
		if (!empty($query)) {
			foreach ($query as $q) {
				$a_id = $q->id;
				$a_fullname = $q->fullname;
				$a_email = $q->email;
				$a_role_id = $q->role_id;

				// can update
				$edit_btn = '';
				if ($role_u) {
					$edit_btn = '
                        <a href="javascript:;" class="text-primary dropdown-item pop" pageTitle="Update Record" pageName="' . base_url('setup/admin/manage/edit/' . $a_id) . '" pageSize="modal-md">
                            <i class="anticon anticon-edit"></i>
                            <span class="m-l-5">Edit</span>
                        </a>
                    ';
				}

				// can delete
				$del_btn = '';
				if ($role_d) {
					$del_btn = '
                        <a href="javascript:;" class="text-danger dropdown-item pop" pageTitle="Delete Record" pageName="' . base_url('setup/admin/manage/delete/' . $a_id) . '" pageSize="modal-md">
                            <i class="anticon anticon-delete"></i>
                            <span class="m-l-5">Delete</span>
                        </a>
                    ';
				}

				// manage button
				$btns = '';
				if ($edit_btn || $del_btn) {
					$btns = '
                        <div class="dropdown dropdown-animated scale-left">
                            <a class="btn btn-default btn-sm text-primary font-size-18" href="javascript:;"
                                data-toggle="dropdown">
                                <i class="anticon anticon-ellipsis"></i>
                            </a>
                            <div class="dropdown-menu">
                                ' . $edit_btn . '
                                ' . $del_btn . '
                            </div>
                        </div>
                    ';
				}

				$admins .= '
					<tr>
						<td>' . $a_fullname . '</td>
						<td>' . $a_email . '</td>
						<td>' . $this->Crud->read_field('id', $a_role_id, 'access_role', 'name') . '</td>
						<td class="text-center">' . $btns . '</td>
					</tr>
				';
			}
		}
		$data['admins'] = $admins;

		if ($param1 == 'manage') { // view for form data posting
			return view('setup/admin_form', $data);
		} else { // view for main page

			$data['title'] = 'Administrators | ' . app_name;
			$data['page_active'] = 'setup/admin';
			return view('setup/admin', $data);
		}
	}

	public function church_admin($param1 = '', $param2 = '', $param3 = '')
	{
		$db = \Config\Database::connect();

		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'setup/church_admin', 'create');
		$role_r = $this->Crud->module($role_id, 'setup/church_admin', 'read');
		$role_u = $this->Crud->module($role_id, 'setup/church_admin', 'update');
		$role_d = $this->Crud->module($role_id, 'setup/church_admin', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'user';

		$form_link = site_url('setup/church_admin/');
		if ($param1) {
			$form_link .= $param1 . '/';
		}
		if ($param2) {
			$form_link .= $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_user_id');
						///// store activities
						$code = $this->Crud->read_field('id', $del_id, 'user', 'fullname');
						$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
						$action = $by . ' deleted Church Administrator ' . $code . ' Account';

						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							$this->Crud->activity('setup', $del_id, $action);

							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_fullname'] = $e->fullname;
								$data['e_email'] = $e->email;
								$data['e_role_id'] = $e->role_id;
								$data['e_church_id'] = $e->church_id;
								$data['e_ban'] = $e->ban;
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$user_id = $this->request->getVar('user_id');
					$fullname = $this->request->getVar('fullname');
					$email = $this->request->getVar('email');
					$ban = $this->request->getVar('ban');
					$password = $this->request->getVar('password');
					$church_id = $this->request->getVar('church_id');

					$p_data['fullname'] = $fullname;
					$p_data['church_id'] = $church_id;
					$p_data['email'] = $email;
					$p_data['ban'] = $ban;
					$is_madmin = $this->Crud->read_field('id', $log_id, 'user', 'is_ministry_admin');
					if ($is_madmin > 0) {
						$p_data['madmin_id'] = $this->Crud->read_field('id', $log_id, 'user', 'ministry_id');
					}
					if (!empty($password))
						$p_data['password'] = md5($password);

					// check if already exist
					if (!empty($user_id)) {
						$upd_rec = $this->Crud->updates('id', $user_id, $table, $p_data);
						if ($upd_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $user_id, 'user', 'fullname');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' updated Church Administrator ' . $code . ' Record';
							$this->Crud->activity('setup', $user_id, $action);

							echo $this->Crud->msg('success', 'Record Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if ($this->Crud->check('email', $email, 'user') > 0) {
							echo $this->Crud->msg('danger', 'Record Already Exist');
						} else {
							$p_data['role_id'] = $this->Crud->read_field('name', 'Church Administrator', 'access_role', 'id');
							$p_data['is_church_admin'] = 1;
							$p_data['reg_date'] = date(fdate);
							$ins_rec = $this->Crud->create($table, $p_data);
							if ($ins_rec > 0) {
								///// store activities
								$code = $this->Crud->read_field('id', $ins_rec, 'user', 'fullname');
								$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
								$action = $by . ' created Church Administrator ' . $code . ' Record';
								$this->Crud->activity('setup', $ins_rec, $action);

								echo $this->Crud->msg('success', 'Record Created');
								echo '<script>location.reload(false);</script>';
							} else {
								echo $this->Crud->msg('danger', 'Please try later');
							}
						}
					}

					die;
				}
			}
		}

		// all ages
		$admins = '';
		$query = $this->Crud->read_single_order('is_church_admin', 1, $table, 'id', 'ASC');
		$is_madmin = $this->Crud->read_field('id', $log_id, 'user', 'is_ministry_admin');
		$ministry_id = $this->Crud->read_field('id', $log_id, 'user', 'ministry_id');
		if ($is_madmin > 0) {
			$query = $this->Crud->read2_order('is_church_admin', 1, 'madmin_id', $ministry_id, $table, 'id', 'ASC');
		}


		if (!empty($query)) {
			foreach ($query as $q) {
				$a_id = $q->id;
				$a_fullname = $q->fullname;
				$a_email = $q->email;
				$a_role_id = $q->role_id;
				$a_church_id = $q->church_id;
				$a_ban = $q->ban;

				// Get church name
				$church_name = $this->Crud->read_field('id', $a_church_id, 'church', 'name');

				// Get account status
				$status_badge = ($a_ban == 0)
					? '<span class="badge badge-success">Active</span>'
					: '<span class="badge badge-danger">Suspended</span>';

				// Edit button
				$edit_btn = '';
				if ($role_u) {
					$edit_btn = '
						<a href="javascript:;" class="text-primary dropdown-item pop" pageTitle="Update Record" pageName="' . base_url('setup/church_admin/manage/edit/' . $a_id) . '" pageSize="modal-md">
							<i class="anticon anticon-edit"></i>
							<span class="m-l-5">Edit</span>
						</a>
					';
				}

				// Delete button
				$del_btn = '';
				if ($role_d) {
					$del_btn = '
						<a href="javascript:;" class="text-danger dropdown-item pop" pageTitle="Delete Record" pageName="' . base_url('setup/church_admin/manage/delete/' . $a_id) . '" pageSize="modal-md">
							<i class="anticon anticon-delete"></i>
							<span class="m-l-5">Delete</span>
						</a>
					';
				}

				// Dropdown menu
				$btns = '';
				if ($edit_btn || $del_btn) {
					$btns = '
						<div class="dropdown dropdown-animated scale-left">
							<a class="btn btn-default btn-sm text-primary font-size-18" href="javascript:;" data-toggle="dropdown">
								<i class="anticon anticon-ellipsis"></i>
							</a>
							<div class="dropdown-menu">
								' . $edit_btn . '
								' . $del_btn . '
							</div>
						</div>
					';
				}

				// Final row output
				$admins .= '
					<tr>
						<td>' . $a_fullname . '</td>
						<td>' . $a_email . '</td>
						<td>' . $this->Crud->read_field('id', $a_role_id, 'access_role', 'name') . '</td>
						<td>' . $church_name . '</td>
						<td>' . $status_badge . '</td>
						<td class="text-center">' . $btns . '</td>
					</tr>
				';
			}
		}

		$data['admins'] = $admins;

		if ($param1 == 'manage') { // view for form data posting
			return view('setup/church_admin_form', $data);
		} else { // view for main page

			$data['title'] = 'Church Administrators | ' . app_name;
			$data['page_active'] = 'setup/church_admin';
			return view('setup/church_admin', $data);
		}
	}

	//// AGE
	public function age($param1 = '', $param2 = '', $param3 = '')
	{
		$db = \Config\Database::connect();

		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'setup/age', 'create');
		$role_r = $this->Crud->module($role_id, 'setup/age', 'read');
		$role_u = $this->Crud->module($role_id, 'setup/age', 'update');
		$role_d = $this->Crud->module($role_id, 'setup/age', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'age';

		$form_link = site_url('setup/age/');
		if ($param1) {
			$form_link .= $param1 . '/';
		}
		if ($param2) {
			$form_link .= $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_age_id');
						///// store activities
						$code = $this->Crud->read_field('id', $del_id, 'age', 'name');
						$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
						$action = $by . ' deleted Age ' . $code . ' Record';

						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							$this->Crud->activity('setup', $del_id, $action);

							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_name'] = $e->name;
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$age_id = $this->request->getVar('age_id');
					$name = $this->request->getVar('name');

					$p_data['name'] = $name;

					// check if already exist
					if (!empty($age_id)) {
						$upd_rec = $this->Crud->updates('id', $age_id, $table, $p_data);
						if ($upd_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $age_id, 'age', 'name');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' updated Age ' . $code . ' Record';
							$this->Crud->activity('setup', $age_id, $action);

							echo $this->Crud->msg('success', 'Record Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						$ins_rec = $this->Crud->create('age', $p_data);
						if ($ins_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $ins_rec, 'age', 'name');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' created Age ' . $code . ' Record';
							$this->Crud->activity('setup', $ins_rec, $action);

							echo $this->Crud->msg('success', 'Record Created');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
					}

					die;
				}
			}
		}

		// all ages
		$ages = '';
		$query = $this->Crud->read_order($table, 'id', 'ASC');
		if (!empty($query)) {
			foreach ($query as $q) {
				$a_id = $q->id;
				$a_name = $q->name;

				$accounts = $this->db->table('child')->where('age_id', $a_id)->countAllResults();

				// can update
				$edit_btn = '';
				if ($role_u) {
					$edit_btn = '
                        <a href="javascript:;" class="text-primary dropdown-item pop" pageTitle="Update Record" pageName="' . base_url('setup/age/manage/edit/' . $a_id) . '" pageSize="modal-sm">
                            <i class="anticon anticon-edit"></i>
                            <span class="m-l-5">Edit</span>
                        </a>
                    ';
				}

				// can delete
				$del_btn = '';
				if ($role_d) {
					$del_btn = '
                        <a href="javascript:;" class="text-danger dropdown-item pop" pageTitle="Delete Record" pageName="' . base_url('setup/age/manage/delete/' . $a_id) . '" pageSize="modal-sm">
                            <i class="anticon anticon-delete"></i>
                            <span class="m-l-5">Delete</span>
                        </a>
                    ';
				}

				// manage button
				$btns = '';
				if ($edit_btn || $del_btn) {
					$btns = '
                        <div class="dropdown dropdown-animated scale-left">
                            <a class="btn btn-default btn-sm text-primary font-size-18" href="javascript:;"
                                data-toggle="dropdown">
                                <i class="anticon anticon-ellipsis"></i>
                            </a>
                            <div class="dropdown-menu">
                                ' . $edit_btn . '
                                ' . $del_btn . '
                            </div>
                        </div>
                    ';
				}

				$ages .= '
					<tr>
						<td>' . $a_name . '</td>
						<td>' . number_format($accounts) . '</td>
						<td class="text-center">' . $btns . '</td>
					</tr>
				';
			}
		}
		$data['ages'] = $ages;

		if ($param1 == 'manage') { // view for form data posting
			return view('setup/age_form', $data);
		} else { // view for main page

			$data['title'] = 'Age Categories | ' . app_name;
			$data['page_active'] = 'setup/age';
			return view('setup/age', $data);
		}
	}

	//// SUBSCRIPTIONS
	public function subscription($param1 = '', $param2 = '', $param3 = '')
	{
		$db = \Config\Database::connect();

		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'setup/subscription', 'create');
		$role_r = $this->Crud->module($role_id, 'setup/subscription', 'read');
		$role_u = $this->Crud->module($role_id, 'setup/subscription', 'update');
		$role_d = $this->Crud->module($role_id, 'setup/subscription', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'subscription';

		$form_link = site_url('setup/subscription/');
		if ($param1) {
			$form_link .= $param1 . '/';
		}
		if ($param2) {
			$form_link .= $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_subscription_id');
						///// store activities
						$code = $this->Crud->read_field('id', $del_id, $table, 'name');
						$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
						$action = $by . ' deleted Subscription ' . $code . ' Record';

						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							$this->Crud->activity('setup', $del_id, $action);

							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_name'] = $e->name;
								$data['e_amount'] = $e->amount;
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$subscription_id = $this->request->getVar('subscription_id');
					$name = $this->request->getVar('name');
					$amount = (float) $this->request->getVar('amount');

					$rate = 700;
					$naira = $amount * $rate;

					$p_data['name'] = $name;
					$p_data['amount'] = $amount;
					$p_data['naira'] = $naira;
					$p_data['rate'] = $rate;

					// check if already exist
					if (!empty($subscription_id)) {
						$upd_rec = $this->Crud->updates('id', $subscription_id, $table, $p_data);
						if ($upd_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $subscription_id, $table, 'name');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' updated Subscription ' . $code . ' Record';
							$this->Crud->activity('setup', $subscription_id, $action);

							echo $this->Crud->msg('success', 'Record Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						$ins_rec = $this->Crud->create('subscription', $p_data);
						if ($ins_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $ins_rec, $table, 'name');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' Created Subscription ' . $code . ' Record';
							$this->Crud->activity('setup', $ins_rec, $action);

							echo $this->Crud->msg('success', 'Record Created');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
					}

					die;
				}
			}
		}

		// all ages
		$subscriptions = '';
		$query = $this->Crud->read_order($table, 'id', 'DESC');
		if (!empty($query)) {
			foreach ($query as $q) {
				$a_id = $q->id;
				$a_name = $q->name;
				$a_amount = $q->amount;

				$accounts = $this->db->table('user')->where('sub_id', $a_id)->countAllResults();

				// can update
				$edit_btn = '';
				if ($role_u) {
					$edit_btn = '
                        <a href="javascript:;" class="text-primary dropdown-item pop" pageTitle="Update Record" pageName="' . base_url('setup/subscription/manage/edit/' . $a_id) . '" pageSize="modal-md">
                            <i class="anticon anticon-edit"></i>
                            <span class="m-l-5">Edit</span>
                        </a>
                    ';
				}

				// can delete
				$del_btn = '';
				if ($role_d) {
					$del_btn = '
                        <a href="javascript:;" class="text-danger dropdown-item pop" pageTitle="Delete Record" pageName="' . base_url('setup/subscription/manage/delete/' . $a_id) . '" pageSize="modal-md">
                            <i class="anticon anticon-delete"></i>
                            <span class="m-l-5">Delete</span>
                        </a>
                    ';
				}

				// manage button
				$btns = '';
				if ($edit_btn || $del_btn) {
					$btns = '
                        <div class="dropdown dropdown-animated scale-left">
                            <a class="btn btn-default btn-sm text-primary font-size-18" href="javascript:;"
                                data-toggle="dropdown">
                                <i class="anticon anticon-ellipsis"></i>
                            </a>
                            <div class="dropdown-menu">
                                ' . $edit_btn . '
                                ' . $del_btn . '
                            </div>
                        </div>
                    ';
				}

				$subscriptions .= '
					<tr>
						<td>' . $a_name . '</td>
						<td class="text-right">$' . number_format($a_amount, 2) . '</td>
						<td>' . number_format($accounts) . '</td>
						<td class="text-center">' . $btns . '</td>
					</tr>
				';
			}
		}
		$data['subscriptions'] = $subscriptions;

		if ($param1 == 'manage') { // view for form data posting
			return view('setup/subscription_form', $data);
		} else { // view for main page

			$data['title'] = 'Subscription Categories | ' . app_name;
			$data['page_active'] = 'setup/subscription';
			return view('setup/subscription', $data);
		}
	}

	//// CATEGORY
	public function category($param1 = '', $param2 = '', $param3 = '')
	{
		$db = \Config\Database::connect();

		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'setup/category', 'create');
		$role_r = $this->Crud->module($role_id, 'setup/category', 'read');
		$role_u = $this->Crud->module($role_id, 'setup/category', 'update');
		$role_d = $this->Crud->module($role_id, 'setup/category', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'category';

		$form_link = site_url('setup/category/');
		if ($param1) {
			$form_link .= $param1 . '/';
		}
		if ($param2) {
			$form_link .= $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_category_id');
						///// store activities
						$code = $this->Crud->read_field('id', $del_id, $table, 'name');
						$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
						$action = $by . ' Deleted Category ' . $code . ' Record';

						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							$this->Crud->activity('setup', $del_id, $action);

							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_name'] = $e->name;
								$data['e_type'] = $e->type;
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$category_id = $this->request->getVar('category_id');
					$name = $this->request->getVar('name');
					$type = $this->request->getVar('type');

					$p_data['name'] = $name;
					$p_data['type'] = $type;

					// check if already exist
					if (!empty($category_id)) {
						$upd_rec = $this->Crud->updates('id', $category_id, $table, $p_data);
						if ($upd_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $category_id, $table, 'name');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' updated Category ' . $code . ' Record';
							$this->Crud->activity('setup', $category_id, $action);

							echo $this->Crud->msg('success', 'Record Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if ($this->Crud->check2('type', $type, 'name', $name, $table) > 0) {
							echo $this->Crud->msg('danger', 'Record Already Exist');
							die;
						}

						$ins_rec = $this->Crud->create($table, $p_data);
						if ($ins_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $ins_rec, $table, 'name');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' Created Category ' . $code . ' Record';
							$this->Crud->activity('setup', $ins_rec, $action);

							echo $this->Crud->msg('success', 'Record Created');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
					}

					die;
				}
			}
		}

		// all categories
		$categories = '';
		$query = $this->Crud->read_order($table, 'id', 'DESC');
		if (!empty($query)) {
			foreach ($query as $q) {
				$a_id = $q->id;
				$a_type = $q->type;
				$a_name = $q->name;

				// can update
				$edit_btn = '';
				if ($role_u) {
					$edit_btn = '
                        <a href="javascript:;" class="text-primary dropdown-item pop" pageTitle="Update Record" pageName="' . base_url('setup/category/manage/edit/' . $a_id) . '" pageSize="modal-md">
                            <i class="anticon anticon-edit"></i>
                            <span class="m-l-5">Edit</span>
                        </a>
                    ';
				}

				$metric = $this->Crud->check('category_id', $a_id, 'video');

				// can delete
				$del_btn = '';
				if ($role_d) {
					$del_btn = '
                        <a href="javascript:;" class="text-danger dropdown-item pop" pageTitle="Delete Record" pageName="' . base_url('setup/category/manage/delete/' . $a_id) . '" pageSize="modal-md">
                            <i class="anticon anticon-delete"></i>
                            <span class="m-l-5">Delete</span>
                        </a>
                    ';
				}

				// manage button
				$btns = '';
				if ($edit_btn || $del_btn) {
					$btns = '
                        <div class="dropdown dropdown-animated scale-left">
                            <a class="btn btn-default btn-sm text-primary font-size-18" href="javascript:;"
                                data-toggle="dropdown">
                                <i class="anticon anticon-ellipsis"></i>
                            </a>
                            <div class="dropdown-menu">
                                ' . $edit_btn . '
                                ' . $del_btn . '
                            </div>
                        </div>
                    ';
				}

				$categories .= '
					<tr>
						<td>' . ucwords($a_type) . '</td>
						<td>' . $a_name . '<br><span class="text-primary">' . $metric . ' Video</span></td>
						<td class="text-center">' . $btns . '</td>
					</tr>
				';
			}
		}
		$data['categories'] = $categories;

		if ($param1 == 'manage') { // view for form data posting
			return view('setup/category_form', $data);
		} else { // view for main page

			$data['title'] = 'Categories | ' . app_name;
			$data['page_active'] = 'setup/category';
			return view('setup/category', $data);
		}
	}

	//// PRODUCTION
	public function production($param1 = '', $param2 = '', $param3 = '')
	{
		$db = \Config\Database::connect();

		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'setup/production', 'create');
		$role_r = $this->Crud->module($role_id, 'setup/production', 'read');
		$role_u = $this->Crud->module($role_id, 'setup/production', 'update');
		$role_d = $this->Crud->module($role_id, 'setup/production', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'production';

		$form_link = site_url('setup/production/');
		if ($param1) {
			$form_link .= $param1 . '/';
		}
		if ($param2) {
			$form_link .= $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_production_id');
						///// store activities
						$code = $this->Crud->read_field('id', $del_id, $table, 'name');
						$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
						$action = $by . ' deleted Production ' . $code . ' Record';

						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							$this->Crud->activity('setup', $del_id, $action);

							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_name'] = $e->name;
								$data['e_image'] = $e->image;
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$production_id = $this->request->getVar('production_id');
					$name = $this->request->getVar('name');
					$img = $this->request->getVar('img');

					/// upload image
					if (file_exists($this->request->getFile('pics'))) {
						$path = 'assets/images/videos/';
						$file = $this->request->getFile('pics');
						$getImg = $this->Crud->img_upload($path, $file);
						$img = $getImg->path;
					}

					$p_data['name'] = $name;
					$p_data['image'] = $img;

					// check if already exist
					if (!empty($production_id)) {
						$upd_rec = $this->Crud->updates('id', $production_id, $table, $p_data);
						if ($upd_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $production_id, $table, 'name');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' updated Production ' . $code . ' Record';
							$this->Crud->activity('setup', $production_id, $action);

							echo $this->Crud->msg('success', 'Record Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if ($this->Crud->check('name', $name, $table) > 0) {
							echo $this->Crud->msg('danger', 'Record Already Exist');
							die;
						}

						$ins_rec = $this->Crud->create($table, $p_data);
						if ($ins_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $ins_rec, $table, 'name');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' Created Production ' . $code . ' Record';
							$this->Crud->activity('setup', $ins_rec, $action);

							echo $this->Crud->msg('success', 'Record Created');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
					}

					die;
				}
			}
		}

		// all productions
		$productions = '';
		$query = $this->Crud->read_order($table, 'id', 'DESC');
		if (!empty($query)) {
			foreach ($query as $q) {
				$a_id = $q->id;
				$a_name = $q->name;
				$a_img = $q->image;
				if (!empty($a_img))
					$a_img = '<img alt="" src="' . site_url($a_img) . '" style="max-width:100%;" />';

				// can update
				$edit_btn = '';
				if ($role_u) {
					$edit_btn = '
                        <a href="javascript:;" class="text-primary dropdown-item pop" pageTitle="Update Record" pageName="' . base_url('setup/production/manage/edit/' . $a_id) . '" pageSize="modal-md">
                            <i class="anticon anticon-edit"></i>
                            <span class="m-l-5">Edit</span>
                        </a>
                    ';
				}

				// can delete
				$del_btn = '';
				if ($role_d) {
					$del_btn = '
                        <a href="javascript:;" class="text-danger dropdown-item pop" pageTitle="Delete Record" pageName="' . base_url('setup/production/manage/delete/' . $a_id) . '" pageSize="modal-md">
                            <i class="anticon anticon-delete"></i>
                            <span class="m-l-5">Delete</span>
                        </a>
                    ';
				}

				// manage button
				$btns = '';
				if ($edit_btn || $del_btn) {
					$btns = '
                        <div class="dropdown dropdown-animated scale-left">
                            <a class="btn btn-default btn-sm text-primary font-size-18" href="javascript:;"
                                data-toggle="dropdown">
                                <i class="anticon anticon-ellipsis"></i>
                            </a>
                            <div class="dropdown-menu">
                                ' . $edit_btn . '
                                ' . $del_btn . '
                            </div>
                        </div>
                    ';
				}

				$productions .= '
					<tr>
						<td>' . $a_name . '</td>
						<td>' . $a_img . '</td>
						<td class="text-center">' . $btns . '</td>
					</tr>
				';
			}
		}
		$data['productions'] = $productions;

		if ($param1 == 'manage') { // view for form data posting
			return view('setup/production_form', $data);
		} else { // view for main page

			$data['title'] = 'Productions | ' . app_name;
			$data['page_active'] = 'setup/production';
			return view('setup/production', $data);
		}
	}

	//// VIDEOS
	public function videos($param1 = '', $param2 = '', $param3 = '')
	{
		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'setup/videos', 'create');
		$role_r = $this->Crud->module($role_id, 'setup/videos', 'read');
		$role_u = $this->Crud->module($role_id, 'setup/videos', 'update');
		$role_d = $this->Crud->module($role_id, 'setup/videos', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'video';

		$form_link = site_url('setup/videos/');
		if ($param1) {
			$form_link .= $param1 . '/';
		}
		if ($param2) {
			$form_link .= $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_video_id');
						///// store activities
						$code = $this->Crud->read_field('id', $del_id, $table, 'title');
						$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
						$action = $by . ' deleted Video ' . $code . ' Record';

						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							$this->Crud->activity('setup', $del_id, $action);
							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_category_id'] = $e->category_id;
								$data['e_production_id'] = $e->production_id;
								$data['e_language_id'] = $e->language_id;
								$data['e_age_id'] = $e->age_id;
								$data['e_title'] = $e->title;
								$data['e_url'] = $e->url;
								$data['e_free'] = $e->free;

								if (file_exists($e->img)) {
									$e_img = $e->img;
								} else {
									$e_img = '';
								}
								$data['e_img'] = $e_img;

								$e_tags = json_decode($e->tag);
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$video_id = $this->request->getVar('video_id');
					$age_id = $this->request->getVar('age_id');
					$category_id = $this->request->getVar('category_id');
					$production_id = $this->request->getVar('production_id');
					$language_id = $this->request->getVar('language_id');
					$title = $this->request->getVar('title');
					if (!empty($this->request->getVar('free'))) {
						$free = 1;
					} else {
						$free = 0;
					}
					$url = $this->request->getVar('url');
					$img = $this->request->getVar('img');

					/// upload image
					if (file_exists($this->request->getFile('pics'))) {
						$path = 'assets/images/videos/';
						$file = $this->request->getFile('pics');
						$getImg = $this->Crud->img_upload($path, $file);
						$img = $getImg->path;
					}

					// echo $path;

					// upload video
					if (file_exists($this->request->getFile('video'))) {
						$vpath = 'assets/images/videos/';
						$vid = $this->request->getFile('video');
						$getVid = $this->Crud->file_upload($vpath, $vid);

						if (!empty($getVid->path)) {
							$url = $getVid->path;
						}
					}

					if (!$category_id || !$production_id || !$title || !$url || !$img) {
						echo $this->Crud->msg('danger', 'All fields are required');
						die;
					}

					// check tags, and save any not in database
					$tags = array();
					$video_tags = $this->session->get('video_tags');
					if (!empty($video_tags)) {
						foreach ($video_tags as $tag) {
							$tags[] = $tag;
							if ($this->Crud->check('name', $tag, 'tag') <= 0) {
								$this->Crud->create('tag', ['name' => $tag]);
							}
						}
					}

					$ins_data['age_id'] = $age_id;
					$ins_data['category_id'] = $category_id;
					$ins_data['production_id'] = $production_id;
					$ins_data['language_id'] = $language_id;
					$ins_data['title'] = $title;
					$ins_data['free'] = $free;
					$ins_data['url'] = $url;
					$ins_data['img'] = $img;
					if (!empty($tags))
						$ins_data['tag'] = json_encode($tags);

					// do create or update
					if ($video_id) {
						$upd_rec = $this->Crud->updates('id', $video_id, $table, $ins_data);
						if ($upd_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $video_id, $table, 'title');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' updated Video ' . $code . ' Record';
							$this->Crud->activity('setup', $video_id, $action);

							echo $this->Crud->msg('success', 'Record Updated');
							// remove tags
							$this->session->remove('video_tags');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if ($this->Crud->check('url', $url, $table) > 0) {
							echo $this->Crud->msg('warning', 'Record Already Exist');
						} else {
							$ins_data['reg_date'] = date(fdate);
							$ins_rec = $this->Crud->create($table, $ins_data);
							if ($ins_rec > 0) {
								///// store activities
								$code = $this->Crud->read_field('id', $ins_rec, $table, 'title');
								$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
								$action = $by . ' Created Video ' . $code . ' Record';
								$this->Crud->activity('setup', $ins_rec, $action);

								echo $this->Crud->msg('success', 'Record Created');
								// remove tags
								$this->session->remove('video_tags');
								echo '<script>location.reload(false);</script>';
							} else {
								echo $this->Crud->msg('danger', 'Please try later');
							}
						}
					}

					die;
				}

				$data['ages'] = $this->Crud->read_order('age', 'name', 'asc');
				$data['categories'] = $this->Crud->read_order('category', 'name', 'asc');
				$data['productions'] = $this->Crud->read_order('production', 'name', 'asc');
				$data['languages'] = $this->Crud->read_order('language', 'name', 'asc');

				// load all tags
				$tags = array();
				$alltags = $this->Crud->read_order('tag', 'name', 'asc');
				if (!empty($alltags)) {
					foreach ($alltags as $t) {
						$tags[] = $t->name;
					}
				}
				$data['tags'] = json_encode($tags);

				//check post tag and load
				if (!empty($e_tags))
					$this->session->set('video_tags', $e_tags);
			}
		}

		// record listing
		if ($param1 == 'list') {
			// DataTable parameters
			$table = 'video';
			$filterValue = $this->request->getPost('filterValue');
			$column_order = array('title');
			$column_search = array('title');
			$order = array('id' => 'desc');
			if (!empty($filterValue) && $filterValue != 'all') {
				$where = array('language_id' => $filterValue);
			} else {
				$where = '';
			}

			// load data into table
			$list = $this->Crud->datatable_load($table, $column_order, $column_search, $order, $where);
			$data = array();
			// $no = $_POST['start'];
			$count = 1;
			foreach ($list as $item) {
				$id = $item->id;
				$age_id = $item->age_id;
				$category_id = $item->category_id;
				$language_id = $item->language_id;
				$production_id = $item->production_id;
				$title = $item->title;
				$url = $item->url;
				$img = $item->img;
				$views = $item->views;
				$free = $item->free;
				$reg_date = date('M d, Y h:sA', strtotime($item->reg_date));

				$age = $this->Crud->read_field('id', $age_id, 'age', 'name');
				$category = $this->Crud->read_field('id', $category_id, 'category', 'name');
				$language = $this->Crud->read_field('id', $language_id, 'language', 'name');
				$production = $this->Crud->read_field('id', $production_id, 'production', 'name');

				// description
				if (empty($age))
					$age = 'All';
				$desc = 'For ' . $age;

				$play = '';
				if (!empty($url))
					$play = ' <a href="' . site_url($url) . '" target="_blank"><i class="anticon anticon-play-circle"></i></a>';

				// free video
				if ($free) {
					$freeVideo = '<span class="badge badge-success">FREE</span>';
				} else {
					$freeVideo = '<span class="badge badge-primary">PAID</span>';
				}

				// can update
				$edit_btn = '';
				if ($role_u) {
					$edit_btn = '
                        <a href="javascript:;" class="text-primary dropdown-item pop" pageTitle="Update Record" pageName="' . base_url('setup/videos/manage/edit/' . $id) . '" pageSize="modal-lg">
                            <i class="anticon anticon-edit"></i>
                            <span class="m-l-5">Edit</span>
                        </a>
                    ';
				}

				// can delete
				$del_btn = '';
				if ($role_d) {
					$del_btn = '
                        <a href="javascript:;" class="text-danger dropdown-item pop" pageTitle="Delete Record" pageName="' . base_url('setup/videos/manage/delete/' . $id) . '" pageSize="modal-sm">
                            <i class="anticon anticon-delete"></i>
                            <span class="m-l-5">Delete</span>
                        </a>
                    ';
				}

				// manage button
				$btns = '';
				if ($edit_btn || $del_btn) {
					$btns = '
                        <div class="dropdown dropdown-animated scale-left">
                            <a class="btn btn-default btn-sm text-primary font-size-18" href="javascript:;"
                                data-toggle="dropdown">
                                <i class="anticon anticon-ellipsis"></i>
                            </a>
                            <div class="dropdown-menu">
                                ' . $edit_btn . '
                                ' . $del_btn . '
                            </div>
                        </div>
                    ';
				}

				$row = array();
				$row[] = $reg_date;
				$row[] = $category . ' <br><small class="text-primary">' . $language . '</small>';
				$row[] = $production;
				$row[] = '<div class="text-muted"><img alt="" src="' . site_url($img) . '" style="width:75px;" /></div>';
				$row[] = $title . $play . '<div class="small text-muted">' . $freeVideo . ' ' . $desc . '</div>';
				$row[] = number_format($views);
				$row[] = '<div class="text-muted">' . $btns . '</div>';

				$data[] = $row;
				$count += 1;
			}

			$output = array(
				"draw" => intval($_POST['draw']),
				"recordsTotal" => $this->Crud->datatable_count($table, $where),
				"recordsFiltered" => $this->Crud->datatable_filtered($table, $column_order, $column_search, $order, $where),
				"data" => $data,
			);

			//output to json format
			echo json_encode($output);
			exit;
		}

		if ($param1 == 'manage') { // view for form data posting
			return view('setup/videos_form', $data);
		} else { // view for main page
			// for datatable
			$data['table_rec'] = 'setup/videos/list'; // ajax table
			$data['order_sort'] = ''; // default ordering (0, 'asc')
			$data['no_sort'] = '1, 3'; // sort disable columns (1,3,5)

			$data['title'] = 'Videos | ' . app_name;
			$data['page_active'] = 'setup/videos';
			return view('setup/videos', $data);
		}
	}

	//// SHORTS
	public function shorts($param1 = '', $param2 = '', $param3 = '')
	{
		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'setup/shorts', 'create');
		$role_r = $this->Crud->module($role_id, 'setup/shorts', 'read');
		$role_u = $this->Crud->module($role_id, 'setup/shorts', 'update');
		$role_d = $this->Crud->module($role_id, 'setup/shorts', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'reel';

		$form_link = site_url('setup/shorts/');
		if ($param1) {
			$form_link .= $param1 . '/';
		}
		if ($param2) {
			$form_link .= $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_video_id');
						///// store activities
						$code = $this->Crud->read_field('id', $del_id, $table, 'title');
						$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
						$action = $by . ' deleted Video ' . $code . ' Record';

						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							$this->Crud->activity('setup', $del_id, $action);
							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_video_id'] = $e->video_id;
								$data['e_category_id'] = $e->category_id;
								$data['e_production_id'] = $e->production_id;
								$data['e_language_id'] = $e->language_id;
								$data['e_title'] = $e->title;
								$data['e_reel_url'] = $e->reel_url;
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$reel_id = $this->request->getVar('reel_id');
					$video_id = $this->request->getVar('video_id'); // original video
					$category_id = $this->request->getVar('category_id');
					$production_id = $this->request->getVar('production_id');
					$language_id = $this->request->getVar('language_id');
					$title = $this->request->getVar('title');
					$reel_url = $this->request->getVar('reel_url');

					// upload reel file (strictly video)
					$file = $this->request->getFile('reel');
					if ($file && $file->isValid()) {
						$mimeType = $file->getMimeType();
						if (strpos($mimeType, 'video/') !== 0) {
							echo $this->Crud->msg('danger', 'Only video files are allowed');
							die;
						}

						$vpath = 'assets/videos/reels/';
						$upload = $this->Crud->file_upload($vpath, $file);
						if (!empty($upload->path)) {
							$reel_url = $upload->path;
						}
					}

					// validations
					if (!$video_id || !$category_id || !$production_id || !$language_id || !$title || !$reel_url) {
						echo $this->Crud->msg('danger', 'All fields are required');
						die;
					}

					// prepare data
					$ins_data = [
						'video_id' => $video_id,
						'category_id' => $category_id,
						'production_id' => $production_id,
						'language_id' => $language_id,
						'title' => $title,
						'reel_url' => $reel_url,
					];

					// do create or update
					if ($reel_id) {
						$upd_rec = $this->Crud->updates('id', $reel_id, $table, $ins_data);
						if ($upd_rec > 0) {
							$code = $this->Crud->read_field('id', $reel_id, $table, 'title');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' updated Reel ' . $code . ' Record';
							$this->Crud->activity('setup', $reel_id, $action);

							echo $this->Crud->msg('success', 'Record Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if ($this->Crud->check('title', $title, $table) > 0) {
							echo $this->Crud->msg('warning', 'Reel Already Exists');
						} else {
							$ins_data['reg_date'] = date(fdate);
							$ins_rec = $this->Crud->create($table, $ins_data);
							if ($ins_rec > 0) {
								$code = $this->Crud->read_field('id', $ins_rec, $table, 'title');
								$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
								$action = $by . ' created Reel ' . $code . ' Record';
								$this->Crud->activity('setup', $ins_rec, $action);

								echo $this->Crud->msg('success', 'Record Created');
								echo '<script>location.reload(false);</script>';
							} else {
								echo $this->Crud->msg('danger', 'Please try later');
							}
						}
					}

					die;
				}

				$data['categories'] = $this->Crud->read_order('category', 'name', 'asc');
				$data['productions'] = $this->Crud->read_order('production', 'name', 'asc');
				$data['languages'] = $this->Crud->read_order('language', 'name', 'asc');

			}
		}

		if ($param1 == 'getVideosByCatProd') {
			$category_id = $this->request->getPost('category_id');
			$production_id = $this->request->getPost('production_id');

			$videos = $this->Crud->read2('category_id', $category_id, 'production_id', $production_id, 'video');
			echo json_encode($videos);
			exit;


		}
		// record listing
		if ($param1 == 'list') {
			// DataTable parameters
			$table = 'pl_reel'; // ✅ use the reels table
			$filterValue = $this->request->getPost('filterValue');
			$column_order = array('title');
			$column_search = array('title');
			$order = array('id' => 'desc');

			if (!empty($filterValue) && $filterValue != 'all') {
				$where = array('language_id' => $filterValue);
			} else {
				$where = '';
			}

			// load data into table
			$list = $this->Crud->datatable_load($table, $column_order, $column_search, $order, $where);
			$data = array();
			$count = 1;

			foreach ($list as $item) {
				$id = $item->id;
				$video_id = $item->video_id;
				$category_id = $item->category_id;
				$language_id = $item->language_id;
				$production_id = $item->production_id;
				$title = $item->title;
				$reel_url = $item->reel_url;
				$views = $item->views;
				$likes = $item->likes;
				$reg_date = date('M d, Y h:i A', strtotime($item->reg_date));

				// fetch related labels
				$category = $this->Crud->read_field('id', $category_id, 'category', 'name');
				$language = $this->Crud->read_field('id', $language_id, 'language', 'name');
				$production = $this->Crud->read_field('id', $production_id, 'production', 'name');
				$original = $this->Crud->read_field('id', $video_id, 'pl_video', 'title');

				if (empty($category))
					$category = 'Uncategorized';
				if (empty($language))
					$language = 'Default';
				if (empty($production))
					$production = 'Unknown';

				// play link
				$play = '';
				if (!empty($reel_url)) {
					$play = ' <a href="' . site_url($reel_url) . '" target="_blank">
								<i class="anticon anticon-play-circle"></i>
							  </a>';
				}

				// manage buttons
				$edit_btn = $del_btn = '';
				if ($role_u) {
					$edit_btn = '
						<a href="javascript:;" class="text-primary dropdown-item pop" 
						   pageTitle="Update Reel" 
						   pageName="' . base_url('setup/shorts/manage/edit/' . $id) . '" 
						   pageSize="modal-lg">
							<i class="anticon anticon-edit"></i>
							<span class="m-l-5">Edit</span>
						</a>';
				}
				if ($role_d) {
					$del_btn = '
						<a href="javascript:;" class="text-danger dropdown-item pop" 
						   pageTitle="Delete Reel" 
						   pageName="' . base_url('setup/shorts/manage/delete/' . $id) . '" 
						   pageSize="modal-sm">
							<i class="anticon anticon-delete"></i>
							<span class="m-l-5">Delete</span>
						</a>';
				}

				$btns = '';
				if ($edit_btn || $del_btn) {
					$btns = '
						<div class="dropdown dropdown-animated scale-left">
							<a class="btn btn-default btn-sm text-primary font-size-18" href="javascript:;" data-toggle="dropdown">
								<i class="anticon anticon-ellipsis"></i>
							</a>
							<div class="dropdown-menu">
								' . $edit_btn . '
								' . $del_btn . '
							</div>
						</div>';
				}

				// prepare row
				$row = array();
				$row[] = $reg_date;
				$row[] = $category . ' <br><small class="text-primary">' . $language . '</small>';
				$row[] = $production;
				$row[] = '<div class="small text-muted">' . $original . '</div>'; // original video
				$row[] = $title . $play;
				$row[] = number_format($views);
				$row[] = number_format($likes);
				$row[] = '<div class="text-muted">' . $btns . '</div>';

				$data[] = $row;
				$count++;
			}

			$output = array(
				"draw" => intval($_POST['draw']),
				"recordsTotal" => $this->Crud->datatable_count($table, $where),
				"recordsFiltered" => $this->Crud->datatable_filtered($table, $column_order, $column_search, $order, $where),
				"data" => $data,
			);

			// output to json format
			echo json_encode($output);
			exit;
		}


		if ($param1 == 'manage') { // view for form data posting
			return view('setup/shorts_form', $data);
		} else { // view for main page
			// for datatable
			$data['table_rec'] = 'setup/shorts/list'; // ajax table
			$data['order_sort'] = '0'; // default ordering (0, 'asc')
			$data['no_sort'] = '7'; // sort disable columns (1,3,5)

			$data['title'] = 'Shorts | ' . app_name;
			$data['page_active'] = 'setup/shorts';
			return view('setup/shorts', $data);
		}
	}

	//// GAMES
	public function games($param1 = '', $param2 = '', $param3 = '')
	{
		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'setup/games', 'create');
		$role_r = $this->Crud->module($role_id, 'setup/games', 'read');
		$role_u = $this->Crud->module($role_id, 'setup/games', 'update');
		$role_d = $this->Crud->module($role_id, 'setup/games', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'game';

		$form_link = site_url('setup/games/');
		if ($param1) {
			$form_link .= $param1 . '/';
		}
		if ($param2) {
			$form_link .= $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_game_id');
						///// store activities
						$code = $this->Crud->read_field('id', $del_id, $table, 'title');
						$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
						$action = $by . ' deleted Game ' . $code . ' Record';

						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							$this->Crud->activity('setup', $del_id, $action);

							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_age_id'] = $e->age_id;
								$data['e_title'] = $e->title;

								if (file_exists($e->img)) {
									$e_img = $e->img;
								} else {
									$e_img = '';
								}
								$data['e_img'] = $e_img;
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$game_id = $this->request->getVar('game_id');
					$age_id = $this->request->getVar('age_id');
					$title = $this->request->getVar('title');
					$img = $this->request->getVar('img');

					/// upload image
					if (file_exists($this->request->getFile('pics'))) {
						$path = 'assets/images/games/';
						$file = $this->request->getFile('pics');
						$getImg = $this->Crud->img_upload($path, $file);
						$img = $getImg->path;
					}

					if (!$title || !$img) {
						echo $this->Crud->msg('danger', 'All fields are required');
						die;
					}

					$ins_data['age_id'] = $age_id;
					$ins_data['title'] = $title;
					$ins_data['url'] = 'games/' . $title;
					$ins_data['img'] = $img;

					// do create or update
					if ($game_id) {
						$upd_rec = $this->Crud->updates('id', $game_id, $table, $ins_data);
						if ($upd_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $game_id, $table, 'title');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' updated Game ' . $code . ' Record';
							$this->Crud->activity('setup', $game_id, $action);

							echo $this->Crud->msg('success', 'Record Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if ($this->Crud->check2('age_id', $age_id, 'title', $title, $table) > 0) {
							echo $this->Crud->msg('warning', 'Record Already Exist');
						} else {
							$ins_data['reg_date'] = date(fdate);
							$ins_rec = $this->Crud->create($table, $ins_data);
							if ($ins_rec > 0) {
								///// store activities
								$code = $this->Crud->read_field('id', $ins_rec, $table, 'title');
								$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
								$action = $by . ' Created Game ' . $code . ' Record';
								$this->Crud->activity('setup', $ins_rec, $action);

								echo $this->Crud->msg('success', 'Record Created');
								echo '<script>location.reload(false);</script>';
							} else {
								echo $this->Crud->msg('danger', 'Please try later');
							}
						}
					}

					die;
				}

				$data['ages'] = $this->Crud->read_order('age', 'name', 'asc');
			}
		}

		// record listing
		if ($param1 == 'list') {
			// DataTable parameters
			$table = 'game';
			$column_order = array('title');
			$column_search = array('title');
			$order = array('id' => 'desc');
			$where = '';

			// load data into table
			$list = $this->Crud->datatable_load($table, $column_order, $column_search, $order, $where);
			$data = array();
			// $no = $_POST['start'];
			$count = 1;
			foreach ($list as $item) {
				$id = $item->id;
				$age_id = $item->age_id;
				$title = $item->title;
				$url = $item->url;
				$img = $item->img;
				$views = $item->views;
				$reg_date = date('M d, Y h:sA', strtotime($item->reg_date));

				$age = $this->Crud->read_field('id', $age_id, 'age', 'name');

				// description
				if (empty($age))
					$age = 'All';
				$desc = 'For ' . $age;

				$play = '';
				if (!empty($url))
					$play = ' <a href="https://pcdl4kids.com/' . $url . '" target="_blank"><i class="anticon anticon-play-circle"></i></a>';

				// can update
				$edit_btn = '';
				if ($role_u) {
					$edit_btn = '
                        <a href="javascript:;" class="text-primary dropdown-item pop" pageTitle="Update Record" pageName="' . base_url('setup/games/manage/edit/' . $id) . '" pageSize="modal-lg">
                            <i class="anticon anticon-edit"></i>
                            <span class="m-l-5">Edit</span>
                        </a>
                    ';
				}

				// can delete
				$del_btn = '';
				if ($role_d) {
					$del_btn = '
                        <a href="javascript:;" class="text-danger dropdown-item pop" pageTitle="Delete Record" pageName="' . base_url('setup/games/manage/delete/' . $id) . '" pageSize="modal-sm">
                            <i class="anticon anticon-delete"></i>
                            <span class="m-l-5">Delete</span>
                        </a>
                    ';
				}

				// manage button
				$btns = '';
				if ($edit_btn || $del_btn) {
					$btns = '
                        <div class="dropdown dropdown-animated scale-left">
                            <a class="btn btn-default btn-sm text-primary font-size-18" href="javascript:;"
                                data-toggle="dropdown">
                                <i class="anticon anticon-ellipsis"></i>
                            </a>
                            <div class="dropdown-menu">
                                ' . $edit_btn . '
                                ' . $del_btn . '
                            </div>
                        </div>
                    ';
				}

				$row = array();
				$row[] = $reg_date;
				$row[] = '<div class="text-muted"><img alt="" src="' . site_url($img) . '" style="width:75px;" /></div>';
				$row[] = $title . $play . '<div class="small text-muted">' . $desc . '</div>';
				$row[] = number_format($views);
				$row[] = '<div class="text-muted">' . $btns . '</div>';

				$data[] = $row;
				$count += 1;
			}

			$output = array(
				"draw" => intval($_POST['draw']),
				"recordsTotal" => $this->Crud->datatable_count($table, $where),
				"recordsFiltered" => $this->Crud->datatable_filtered($table, $column_order, $column_search, $order, $where),
				"data" => $data,
			);

			//output to json format
			echo json_encode($output);
			exit;
		}

		if ($param1 == 'manage') { // view for form data posting
			return view('setup/games_form', $data);
		} else { // view for main page
			// for datatable
			$data['table_rec'] = 'setup/games/list'; // ajax table
			$data['order_sort'] = ''; // default ordering (0, 'asc')
			$data['no_sort'] = '1, 4'; // sort disable columns (1,3,5)

			$data['title'] = 'Games | ' . app_name;
			$data['page_active'] = 'setup/games';
			return view('setup/games', $data);
		}
	}

	//// COUPONS
	public function coupons($param1 = '', $param2 = '', $param3 = '')
	{
		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'setup/coupons', 'create');
		$role_r = $this->Crud->module($role_id, 'setup/coupons', 'read');
		$role_u = $this->Crud->module($role_id, 'setup/coupons', 'update');
		$role_d = $this->Crud->module($role_id, 'setup/coupons', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'coupon';

		$form_link = site_url('setup/coupons/');
		if ($param1) {
			$form_link .= $param1;
		}
		if ($param2) {
			$form_link .= '/' . $param2;
		}
		if ($param3) {
			$form_link .= '/' . $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_coupon_id');

						$code = $this->Crud->read_field('id', $del_id, 'coupon', 'code');
						$coup = $this->Crud->read2('code', $code, 'used', 0, 'coupon');
						if (empty($coup)) {
							echo $this->Crud->msg('danger', 'No Unused Coupon to Delete');
						} else {
							///// store activities
							$code = $this->Crud->read_field('id', $del_id, $table, 'code');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' deleted ' . count($coup) . ' Unused Coupon ' . $code . ' Record';

							foreach ($coup as $c) {
								$del = $this->Crud->deletes('id', $c->id, $table);
							}

							if ($del > 0) {
								$this->Crud->activity('setup', $del_id, $action);

								echo $this->Crud->msg('success', 'Record Deleted');
								echo '<script>location.reload(false);</script>';
							} else {
								echo $this->Crud->msg('danger', 'Please try later');
							}
						}

						exit;
					}
				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_sub_id'] = $e->sub_id;
								$data['e_marketer_id'] = $e->marketer_id;
								$data['e_code'] = $e->code;
								$data['e_no'] = $this->Crud->check('code', $e->code, $table);

							}
						}
					}
				}

				if ($param2 == 'view') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_sub_id'] = $e->sub_id;
								$data['e_marketer_id'] = $e->marketer_id;
								$data['e_code'] = $e->code;
								$data['e_no'] = $this->Crud->check('code', $e->code, $table);

							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$coupon_id = $this->request->getPost('coupon_id');
					$marketer_id = $this->request->getPost('marketer_id');
					$sub_id = $this->request->getPost('sub_id');
					$code_type = $this->request->getPost('code_type');
					$code = $this->request->getPost('code');
					$user_type = $this->request->getPost('user_type');
					$no = $this->request->getPost('no');
					if (!empty($this->request->getPost('bulk'))) {
						$bulk = $this->request->getPost('bulk');
					} else {
						$bulk = 0;
					}
					$log_id = $this->session->get('plx_id');

					// do create or update
					if ($coupon_id) {
						$prev_code = $this->Crud->read_field('id', $coupon_id, $table, 'code');
						$prev_no = $this->Crud->check('code', $prev_code, $table);
						if ($prev_code == $code) {
							$codes = $code;
						} else {
							$rands = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
							$codes = substr(str_shuffle($rands), 0, 10);
							if (!$code_type) {
								if (empty($code)) {
									echo $this->Crud->msg('danger', 'Coupon Code cannot be Empty');
									die;
								}
								$codes = strtoupper($code);

							}

						}
						if ($prev_no == $no) {
							$coupon = $this->Crud->read_single('code', $prev_code, $table);

							$vdata['marketer_id'] = $marketer_id;
							foreach ($coupon as $c) {
								$vdata['sub_id'] = $sub_id;
								$vdata['code'] = $codes;
								$upd_rec = $this->Crud->updates('id', $c->id, $table, $vdata);
							}
						} else {
							if ($prev_no > $no) {
								//Remove Excess Coupon that has not been used
								$new_no = $prev_no - $no;
								//Check if there is coupon left that is unused
								if ($this->Crud->check2('code', $prev_code, 'used', 0, $table) > 0) {
									//Remove the Coupon
									$coupon = $this->Crud->read2('code', $prev_code, 'used', 0, $table);

									$a = 0;
									foreach ($coupon as $c) {
										if ($c->used > 0)
											continue;
										if ($a >= $new_no)
											continue;
										$upd_rec = $this->Crud->deletes('id', $c->id, $table);
										$a++;
									}
								} else {
									echo $this->Crud->msg('danger', 'All Coupon has been used.<br> You can`t reduce number of users');
									die;
								}
							} else {
								//Add more coupon
								$new_no = $no - $prev_no;
								for ($i = 1; $i <= $new_no; $i++) {

									$vdata['sub_id'] = $sub_id;
									$vdata['code'] = $codes;
									$vdata['reg_date'] = date(fdate);

									$upd_rec = $this->Crud->create('coupon', $vdata);
								}
							}
						}
						if ($upd_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $coupon_id, 'coupon', 'code');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' updated Coupon (' . $code . ') ';
							$this->Crud->activity('setup', $coupon_id, $action);

							echo $this->Crud->msg('success', 'Record Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {

						$vdata['marketer_id'] = $marketer_id;
						if (empty($bulk)) {
							for ($i = 1; $i <= $no; $i++) {
								$vdata['sub_id'] = $sub_id;
								$vdata['code'] = $code;
								$vdata['reg_date'] = date(fdate);

								$ins_rec = $this->Crud->create('coupon', $vdata);
							}
						} else {
							$rands = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
							for ($i = 1; $i <= $bulk; $i++) {
								// generate fresh code for each
								$code = substr(str_shuffle($rands), 0, 10);

								$vdata['sub_id'] = $sub_id;
								$vdata['code'] = $code;
								$vdata['reg_date'] = date(fdate);

								$ins_rec = $this->Crud->create('coupon', $vdata);
							}
						}


						if ($ins_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $ins_rec, 'coupon', 'code');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' Created Coupon (' . $code . ') for ' . $no . ' Users';
							$this->Crud->activity('setup', $ins_rec, $action);

							echo $this->Crud->msg('success', 'Coupon Code Created');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					}

					die;
				}

			}
		}


		if ($param1 == 'export') {
			$start = $this->session->get('start_date');
			$end = $this->session->get('end_date');
			$sub_id = $this->session->get('sub_id');
			$marketer_id = $this->session->get('marketer_id');


			$comms = $this->Crud->filter_coupon('', '', '', '', $start, $end, $sub_id);


			if (!empty($comms)) {
				$count = 1;
				foreach ($comms as $co) {
					$item = array();

					$item[] = $count;
					$item[] = date('Y-m-d', strtotime($co->reg_date));

					$i = 0;
					$sub = $this->Crud->read_field('id', $co->sub_id, 'subscription', 'name');
					$used = 'Not Used';
					$dates = '-';
					$user = '-';
					if ($co->used > 0) {
						$user = $this->Crud->read_field('id', $co->user_id, 'user', 'fullname');

						$used = 'Used';
						$dates = date('Y-m-d h:iA', strtotime($co->used_date));

					}
					$item[] = $co->code;
					$item[] = $sub;
					$item[] = $used;
					$item[] = $user;
					$item[] = $dates;

					$i++;
					$row[] = $item;
					$count += 1;

				}
			}

			$date = '';
			if (!empty($start) && !empty($end)) {
				$date = date('dmY', strtotime($start)) . '-' . date('dmY', strtotime($end));
			}
			// now export CSV
			$dfile_name = 'Coupons Report ' . $date;
			$fname = $dfile_name . '.csv';
			header("Content-Type: text/csv;charset=utf-8");
			header("Content-Disposition: attachment;filename=$fname");
			header("Pragma: no-cache");
			header("Expires: 0");

			$output = fopen('php://output', 'w');

			// Column Title
			fputcsv($output, array('S/N', 'Reg Date', 'Coupon', 'Subscription', 'Used Status', 'Used By', 'Used Date'));

			// Column Items
			if (!empty($row)) {
				foreach ($row as $fields) {
					fputcsv($output, $fields);
				}
			}

			fclose($output);
			die;
		}

		// record listing
		if ($param1 == 'load') {
			// DataTable parameters
			$table = 'coupon';

			$limit = $param2;
			$offset = $param3;

			$rec_limit = 75;
			$item = '';
			$counts = 0;

			if (empty($limit)) {
				$limit = $rec_limit;
			}
			if (empty($offset)) {
				$offset = 0;
			}

			if (!empty($this->request->getPost('start_date'))) {
				$start_date = $this->request->getPost('start_date');

			} else {
				$start_date = '';
			}
			if (!empty($this->request->getPost('end_date'))) {
				$end_date = $this->request->getPost('end_date');
			} else {
				$end_date = '';
			}
			if (!empty($this->request->getPost('payment'))) {
				$payment = $this->request->getPost('payment');
			} else {
				$payment = '';
			}
			if (!empty($this->request->getPost('sub_id'))) {
				$sub_id = $this->request->getPost('sub_id');
			} else {
				$sub_id = '';
			}
			if (!empty($this->request->getPost('marketer_id'))) {
				$marketer_id = $this->request->getPost('marketer_id');
			} else {
				$marketer_id = '';
			}
			$search = $this->request->getPost('search');
			$this->session->set('start_date', $start_date);
			$this->session->set('end_date', $end_date);
			$this->session->set('sub_id', $sub_id);
			$this->session->set('marketer_id', $marketer_id);

			$log_id = $this->session->get('plx_id');
			if (!$log_id) {
				$item = '<div class="text-center text-muted">Session Timeout! - Please login again</div>';
			} else {
				$all_rec = $this->Crud->filter_coupon('', '', $log_id, $search, $start_date, $end_date, $sub_id, $marketer_id);
				if (!empty($all_rec)) {
					$counts = count($all_rec);
				}
				$query = $this->Crud->filter_coupon($limit, $offset, $log_id, $search, $start_date, $end_date, $sub_id, $marketer_id);

				if (!empty($query)) {
					foreach ($query as $q) {
						$id = $q->id;
						$sub_id = $q->sub_id;
						$code = $q->code;
						$used = $q->used;
						$sub = $this->Crud->read_field('id', $q->sub_id, 'subscription', 'name');
						$marketer = $this->Crud->read_field('id', $q->marketer_id, 'user', 'fullname');
						$mar = '';
						if ($marketer) {
							$mar = '<div class="font-size-14 mb-2">Marketer: ' . ucwords($marketer) . '</div>';
						}

						$reg_date = date('M d, Y h:i A', strtotime($q->reg_date));

						$metric = $this->Crud->check('code', $code, 'coupon');
						$met = $metric . ' User';
						if ($metric > 1) {
							$met = $metric . ' Users';
						}
						$used_metric = $this->Crud->check2('code', $code, 'used >', 0, 'coupon');
						$used = $used_metric . ' User';
						if ($used_metric > 1) {
							$used = $used_metric . ' Users';
						}
						$rem_metric = $this->Crud->check2('code', $code, 'used', 0, 'coupon');
						$rem = $rem_metric . ' User';
						if ($rem_metric > 1) {
							$rem = $rem_metric . ' Users';
						}
						// add manage buttons
						$all_btn = '';
						if ($role_u != 1) {
							$all_btn = '';
						} else {
							$all_btn = '
								<div class="text-right">
									<a href="javascript:;" class="text-success pop m-r-10" pageTitle="View ' . $code . ' Details" pageName="' . base_url('setup/coupons/manage/view/' . $id) . '" pageSize="modal-md">
										<i class="anticon anticon-eye"></i> VIEW
									</a>
									<a href="javascript:;" class="text-primary pop m-r-10" pageTitle="Edit ' . $code . ' Details" pageName="' . base_url('setup/coupons/manage/edit/' . $id) . '" pageSize="modal-md">
										<i class="anticon anticon-edit"></i> EDIT
									</a>
									<a href="javascript:;" class="text-danger pop" pageTitle="Delete ' . $code . ' Details" pageName="' . base_url('setup/coupons/manage/delete/' . $id) . '" pageSize="modal-md">
									<i class="anticon anticon-delete"></i> DELETE
								</a>
								</div>
							';
						}

						$item .= '
							<li class="list-group-item">
								<div class="row p-t-10">
									<div class="col-10 col-md-5 m-b-10">
										<div class="single">
											<b class="font-size-16 text-primary">' . $code . '</b>
											<div class="small text-muted">' . $reg_date . '</div>
											<span class="text-primary">' . $met . ' Generated</span>
										</div>
									</div>
									<div class="col-7 col-md-4 m-b-5">
										<b class="font-size-14">
											' . $sub . '
										</b>
										<div class="text-danger font-size-14">' . $used . ' Used</div>
										<div class="text-info font-size-14">' . $rem . ' Left</div>
										' . $mar . '
									</div>
									<div class="col-5 col-md-3">
										
										<b class="font-size-14">' . $all_btn . '</b>
									</div>
								</div>
							</li>
						';
					}
				}
			}

			if (empty($item)) {
				$resp['item'] = '
					<div class="text-center text-muted">
						<br/><br/><br/><br/>
						<i class="anticon anticon-team" style="font-size:150px;"></i><br/><br/>No Coupon Returned
					</div>
				';
			} else {
				$resp['item'] = $item;
			}

			$resp['count'] = $counts;

			$more_record = $counts - ($offset + $rec_limit);
			$resp['left'] = $more_record;

			if ($counts > ($offset + $rec_limit)) { // for load more records
				$resp['limit'] = $rec_limit;
				$resp['offset'] = $offset + $limit;
			} else {
				$resp['limit'] = 0;
				$resp['offset'] = 0;
			}

			echo json_encode($resp);
			die;

		}

		if ($param1 == 'manage') { // view for form data posting
			return view('setup/coupons_form', $data);
		} else { // view for main page

			$data['title'] = 'Coupons | ' . app_name;
			$data['page_active'] = 'setup/coupons';
			return view('setup/coupons', $data);
		}
	}

	public function sync_tags()
	{
		$video_tags = array();
		$tags = '';
		// $this->session->remove('video_tags');

		$tag = strtolower($this->request->getVar('tag'));
		if (!empty($tag)) {
			// check for existing tag
			$video_tags = $this->session->get('video_tags');
			if (empty($video_tags)) {
				$video_tags[] = $tag;
			} else {
				if (!in_array($tag, $video_tags))
					$video_tags[] = $tag;
			}

			$this->session->set('video_tags', $video_tags);
		}

		$tags = $this->list_tags();
		echo $tags;
		die;
	}

	public function remove_tag($name)
	{
		$video_tags = $this->session->get('video_tags');
		if (!empty($video_tags)) {
			unset($video_tags[array_search($name, $video_tags)]);
			$this->session->set('video_tags', $video_tags);
		}

		echo $this->list_tags();
		die;
	}

	private function list_tags()
	{
		$tags = '';

		$video_tags = $this->session->get('video_tags');
		if (!empty($video_tags)) {
			foreach ($video_tags as $tag) {
				$tags .= '
					<span class="badge badge-pill badge-default">
						' . $tag . ' <a href="javascript:;" class="text-danger" onclick="removeTag(\'' . $tag . '\')"><i class="anticon anticon-close"></i></a>
					</span>&nbsp;&nbsp;
				';
			}
		}

		return $tags;
	}

	public function generate_coupon()
	{
		if ($this->request->getMethod() == 'post') {
			$sub_id = $this->request->getPost('sub_id');
			$no = $this->request->getPost('no');
			$log_id = $this->session->get('plx_id');

			$rands = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			for ($i = 1; $i <= $no; $i++) {
				$code = substr(str_shuffle($rands), 0, 10);

				$vdata['sub_id'] = $sub_id;
				$vdata['code'] = $code;
				$vdata['reg_date'] = date(fdate);

				$ins_rec = $this->Crud->create('coupon', $vdata);
				///// store activities
				$code = $this->Crud->read_field('id', $ins_rec, 'coupon', 'code');
				$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
				$action = $by . ' Created Coupon ' . $code . ' Record';
				$this->Crud->activity('setup', $ins_rec, $action);

			}

			echo '<script>location.reload(false);</script>';
		}
	}
}
