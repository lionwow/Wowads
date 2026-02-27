<?php
namespace App\Controllers;

class DashboardController {
    public function index(): void {
        if (!\Session::isLoggedIn()) {
            \Session::redirect('/');
        }
        require __DIR__ . '/../../views/dashboard.php';
    }
}
