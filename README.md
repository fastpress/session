# Fastpress\Session
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fastpress/session/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/fastpress/session/?branch=main)
**Fastpress\Session** is a modern, secure, and easy-to-use session management library for PHP. Crafted with simplicity and efficiency in mind, it seamlessly integrates with the Fastpress framework, yet remains flexible enough to be used in any PHP project. Whether you're building a small website or a large-scale application, Fastpress\Session provides a robust solution for handling session data securely and effectively.

## Features

- **Simple and intuitive API**: Easy to use methods for session manipulation.
- **Flash messages support**: Conveniently handle one-time messages for user feedback.
- **Secure session handling**: Enhanced security features to prevent common vulnerabilities.
- **Flexible configuration**: Customize session behavior to fit your application's needs.
- **PSR-4 autoloading**: Fully compliant with modern PHP standards.

## Installation

Use Composer to install Fastpress\Session into your project:

```bash
composer require fastpress/session
```
## Requirements
- PHP 7.4 or higher.

## Usage

### `start(): bool`

Starts the session.

**Returns:**

- `true` if the session was started successfully, `false` otherwise.


### `regenerateId(bool $deleteOldSession = true): bool`

Regenerates the session ID.

**Parameters:**

- `$deleteOldSession`: Whether to delete the old session data.

**Returns:**

- `true` on success, throws `RuntimeException` on failure.


### `token(): string`

Generates a CSRF token.

**Returns:**

- The generated CSRF token.


### `validateToken(string $token): bool`

Validates a CSRF token.

**Parameters:**

- `$token`: The token to validate.

**Returns:**

- `true` if the token is valid, `false` otherwise.


### `setFlash(string $key, mixed $value, string $type = 'info'): void`

Sets a flash message.

**Parameters:**

- `$key`: The key for the flash message.
- `$value`: The value of the flash message.
- `$type`: The type of flash message (e.g., 'info', 'success', 'error').

**Returns:**

- `void`


### `getFlash(string $key, mixed $default = null): mixed`

Gets a flash message.

**Parameters:**

- `$key`: The key for the flash message.
- `$default`: The default value to return if the flash message does not exist.

**Returns:**

- The flash message value or the default value.


### `closeWrite(): bool`

Closes the session for writing.

**Returns:**

- `true` on success, `false` otherwise.


### `gc(bool $force = false): bool`

Performs garbage collection on the session.

**Parameters:**

- `$force`: Whether to force garbage collection.

**Returns:**

- `true` on success, `false` otherwise.


### `destroy(): void`

Destroys the session.

**Returns:**

- `void`


### `set(string $key, mixed $value): void`

Sets a session variable.

**Parameters:**

- `$key`: The key for the session variable.
- `$value`: The value of the session variable.

**Returns:**

- `void`


### `get(string $key, mixed $default = null): mixed`

Gets a session variable.

**Parameters:**

- `$key`: The key for the session variable.
- `$default`: The default value to return if the session variable does not exist.

**Returns:**

- The session variable value or the default value.


### `has(string $key): bool`

Checks if a session variable exists.

**Parameters:**

- `$key`: The key for the session variable.

**Returns:**

- `true` if the session variable exists, `false` otherwise.


### `delete(string $key): void`

Deletes a session variable.

**Parameters:**

- `$key`: The key for the session variable.

**Returns:**

- `void`


### `clear(): void`

Clears all session variables.

**Returns:**

- `void`