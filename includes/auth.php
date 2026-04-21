<?php
if (session_status() === PHP_SESSION_NONE) session_start();

define('BASE_URL', '/cuponera');

function isLoggedIn(): bool { return isset($_SESSION['user_id'], $_SESSION['user_tipo']); }
function isAdmin(): bool    { return isLoggedIn() && $_SESSION['user_tipo'] === 'admin'; }
function isEmpresa(): bool  { return isLoggedIn() && $_SESSION['user_tipo'] === 'empresa'; }
function isCliente(): bool  { return isLoggedIn() && $_SESSION['user_tipo'] === 'cliente'; }

function requireAdmin(): void {
    if (!isAdmin()) { redirect('/login.php?error=acceso_denegado'); }
}
function requireEmpresa(): void {
    if (!isEmpresa()) { redirect('/login.php?error=acceso_denegado'); }
}
function requireCliente(): void {
    if (!isCliente()) { redirect('/login.php?error=debes_iniciar_sesion'); }
}

function loginUser(int $id, string $nombre, string $tipo): void {
    session_regenerate_id(true);
    $_SESSION['user_id']     = $id;
    $_SESSION['user_nombre'] = $nombre;
    $_SESSION['user_tipo']   = $tipo;
}

function logoutUser(): void {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

function redirect(string $url): void {
    header('Location: ' . BASE_URL . $url);
    exit;
}

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function setFlash(string $tipo, string $mensaje): void {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function calcularEdad(string $fechaNacimiento): int {
    return (int)(new DateTime())->diff(new DateTime($fechaNacimiento))->y;
}

function generarToken(): string {
    return bin2hex(random_bytes(32));
}
