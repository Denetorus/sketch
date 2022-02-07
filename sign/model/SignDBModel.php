<?php

namespace sketch\sign\model;

use sketch\sign\SignModelInterface;

class SignDBModel implements SignModelInterface
{

    public $db = null;
    public $user = null;
    private $id = null;
    private $login = '';
    private $password = '';
    private $status = -1;
    private $CookieTime = 2592000;

    public function signIn()
    {
        if (isset($_POST['login']) && isset($_POST['password'])) {
            if ($this->SignInByLoginPassword($_POST['login'], $_POST['password'], false)) {
                return;
            }
        }

        if ($this->SignInBySession()){
            return;
        }

        if ($this->SignInByCookies()){
            return;
        }

        $this->Clear();

    }

    public function signedIn()
    {

        return ($this->id != null);
    }

    public function signedInfo()
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'status' => $this->status
        ];
    }

    // ========= AUTHENTICATION =========

    public function Clear(){

        $this->id = null;
        $this->password = '';
        $this->login = '';
        $this->status = -1;
        $this->DeleteSignCookies();
        $this->DeleteSignSession();

    }

    private function SignInBySession(){

        if (isset($_SESSION['id_user']) && isset($_SESSION['password'])){
            $this->id = $_SESSION['id_user'];
            if (!$this->SetLoginPasswordByID()){
                return false;
            }

            if ($_SESSION['password'] !== $this->password) {
                return false;
            }

            return true;

        }

        return false;

    }
    private function SignInByCookies(){

        if (isset($_COOKIE['id_user']) & isset($_COOKIE['password']))
        {
            $this->id = $_COOKIE['id_user'];
            if (!$this->SetLoginPasswordByID()){
                return false;
            }

            if (!password_verify($_SESSION['password'], $this->password)) {
                $this->Clear();
                return false;
            }

            $this->AddSignCookies();
            return true;

        }
        return false;

    }
    private function SignInByLoginPassword($login, $password, $remember){

        $this->login = $login;
        if (!$this->SetIDPasswordByLogin()){
            $this->Clear();
            return false;
        }

        if (!password_verify($password, $this->password)) {
            $this->Clear();
            return false;
        }

        //if ($remember) {
        //    $this->AddSignCookies();
        //}

        $this->AddSignSessions();

        return true;

    }

    // =========== COOKIES =============

    private function AddSignCookies(){
        setcookie('id_user', $this->id, time()+ $this->CookieTime , '/');
        setcookie('user', $this->login, time()+ $this->CookieTime , '/');
        setcookie('password', $this->password, time()+ $this->CookieTime , '/');
        setcookie('status', $this->status, time()+ $this->CookieTime , '/');
    }
    private function DeleteSignCookies(){
        setcookie('user', '', 0, '/');
        setcookie('password', '', 0, '/');
        setcookie('id_user', '', 0, '/');
        setcookie('status', '', 0, '/');
    }

    //  =========== SESSIONS ===========

    private function AddSignSessions(){
        $_SESSION['id_user'] = $this->id;
        $_SESSION['user'] = $this->login;
        $_SESSION['status'] = $this->status;
        $_SESSION['password'] = $this->password;
    }
    private function DeleteSignSession(){

        unset($_SESSION['id_user']);
        unset($_SESSION['user']);
        unset($_SESSION['password']);
        $_SESSION['status']=-1;

    }

    //  ====== GET BY DATABASE =======

    public function SetIDPasswordByLogin(){

        $this->user->loadByLogin($this->login);

        if ($this->user->props !== null)
        {
            $this->id = $this->user->props['id'];
            $this->password = $this->user->props['password_hash'];
            $this->status = $this->user->props['status'];
            return true;
        }

        $this->id = null;
        $this->password = "";
        return false;
    }
    public function SetLoginPasswordByID(){

        $this->user->ref = $this->id;
        $this->user->load();

        if ($this->user->props !== null)
        {
            $this->login = $this->user->props['login'];
            $this->password = $this->user->props['password_hash'];
            $this->status = $this->user->props['status'];
            return true;
        }

        $this->id = null;
        $this->password = "";
        $this->status = -1;
        return false;
    }


}
