# jardissupport/factory

Minimal PSR-11 container: a single `Factory` class, no shared registry, no ClassVersion support, Reflection fallback only for parameterless constructors.

## Usage essentials

- **One class, two APIs:** `Factory` implements `Psr\Container\ContainerInterface` (`get()`, `has()`) and additionally provides `create(string $className, mixed ...$parameters): object`. `get()` is a lookup with a fallback chain, `create()` always returns a new instance with parameters — no cache, no container lookup.
- **`get()` resolution order is strict:** 1) pre-registered `$instances` (exact key match), 2) backend `ContainerInterface::has()/get()`, 3) `class_exists()` + Reflection `new $className()`, 4) `NotFoundException`. Step 3 applies **only** for parameterless constructors — classes with required params via `get()` throw `ContainerException`; use `create()` for those.
- **Immutable after construction:** `$instances` and `$container` are `readonly`. No `register*()`/`registerShared()` methods, no post-construction mutation. All instances must be passed in the constructor: `new Factory($backend, ['logger' => $logger])`.
- **No shared registry, no instance reuse:** Step 3 (Reflection) creates a new instance every time — if Singleton behavior is required, inject a backend container (e.g. PHP-DI) or pre-register the instance.
- **No ClassVersion support.** Versioned classes are resolved in the Kernel (`jardiscore/kernel`), not in the Factory. The Factory sees only the final class name.
- **Layer rule:** `Factory` lives in `Infrastructure/Support` and is consumed by the Application Layer — the **Domain never imports** `JardisSupport\Factory\Factory`. Exceptions: `NotFoundException` (`extends \InvalidArgumentException implements NotFoundExceptionInterface`) and `ContainerException` (`extends \RuntimeException implements ContainerExceptionInterface`).

## Full reference

https://docs.jardis.io/en/support/factory
