<?php
/**
 * PSR-4 style autoloader for the plugin.
 *
 * @package JourneyLoom
 */

namespace JourneyLoom;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Autoloader
 *
 * Maps JourneyLoom namespace to plugin directories.
 */
class Autoloader {

    /**
     * Namespace prefix.
     *
     * @var string
     */
    private static $prefix = 'JourneyLoom\\';

    /**
     * Namespace to directory mapping.
     *
     * @var array
     */
    private static $map = array(
        'JourneyLoom\\'          => 'includes/',
        'JourneyLoom\\Admin\\'   => 'admin/',
        'JourneyLoom\\Pub\\'     => 'public/',
    );

    /**
     * Register the autoloader.
     */
    public static function register() {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    /**
     * Autoload a class.
     *
     * @param string $class Fully qualified class name.
     */
    public static function autoload( $class ) {
        // Only handle our namespace.
        if ( 0 !== strpos( $class, self::$prefix ) ) {
            return;
        }

        // Try each mapping (most specific first).
        $file = self::resolve_file( $class );

        if ( $file && file_exists( $file ) ) {
            require_once $file;
        }
    }

    /**
     * Resolve a class name to a file path.
     *
     * @param string $class Fully qualified class name.
     * @return string|false File path or false.
     */
    private static function resolve_file( $class ) {
        $base_dir = WPTM_PLUGIN_DIR;

        // Sort mappings by length (longest prefix first).
        $sorted_map = self::$map;
        uksort( $sorted_map, function ( $a, $b ) {
            return strlen( $b ) - strlen( $a );
        } );

        foreach ( $sorted_map as $namespace => $dir ) {
            if ( 0 === strpos( $class, $namespace ) ) {
                $relative_class = substr( $class, strlen( $namespace ) );
                $relative_path  = str_replace( '\\', '/', $relative_class );

                // Convert CamelCase to kebab-case for file names.
                $parts    = explode( '/', $relative_path );
                $filename = array_pop( $parts );
                $filename = 'class-' . self::camel_to_kebab( $filename ) . '.php';

                $subdir = ! empty( $parts )
                    ? implode( '/', array_map( array( __CLASS__, 'camel_to_kebab' ), $parts ) ) . '/'
                    : '';

                return $base_dir . $dir . $subdir . $filename;
            }
        }

        return false;
    }

    /**
     * Convert CamelCase to kebab-case.
     *
     * @param string $string CamelCase string.
     * @return string kebab-case string.
     */
    private static function camel_to_kebab( $string ) {
        // Insert hyphen between a lowercase letter and an uppercase letter: tripType → trip-Type.
        $string = preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', $string );
        // Insert hyphen between an acronym and the start of the next word: AIEngine → AI-Engine.
        $string = preg_replace( '/([A-Z]+)([A-Z][a-z])/', '$1-$2', $string );
        return strtolower( $string );
    }
}
