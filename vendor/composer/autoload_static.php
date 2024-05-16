<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita00942601aaeac7a7258a5bdd0ff1cb2
{
    public static $prefixLengthsPsr4 = array (
        'Z' => 
        array (
            'Zishu\\Myextend\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Zishu\\Myextend\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita00942601aaeac7a7258a5bdd0ff1cb2::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita00942601aaeac7a7258a5bdd0ff1cb2::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita00942601aaeac7a7258a5bdd0ff1cb2::$classMap;

        }, null, ClassLoader::class);
    }
}