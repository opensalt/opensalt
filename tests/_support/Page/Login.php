<?php

namespace Page;

interface Login
{
    public function logout(): Login;
    public function loginAsRole(string $role): Login;
    public function iFillInTheUsername(): Login;
    public function iFillInThePassword(): Login;
    public function aUserExistsWithRole(string $role): Login;
    public function aPendingUserExistsWithRole(string $role): Login;

    public function loginWithPassword(string $username, string $password): Login;
}
