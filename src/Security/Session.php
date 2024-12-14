<?php

declare(strict_types=1);

namespace Fastpress\Security;

use RuntimeException;

/**
 * A secure session management class with enhanced features.
 *
 * This class provides a secure way to manage PHP sessions, with features
 * such as CSRF protection, flash messages, and session regeneration.
 *
 * @implements \ArrayAccess<string, mixed>
 */
class Session implements \ArrayAccess
{
    /** @var array<string, mixed> The session data. */
    private array $session;
    /** @var array<string, mixed> Flash message data. */
    private array $flashData = [];
    /** @var bool Whether the session has been started. */
    private bool $isStarted = false;
    /** @var int|null The last time the session ID was regenerated. */
    private int $lastRegenerationTime;

    /** @var int Session ID regeneration interval in seconds. */
    private const ID_REGENERATION_INTERVAL = 300; // 5 minutes
    /** @var int Flash message lifetime in seconds. */
    private const FLASH_LIFETIME = 3600; // 1 hour
    /** @var int CSRF token lifetime in seconds. */
    private const TOKEN_LIFETIME = 1800; // 30 minutes

    /** @var array<string, mixed> Default session configuration. */
    private const DEFAULT_CONFIG = [
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => true,
        'use_only_cookies' => 1,
        'use_strict_mode' => 1,
        'sid_length' => 48,
        'sid_bits_per_character' => 6,
        'hash_function' => 'sha256',
        'use_trans_sid' => 0,
        'gc_maxlifetime' => 7200,
        'gc_probability' => 1,
        'gc_divisor' => 100,
        'cookie_lifetime' => 0,
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cache_limiter' => 'nocache',
        'cache_expire' => 180,
    ];

    /**
     * Constructor for the Session class.
     *
     * This constructor initializes the session with the provided configuration.
     * If a session has not already been started, it merges the default configuration
     * with the provided configuration, configures the session, and starts it.
     * 
     * @param array $config Optional. An array of configuration settings to override the default settings.
     */
    public function __construct(array $config = [])
    {
        // Start session first if needed
        if (session_status() === PHP_SESSION_NONE) {
            $this->configure(array_merge(self::DEFAULT_CONFIG, $config));
            session_start();
        }
        
        $this->session = &$_SESSION;
        $this->loadFlashData(); // Add this line to load flash data when session starts
    }

    /**
     * Validates the session configuration.
     *
     * @param array<string, mixed> $config  The session configuration.
     *
     * @throws RuntimeException If a security critical option is disabled.
     */
    private function validateConfig(array $config): void
    {
        $required = ['cookie_secure', 'cookie_httponly', 'use_strict_mode'];
        foreach ($required as $key) {
            if (isset($config[$key]) && !$config[$key]) {
                throw new RuntimeException("Security critical option '$key' cannot be disabled");
            }
        }
    }

    /**
     * Configures the session.
     *
     * @param array<string, mixed> $config  The session configuration.
     */
    private function configure(array $config): void
    {
        // Skip configuration if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Apply session settings
        session_set_cookie_params([
            'lifetime' => $config['cookie_lifetime'],
            'path' => $config['cookie_path'],
            'domain' => $config['cookie_domain'],
            'secure' => $config['cookie_secure'],
            'httponly' => $config['cookie_httponly'],
            'samesite' => $config['cookie_samesite']
        ]);

        // Set additional INI settings
        ini_set('session.sid_length', (string)$config['sid_length']);
        ini_set('session.sid_bits_per_character', (string)$config['sid_bits_per_character']);
        ini_set('session.hash_function', $config['hash_function']);
        ini_set('session.use_trans_sid', (string)$config['use_trans_sid']);
    }

    /**
     * Starts the session.
     *
     * @return bool True if the session was started, false otherwise.
     *
     * @throws RuntimeException If headers have already been sent or the session failed to start.
     */
    public function start(): bool
    {
        if ($this->isStarted) {
            return true;
        }

        if (headers_sent($file, $line)) {
            throw new RuntimeException(
                sprintf('Headers have already been sent in "%s" at line %d', $file, $line)
            );
        }

        // Use options array for atomic session start
        $success = session_start([
            'use_strict_mode' => 1,
            'cookie_httponly' => true,
            'cookie_secure' => true
        ]);

        if (!$success) {
            throw new RuntimeException('Failed to start session');
        }

        $this->isStarted = true;
        $this->checkRegenerateId();
        $this->gc();

        return true;
    }

    /**
     * Checks if the session ID needs to be regenerated.
     */
    private function checkRegenerateId(): void
    {
        $now = time();
        if ($now - ($this->lastRegenerationTime ?? 0) > self::ID_REGENERATION_INTERVAL) {
            $this->regenerateId();
            $this->lastRegenerationTime = $now;
            $this->session['__last_regeneration'] = $now;
        }
    }

    /**
     * Regenerates the session ID.
     *
     * @param bool $deleteOldSession Whether to delete the old session.
     *
     * @return bool True if the session ID was regenerated, false otherwise.
     *
     * @throws RuntimeException If the session is not started or the ID regeneration failed.
     */
    public function regenerateId(bool $deleteOldSession = true): bool
    {
        if (!$this->isStarted) {
            throw new RuntimeException('Session not started');
        }

        if (!session_regenerate_id($deleteOldSession)) {
            throw new RuntimeException('Failed to regenerate session ID');
        }

        return true;
    }

    /**
     * Generates a CSRF token.
     *
     * @return string The CSRF token.
     */
    public function token(): string
    {
        $token = $this->session['_token'] ?? null;
        $timestamp = $this->session['_token_timestamp'] ?? 0;

        if (!$token || (time() - $timestamp) > self::TOKEN_LIFETIME) {
            $token = bin2hex(random_bytes(32));
            $this->session['_token'] = $token;
            $this->session['_token_timestamp'] = time();
        }

        return $token;
    }

    /**
     * Validates a CSRF token.
     *
     * @param string $token The CSRF token to validate.
     *
     * @return bool True if the token is valid, false otherwise.
     */
    public function validateToken(string $token): bool
    {
        $storedToken = $this->session['_token'] ?? null;
        $timestamp = $this->session['_token_timestamp'] ?? 0;

        if (!$storedToken || (time() - $timestamp) > self::TOKEN_LIFETIME) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    /**
     * Sets a flash message.
     *
     * @param string $key The key of the flash message.
     * @param mixed $value The value of the flash message.
     * @param string $type The type of the flash message (e.g., 'info', 'error').
     */
    public function setFlash(string $key, mixed $value, string $type = 'info'): void
    {
        $this->session['__flash'][$key] = [
            'value' => $value,
            'type' => $type,
            'timestamp' => time()
        ];
    }
    

    /**
     * Gets a flash message.
     *
     * @param string $key The key of the flash message.
     * @param mixed $default The default value to return if the message does not exist.
     *
     * @return mixed The value of the flash message or the default value.
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        if (isset($this->session['__flash'][$key])) {
            $flash = $this->session['__flash'][$key];
    
            // Check if flash data has expired
            if (time() - $flash['timestamp'] > self::FLASH_LIFETIME) {
                unset($this->session['__flash'][$key]);
                return $default;
            }
    
            // Mark for removal after being retrieved
            unset($this->session['__flash'][$key]);
            return $flash['value'];
        }
        return $default;
    }


    /**
     * Generates an HTML input field containing the CSRF token.
     *
     * @return string The HTML input field with the CSRF token.
     */
    public function csrfField(): string
    {
        return sprintf(
            '<input type="hidden" name="_csrf" value="%s">',
            htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Checks if a flash message exists and hasn't expired.
     *
     * @param string $key The key of the flash message.
     * @param string|null $type Optional type to check for specific flash message type.
     *
     * @return bool True if the flash message exists and is valid, false otherwise.
     */
    public function hasFlash(string $key, ?string $type = null): bool
    {
        // Check if flash data exists
        if (!isset($this->session['__flash'][$key])) {
            return false;
        }
    
        $flash = $this->session['__flash'][$key];
    
        // Check if flash has expired
        if (time() - $flash['timestamp'] > self::FLASH_LIFETIME) {
            unset($this->session['__flash'][$key]);
            return false;
        }
    
        // If type is specified, check if it matches
        if ($type !== null && (!isset($flash['type']) || $flash['type'] !== $type)) {
            return false;
        }
    
        return true;
    }

    /**
     * Loads flash message data from the session.
     */
    private function loadFlashData(): void
    {
        // Move new flash data to current
        if (isset($this->session['__flash_new'])) {
            $this->session['__flash'] = $this->session['__flash_new'];
            unset($this->session['__flash_new']);
        }

        // Clean expired flash data
        $this->flashData = array_filter(
            $this->session['__flash'] ?? [],
            fn($flash) => (time() - $flash['timestamp']) <= self::FLASH_LIFETIME
        );

        $this->session['__flash'] = $this->flashData;
    }

    /**
     * Closes the session for writing.
     *
     * @return bool True if the session was closed for writing, false otherwise.
     */
    public function closeWrite(): bool
    {
        if ($this->isStarted) {
            $this->isStarted = !session_write_close();
            return !$this->isStarted;
        }
        return true;
    }

    /**
     * Performs garbage collection on the session.
     *
     * @param bool $force Whether to force garbage collection.
     *
     * @return bool True if garbage collection was successful, false otherwise.
     */
    public function gc(bool $force = false): bool
    {
        if ($force || (mt_rand(1, 100) <= self::DEFAULT_CONFIG['gc_probability'])) {
            return session_gc();
        }
        return true;
    }

    /**
     * Destroys the session.
     *
     * @throws RuntimeException If the session failed to be destroyed.
     */
    public function destroy(): void
    {
        if ($this->isStarted) {
            $this->clear();
            if (!session_destroy()) {
                throw new RuntimeException('Failed to destroy session');
            }
            $this->isStarted = false;
        }

        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            array_merge($params, ['expires' => time() - 42000])
        );
    }

    /**
     * Sets a session variable.
     *
     * @param string $key The key of the variable.
     * @param mixed $value The value of the variable.
     */
    public function set(string $key, mixed $value): void
    {
        $this->session[$key] = $value;
    }

    /**
     * Gets a session variable.
     *
     * @param string $key The key of the variable.
     * @param mixed $default The default value to return if the variable does not exist.
     *
     * @return mixed The value of the variable or the default value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->session[$key] ?? $default;
    }

    /**
     * Checks if a session variable exists.
     *
     * @param string $key The key of the variable.
     *
     * @return bool True if the variable exists, false otherwise.
     */
    public function has(string $key): bool
    {
        return isset($this->session[$key]);
    }

    /**
     * Deletes a session variable.
     *
     * @param string $key The key of the variable.
     */
    public function delete(string $key): void
    {
        unset($this->session[$key]);
    }

    /**
     * Clears all session variables.
     */
    public function clear(): void
    {
        $this->session = [];
    }

    /**
     * Checks if an offset exists.
     *
     * @param string $offset The offset to check.
     *
     * @return bool True if the offset exists, false otherwise.
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Gets the value at an offset.
     *
     * @param string $offset The offset to get the value from.
     *
     * @return mixed The value at the offset.
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Sets the value at an offset.
     *
     * @param string $offset The offset to set the value at.
     * @param mixed $value The value to set.
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Unsets the value at an offset.
     *
     * @param string $offset The offset to unset.
     */
    public function offsetUnset($offset): void
    {
        $this->delete($offset);
    }

    /**
     * Destructor.
     *
     * Closes the session for writing when the object is destroyed.
     */
    public function __destruct()
    {
        if ($this->isStarted) {
            $this->closeWrite();
        }
    }
}