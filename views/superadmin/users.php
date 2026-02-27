<?php
// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/wowads/' : rtrim($scriptDir, '/') . '/';
} else {
    $baseUrl = BASE_URL;
}

$role = $_GET['role'] ?? null;
$status = $_GET['status'] ?? null;
$search = $_GET['search'] ?? '';
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Super Admin - WowAds</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%23A8FF35' rx='20' width='100' height='100'/><text y='.9em' font-size='80' x='50%' text-anchor='middle' fill='%23000'>W</text></svg>">
    <style>
        .superadmin-layout { display: flex; min-height: 100vh; }
        .sidebar-admin { width: 260px; background: var(--bg-light); border-right: 1px solid var(--border); position: fixed; top: 0; left: 0; bottom: 0; display: flex; flex-direction: column; }
        .sidebar-admin .brand { padding: 24px; border-bottom: 1px solid var(--border); }
        .sidebar-admin .brand h2 { font-size: 18px; color: var(--primary); }
        .sidebar-admin .brand span { font-size: 12px; color: var(--text-muted); display: block; margin-top: 4px; }
        .sidebar-admin .nav { flex: 1; padding: 16px 12px; }
        .sidebar-admin .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: var(--text-muted); text-decoration: none; border-radius: var(--radius-sm); font-size: 14px; margin-bottom: 4px; transition: all 0.15s; }
        .sidebar-admin .nav-item:hover, .sidebar-admin .nav-item.active { background: var(--bg-lighter); color: var(--text); }
        .sidebar-admin .nav-item.active { border-left: 3px solid var(--primary); }
        .sidebar-admin .nav-section { margin-bottom: 20px; }
        .sidebar-admin .nav-section-title { font-size: 11px; font-weight: 600; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; padding: 8px 16px; }
        .main-admin { flex: 1; margin-left: 260px; padding: 32px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .admin-header h1 { font-size: 24px; font-weight: 600; }
        .admin-table { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .admin-table table { width: 100%; border-collapse: collapse; }
        .admin-table th { text-align: left; padding: 16px; background: var(--bg-light); font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }
        .admin-table td { padding: 16px; border-bottom: 1px solid var(--border); }
        .admin-table tr:last-child td { border-bottom: none; }
        .admin-table tr:hover td { background: var(--bg-lighter); }
        .admin-table .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .admin-table .btn-action { padding: 6px 12px; border-radius: var(--radius-xs); font-size: 12px; cursor: pointer; border: none; text-decoration: none; display: inline-block; }
        .admin-table .btn-view { background: var(--primary-dim); color: var(--primary); }
        .admin-table .btn-edit { background: rgba(255, 165, 2, 0.1); color: var(--warning); }
        .admin-table .btn-delete { background: rgba(255, 71, 87, 0.1); color: var(--danger); }
        .admin-table .btn-suspend { background: rgba(136, 136, 136, 0.1); color: var(--text-muted); }
        .admin-table .btn-activate { background: rgba(46, 213, 115, 0.1); color: var(--success); }
        .status-badge { display: inline-flex; padding: 4px 10px; border-radius: var(--radius-xs); font-size: 12px; font-weight: 500; }
        .status-active { background: rgba(46, 213, 115, 0.1); color: var(--success); }
        .status-suspended { background: rgba(255, 71, 87, 0.1); color: var(--danger); }
        .btn-primary-admin { padding: 12px 24px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .filters { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
        .filters select, .filters input { padding: 10px 16px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); }
        .filters input { width: 250px; }
        .filters button { padding: 10px 20px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 32px; max-width: 500px; width: 90%; }
        .modal-content h3 { margin-bottom: 24px; }
        .form-group-admin { margin-bottom: 20px; }
        .form-group-admin label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px; }
        .form-group-admin input, .form-group-admin select { width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-size: 14px; }
        .modal-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; }
        .btn-cancel { padding: 12px 24px; background: transparent; color: var(--text); border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
    <div class="superadmin-layout">
        <aside class="sidebar-admin">
            <div class="brand">
                <h2>WowAds</h2>
                <span>Super Admin</span>
            </div>
            <nav class="nav">
                <div class="nav-section">
                    <div class="nav-section-title">General</div>
                    <a href="<?php echo $baseUrl; ?>superadmin" class="nav-item">Dashboard</a>
                    <a href="<?php echo $baseUrl; ?>superadmin/reports" class="nav-item">Informes</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Gestión</div>
                    <a href="<?php echo $baseUrl; ?>superadmin/users" class="nav-item active">Usuarios</a>
                    <a href="<?php echo $baseUrl; ?>superadmin/agencies" class="nav-item">Agencias</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Configuración</div>
                    <a href="<?php echo $baseUrl; ?>superadmin/plans" class="nav-item">Planes</a>
                    <a href="<?php echo $baseUrl; ?>superadmin/settings" class="nav-item">API & Conexiones</a>
                </div>
            </nav>
            <div style="padding: 16px; border-top: 1px solid var(--border);">
                <a href="<?php echo $baseUrl; ?>dashboard" class="nav-item">← Volver al Panel</a>
                <a href="<?php echo $baseUrl; ?>auth/logout" class="nav-item" style="color: var(--danger);">Cerrar Sesión</a>
            </div>
        </aside>
        
        <main class="main-admin">
            <div class="admin-header">
                <h1>Gestión de Usuarios</h1>
            </div>
            
            <form method="GET" action="<?php echo $baseUrl; ?>superadmin/users" class="filters">
                <input type="text" name="search" placeholder="Buscar usuario..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="role">
                    <option value="">Todos los roles</option>
                    <option value="client" <?php echo $role === 'client' ? 'selected' : ''; ?>>Cliente</option>
                    <option value="agency" <?php echo $role === 'agency' ? 'selected' : ''; ?>>Agencia</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
                <select name="status">
                    <option value="">Todos los estados</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Activo</option>
                    <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspendido</option>
                </select>
                <button type="submit">Filtrar</button>
            </form>
            
            <div class="admin-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Rol</th>
                            <th>Créditos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">No hay usuarios encontrados</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                            <td><?php echo ucfirst($user['role']); ?></td>
                            <td><?php echo number_format($user['credits'] ?? 0, 2); ?></td>
                            <td>
                                <span class="status-badge <?php echo ($user['status'] ?? 'active') === 'active' ? 'status-active' : 'status-suspended'; ?>">
                                    <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="<?php echo $baseUrl; ?>superadmin/user/<?php echo $user['id']; ?>" class="btn-action btn-view">Ver</a>
                                <a href="<?php echo $baseUrl; ?>superadmin/masquerade?id=<?php echo $user['id']; ?>" class="btn-action btn-login-as" style="background: rgba(168, 255, 53, 0.15); color: var(--primary);" onclick="return confirm('¿Iniciar sesión como este usuario?')">🔑 Entrar</a>
                                <button class="btn-action btn-edit" onclick="openEditModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['phone'] ?? ''); ?>', '<?php echo $user['role']; ?>', <?php echo $user['credits'] ?? 0; ?>)">Editar</button>
                                <?php if (($user['status'] ?? 'active') === 'active'): ?>
                                <a href="<?php echo $baseUrl; ?>superadmin/user/suspend?id=<?php echo $user['id']; ?>" class="btn-action btn-suspend" onclick="return confirm('¿Suspender usuario?')">Suspender</a>
                                <?php else: ?>
                                <a href="<?php echo $baseUrl; ?>superadmin/user/activate?id=<?php echo $user['id']; ?>" class="btn-action btn-activate" onclick="return confirm('¿Activar usuario?')">Activar</a>
                                <?php endif; ?>
                                <a href="<?php echo $baseUrl; ?>superadmin/user/delete?id=<?php echo $user['id']; ?>" class="btn-action btn-delete" onclick="return confirm('¿Eliminar usuario? Esta acción no se puede deshacer.')">Eliminar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h3>Editar Usuario</h3>
            <form method="POST" action="<?php echo $baseUrl; ?>superadmin/user/update">
                <input type="hidden" name="id" id="editId">
                <div class="form-group-admin">
                    <label>Nombre</label>
                    <input type="text" name="name" id="editName" required>
                </div>
                <div class="form-group-admin">
                    <label>Email</label>
                    <input type="email" name="email" id="editEmail" required>
                </div>
                <div class="form-group-admin">
                    <label>Teléfono</label>
                    <input type="text" name="phone" id="editPhone">
                </div>
                <div class="form-group-admin">
                    <label>Rol</label>
                    <select name="role" id="editRole">
                        <option value="client">Cliente</option>
                        <option value="agency">Agencia</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group-admin">
                    <label>Créditos</label>
                    <input type="number" name="credits" id="editCredits" step="0.01" min="0">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancelar</button>
                    <button type="submit" class="btn-primary-admin">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal(id, name, email, phone, role, credits) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editEmail').value = email;
            document.getElementById('editPhone').value = phone;
            document.getElementById('editRole').value = role;
            document.getElementById('editCredits').value = credits;
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
    </script>
</body>
</html>
