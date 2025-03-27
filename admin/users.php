<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isLoggedIn() || !isAdmin()) {
    setAlert('danger', 'Accès non autorisé.');
    redirect('../index.php');
}

// Paramètres de pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filtres
$role = cleanInput($_GET['role'] ?? '');
$status = cleanInput($_GET['status'] ?? '');
$search = cleanInput($_GET['search'] ?? '');

// Construction de la requête
$params = [];
$sql = "SELECT * FROM users WHERE 1=1";

if (!empty($role)) {
    $sql .= " AND role = :role";
    $params['role'] = $role;
}

if (!empty($status)) {
    $sql .= " AND status = :status";
    $params['status'] = $status;
}

if (!empty($search)) {
    $sql .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params['search'] = "%$search%";
}

// Compter le nombre total d'utilisateurs
$countSql = str_replace("SELECT *", "SELECT COUNT(*) as count", $sql);
$totalUsers = fetchOne($countSql, $params)['count'];
$totalPages = ceil($totalUsers / $perPage);

// Ajouter la pagination à la requête
$sql .= " ORDER BY created_at DESC LIMIT :offset, :perPage";
$params['offset'] = $offset;
$params['perPage'] = $perPage;

// Récupérer les utilisateurs
$users = fetchAll($sql, $params);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Vérifier le token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setAlert('danger', 'Erreur de sécurité. Veuillez réessayer.');
    } else {
        $userId = intval($_POST['user_id'] ?? 0);
        $action = $_POST['action'];
        
        if ($userId > 0) {
            switch ($action) {
                case 'activate':
                    update('users', ['status' => 'active'], 'id = :id', ['id' => $userId]);
                    setAlert('success', 'Utilisateur activé avec succès.');
                    break;
                    
                case 'deactivate':
                    update('users', ['status' => 'inactive'], 'id = :id', ['id' => $userId]);
                    setAlert('success', 'Utilisateur désactivé avec succès.');
                    break;
                    
                case 'ban':
                    update('users', ['status' => 'banned'], 'id = :id', ['id' => $userId]);
                    setAlert('success', 'Utilisateur banni avec succès.');
                    break;
                    
                case 'delete':
                    delete('users', 'id = :id', ['id' => $userId]);
                    setAlert('success', 'Utilisateur supprimé avec succès.');
                    break;
                    
                case 'change_role':
                    $newRole = cleanInput($_POST['new_role'] ?? '');
                    if (in_array($newRole, ['user', 'provider', 'admin'])) {
                        update('users', ['role' => $newRole], 'id = :id', ['id' => $userId]);
                        setAlert('success', 'Rôle de l\'utilisateur modifié avec succès.');
                    }
                    break;
            }
        }
        
        // Rediriger pour éviter la soumission multiple du formulaire
        redirect('users.php');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs - Administration - QuickReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestion des utilisateurs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add-user.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Ajouter un utilisateur
                        </a>
                    </div>
                </div>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
                    </div>
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Recherche</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nom, email, téléphone...">
                            </div>
                            <div class="col-md-3">
                                <label for="role" class="form-label">Rôle</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="">Tous les rôles</option>
                                    <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>Utilisateur</option>
                                    <option value="provider" <?php echo $role === 'provider' ? 'selected' : ''; ?>>Prestataire</option>
                                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Statut</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Tous les statuts</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Actif</option>
                                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactif</option>
                                    <option value="banned" <?php echo $status === 'banned' ? 'selected' : ''; ?>>Banni</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Liste des utilisateurs</h6>
                        <div class="dropdown no-arrow">
                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-gear"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="#" id="exportCSV">Exporter en CSV</a></li>
                                <li><a class="dropdown-item" href="#" id="printList">Imprimer la liste</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th>Rôle</th>
                                        <th>Statut</th>
                                        <th>Date d'inscription</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?? 'Non renseigné'); ?></td>
                                            <td>
                                                <?php if ($user['role'] === 'admin'): ?>
                                                    <span class="badge bg-danger">Administrateur</span>
                                                <?php elseif ($user['role'] === 'provider'): ?>
                                                    <span class="badge bg-primary">Prestataire</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Utilisateur</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Actif</span>
                                                <?php elseif ($user['status'] === 'inactive'): ?>
                                                    <span class="badge bg-warning">Inactif</span>
                                                <?php elseif ($user['status'] === 'banned'): ?>
                                                    <span class="badge bg-danger">Banni</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDate($user['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#userModal<?php echo $user['id']; ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $user['id']; ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                                
                                                <!-- Modal de détails -->
                                                <div class="modal fade" id="userModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="userModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="userModalLabel<?php echo $user['id']; ?>">Détails de l'utilisateur</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p><strong>ID:</strong> <?php echo $user['id']; ?></p>
                                                                <p><strong>Nom:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                                                <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Non renseigné'); ?></p>
                                                                <p><strong>Rôle:</strong> <?php echo ucfirst($user['role']); ?></p>
                                                                <p><strong>Statut:</strong> <?php echo ucfirst($user['status']); ?></p>
                                                                <p><strong>Date d'inscription:</strong> <?php echo formatDate($user['created_at']); ?></p>
                                                                <p><strong>Dernière mise à jour:</strong> <?php echo $user['updated_at'] ? formatDate($user['updated_at']) : 'Jamais'; ?></p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                                <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">Modifier</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Modal de suppression -->
                                                <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $user['id']; ?>">Confirmer la suppression</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong><?php echo htmlspecialchars($user['name']); ?></strong> ?</p>
                                                                <p class="text-danger">Cette action est irréversible.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <form method="POST" action="">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Aucun utilisateur trouvé.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>" aria-label="Précédent">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>" aria-label="Suivant">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>

