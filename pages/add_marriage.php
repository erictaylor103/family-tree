<?php
require_once '../config/database.php';
require_once '../includes/header.php';

$success_message = '';
$error_message = '';

// Get existing persons for spouse selection
$persons_query = "SELECT id, first_name, last_name FROM persons ORDER BY first_name, last_name";
$persons_result = $conn->query($persons_query);

// Get locations for marriage location selection
$locations_query = "SELECT id, location_name FROM locations ORDER BY location_name";
$locations_result = $conn->query($locations_query);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $spouse1_id = $_POST['spouse1_id'];
    $spouse2_id = $_POST['spouse2_id'];
    $marriage_date = $_POST['marriage_date'];
    $divorce_date = !empty($_POST['divorce_date']) ? $_POST['divorce_date'] : null;
    $end_reason = !empty($_POST['end_reason']) ? $_POST['end_reason'] : null;
    $relationship_type = $_POST['relationship_type'];
    $location_id = !empty($_POST['location_id']) ? $_POST['location_id'] : null;
    
    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("INSERT INTO marriages (spouse1_id, spouse2_id, marriage_date, divorce_date, end_reason, relationship_type, location_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssi", $spouse1_id, $spouse2_id, $marriage_date, $divorce_date, $end_reason, $relationship_type, $location_id);
    
    if ($stmt->execute()) {
        $success_message = "Marriage added successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}
?>

<div class="form-container">
    <h2>Add New Marriage/Relationship</h2>
    
    <?php if ($success_message): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form id="addMarriageForm" method="POST" onsubmit="return validateForm('addMarriageForm')">
        <div class="form-group">
            <label for="spouse1_id">Spouse 1 *</label>
            <select id="spouse1_id" name="spouse1_id" required>
                <option value="">Select First Spouse</option>
                <?php while ($person = $persons_result->fetch_assoc()): ?>
                    <option value="<?php echo $person['id']; ?>">
                        <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="spouse2_id">Spouse 2 *</label>
            <select id="spouse2_id" name="spouse2_id" required>
                <option value="">Select Second Spouse</option>
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
            <label for="marriage_date">Marriage Date *</label>
            <input type="date" id="marriage_date" name="marriage_date" required>
        </div>

        <div class="form-group">
            <label for="divorce_date">Divorce Date</label>
            <input type="date" id="divorce_date" name="divorce_date">
        </div>

        <div class="form-group">
            <label for="end_reason">End Reason (if applicable)</label>
            <select id="end_reason" name="end_reason">
                <option value="">Select Reason</option>
                <option value="divorce">Divorce</option>
                <option value="death">Death</option>
                <option value="unknown">Unknown</option>
            </select>
        </div>

        <div class="form-group">
            <label for="relationship_type">Relationship Type *</label>
            <select id="relationship_type" name="relationship_type" required>
                <option value="marriage">Marriage</option>
                <option value="civil_partnership">Civil Partnership</option>
                <option value="common_law">Common Law</option>
                <option value="other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="location_id">Marriage Location</label>
            <select id="location_id" name="location_id">
                <option value="">Select Location</option>
                <?php while ($location = $locations_result->fetch_assoc()): ?>
                    <option value="<?php echo $location['id']; ?>">
                        <?php echo htmlspecialchars($location['location_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit">Add Marriage/Relationship</button>
    </form>
</div>

<script>
// Add validation to ensure spouse1 and spouse2 are different
document.getElementById('addMarriageForm').addEventListener('submit', function(e) {
    const spouse1 = document.getElementById('spouse1_id').value;
    const spouse2 = document.getElementById('spouse2_id').value;
    
    if (spouse1 === spouse2) {
        e.preventDefault();
        alert('Please select two different people for the marriage/relationship.');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 