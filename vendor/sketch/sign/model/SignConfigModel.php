<?php

namespace sketch\sign\model;

use sketch\sign\SignModelInterface;

class SignConfigModel implements SignModelInterface
{
    public $users = [];
    private $id = null;
    private $login = '';
    private $status = -1;
    private $password = '';
    private $CookieTime = 2592000;

    public function signIn()
    {
        if (! $this->SignInBySession() ){
            if (! $this->SignInByCookies()){
                if (isset($_POST['login']) && isset($_POST['password'])){
                    if (!$this->SignInByLoginPassword($_POST['login'], $_POST['password'], false)){
                        $this->Clear();
                    }
                }
            }
        }
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
            return false;
        }

        if (!password_verify($password, $this->password)) {
            $this->Clear();
            return false;
        }

        if ($remember) {
            $this->AddSignCookies();
        }

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
        unset($_SESSION['status']);

    }

    //  ====== GET BY DATABASE =======

    public function SetIDPasswordByLogin(){

        foreach( $this->users as $value) {
            if ($value['login'] === $this->login){
                $this->id = $value['id'];
                $this->password = $value['password'];
                $this->status = $value['status'];
                return true;
            }
        }

        return false;
    }
    public function SetLoginPasswordByID(){

        foreach( $this->users as $value) {
            if ($value['id'] === $this->id){
                $this->login = $value['login'];
                $this->password = $value['password'];
                $this->status = $value['status'];
                return true;
            }
        }

        return false;
    }

    public function GetIdByLoginPassword(){
        foreach ($this->users as $value) {
            if ($value->login === $this->login && $value->password === $this->password) return $value->id;
        }

        return null;
    }
    public function GetLoginByIdPassword(){

        foreach ($this->users as $value) {
            if ($value->id === $this->id && $value->password === $this->password) return $value->login;
        }

        return '';

    }

}
