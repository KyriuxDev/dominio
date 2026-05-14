<?php
require_once __DIR__ . '/../models/UsuarioModel.php';

class UsuarioController {

    // Minutos antes de que el cache expire automáticamente
    const CACHE_TTL = 30;

    public function detalle() {
        $sam   = isset($_GET['q']) ? trim($_GET['q']) : '';
        $entry = null;
        $error = '';

        if ($sam !== '') {
            $model = new UsuarioModel();
            try {
                $entry = $model->buscar($sam);
                if (!$entry) {
                    $error = 'Usuario no encontrado: ' . htmlspecialchars($sam);
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'No se especifico un usuario.';
        }

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/usuarios/detalle.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    public function lista() {
        $results  = array();
        $error    = '';
        $searched = false;

        $forzar = ($_SERVER['REQUEST_METHOD'] === 'POST');

        // Si hay cache válido y no se forzó actualización, usar cache
        if (!$forzar
            && isset($_SESSION['usuarios_cache'])
            && isset($_SESSION['usuarios_cache_time'])
            && (time() - $_SESSION['usuarios_cache_time']) < (self::CACHE_TTL * 60)
        ) {
            $results  = $_SESSION['usuarios_cache'];
            $searched = true;

        } elseif ($forzar || !isset($_SESSION['usuarios_cache'])) {
            $searched = true;
            $model    = new UsuarioModel();
            try {
                $results = $model->listar();
                // Guardar en sesión
                $_SESSION['usuarios_cache']      = $results;
                $_SESSION['usuarios_cache_time'] = time();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        $cacheInfo = isset($_SESSION['usuarios_cache_time'])
            ? date('d/m/Y H:i', $_SESSION['usuarios_cache_time'] + TZ_OFFSET * 3600)
            : null;

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/usuarios/lista.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }
}