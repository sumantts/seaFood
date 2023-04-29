<?php


class Registration extends My_Controller {

    private $user_type = null;

    function __construct() {
        parent::__construct();

        if($this->session->has_userdata('user_id')) { //if logged-in
            $this->user_type = $this->session->usertype;
        }
    }

    public function index() {
        redirect(base_url('registration'));
    }

    public function admin_registration() {
        $this->load->helper('form');
        $this->load->library('form_validation');

        //if already logged-in
        if($this->user_type != null) {
            redirect(base_url('admin/dashboard')); //redirect to dashboard
        }
        //if login form submitted
        elseif($this->input->post('submit') == 'registration') {
            $this->load->model('Registration_m');
            $data = $this->Registration_m->admin_registration();
            if( $data['type'] == 'load_view') {
                $this->load->view($data['page']); //loading admin login page
            } else {
                redirect(base_url($data['page']));
            }

        } else {
            $this->load->view('admin_registration_v'); //loading login page
        }
    }

    public function admin_logout() { //admin logout
        if( $this->user_type != null ) { //if already logged-in
            $this->load->model('Registration_m');
            $data = $this->Registration_m->admin_logout();
            redirect(base_url($data));
        } else { //if admin not logged-in
            $this->session->set_flashdata('title', 'Oh, Snap!');
            $this->session->set_flashdata('msg', 'At-first login, eh!');
            redirect(base_url('admin'));
        }
    }

    public function change_password($otp) {
        $this->load->helper('form');
        $this->load->library('form_validation');

        if($this->user_type != null) { //if user already logged-in
            $this->session->set_flashdata('title', 'Log-out!');
            $this->session->set_flashdata('msg', 'You must log-out first, to change password of another account.');
            redirect(base_url('admin/dashboard'));
        } else {
            $this->load->model('Registration_m');
            $data = $this->Registration_m->change_password($otp);
            if( $data['type'] == 'load_view') {
                $this->load->view($data['page'], $data['data']);
            } else {
                redirect(base_url($data['page']));
            }
        }
    }

    public function update_password() {
        $this->load->helper('form');
        $this->load->library('form_validation');

        if($this->user_type != null) { //if user already logged-in
            $this->session->set_flashdata('title', 'Log-out!');
            $this->session->set_flashdata('msg', 'You must log-out first, to reset password of another account.');
            redirect(base_url('admin/dashboard'));
        }
        elseif($this->input->post('submit') == 'change_pass') { //if change password form submitted
            $this->load->model('Registration_m');
            $data = $this->Registration_m->update_password();
            if( $data['type'] == 'load_view') {
                $this->load->view($data['page'], $data['data']);
            } else {
                redirect(base_url($data['page']));
            }
        } else {
            redirect(base_url('login')); //loading login page
        }
    }

} // /.class Login{}