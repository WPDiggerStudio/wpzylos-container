# WPZylos Container

[![PHP Version](https://img.shields.io/badge/php-%5E8.0-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![GitHub](https://img.shields.io/badge/GitHub-WPDiggerStudio-181717?logo=github)](https://github.com/WPDiggerStudio/wpzylos-container)

PSR-11 compatible dependency injection container with auto-wiring for WordPress plugins.

📖 **[Full Documentation](https://wpzylos.com)** | 🐛 **[Report Issues](https://github.com/WPDiggerStudio/wpzylos-container/issues)**

---

## ✨ Features

- **PSR-11 Compatible** — Implements `ContainerInterface`
- **Auto-wiring** — Automatic constructor injection
- **Singletons** — Shared instances across resolves
- **Factories** — New instances per resolution
- **Tagged Services** — Group related services
- **Contextual Binding** — Different implementations per consumer

---

## 📋 Requirements

| Requirement | Version |
| ----------- | ------- |
| PHP         | ^8.0    |

---

## 🚀 Installation

```bash
composer require wpdiggerstudio/wpzylos-container
```

---

## 📖 Quick Start

```php
use WPZylos\Framework\Container\Container;

$container = new Container();

// Bind a singleton (shared instance)
$container->singleton(DatabaseConnection::class, fn() => new DatabaseConnection());

// Bind a factory (new instance each time)
$container->bind(Logger::class, fn() => new Logger());

// Auto-wiring (automatic dependency resolution)
$container->bind(UserService::class);

// Resolve
$db = $container->get(DatabaseConnection::class);
$logger = $container->get(Logger::class);
$userService = $container->get(UserService::class);
```

---

## 🏗️ Core Features

### Singleton Binding

```php
// Registered once, shared everywhere
$container->singleton(Config::class, fn() => new Config('config.php'));

$config1 = $container->get(Config::class);
$config2 = $container->get(Config::class);
// $config1 === $config2
```

### Factory Binding

```php
// New instance every time
$container->bind(Request::class, fn() => new Request());

$req1 = $container->get(Request::class);
$req2 = $container->get(Request::class);
// $req1 !== $req2
```

### Auto-wiring

```php
class UserService {
    public function __construct(
        private DatabaseConnection $db,
        private Logger $logger
    ) {}
}

// Container automatically resolves dependencies
$container->bind(UserService::class);
$userService = $container->get(UserService::class);
```

### Interface Binding

```php
$container->bind(CacheInterface::class, RedisCache::class);
$container->singleton(LoggerInterface::class, FileLogger::class);
```

### Tagged Services

```php
$container->tag([EmailNotifier::class, SlackNotifier::class], 'notifiers');

$notifiers = $container->tagged('notifiers');
foreach ($notifiers as $notifier) {
    $notifier->send($message);
}
```

---

## 📦 Related Packages

| Package                                                                | Description              |
| ---------------------------------------------------------------------- | ------------------------ |
| [wpzylos-core](https://github.com/WPDiggerStudio/wpzylos-core)         | Application foundation   |
| [wpzylos-config](https://github.com/WPDiggerStudio/wpzylos-config)     | Configuration management |
| [wpzylos-scaffold](https://github.com/WPDiggerStudio/wpzylos-scaffold) | Plugin template          |

---

## 📖 Documentation

For comprehensive documentation, tutorials, and API reference, visit **[wpzylos.com](https://wpzylos.com)**.

---

## ☕ Support the Project

If you find this package helpful, consider buying me a coffee! Your support helps maintain and improve the WPZylos ecosystem.

<a href="https://www.paypal.com/donate/?hosted_button_id=66U4L3HG4TLCC" target="_blank">
  <img src="https://img.shields.io/badge/Donate-PayPal-blue.svg?style=for-the-badge&logo=paypal" alt="Donate with PayPal" />
</a>

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

**Made with ❤️ by [WPDiggerStudio](https://github.com/WPDiggerStudio)**
