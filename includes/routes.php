<?php
if (!defined('ABSPATH')) exit;

/**
 * Carga recursiva de todos los .php dentro de un directorio,
 * con exclusiones de carpetas y orden alfabético estable.
 *
 * - Ignora: archivos que NO terminen en .php
 * - Ignora: archivos cuyo nombre empiece por "_"
 * - Excluye: directorios en $exclude_dirs
 *
 * Requisitos:
 * - Constante WEBHELPERS_PATH definida (ruta absoluta del plugin, con slash final).
 */
if (!function_exists('webh_require_all_php_recursive')) {
    function webh_require_all_php_recursive($base_dir, array $exclude_dirs = []) {
        if (!is_dir($base_dir)) return;

        $base_dir = rtrim($base_dir, '/\\') . DIRECTORY_SEPARATOR;

        $it = new RecursiveDirectoryIterator(
            $base_dir,
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::FOLLOW_SYMLINKS
        );

        // Filtra directorios excluidos
        $filter = new RecursiveCallbackFilterIterator($it, function ($current, $key, $iterator) use ($base_dir, $exclude_dirs) {
            if ($current->isDir()) {
                $rel = str_replace($base_dir, '', $current->getPathname());
                $rel = trim($rel, '/\\');
                $first = $rel === '' ? '' : explode(DIRECTORY_SEPARATOR, $rel)[0];
                if ($first !== '' && in_array($first, $exclude_dirs, true)) {
                    return false; // no entrar en este dir
                }
            }
            return true;
        });

        $rii = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);

        // Volcamos en array y ordenamos por ruta para una carga determinista
        $files = [];
        foreach ($rii as $fileinfo) {
            if ($fileinfo->isFile()) {
                $path = $fileinfo->getPathname();
                $basename = $fileinfo->getBasename();

                // Solo .php
                if (substr($basename, -4) !== '.php') continue;

                // Evitar archivos "privados" tipo _algo.php
                if (substr($basename, 0, 1) === '_') continue;

                $files[] = $path;
            }
        }

        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($files as $php_file) {
            require_once $php_file;
        }
    }
}

/**
 * Orden recomendado de carga (por dependencias habituales):
 * 1) CPTs
 * 2) Helpers
 * 3) Shortcodes
 * 4) Enqueue y otros “bootstrap”
 *
 * Puedes ajustar $exclude_dirs si en el futuro agregas /vendor, /tests, etc.
 */
$exclude_dirs = ['assets', 'languages', 'node_modules', 'vendor'];

// CPTs (recursivo)
webh_require_all_php_recursive(WEBHELPERS_PATH . 'includes/cpt/', $exclude_dirs);

// Helpers (recursivo)
webh_require_all_php_recursive(WEBHELPERS_PATH . 'includes/helpers/', $exclude_dirs);

// Shortcodes (recursivo)
webh_require_all_php_recursive(WEBHELPERS_PATH . 'includes/shortcodes/', $exclude_dirs);

// Enqueues y bootstrap final (si quieres mantener archivos sueltos a mano)
require_once WEBHELPERS_PATH . 'includes/wp_enqueue_scripts.php';
