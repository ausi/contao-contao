# Contao 4 core bundle

[![](https://img.shields.io/travis/contao/core-bundle/master.svg?style=flat-square)](https://travis-ci.org/contao/core-bundle/)
[![](https://img.shields.io/coveralls/contao/core-bundle/master.svg?style=flat-square)](https://coveralls.io/github/contao/core-bundle)
[![](https://img.shields.io/packagist/v/contao/core-bundle.svg?style=flat-square)](https://packagist.org/packages/contao/core-bundle)
[![](https://img.shields.io/packagist/dt/contao/core-bundle.svg?style=flat-square)](https://packagist.org/packages/contao/core-bundle)

Contao is an Open Source PHP Content Management System for people who want a
professional website that is easy to maintain. Visit the [project website][1]
for more information.

Contao 4 has been designed as a [Symfony][2] bundle, which can be used to add
CMS functionality to any Symfony application. If you do not have an existing
Symfony application yet, we recommend using the [Contao managed edition][3] as
basis for your application.

## Installation

Edit your `composer.json` file and add the following:

```json
"require": {
    "contao/core-bundle": "4.5.*",
    "contao/installation-bundle": "^4.5",
    "php-http/guzzle6-adapter": "^1.1"
},
"config": {
    "component-dir": "assets"
},
"post-install-cmd": [
    "Contao\\CoreBundle\\Composer\\ScriptHandler::addDirectories",
    "Contao\\CoreBundle\\Composer\\ScriptHandler::generateSymlinks"
],
"post-update-cmd": [
    "Contao\\CoreBundle\\Composer\\ScriptHandler::addDirectories",
    "Contao\\CoreBundle\\Composer\\ScriptHandler::generateSymlinks"
]
```

Then run `php composer.phar update` to install the vendor files.

Note that you can exchange the `php-http/guzzle6-adapter` package with any other [HTTP client implementation][4]. If you already have an HTTP client implementation, you can omit the package entirely.

## Activation

Remove the `parameters.yml` import from your `app/config/config.yml` file:

```yml
imports:
    - { resource: parameters.yml } # <-- remove this line
    - { resource: security.yml }
```

Then adjust to your `app/AppKernel.php` file:

```php
// app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Knp\Bundle\TimeBundle\KnpTimeBundle(),
            new Nelmio\CorsBundle\NelmioCorsBundle(),
            new Contao\CoreBundle\ContaoCoreBundle(),
            new Contao\InstallationBundle\ContaoInstallationBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $rootDir = $this->getRootDir();

        if (file_exists($rootDir.'/config/parameters.yml')) {
            $loader->load($rootDir.'/config/parameters.yml');
        }

        $loader->load($rootDir.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
```

## Configuration

Add the Contao routes to your `app/config/routing.yml` file:

```yml
ContaoInstallationBundle:
    resource: "@ContaoInstallationBundle/Resources/config/routing.yml"

ContaoCoreBundle:
    resource: "@ContaoCoreBundle/Resources/config/routing.yml"
```

Edit your `app/config/security.yml` file:

```yml
security:
    providers:
        contao.security.backend_user_provider:
            id: contao.security.backend_user_provider

        contao.security.frontend_user_provider:
            id: contao.security.frontend_user_provider

    encoders:
        Contao\User:
            algorithm: bcrypt

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt|error)|css|images|js)/
            security: false

        contao_install:
            pattern: ^/contao/install
            security: false

        contao_backend:
            entry_point: contao.security.entry_point
            request_matcher: contao.routing.backend_matcher
            provider: contao.security.backend_user_provider
            user_checker: contao.security.user_checker
            anonymous: ~
            switch_user: true
            logout_on_user_change: true

            contao_login:
                login_path: contao_backend_login
                check_path: contao_backend_login
                default_target_path: contao_backend
                success_handler: contao.security.authentication_success_handler
                failure_handler: contao.security.authentication_failure_handler
                remember_me: false

            two_factor:
                auth_form_path: contao_backend_login
                check_path: contao_backend_two_factor
                auth_code_parameter_name: verify

            logout:
                path: contao_backend_logout
                target: contao_backend_login
                handlers:
                    - contao.security.logout_handler

        contao_frontend:
            request_matcher: contao.routing.frontend_matcher
            provider: contao.security.frontend_user_provider
            user_checker: contao.security.user_checker
            anonymous: ~
            switch_user: false
            logout_on_user_change: true

            contao_login:
                login_path: contao_frontend_login
                check_path: contao_frontend_login
                default_target_path: contao_root
                failure_path: contao_root
                success_handler: contao.security.authentication_success_handler
                failure_handler: contao.security.authentication_failure_handler
                remember_me: true
                use_forward: true

            remember_me:
                secret: '%secret%'
                remember_me_parameter: autologin
                token_provider: contao.security.database_token_provider

            logout:
                path: contao_frontend_logout
                target: contao_root
                handlers:
                    - contao.security.logout_handler
                success_handler: contao.security.logout_success_handler

    access_control:
        - { path: ^/contao/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/contao, roles: ROLE_USER }
```

Edit your `app/config/config.yml` file and add the following:

```yml
# Contao configuration (all parameters are optional)
contao:
    web_dir:                  "%kernel.project_dir%/web"
    prepend_locale:           false
    encryption_key:           "%kernel.secret%"
    url_suffix:               .html
    upload_path:              files
    csrf_token_name:          contao_csrf_token
    pretty_error_screens:     true
    error_level:              8183 # E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED
    image:
        bypass_cache:         false
        target_dir:           "%kernel.project_dir%/assets/images"
        valid_extensions:     ['jpg', 'jpeg', 'gif', 'png', 'tif', 'tiff', 'bmp', 'svg', 'svgz']
        imagine_service:      ~
        imagine_options:
            jpeg_quality:     80
            interlace:        plane
        reject_large_uploads: false
    security:
        two_factor:
            enforce_backend:  false
```

You can also overwrite any parameter stored in the `localconfig.php` file:

```yml
# Contao configuration
contao:
    localconfig:
        adminEmail: foo@bar.com
        dateFormat: Y-m-d
```

## License

Contao is licensed under the terms of the LGPLv3.

## Getting support

Visit the [support page][5] to learn about the available support options.

[1]: https://contao.org
[2]: https://symfony.com
[3]: https://github.com/contao/managed-edition
[4]: https://packagist.org/providers/php-http/client-implementation
[5]: https://contao.org/en/support.html
