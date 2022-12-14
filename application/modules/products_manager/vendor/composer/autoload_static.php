<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit57d68fcbc5ab20b09f0ea03c0fec09b4
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'Automattic\\WooCommerce\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Automattic\\WooCommerce\\' => 
        array (
            0 => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit57d68fcbc5ab20b09f0ea03c0fec09b4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit57d68fcbc5ab20b09f0ea03c0fec09b4::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
