<?php
require_once '../config/database.php';
require_once '../includes/header.php';

$success_message = '';
$error_message = '';

// Get person ID from URL
$person_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($person_id <= 0) {
    header('Location: /familytree/index.php');
    exit();
}

// Get existing persons for parent selection
$persons_query = "SELECT id, first_name, last_name FROM persons WHERE id != ? ORDER BY first_name, last_name";
$persons_stmt = $conn->prepare($persons_query);
$persons_stmt->bind_param("i", $person_id);
$persons_stmt->execute();
$persons_result = $persons_stmt->get_result();

// Get existing locations for location selection
$locations_query = "SELECT id, location_name FROM locations ORDER BY location_name";
$locations_result = $conn->query($locations_query);

// Get person's current data
$person_query = "SELECT * FROM persons WHERE id = ?";
$person_stmt = $conn->prepare($person_query);
$person_stmt->bind_param("i", $person_id);
$person_stmt->execute();
$person_result = $person_stmt->get_result();
$person = $person_result->fetch_assoc();

if (!$person) {
    header('Location: /familytree/index.php');
    exit();
}

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
    $main_photo_id = $person['main_photo_id'];
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
    $stmt = $conn->prepare("UPDATE persons SET first_name = ?, last_name = ?, gender = ?, birth_date = ?, death_date = ?, is_alive = ?, biography = ?, father_id = ?, mother_id = ?, location_id = ?, main_photo_id = ? WHERE id = ?");
    $stmt->bind_param("sssssisiiiii", $first_name, $last_name, $gender, $birth_date, $death_date, $is_alive, $biography, $father_id, $mother_id, $location_id, $main_photo_id, $person_id);
    
    if ($stmt->execute()) {
        $success_message = "Person updated successfully!";
        // Refresh person data
        $person_stmt->execute();
        $person_result = $person_stmt->get_result();
        $person = $person_result->fetch_assoc();
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}
?>

<div class="form-container">
    <h2>Edit Person: <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></h2>
    
    <?php if ($success_message): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form id="editPersonForm" method="POST" enctype="multipart/form-data" onsubmit="return validateForm('editPersonForm')">
        <div class="form-group">
            <label for="first_name">First Name *</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($person['first_name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="last_name">Last Name *</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($person['last_name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="gender">Gender *</label>
            <select id="gender" name="gender" required>
                <option value="">Select Gender</option>
                <option value="male" <?php echo $person['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                <option value="female" <?php echo $person['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                <option value="other" <?php echo $person['gender'] == 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="birth_date">Birth Date *</label>
            <input type="date" id="birth_date" name="birth_date" value="<?php echo $person['birth_date']; ?>" required>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" id="is_alive" name="is_alive" <?php echo $person['is_alive'] ? 'checked' : ''; ?> onchange="toggleDeathDate()">
                Person is alive
            </label>
        </div>

        <div class="form-group" id="death_date_group" style="display: <?php echo $person['is_alive'] ? 'none' : 'block'; ?>;">
            <label for="death_date">Death Date</label>
            <input type="date" id="death_date" name="death_date" value="<?php echo $person['death_date']; ?>">
        </div>

        <div class="form-group">
            <label for="biography">Biography</label>
            <textarea id="biography" name="biography" rows="4"><?php echo htmlspecialchars($person['biography']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="father_id">Father</label>
            <select id="father_id" name="father_id">
                <option value="">Select Father</option>
                <?php while ($father = $persons_result->fetch_assoc()): ?>
                    <option value="<?php echo $father['id']; ?>" <?php echo $person['father_id'] == $father['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($father['first_name'] . ' ' . $father['last_name']); ?>
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
                while ($mother = $persons_result->fetch_assoc()): 
                ?>
                    <option value="<?php echo $mother['id']; ?>" <?php echo $person['mother_id'] == $mother['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($mother['first_name'] . ' ' . $mother['last_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="location_id">Birth Location</label>
            <select id="location_id" name="location_id">
                <option value="">Select Location</option>
                <?php while ($location = $locations_result->fetch_assoc()): ?>
                    <option value="<?php echo $location['id']; ?>" <?php echo $person['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($location['location_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="photo">Profile Photo</label>
            <input type="file" id="photo" name="photo" accept="image/*">
            <?php if ($person['main_photo_id']): ?>
                <div class="current-photo">
                    <p>Current Photo:</p>
                    <?php
                    $photo_query = "SELECT photo_url FROM photos WHERE id = ?";
                    $photo_stmt = $conn->prepare($photo_query);
                    $photo_stmt->bind_param("i", $person['main_photo_id']);
                    $photo_stmt->execute();
                    $photo_result = $photo_stmt->get_result();
                    $photo = $photo_result->fetch_assoc();
                    if ($photo): ?>
                        <img src="/familytree/<?php echo htmlspecialchars($photo['photo_url']); ?>" alt="Current profile photo" style="max-width: 200px;">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <button type="submit">Update Person</button>
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