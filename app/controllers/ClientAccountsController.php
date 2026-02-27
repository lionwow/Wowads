<?php
namespace App\Controllers;

class ClientAccountsController {
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
        
        $accounts = \App\Models\SocialAccount::getByAgency($agencyId, $platform);
        
        require __DIR__ . '/../../views/dashboard/client_accounts.php';
    }

    public function add(): void {
        $user = $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $platform = $_POST['platform'] ?? '';
            $accountId = $_POST['account_id'] ?? '';
            $accountName = $_POST['account_name'] ?? '';
            
            if ($platform && $accountId && $accountName) {
                $agencyId = $user['agency_id'] ?? null;
                
                \App\Models\SocialAccount::create(
                    $user['id'],
                    $agencyId,
                    $platform,
                    $accountId,
                    $accountName
                );
                
                \Session::redirect('/client-accounts?success=added');
            }
        }
        
        \Session::redirect('/client-accounts');
    }

    public function delete(): void {
        $this->checkAuth();
        
        $id = $_GET['id'] ?? 0;
        if ($id) {
            \App\Models\SocialAccount::delete((int)$id);
        }
        
        \Session::redirect('/client-accounts');
    }
}
