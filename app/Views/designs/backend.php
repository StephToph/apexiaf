<?php
$Crud = model(Crud::class);

$log_name = $Crud->read_field('id', $log_id, 'user', 'fullname');
$log_role_id = $Crud->read_field('id', $log_id, 'user', 'role_id');
$log_role = strtolower($Crud->read_field('id', $log_role_id, 'access_role', 'name'));
$log_user_img = 'assets/back/images/avatar.png';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $this->renderSection('title'); ?></title>
    <link rel="shortcut icon" href="<?php echo site_url() ?>assets/back/images/logo.png">

    <link href="<?php echo site_url(); ?>assets/back/vendors/datatables/dataTables.bootstrap.min.css" rel="stylesheet" />
    <link href="<?php echo site_url(); ?>assets/back/vendors/select2/select2.css" rel="stylesheet" />
    <link href="<?php echo site_url() ?>assets/back/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css"
        rel="stylesheet">
    <link href="<?php echo site_url() ?>assets/back/css/app.min.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo site_url(); ?>assets/back/css/animate.css" rel="stylesheet" />
</head>

<body>
    <div class="app">
        <div class="layout">
            <div class="header">
                <div class="logo logo-dark" style="padding-top:15px;">
                    <a href="<?php echo site_url('dashboard'); ?>">
                        <img src="<?php echo site_url() ?>assets/back/images/logo.png" alt="" height="50px">
                        <img class="logo-fold" src="<?php echo site_url() ?>assets/back/images/logo.png" alt=""
                            height="50px">
                    </a>
                </div>
                <div class="logo logo-white" style="padding-top:15px;">
                    <a href="<?php echo site_url('dashboard'); ?>">
                        <img src="<?php echo site_url() ?>assets/back/images/logo.png" alt="" height="50px">
                        <img class="logo-fold" src="<?php echo site_url() ?>assets/back/images/logo.png" alt=""
                            height="50px">
                    </a>
                </div>
                <div class="nav-wrap">
                    <ul class="nav-left">
                        <li class="desktop-toggle">
                            <a href="javascript:void(0);">
                                <i class="anticon"></i>
                            </a>
                        </li>
                        <li class="mobile-toggle">
                            <a href="javascript:void(0);">
                                <i class="anticon"></i>
                            </a>
                        </li>
                    </ul>
                    <ul class="nav-right">
                        <li class="dropdown dropdown-animated scale-left">
                            <a href="javascript:void(0);" data-toggle="dropdown">
                                <i class="anticon anticon-bell notification-badge"></i>
                            </a>
                            <div class="dropdown-menu pop-notification">
                                <div
                                    class="p-v-15 p-h-25 border-bottom d-flex justify-content-between align-items-center">
                                    <p class="text-dark font-weight-semibold m-b-0">
                                        <i class="anticon anticon-bell"></i>
                                        <span class="m-l-10">Notification</span>
                                    </p>
                                    <a class="btn-sm btn-default btn" href="javascript:void(0);">
                                        <small>View All</small>
                                    </a>
                                </div>
                                <div class="relative">
                                    <div class="overflow-y-auto relative scrollable" style="max-height: 300px">
                                        <!-- <a href="javascript:void(0);" class="dropdown-item d-block p-15 border-bottom">
                                            <div class="d-flex">
                                                <div class="avatar avatar-blue avatar-icon">
                                                    <i class="anticon anticon-mail"></i>
                                                </div>
                                                <div class="m-l-15">
                                                    <p class="m-b-0 text-dark">You received a new message</p>
                                                    <p class="m-b-0"><small>8 min ago</small></p>
                                                </div>
                                            </div>
                                        </a> -->
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li class="dropdown dropdown-animated scale-left">
                            <div class="pointer" data-toggle="dropdown">
                                <div class="avatar avatar-image  m-h-10 m-r-15">
                                    <img src="<?php echo site_url($log_user_img) ?>" alt="">
                                </div>
                            </div>
                            <div class="p-b-15 p-t-20 dropdown-menu pop-profile">
                                <div class="p-h-20 p-b-15 m-b-10 border-bottom">
                                    <div class="d-flex m-r-50">
                                        <div class="avatar avatar-lg avatar-image">
                                            <img src="<?php echo site_url($log_user_img) ?>" alt="">
                                        </div>
                                        <div class="m-l-10">
                                            <p class="m-b-0 text-dark font-weight-semibold"><?php echo $log_name; ?></p>
                                            <p class="m-b-0 opacity-07"><?php echo ucwords($log_role); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <a href="<?php echo site_url('profile'); ?>"
                                    class="dropdown-item d-block p-h-15 p-v-10">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="anticon opacity-04 font-size-16 anticon-user"></i>
                                            <span class="m-l-10">Edit Profile</span>
                                        </div>
                                        <i class="anticon font-size-10 anticon-right"></i>
                                    </div>
                                </a>
                                <a href="<?php echo site_url('profile/password'); ?>"
                                    class="dropdown-item d-block p-h-15 p-v-10">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="anticon opacity-04 font-size-16 anticon-lock"></i>
                                            <span class="m-l-10">Change Password</span>
                                        </div>
                                        <i class="anticon font-size-10 anticon-right"></i>
                                    </div>
                                </a>
                                <a href="<?php echo site_url('auth/logout'); ?>"
                                    class="dropdown-item d-block p-h-15 p-v-10">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="anticon opacity-04 font-size-16 anticon-logout"></i>
                                            <span class="m-l-10">Logout</span>
                                        </div>
                                        <i class="anticon font-size-10 anticon-right"></i>
                                    </div>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Side Nav START -->
            <div class="side-nav">
                <div class="side-nav-inner">
                    <ul class="side-nav-menu scrollable">
                        <!-- Dynamic Menu Items -->
                        <?php
                        $menu = '';
                        $modules = $Crud->read_single_order('parent', 0, 'access_module', 'priority', 'asc');
                        if (!empty($modules)) {
                            foreach ($modules as $mod) {
                                // get level 2
                                $level2 = '';
                                if ($Crud->mod_read($log_role_id, $mod->link) == 1) {
                                    $mod_level2 = $Crud->read_single_order('parent', $mod->id, 'access_module', 'priority', 'asc');
                                    if (!empty($mod_level2)) {
                                        $open = false;
                                        $show = false;
                                        foreach ($mod_level2 as $mod2) {

                                            if ($Crud->mod_read($log_role_id, $mod2->link) == 1) {
                                                // add parent to first
                                                if (empty($level2)) {
                                                    // $level2 = '
                                                    //     <li>
                                                    //         <a href="'.site_url($mod->link).'">'.$mod->name.'</a>
                                                    //     </li>
                                                    // ';
                                                }
                                                if ($page_active == $mod2->link) {
                                                    $open = true;
                                                    $a_active = 'active';
                                                } else {
                                                    $a_active = '';
                                                }

                                                // add the rest
                                                $level2 .= '
                                                        <li class="' . $a_active . '">
                                                            <a href="' . site_url($mod2->link) . '">' . $mod2->name . '</a>
                                                        </li>
                                                    ';
                                            }
                                        }

                                        $level2 = '
                                                <ul class="dropdown-menu">
                                                    ' . $level2 . '
                                                </ul>
                                            ';
                                    }

                                    if ($page_active == $mod->link) {
                                        $a_active = 'active';
                                    } else {
                                        $a_active = '';
                                    }
                                    if ($level2) {
                                        $topmenu = 'nav-item dropdown';
                                        $submenu = 'dropdown-toggle';
                                        $dlink = 'javascript:void(0);';
                                        $menu_arraow = '<span class="arrow"><i class="arrow-icon"></i></span>';
                                    } else {
                                        $topmenu = '';
                                        $submenu = '';
                                        $dlink = site_url($mod->link);
                                        $menu_arraow = '';
                                    }

                                    $menu .= '
                                            <li class="' . $topmenu . ' ' . $a_active . '">
                                                <a class="' . $submenu . '" href="' . $dlink . '">
                                                    <span class="icon-holder">
                                                        <i class="' . $mod->icon . '"></i>
                                                    </span>
                                                    <span class="title">' . $mod->name . '</span>
                                                    ' . $menu_arraow . '
                                                </a>
                                                ' . $level2 . '
                                            </li>
                                        ';
                                }
                            }
                        }

                        echo $menu;
                        ?>

                        <!-- Modules and Roles -->
                        <?php if ($log_role == 'developer' || $log_role == 'administrator') { ?>
                            <li class="nav-item dropdown">
                                <a class="dropdown-toggle" href="javascript:void(0);">
                                    <span class="icon-holder">
                                        <i class="anticon anticon-setting"></i>
                                    </span>
                                    <span class="title">Access Roles</span>
                                    <span class="arrow">
                                        <i class="arrow-icon"></i>
                                    </span>
                                </a>
                                <ul class="dropdown-menu">
                                    <?php if ($log_role == 'developer') { ?>
                                        <li class="<?php if ($page_active == 'module') {
                                            echo 'active';
                                        } ?>">
                                            <a href="<?php echo site_url('settings/modules'); ?>">Modules</a>
                                        </li>
                                    <?php } ?>
                                    <li class="<?php if ($page_active == 'role') {
                                        echo 'active';
                                    } ?>">
                                        <a href="<?php echo site_url('settings/roles'); ?>">Roles</a>
                                    </li>
                                    <li class="<?php if ($page_active == 'access') {
                                        echo 'active';
                                    } ?>">
                                        <a href="<?php echo site_url('settings/access'); ?>">Access CRUD</a>
                                    </li>
                                </ul>
                            </li>

                            <!--<li class="<?php if ($page_active == 'app') {
                                echo 'active';
                            } ?>">
                            <a href="<?php echo site_url('settings/app'); ?>">
                                <span class="icon-holder">
                                    <i class="anticon anticon-setting"></i>
                                </span>
                                <span class="title">App Settings</span>
                            </a>
                        </li>-->
                        <?php } ?>

                        <!-- Sign Out -->
                        <li>
                            <a href="<?php echo site_url('auth/logout'); ?>">
                                <span class="icon-holder">
                                    <i class="anticon anticon-logout"></i>
                                </span>
                                <span class="title">Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Side Nav END -->

            <div class="page-container">
                <?php echo $this->renderSection('content'); ?>

                <footer class="footer">
                    <div class="footer-content">
                        <p class="m-b-0">Copyright © <?php echo date('Y'); ?> <?php echo app_name; ?> | All rights
                            reserved.</p>
                    </div>
                </footer>

            </div>

            <div class="modal modal-center fade" id="modal">
                <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"> </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <i class="anticon anticon-close-circle"></i>
                            </button>
                        </div>
                        <div class="modal-body">

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal"><i
                                    class="anticon anticon-close-circle"></i> Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal modal-right fade">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="side-modal-wrapper" style="overflow:auto">
                            <!-- <div class="vertical-align"> -->
                            <div class="table-cell">
                                <div class="modal-header">
                                    <h5 class="modal-title"> </h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <i class="anticon anticon-close-circle"></i>
                                    </button>
                                </div>
                                <div class="modal-body">

                                </div>
                            </div>
                            <!-- </div> -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal modal-left fade">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="side-modal-wrapper" style="overflow:auto">
                            <!-- <div class="vertical-align"> -->
                            <div class="table-cell">
                                <div class="modal-header">
                                    <h5 class="modal-title"> </h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <i class="anticon anticon-close-circle"></i>
                                    </button>
                                </div>
                                <div class="modal-body">

                                </div>
                            </div>
                            <!-- </div> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo site_url() ?>assets/back/js/vendors.min.js"></script>
    <script src="<?php echo site_url(); ?>assets/back/vendors/select2/select2.min.js"></script>
    <script src="<?php echo site_url(); ?>assets/back/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script>
        $('.selects2').select2();
        // Event listener for dropdown change
       
    </script>
    <!-- <script src="<?php echo site_url(); ?>assets/back/js/pages/form-elements.js"></script> -->
    <?php echo $this->renderSection('footer_top'); ?>

    <script src="<?php echo site_url() ?>assets/back/js/app.min.js"></script>
    <?php echo $this->renderSection('footer_bottom'); ?>

    <?php if (!empty($table_rec)) { ?>
        <script src="<?php echo site_url(); ?>assets/back/vendors/datatables/jquery.dataTables.min.js"></script>
        <script src="<?php echo site_url(); ?>assets/back/vendors/datatables/dataTables.bootstrap.min.js"></script>
        <script src="<?php echo site_url(); ?>assets/back/js/pages/datatables.js"></script>
        <script type="text/javascript">
            $(document).ready(function () {
                //datatables
                var table = $('#dtable').DataTable({
                    "processing": true, //Feature control the processing indicator.
                    "serverSide": true, //Feature control DataTables' server-side processing mode.
                    "order": [<?php if (!empty($order_sort)) {
                        echo '[' . $order_sort . ']';
                    } ?>], //Initial order.
                    "language": {
                        "processing": "<i class='anticon anticon-loading' aria-hidden='true'></i> Processing... please wait"
                    },
                    // "pagingType": "full",

                    // Load data for the table's content from an Ajax source
                    "ajax": {
                        url: "<?php echo site_url($table_rec); ?>",
                        type: "POST",
                        data: function (d) {
                            var filterValue = $('#language_id').val();
                            if (filterValue) {
                                d.filterValue = filterValue; // Add the filter parameter only if a value is selected
                            }
                        },
                        complete: function () {
                            $.getScript('<?php echo site_url(); ?>assets/back/js/jsmodal.js');
                        }
                    },

                    //Set column definition initialisation properties.
                    "columnDefs": [
                        {
                            "targets": [<?php if (!empty($no_sort)) {
                                echo $no_sort;
                            } ?>], //columns not sortable
                            "orderable": false, //set not orderable
                        },
                    ],

                });

                $('#language_id').change(function () {
                    table.ajax.reload(); // Reload the table data when the dropdown changes
                });
            });
            
        </script>
    <?php } ?>
</body>

</html>