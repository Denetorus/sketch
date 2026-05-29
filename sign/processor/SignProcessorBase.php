<?php

namespace sketch\sign\processor;

abstract class SignProcessorBase implements SignProcessorInterface
{

    /**
     * @var string
     */
    public string $message = "";

    /**
     * @var array
     */
    public array $user_data = [];

    /**
     * @var int
     */
    public int $user_id = 0;

    /**
     * @var string
     */
    public string $user_login = '';

    /**
     * @var string
     */
    public string $user_password = '';

    /**
     * @var int
     */
    public int $user_status = -1;

    /**
     * @var array
     */
    public array $user_roles = [];

    /**
     * @var bool
     */
    public bool $user_signed_in = false;

    /**
     * @var bool
     */
    public bool $useCookie = false;

    /**
     * @var int
     */
    public int $cookieTime = 2592000;

    /**
     * @return bool
     */
    public function signedIn(): bool
    {
        return $this->user_signed_in;
    }


    /**
     * @return void
     */
    public function signIn(): void
    {
        $this->message = "Start; ";
        $this->message .= "Sign by session; ";
        if ($this->SignInBySession()){
            $this->message .= "Sign by session is success; ";
            return;
        }

        if($this->useCookie){
            $this->message .= "Sign by cookies; ";
            if ($this->SignInByCookies()){
                $this->message .= "Sign by cookies is success; ";
                return;
            }
        }

        $this->message .= "Sign by post params; ";
        if ($this->SignInByPostParams()){
            $this->message .= "Sign by post params is success; ";
        }

    }

    /**
     * @return array
     */
    public function signedInfo(): array
    {
        return [
            'user_signed_in' => $this->user_signed_in,
            'user_id' => $this->user_id,
            'user_login' => $this->user_login,
            'user_status' => $this->user_status,
            'user_roles' => $this->user_roles,
        ];
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->user_data = [];
        $this->user_signed_in = false;
        $this->user_id = 0;
        $this->user_login = '';
        $this->user_password = '';
        $this->user_status = -1;
        $this->user_roles = [];
        $this->deleteSignSession();
        $this->deleteSignCookies();
    }

    /**
     * @return bool
     */
    public function SignInBySession(): bool
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_password'])) {
            $this->message .= "user_id or user_password is undefined; ";
            $this->DeleteSignSession();
            return false;
        }

        $this->user_id = $_SESSION['user_id'];
        if (!$this->fillByUserID()) {
            $this->message .= "user_id not match login; ";
            $this->DeleteSignSession();
            return false;
        }

        if ($_SESSION['user_password'] !== $this->user_password) {
            $this->message .= "password not verified; ";
            $this->DeleteSignSession();
            return false;
        }

        return true;

    }

    /**
     * @return bool
     */
    private function SignInByCookies():bool
    {

        if (!isset($_COOKIE['user_id']) || !isset($_COOKIE['user_password'])){
            $this->message .= "user_id or user_password is undefined; ";
            $this->DeleteSignCookies();
            return false;
        }

        $this->user_id = $_COOKIE['user_id'];
        if (!$this->fillByUserID()){
            $this->message .= "user_id not match login; ";
            $this->DeleteSignCookies();
            return false;
        }

        if ($_COOKIE['user_password'] !== $this->user_password) {
            $this->message .= "password not verified; ";
            $this->DeleteSignCookies();
            return false;
        }

        $this->AddSignCookies();
        return true;

    }

    /**
     * @return bool
     */
    public function SignInByPostParams(): bool
    {
        if (!isset($_POST['login']) || !isset($_POST['password'])){
            $this->message .= "login or password is undefined on params; ";
            return false;
        }

        $this->user_login = $_POST['login'];
        if(!$this->fillByUserLogin()){
            $this->message .= "user_id not match login; ";
            return false;
        }

        if (!password_verify($_POST['password'], $this->user_password)) {
            $this->message .= "password not verified; ";
            return false;
        }

        return true;

    }

    /**
     * @return bool
     */
    abstract public function fillByUserID ():bool;

    /**
     * @return bool
     */
    abstract public function fillByUserLogin():bool;

    /**
     * @return bool
     */
    public function fillByUserData(): bool
    {
        if(empty($this->user_data)){
            $this->clear();
            return false;
        }

        $this->user_id = $this->user_data['id'];
        $this->user_login = $this->user_data['login'];
        $this->user_password = $this->user_data['password_hash'];
        $this->user_status = $this->user_data['status'];
        $this->user_roles = is_null($this->user_data['roles']) ? [] : $this->user_data['roles'];
        $this->addSignSession();
        if($this->useCookie){
            $this->addSignCookies();
        }
        return true;
    }

    /**
     * @return void
     */
    private function addSignSession(): void
    {
        $_SESSION['user_signed_in'] = $this->user_signed_in;
        $_SESSION['user_id'] = $this->user_id;
        $_SESSION['user_login'] = $this->user_login;
        $_SESSION['user_password'] = $this->user_password;
        $_SESSION['user_status'] = $this->user_status;
        $_SESSION['user_roles'] = $this->user_roles;
    }

    /**
     * @return void
     */
    private function deleteSignSession(): void
    {
        $_SESSION['user_signed_in'] = false;
        $_SESSION['user_id'] = 0;
        $_SESSION['user_login'] = "";
        $_SESSION['user_password'] = "";
        $_SESSION['user_status'] = -1;
        $_SESSION['user_roles'] = [];
    }

    /**
     * @return void
     */
    private function addSignCookies(): void
    {
        if($this->useCookie){
            $AuthData = $this->encrypt_text(json_encode([
                'user_id' => $this->user_id,
                'user_password' => $this->user_password
            ]));
            setcookie('SecureAuthCookie', $AuthData, time() + $this->cookieTime, '/');
        }
    }

    /**
     * @return void
     */
    private function deleteSignCookies(): void
    {
        if($this->useCookie) {
            setcookie('SecureAuthCookie', '', 0, '/');
        }
    }

    /**'
     * @param string $text
     * @return string
     */
    public function encrypt_text(string $text): string
    {
        return openssl_encrypt($text, 0, "aes-256-cbc", OPENSSL_RAW_DATA);
    }
}