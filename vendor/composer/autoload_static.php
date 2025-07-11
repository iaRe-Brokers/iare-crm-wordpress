<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit860429726d06481305a1c2ad98028310
{
    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'IareCrm\\Admin\\MenuManager' => __DIR__ . '/../..' . '/app/admin/menu-manager.php',
        'IareCrm\\Admin\\Pages\\SettingsPage' => __DIR__ . '/../..' . '/app/admin/pages/settings-page.php',
        'IareCrm\\Admin\\Settings\\ApiSettings' => __DIR__ . '/../..' . '/app/admin/settings/api-settings.php',
        'IareCrm\\Api\\Client' => __DIR__ . '/../..' . '/app/api/client.php',
        'IareCrm\\Core\\Activator' => __DIR__ . '/../..' . '/app/core/activator.php',
        'IareCrm\\Core\\Deactivator' => __DIR__ . '/../..' . '/app/core/deactivator.php',
        'IareCrm\\Core\\Initializer' => __DIR__ . '/../..' . '/app/core/initializer.php',
        'IareCrm\\Helpers\\Validator' => __DIR__ . '/../..' . '/app/helpers/validator.php',
        'IareCrm\\Traits\\Singleton' => __DIR__ . '/../..' . '/app/traits/singleton.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit860429726d06481305a1c2ad98028310::$classMap;

        }, null, ClassLoader::class);
    }
}
