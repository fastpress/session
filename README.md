# Fastpress\Session

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
**Setting Session Data**
```php
use Fastpress\Security\Session;

$session = new Session();
$session->start();
```

**Setting Session Data**
```php
$session->set('username', 'JohnDoe');
```

**Retrieving Session Data**
```php
$username = $session->get('username');
```

**Flash Messages**
```php
// Set a flash message
$session->setFlash('success', 'You have successfully logged in.');

// Retrieve and clear the flash message
$message = $session->getFlash('success');
```

**Destroying a Session**
```php
$session->destroy();
```

## Contributing
Contributions are welcome! Please feel free to submit a pull request or open issues to improve the library.


## License
This library is open-sourced software licensed under the MIT license.

## Support
If you encounter any issues or have questions, please file them in the issues section on GitHub.


