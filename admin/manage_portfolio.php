<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

$tailor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$tailor_id) {
    header("Location: index.php");
    exit;
}

// Fetch Tailor
$stmt = $pdo->prepare("SELECT name FROM tailors WHERE id = ?");
$stmt->execute([$tailor_id]);
$tailor = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle Add Image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_url'])) {
    $stmt = $pdo->prepare("INSERT INTO portfolio_images (tailor_id, image_url, description) VALUES (?, ?, ?)");
    $stmt->execute([$tailor_id, $_POST['image_url'], $_POST['description']]);
}

// Handle Delete Image
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM portfolio_images WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
}

// Fetch Images
$stmt = $pdo->prepare("SELECT * FROM portfolio_images WHERE tailor_id = ?");
$stmt->execute([$tailor_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Portfolio: <?= htmlspecialchars((string)$tailor['name']) ?> | Silah Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="container bg-white p-5 rounded shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Portfolio: <?= htmlspecialchars((string)$tailor['name']) ?></h2>
            <a href="index.php" class="btn btn-outline-secondary">Back to Tailors</a>
        </div>

        <div class="row mb-5">
            <div class="col-md-6">
                <div class="card p-3 bg-light">
                    <h5>Add New Image</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="url" name="image_url" class="form-control" placeholder="https://..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="e.g. Wedding Dress">
                        </div>
                        <button type="submit" class="btn btn-success">Add Image</button>
                    </form>
                </div>
            </div>
        </div>

        <h4>Current Portfolio</h4>
        <div class="row g-3">
            <?php foreach ($images as $img): ?>
            <div class="col-md-3">
                <div class="card h-100">
                    <img src="<?= htmlspecialchars((string)$img['image_url']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <p class="card-text small"><?= htmlspecialchars((string)$img['description']) ?></p>
                        <a href="?id=<?= $tailor_id ?>&delete=<?= $img['id'] ?>" class="btn btn-sm btn-danger w-100" onclick="return confirm('Delete image?')">Delete</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
