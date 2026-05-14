<?php
class Router {

    public function dispatch() {
        $controller = isset($_GET['c']) ? $_GET['c'] : 'usuario';
        $action     = isset($_GET['a']) ? $_GET['a'] : 'lista';

        // Sanitizar: solo letras y numeros
        $controller = preg_replace('/[^a-zA-Z0-9]/', '', $controller);
        $action     = preg_replace('/[^a-zA-Z0-9]/', '', $action);

        $className = ucfirst($controller) . 'Controller';
        $file      = __DIR__ . '/../controllers/' . $className . '.php';

        if (!file_exists($file)) {
            $this->notFound('Controlador no encontrado: ' . htmlspecialchars($className));
            return;
        }

        require_once $file;

        if (!class_exists($className)) {
            $this->notFound('Clase no encontrada: ' . htmlspecialchars($className));
            return;
        }

        $obj = new $className();

        if (!method_exists($obj, $action)) {
            $this->notFound('Accion no encontrada: ' . htmlspecialchars($action));
            return;
        }

        $obj->$action();
    }

    private function notFound($msg) {
        require_once __DIR__ . '/../views/layout/header.php';
        echo '<div style="padding:40px;color:#c00"><strong>404 - ' . $msg . '</strong></div>';
        require_once __DIR__ . '/../views/layout/footer.php';
    }
}