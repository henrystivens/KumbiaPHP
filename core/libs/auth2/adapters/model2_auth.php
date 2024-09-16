<?php
/**
 * KumbiaPHP web & app Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @category   Kumbia
 * @package    Auth
 * @subpackage Adapters
 * 
 * @copyright  Copyright (c) 2005 - 2024 KumbiaPHP Team (http://www.kumbiaphp.com)
 * @license    https://github.com/KumbiaPHP/KumbiaPHP/blob/master/LICENSE   New BSD License
 */

/**
 * Authentication Class using password_hash and password_verify
 *
 * This class provides a secure method for user authentication using PHP's
 * built-in password hashing and verification functions.
 *
 * @category   Kumbia
 * @package    Auth
 * @subpackage Adapters
 */
class Model2Auth extends Auth2
{
    /**
     * Model to use for the authentication process
     *
     * @var string
     */
    protected $_model = 'users';

    /**
     * Session namespace where model fields will be loaded
     *
     * @var string
     */
    protected $_sessionNamespace = 'default';

    /**
     * Fields to be loaded from the model
     *
     * @var array
     */
    protected $_fields = ['id'];

    /**
     * Method to use for finding the user
     *
     * @var string
     */
    protected $_findMethod = 'findByLogin';

    /**
     * Set the model to be used for authentication
     *
     * @param string $model Name of the model
     * @return void
     */
    public function setModel($model)
    {
        $this->_model = $model;
    }

    /**
     * Set the session namespace where model fields will be loaded
     *
     * @param string $namespace Session namespace
     * @return void
     */
    public function setSessionNamespace($namespace)
    {
        $this->_sessionNamespace = $namespace;
    }

    /**
     * Specify which model fields should be loaded into the session
     *
     * @param array $fields Fields to load
     * @return void
     */
    public function setFields($fields)
    {
        $this->_fields = $fields;
    }

    /**
     * Set the method to use for finding the user
     *
     * @param string $method Name of the method
     * @return void
     */
    public function setFindMethod($method)
    {
        $this->_findMethod = $method;
    }

    /**
     * Check user credentials
     *
     * This method verifies the user's credentials and sets session variables
     * if authentication is successful.
     *
     * @param string $username The username to check
     * @param string $password The password to verify
     * @return bool True if authentication is successful, false otherwise
     */
    protected function _check($username, $password)
    {
        if ($this->hasEmptyCredentials($username, $password)) {
            $this->setError('Username and password are required.');
            return false;
        }

        if (!$this->isValidReferer()) {
            return false;
        }

        $username = $this->sanitizeUsername($username);
        $user = $this->findUser($username);
        if (!$user || !$this->isPasswordValid($password, $user->{$this->_pass})) {
            $this->handleInvalidCredentials();
            return false;
        }

        $this->loadUserAttributesIntoSession($user);
        Session::set($this->_key, true);
        return true;
    }

    /**
     * Checks if the provided username and password are empty
     *
     * @param string $username The username to check
     * @param string $password The password to check
     * @return bool True if either the username or password is empty, false otherwise
     */
    private function hasEmptyCredentials(string $username, string $password): bool
    {
        return empty($username) || empty($password);
    }

    /**
     * Validates the HTTP referer to prevent CSRF attacks
     *
     * This method checks if the referer is from the same host as the current request.
     * If the referer is invalid or missing, it logs the potential security breach
     * and sets an error message.
     *
     * @return bool Returns true if the referer is valid, false otherwise
     */
    private function isValidReferer(): bool
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';

        if (empty($referer) || !str_contains($referer, $host)) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            self::log(sprintf(
                "Potential security breach: Invalid referer detected. IP: %s, User-Agent: %s, Referer: %s",
                $ip,
                $userAgent,
                $referer
            ));
            $this->setError(
                'Access denied due to security policy. If you believe this is an error, please contact the administrator.'
            );
            return false;
        }
        return true;
    }

    /**
     * Sanitizes a provided username.
     *
     * @param string $username The username to be sanitized.
     * @return string The sanitized username.
     */
    private function sanitizeUsername(string $username): string
    {
        return htmlspecialchars(strip_tags($username), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Finds a user by their username.
     *
     * @param string $username The username of the user to find.
     * @return object|null Returns the user object if found, null otherwise.
     */
    private function findUser(string $username): ?object
    {
        $model = new $this->_model;
        if (!method_exists($model, $this->_findMethod)) {
            $this->setError(sprintf("Method %s not found in model", $this->_findMethod));
            return null;
        }
        return $model->{$this->_findMethod}($username);
    }

    /**
     * Checks if the provided password matches the hashed password.
     *
     * @param string $password The password to be checked.
     * @param string $hashedPassword The hashed password to compare against.
     * @return bool Returns true if the password is valid, false otherwise.
     */
    private function isPasswordValid(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Sets an error message for invalid credentials and updates the session key.
     *
     * @return void
     */
    private function handleInvalidCredentials(): void
    {
        $this->setError('Invalid username or password. Please try again.');
        Session::set($this->_key, false);
    }

    /**
     * Loads user attributes into session.
     *
     * @param object $user The user object.
     * @return void
     */
    private function loadUserAttributesIntoSession(object $user): void
    {
        foreach ($this->_fields as $field) {
            Session::set($field, $user->$field, $this->_sessionNamespace);
        }
    }

    /**
     * Create a password hash for storage in the database
     *
     * This static method should be used when creating a new user or
     * updating a user's password.
     *
     * @param string $password The password to hash
     * @return string The hashed password
     */
    public static function createHash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
