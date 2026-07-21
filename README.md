# Jardis Factory

![Build Status](https://github.com/jardisSupport/factory/actions/workflows/ci.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.2-777BB4.svg)](https://www.php.net/)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg)](phpstan.neon)
[![PSR-12](https://img.shields.io/badge/Code%20Style-PSR--12-blue.svg)](phpcs.xml)
[![PSR-11](https://img.shields.io/badge/PSR--11-Container-blue.svg)](https://www.php-fig.org/psr/psr-11/)

> Part of **[Jardis](https://jardis.io)** — the Domain-Driven Design platform for PHP. You model your domain; Jardis generates the production-ready hexagonal code (DTOs, Command/Query handlers, repositories, persistence). This package is part of the open-source foundation that generated code runs on.

Lightweight PSR-11 container for PHP with pre-registered instances and reflection fallback. Three source files, one dependency (`psr/container`), zero configuration overhead.

---

## Features

- **PSR-11 ContainerInterface** — standard `get()` and `has()` contract
- **Pre-registered Instances** — pass objects at construction, retrieved by key
- **Backend Container Delegation** — delegates to Symfony, Laravel, PHP-DI, or any PSR-11 container
- **Reflection Fallback** — classes without required constructor params are instantiated automatically
- **Resolution Order** — Instances → Backend → Reflection, each step optional
- **Immutable** — all configuration via constructor, readonly after creation

---

## Installation

```bash
composer require jardissupport/factory
```

## Quick Start

```php
use JardisSupport\Factory\Factory;

// Minimal — uses reflection for instantiation
$factory = new Factory();
$instance = $factory->get(MyService::class);

// With pre-registered instances
$factory = new Factory(instances: [
    LoggerInterface::class => $logger,
    CacheInterface::class => $cache,
]);

$factory->get(LoggerInterface::class);  // → $logger
$factory->get(SomeSimpleClass::class);  // → new instance via reflection

// With backend container
$factory = new Factory(container: $symfonyContainer, instances: [
    'override.service' => $myOverride,
]);

// Instances win over backend; backend wins over reflection
```

## Resolution Order

```
get($id)
  ├── 1. Pre-registered instances (exact key match)
  ├── 2. Backend container ($container->has($id) ? $container->get($id))
  └── 3. Reflection (class_exists($id) ? new $id())
```

If none matches → `NotFoundException`.
If class exists but has required constructor params → `ContainerException`.

## Documentation

Full documentation, guides, and API reference:

**[docs.jardis.io/en/support/factory](https://docs.jardis.io/en/support/factory)**

---

## License

This package is licensed under the [MIT License](LICENSE.md).

---

**[Jardis](https://jardis.io)** · [Documentation](https://docs.jardis.io) · [Headgent](https://headgent.com)

<!-- BEGIN jardis/dev-skills README block — do not edit by hand -->
## AI-Assisted Development

This package ships with a skill for Claude Code, Cursor, Continue, and Aider. Install it in your consuming project:

```bash
composer require --dev jardis/dev-skills
```

More details: <https://docs.jardis.io/en/skills>
<!-- END jardis/dev-skills README block -->
