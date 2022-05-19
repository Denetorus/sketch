<?php

namespace sketch\sign\model;

use sketch\database\DBBase;
use sketch\sign\SignModelInterface;

class SignConfigModel implements SignModelInterface
{

    /**
     * @var DBBase|null
     */
    public $users = null;
    /**
     * @var int
     */
    public $cookieTime = 2592000;

    /**
     * @var bool
     */
    public $useCookie = false;

    /**
     * @var integer
     */
    private $id = -1;
    /**
     * @var string
     */
    private $login = '';
    /**
     * @var string
     */
    private $password = '';
    /**
     * @var string
     */
    private $password_hash = '';
    /**
     * @var int
     */
    private $status = -1;

    /**
     * @return void
     */
    public function signIn():void
    {

        if ($this->login==='' && isset($_POST['login']))
            $this->login = $_POST['login'];

        if ($this->password==='' && isset($_POST['password']))
            $this->password = $_POST['password'];

        if ($this->login!=='' && $this->SignInByLoginPassword($_POST['password']))
            return;

        if ($this->SignInBySession())
            return;

        if ($this->useCookie && $this->SignInByCookies())
            return;

        $this->clear();

    }

    /**
     * @return bool
     */
    public function signedIn(): bool
    {

        return ($this->id !== -1);
    }

    /**
     * @return array
     */
    public function signedInfo():array
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'status' => $this->status
        ];
    }

    // ========= AUTHENTICATION =========

    /**
     * @return void
     */
    public function clear():void
    {

        $this->id = -1;
        $this->password = '';
        $this->password_hash = '';
        $this->login = '';
        $this->status = -1;
        $this->DeleteSignCookies();
        $this->DeleteSignSession();

    }

    /**
     * @return bool
     */
    private function SignInBySession():bool
    {
        if (!isset($_SESSION['id_user']) || isset($_SESSION['password_hash']))
            return false;

        $this->id = $_SESSION['id_user'];

        if (!$this->SetLoginPasswordByID()){
            return false;
        }

        if ($_SESSION['password_hash'] !== $this->password_hash) {
            return false;
        }

        return true;

    }

    /**
     * @return bool
     */
    private function SignInByCookies():bool
    {

        if (!isset($_COOKIE['id_user']) || !isset($_COOKIE['password_hash']))
            return false;

        $this->id = $_COOKIE['id_user'];
        if (!$this->SetLoginPasswordByID()){
            return false;
        }

        if ($_SESSION['password_hash'] !== $this->password_hash) {
            $this->clear();
            return false;
        }

        $this->AddSignCookies();
        return true;

    }

    /**
     * @return bool
     */
    private function SignInByLoginPassword(): bool
    {

        if (!$this->SetIDPasswordByLogin()){
            $this->clear();
            return false;
        }

        if (!password_verify($this->password, $this->password_hash)) {
            $this->clear();
            return false;
        }

        if ($this->useCookie) {
            $this->AddSignCookies();
        }

        $this->AddSignSessions();

        return true;

    }

    // =========== COOKIES =============

    /**
     * @return void
     */
    private function AddSignCookies()
    {
        setcookie('id_user', $this->id, time()+ $this->cookieTime , '/');
        setcookie('user', $this->login, time()+ $this->cookieTime , '/');
        setcookie('password_hash', $this->password_hash, time()+ $this->cookieTime , '/');
        setcookie('status', $this->status, time()+ $this->cookieTime , '/');
    }

    /**
     * @return void
     */
    private function DeleteSignCookies()
    {
        setcookie('user', '', 0, '/');
        setcookie('password_hash', '', 0, '/');
        setcookie('id_user', '', 0, '/');
        setcookie('status', '', 0, '/');
    }

    //  =========== SESSIONS ===========

    /**
     * @return void
     */
    private function AddSignSessions()
    {
        $_SESSION['id_user'] = $this->id;
        $_SESSION['user'] = $this->login;
        $_SESSION['status'] = $this->status;
        $_SESSION['password_hash'] = $this->password_hash;
    }

    /**
     * @return void
     */
    private function DeleteSignSession()
    {

        unset($_SESSION['id_user']);
        unset($_SESSION['user']);
        unset($_SESSION['password_hash']);
        $_SESSION['status']=-1;

    }

    //  ====== GET BY DATABASE =======

    /**
     * @return bool
     */
    public function SetIDPasswordByLogin():bool
    {

        foreach( $this->users as $value) {
            if ($value['login'] === $this->login){
                $this->id = $value['id'];
                $this->password_hash = password_hash($value['password'], PASSWORD_DEFAULT);
                $this->status = $value['status'];
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function SetLoginPasswordByID():bool
    {

        foreach( $this->users as $value) {
            if ($value['id'] === $this->id){
                $this->login = $value['login'];
                $this->password_hash = password_hash($value['password'], PASSWORD_DEFAULT);
                $this->status = $value['status'];
                return true;
            }
        }

        return false;
    }
}
