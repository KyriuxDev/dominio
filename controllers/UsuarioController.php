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

    public function exportar() {
        // Tiempo máximo: 5 minutos para consultas grandes
        set_time_limit(300);

        $model = new UsuarioModel();
        try {
            $data = $model->exportar();
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }

        $filename = 'trabajadores_oaxaca_' . date('Ymd_Hi') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel

        fputcsv($out, $data['headers']);

        foreach ($data['rows'] as $row) {
            $line = array();
            foreach ($data['headers'] as $h) {
                $line[] = isset($row[$h]) ? $row[$h] : '';
            }
            fputcsv($out, $line);
        }

        fclose($out);
        exit;
    }
}