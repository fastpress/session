<?php

declare(strict_types=1);

namespace Fastpress\Security;

/**
 * Class Session
 * A class for managing PHP sessions with configurable op
 *
 * @package Fastpress\Security
 */
class Session implements \ArrayAccess
{
    /**
     * Represents $_SESSION for testing purposes
     */
    private $session;

    /**
     * Default session configuration.
     */
    private $defaultConfig = [
        'strict' => true,
        'cookie_path' => '/',
        'cache_expire' => 180,
        'cookie_secure' => false,
        'cache_limiter' => 'nocache',
        'hash_function' => 'sha256',
        'cookie_domain' => '',
        'referer_check' => '',
        'gc_maxlifetime' => 1440,
        'cookie_lifetime' => 0,
        'cookie_httponly' => true,
        'use_only_cookies' => 1,
        'session.sid_length' => 64,
        'session.sid_bits_per_character' => 5,
        'session.use_trans_sid' => 0,
        'session.cookie_samesite' => 'Lax'
    ];

    /**
     * Session constructor.
     *
     * @param array $config An array of session configuration options.
     */
    public function __construct(array &$session = null, array $config = [])
    {
        $this->session = &$session ?? $_SESSION;
        $this->applyConfig($config);
        $this->start();
    }

    /**
     * Apply session configuration options.
     *
     * @param array $config An array of session configuration options.
     */
    private function applyConfig(array $config): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = array_merge($this->defaultConfig, $config);

            // Apply session settings
            ini_set('session.use_strict_mode', $config['strict'] ? '1' : '0');
            session_set_cookie_params(
                $config['cookie_lifetime'],
                $config['cookie_path'],
                $config['cookie_domain'],
                $config['cookie_secure'],
                $config['cookie_httponly']
            );
            session_cache_expire($config['cache_expire']);
            session_cache_limiter($config['cache_limiter']);
            ini_set('session.hash_function', $config['hash_function']);
            ini_set('session.referer_check', $config['referer_check']);
            ini_set('session.gc_maxlifetime', strval($config['gc_maxlifetime']));
        }
    }

    /**
     * Start the session if not already started.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Set a session variable.
     *
     * @param string $key   The key of the session variable.
     * @param mixed  $value The value to set.
     */
    public function set(string $key, mixed $value): void
    {
        $this->session[$key] = $value;
    }

    /**
     * Set a flash message.
     *
     * @param string $key     The key of the flash message.
     * @param mixed  $message The message to set.
     */
    public function setFlash(string $key, mixed $message): void
    {
        if (!isset($this->session['flash'])) {
            $this->session['flash'] = [];
        }
        $this->session['flash'][$key] = $message;
    }

    /**
     * Get and remove a flash message.
     *
     * @param  string $key     The key of the flash message.
     * @param  mixed  $default The default value if flash message doesn't exist.
     * @return mixed The flash message value.
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        if ($this->hasFlash($key)) {
            $value = $this->session['flash'][$key];
            unset($this->session['flash'][$key]);
            return $value;
        }
        return $default;
    }

    /**
     * Check if a flash message exists.
     *
     * @param  string $key The key of the flash message.
     * @return bool True if flash message exists, false otherwise.
     */
    public function hasFlash(string $key): bool
    {
        return isset($this->session['flash'][$key]);
    }

    /**
     * Get a session variable.
     *
     * @param  string $key The key of the session variable.
     * @return mixed The value of the session variable.
     */
    public function get(string $key): mixed
    {
        if ($this->hasKey($key)) {
            return $this->session[$key];
        }
        return null;
    }

    /**
     * Check if a session variable exists.
     *
     * @param  string $key The key of the session variable.
     * @return bool True if session variable exists, false otherwise.
     */
    private function hasKey(string $key): bool
    {
        return isset($this->session[$key]);
    }

    /**
     * Delete a session variable.
     *
     * @param string $key The key of the session variable to delete.
     */
    public function delete(string $key): void
    {
        unset($this->session[$key]);
    }

    /**
     * Destroy the session.
     */
    public function destroy(): void
    {
        session_unset();
        session_destroy();
    }

    /**
     * Regenerate the session ID.
     */
    public function regenerateId(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Get all sessions
     */
    public function getAll(): array
    {
        return $this->session;
    }

    /**
     * Get a session variable using array access.
     *
     * @param  mixed $offset The key of the session variable.
     * @return mixed The value of the session variable.
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Set a session variable using array access.
     *
     * @param mixed $offset The key of the session variable.
     * @param mixed $value  The value to set.
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Check if a session variable exists using array access.
     *
     * @param  mixed $offset The key of the session variable.
     * @return bool True if session variable exists, false otherwise.
     */
    public function offsetExists($offset): bool
    {
        return $this->hasKey($offset);
    }

    /**
     * Unset a session variable using array access.
     *
     * @param mixed $offset The key of the session variable to unset.
     */
    public function offsetUnset($offset): void
    {
        $this->delete($offset);
    }
}
