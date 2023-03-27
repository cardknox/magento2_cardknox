<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3659f1803ec6116325a52d210140d006
{
    public static $files = array (
        '0a46c591a95f136ab495524fe91af60f' => __DIR__ . '/../..' . '/registration.php',
    );

    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'CardknoxDevelopment\\Cardknox\\' => 29,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'CardknoxDevelopment\\Cardknox\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3659f1803ec6116325a52d210140d006::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3659f1803ec6116325a52d210140d006::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit3659f1803ec6116325a52d210140d006::$classMap;

        }, null, ClassLoader::class);
    }
}