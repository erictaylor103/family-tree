<?php
require_once '../config/database.php';
require_once '../includes/header.php';

$success_message = '';
$error_message = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $location_name = trim(htmlspecialchars($_POST['location_name']));
    $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    
    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("INSERT INTO locations (location_name, location_latitude, location_longitude) VALUES (?, ?, ?)");
    $stmt->bind_param("sdd", $location_name, $latitude, $longitude);
    
    if ($stmt->execute()) {
        $success_message = "Location added successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}
?>

<div class="form-container">
    <h2>Add New Location</h2>
    
    <?php if ($success_message): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form id="addLocationForm" method="POST" onsubmit="return validateForm('addLocationForm')">
        <div class="form-group">
            <label for="location_name">Location Name *</label>
            <input type="text" id="location_name" name="location_name" required>
        </div>

        <div class="form-group">
            <label for="latitude">Latitude</label>
            <input type="number" id="latitude" name="latitude" step="any" placeholder="e.g., 40.7128">
        </div>

        <div class="form-group">
            <label for="longitude">Longitude</label>
            <input type="number" id="longitude" name="longitude" step="any" placeholder="e.g., -74.0060">
        </div>

        <button type="submit">Add Location</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?> 