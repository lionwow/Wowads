<?php
namespace App\Controllers;

class ImagesController {
    private function checkAuth() {
        if (!\Session::isLoggedIn()) {
            \Session::redirect('/');
        }
        return \App\Models\User::findById(\Session::getUserId());
    }

    public function index(): void {
        $user = $this->checkAuth();
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        $platform = $_GET['platform'] ?? null;
        $search = $_GET['search'] ?? '';
        
        $images = \App\Models\Image::getByAgency($agencyId, $platform, $search);
        
        require __DIR__ . '/../../views/dashboard/images.php';
    }

    public function delete(): void {
        $this->checkAuth();
        
        $id = $_GET['id'] ?? 0;
        if ($id) {
            \App\Models\Image::delete((int)$id);
        }
        
        \Session::redirect('/images');
    }
}
