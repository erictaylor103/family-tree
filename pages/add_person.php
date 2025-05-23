<?php
require_once '../config/database.php';
require_once '../includes/header.php';

$success_message = '';
$error_message = '';

// Get existing persons for parent selection
$persons_query = "SELECT id, first_name, last_name FROM persons ORDER BY first_name, last_name";
$persons_result = $conn->query($persons_query);

// Get existing locations for location selection
$locations_query = "SELECT id, location_name FROM locations ORDER BY location_name";
$locations_result = $conn->query($locations_query);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $first_name = trim(htmlspecialchars($_POST['first_name']));
    $last_name = trim(htmlspecialchars($_POST['last_name']));
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];
    $is_alive = isset($_POST['is_alive']) ? 1 : 0;
    $death_date = $is_alive ? null : ($_POST['death_date'] ?? null);
    $biography = trim(htmlspecialchars($_POST['biography']));
    $father_id = !empty($_POST['father_id']) ? $_POST['father_id'] : null;
    $mother_id = !empty($_POST['mother_id']) ? $_POST['mother_id'] : null;
    $location_id = !empty($_POST['location_id']) ? $_POST['location_id'] : null;
    
    // Handle photo upload
    $main_photo_id = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo = $_FILES['photo'];
        $upload_dir = '../uploads/photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($photo['tmp_name'], $upload_path)) {
            $photo_url = 'uploads/photos/' . $new_filename;
            $photo_caption = $first_name . ' ' . $last_name . ' photo';
            
            $photo_stmt = $conn->prepare("INSERT INTO photos (person_id, photo_url, photo_caption) VALUES (?, ?, ?)");
            $photo_stmt->bind_param("iss", $person_id, $photo_url, $photo_caption);
            $photo_stmt->execute();
            $main_photo_id = $conn->insert_id;
            $photo_stmt->close();
        }
    }
    
    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("INSERT INTO persons (first_name, last_name, gender, birth_date, death_date, is_alive, biography, father_id, mother_id, location_id, main_photo_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssisiiii", $first_name, $last_name, $gender, $birth_date, $death_date, $is_alive, $biography, $father_id, $mother_id, $location_id, $main_photo_id);
    
    if ($stmt->execute()) {
        $success_message = "Person added successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}
?>

<div class="form-container">
    <h2>Add New Person</h2>
    
    <?php if ($success_message): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form id="addPersonForm" method="POST" enctype="multipart/form-data" onsubmit="return validateForm('addPersonForm')">
        <div class="form-group">
            <label for="first_name">First Name *</label>
            <input type="text" id="first_name" name="first_name" required>
        </div>

        <div class="form-group">
            <label for="last_name">Last Name / Maiden Name *</label>
            <input type="text" id="last_name" name="last_name" required>
        </div>

        <div class="form-group">
            <label for="gender">Gender *</label>
            <select id="gender" name="gender" required>
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="birth_date">Birth Date *</label>
            <input type="date" id="birth_date" name="birth_date" required>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" id="is_alive" name="is_alive" checked onchange="toggleDeathDate()">
                Person is alive
            </label>
        </div>

        <div class="form-group" id="death_date_group" style="display: none;">
            <label for="death_date">Death Date</label>
            <input type="date" id="death_date" name="death_date">
        </div>

        <div class="form-group">
            <label for="biography">Biography</label>
            <textarea id="biography" name="biography" rows="4"></textarea>
        </div>

        <div class="form-group">
            <label for="father_id">Father</label>
            <select id="father_id" name="father_id">
                <option value="">Select Father</option>
                <?php while ($person = $persons_result->fetch_assoc()): ?>
                    <option value="<?php echo $person['id']; ?>">
                        <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="mother_id">Mother</label>
            <select id="mother_id" name="mother_id">
                <option value="">Select Mother</option>
                <?php 
                // Reset the result pointer to reuse the query
                $persons_result->data_seek(0);
                while ($person = $persons_result->fetch_assoc()): 
                ?>
                    <option value="<?php echo $person['id']; ?>">
                        <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="location_id">Birth Location</label>
            <select id="location_id" name="location_id">
                <option value="">Select Location</option>
                <?php while ($location = $locations_result->fetch_assoc()): ?>
                    <option value="<?php echo $location['id']; ?>">
                        <?php echo htmlspecialchars($location['location_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="photo">Profile Photo</label>
            <input type="file" id="photo" name="photo" accept="image/*">
        </div>

        <button type="submit">Add Person</button>
    </form>
</div>

<script>
function toggleDeathDate() {
    const isAlive = document.getElementById('is_alive').checked;
    const deathDateGroup = document.getElementById('death_date_group');
    deathDateGroup.style.display = isAlive ? 'none' : 'block';
}
</script>

<?php require_once '../includes/footer.php'; ?> 