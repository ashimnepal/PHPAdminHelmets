<?php
// dbinit.php
$host = 'localhost';
$dbname = 'Helmets';
$username = 'root';
$password = '';

// Create connection
try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create the database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");

    // Create table
    $createTableSql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        productAddedBy VARCHAR(100) NOT NULL DEFAULT 'Ashim'
    )";
    $pdo->exec($createTableSql);
} catch (PDOException $e) {
    die("DB ERROR: ". $e->getMessage());
}

// Initialize variables
$action = $_GET['action'] ?? 'view'; // Default action is 'view'
$id = $_GET['id'] ?? null;
$name = $price = '';
$errorMessage = $successMessage = '';

// Handle form submissions for add/update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? '';

    if (empty($name)) {
        $errorMessage = "Product name is required.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $errorMessage = "Price must be a positive number.";
    } else {
        if ($action === 'add') {
            try {
                $stmt = $pdo->prepare("INSERT INTO products (name, price) VALUES (:name, :price)");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':price', $price);
                $stmt->execute();
                $successMessage = "Product added successfully!";
                $name = $price = ''; // Clear form values
            } catch (PDOException $e) {
                $errorMessage = "Error adding product: " . $e->getMessage();
            }
        } elseif ($action === 'update' && $id) {
            try {
                $stmt = $pdo->prepare("UPDATE products SET name = :name, price = :price WHERE id = :id");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                $successMessage = "Product updated successfully!";
            } catch (PDOException $e) {
                $errorMessage = "Error updating product: " . $e->getMessage();
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        header('Location: ?'); // Redirect to the view page
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Error deleting product: " . $e->getMessage();
    }
}

// Fetch product for update
if ($action === 'update' && $id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) {
        $name = $product['name'];
        $price = $product['price'];
    } else {
        $errorMessage = "Product not found.";
    }
}

// Fetch all products for viewing
$products = [];
if ($action === 'view') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errorMessage = "Error fetching products: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h2>Product Management System</h2>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?= $successMessage ?></div>
    <?php elseif ($errorMessage): ?>
        <div class="alert alert-danger"><?= $errorMessage ?></div>
    <?php endif; ?>

    <!-- Product Form (for Add or Update) -->
    <?php if ($action === 'add' || $action === 'update'): ?>
        <h3><?= $action === 'add' ? 'Add New Product' : 'Update Product' ?></h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="text" name="price" id="price" class="form-control" value="<?= htmlspecialchars($price) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary"><?= $action === 'add' ? 'Add Product' : 'Update Product' ?></button>
        </form>
        <a href="?action=view" class="btn btn-secondary mt-3">Cancel</a>

    <!-- Product List (for Viewing) -->
    <?php elseif ($action === 'view'): ?>
        <h3>Product List</h3>
        <a href="?action=add" class="btn btn-success mb-3">Add New Product</a>
        <?php if (empty($products)): ?>
            <p>No products found.</p>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Product Added By</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= $product['id'] ?></td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= number_format($product['price'], 2) ?></td>
                        <td><?= htmlspecialchars($product['productAddedBy']) ?></td>
                        <td>
                            <a href="?action=update&id=<?= $product['id'] ?>" class="btn btn-warning btn-sm">Update</a>
                            <a href="?action=delete&id=<?= $product['id'] ?>" class="btn btn-danger btn-sm" 
                               onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
