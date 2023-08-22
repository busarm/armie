# Change Log

All notable changes to this project will be documented in this file.

## [2.0.3]

### Added

- Support for socket connections

## [2.0.1]

### Added

- Async support for traditional http server using process forking
- Error handler interface
- Event handler for traditional http server using process forking
- Async bug fixes

### Changed

- Removed `slice()` from StorageBagInterface
- Update Queue handler for better performance
- Simplify Promises for better performance with promise chaining
- Update Types in docs

## [2.0]

Renamed to Armie

## [1.6.2]

### Added

- Added worker queue handler
- Added worker event handler
- Bug fixes

## [1.6.1]

### Added

- Use PHP 8.1 Enums
- Added Fibers support for await list
- Added Buffer support for async
- Added support for Promises

## [1.6.0]

### Added

- Major update for async and events support
- Upgrade PHP to v8.1

## [1.5.1]

### Added

- Added CRUD service repository for microservice clients
- Added distributed service discovery support

## [1.5.0]

### Added

- Event Loop Support with Workerman
- Support for stateless sessions and cookies
- Support encrypted sessions
- Bug fixes

## [1.4.3]

### Added

- Support for Providers: To inject custom features on init
- Auth resolvers for request
- Enum Type inferrence using php docs

### Changed

- Folder structure

## [1.4.0]

### Added

- Service discovery muti tenency services and microservices

## [1.3.9]

### Added

- Support for muti tenency services and service request

## [1.3.8]

### Added

- View route middleware - Route directly to view file

## [1.3.7]

### Added

- Session Middleware

### Changed

- Bags interfaces folder structure
- File names

## [1.3.5]

### Added

- Mini ORM - Modify relation conditions and limit
- Mini ORM - Save relations along with model

## [1.3.0]

### Added

- Mini ORM for database management

## [1.2.0]

### Changed

- Refactored Middleware Interface
- Refactored Dependency Management

### Added

- Support for PSR-15 Middlewares
- Custom extensible dependency resolver

## [1.1.7]

### Fixed

- Dependency Injection for bug in constructors for request and response object

### Added

- Container Interface and Trait
- Routes, support singletons

## [1.1.6]

### Changed

- Removed App-Wide Resolvers
- Prevent adding app-wide singletons on stateless mode

### Added

- Improve support for stateless mode
- Added support for stateless singletons in Request. Use `SingletonStatelessInterface` interface and `SingletonStateless` trait.

## [1.1.5]

### Changed

- Singleton Interface function `getInstance` to `make`

## [1.1.4]

### Fixed

- Minor bugs

### Added

- phpDocs generator
- Support for custom parameters for Dependency Injector

## [1.1.2]

### Fixed

- Middleware request bugs

### Added

- HttpServerInterface Interface
- Attach HttpServerInterface to App to allow adding routes directly on App instance

## [1.1.0]

### Changed

- Restructure Request Interface to support attributes bag
- Use Request & Response in Middleware instead of App instance to allow for stateless request

### Added

- Caching Interface
- Session Interface
- PHP Session Manager
- Cookie Manager
- Attributes bags for request attributes
- Support for PSR7 request and response
- Support for stateless request
- Support for CLI routing

## [1.0.17]

### Fixed

- Circular dependencies causing infinite loops

### Added

- On complete hooks for continious mode
- Callback for dependency injection to customise resolution
- `Arrayable` interface for custom array response handling

### Changed

- Improved dependency injection

## [1.0.16]

### Added

- Custom set functions for Request headers, query, files, cookies, attributes, body
- `ResponseHandlerInterface` for custom response handling

### Changed

- Dynamic config file loading. Moved from `app()->loadConfig(...)` to `app()->config->addFile(...)`
- Dynamic config management. Moved from `app()->config(...)` to `app()->config->get()/set()`
- Improve multi tenancy support
- Strict cors header validation. Fail if not valid
- Removed `environmentVars` params from `Request::fromUrl`

## [1.0.15]

### Fixed

- Cors not working in HTTP mode.

### Changed

- Remove default cors middleware.

## [1.0.14]

### Added

- CORS Middleware

## [1.0.12]

### Fixed

- Get App instance bug

## [1.0.11]

### Added

- Simple mock request capabilities to facilitate testing

### Fixed

- 'explode' extra param bug

## [1.0.10]

### Changed

- Removed function exist checking from helpers

## [1.0.9]

### Added

- Multi tenancy support
- Namespace for helper functions
- Guzzle Http for dev test
- Response optional buffer clearing based on 'continue' option

### Changed

- Removed basePath from configs. Use appPath only

## [1.0.6]

### Fixed

- Dependency Injection related bugs

## [1.0.5]

### Added

- Tests scripts
- Enums folder

### Changed

- Request, Response and Routing mechanisms

### Fixed

- Minor bugs
