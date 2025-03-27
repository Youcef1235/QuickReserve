<?php
// Script d'installation pour QuickReserve
// Créé le <?php echo date('Y-m-d'); 

// Configuration
$githubUser = "Youcef1235"; // Remplacez par votre nom d'utilisateur GitHub
$repoName = "QuickReserve"; // Remplacez par le nom de votre dépôt
$branch = "main"; // ou "master" selon votre branche principale
$zipUrl = "https://github.com/{$githubUser}/{$repoName}/archive/refs/heads/{$branch}.zip";
$localZip = "quickreserve.zip";
$extractDir = "./"; // Répertoire d'extraction (répertoire courant)

// Fonction pour afficher les messages avec style
function showMessage($message, $type = 'info') {
    $color = $type == 'error' ? 'red' : ($type == 'success' ? 'green' : 'blue');
    echo "<div style='margin: 10px 0; padding: 10px; background-color: #f8f9fa; border-left: 4px solid {$color}; color: #333;'>{$message}</div>";
    flush();
    ob_flush();
}

// Vérifier les prérequis
showMessage("Vérification des prérequis...");
if (!extension_loaded('zip')) {
    showMessage("L'extension ZIP n'est pas disponible. Contactez votre hébergeur.", "error");
    exit;
}

// Créer un dossier temporaire
$tempDir = "temp_" . time();
if (!file_exists($tempDir)) {
    mkdir($tempDir);
}

try {
    // Télécharger le ZIP
    showMessage("Téléchargement du projet depuis GitHub...");
    $zipData = file_get_contents($zipUrl);
    if ($zipData === false) {
        throw new Exception("Impossible de télécharger l'archive. Vérifiez l'URL ou les permissions GitHub.");
    }
    file_put_contents($tempDir . '/' . $localZip, $zipData);
    showMessage("Téléchargement terminé!", "success");

    // Extraire le ZIP
    showMessage("Extraction des fichiers...");
    $zip = new ZipArchive;
    if ($zip->open($tempDir . '/' . $localZip) !== TRUE) {
        throw new Exception("Impossible d'ouvrir l'archive ZIP.");
    }
    
    // Le contenu du ZIP est généralement dans un dossier nommé {repo}-{branch}
    $folderName = "{$repoName}-{$branch}";
    
    // Extraire dans le dossier temporaire
    $zip->extractTo($tempDir);
    $zip->close();
    showMessage("Extraction terminée!", "success");
    
    // Déplacer les fichiers vers le répertoire final
    showMessage("Installation des fichiers...");
    $sourceDir = $tempDir . '/' . $folderName;
    
    // Fonction récursive pour copier les fichiers
    function copyDirectory($source, $destination) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $sourcePath = $source . '/' . $file;
                $destPath = $destination . '/' . $file;
                
                if (is_dir($sourcePath)) {
                    copyDirectory($sourcePath, $destPath);
                } else {
                    copy($sourcePath, $destPath);
                }
            }
        }
        closedir($dir);
    }
    
    copyDirectory($sourceDir, $extractDir);
    showMessage("Fichiers installés avec succès!", "success");
    
    // Nettoyer
    showMessage("Nettoyage des fichiers temporaires...");
    function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? deleteDirectory($path) : unlink($path);
        }
        return rmdir($dir);
    }
    
    deleteDirectory($tempDir);
    showMessage("Nettoyage terminé!", "success");
    
    // Configuration de la base de données
    showMessage("Pour terminer l'installation, vous devez configurer la base de données :");
    echo "<ol>";
    echo "<li>Créez une base de données MySQL dans votre panneau de contrôle InfinityFree</li>";
    echo "<li>Importez le fichier <code>database.sql</code> dans votre base de données</li>";
    echo "<li>Modifiez le fichier <code>config/database.php</code> avec vos informations de connexion</li>";
    echo "</ol>";
    
    showMessage("Installation terminée avec succès! Vous pouvez maintenant accéder à votre site QuickReserve.", "success");
    
} catch (Exception $e) {
    showMessage("Erreur : " . $e->getMessage(), "error");
    // Nettoyer en cas d'erreur
    if (file_exists($tempDir)) {
        deleteDirectory($tempDir);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation de QuickReserve</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #4e73df;
            border-bottom: 2px solid #e3e6f0;
            padding-bottom: 10px;
        }
        code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 4px;
            font-family: monospace;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #4e73df;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Installation de QuickReserve</h1>
    <p>Ce script a installé automatiquement QuickReserve sur votre hébergement.</p>
    <p>N'oubliez pas de supprimer ce fichier d'installation une fois que vous avez terminé!</p>
    <a href="index.php" class="btn">Accéder à QuickReserve</a>
</body>
</html>