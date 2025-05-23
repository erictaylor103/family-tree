<?php
require_once '../config/database.php';
require_once '../includes/header.php';

$success_message = '';
$error_message = '';

// Get marriage ID from URL
$marriage_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($marriage_id <= 0) {
    header('Location: /familytree/index.php');
    exit();
}

// Get existing persons for spouse selection
$persons_query = "SELECT id, first_name, last_name FROM persons ORDER BY first_name, last_name";
$persons_result = $conn->query($persons_query);

// Get existing locations for marriage location selection
$locations_query = "SELECT id, location_name FROM locations ORDER BY location_name";
$locations_result = $conn->query($locations_query);

// Get marriage's current data
$marriage_query = "SELECT * FROM marriages WHERE id = ?";
$marriage_stmt = $conn->prepare($marriage_query);
$marriage_stmt->bind_param("i", $marriage_id);
$marriage_stmt->execute();
$marriage_result = $marriage_stmt->get_result();
$marriage = $marriage_result->fetch_assoc();

if (!$marriage) {
    header('Location: /familytree/index.php');
    exit();
}

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
    $stmt = $conn->prepare("UPDATE marriages SET spouse1_id = ?, spouse2_id = ?, marriage_date = ?, divorce_date = ?, end_reason = ?, relationship_type = ?, location_id = ? WHERE id = ?");
    $stmt->bind_param("iissssii", $spouse1_id, $spouse2_id, $marriage_date, $divorce_date, $end_reason, $relationship_type, $location_id, $marriage_id);
    
    if ($stmt->execute()) {
        $success_message = "Marriage updated successfully!";
        // Refresh marriage data
        $marriage_stmt->execute();
        $marriage_result = $marriage_stmt->get_result();
        $marriage = $marriage_result->fetch_assoc();
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Get spouse names for display
$spouse1_query = "SELECT first_name, last_name FROM persons WHERE id = ?";
$spouse1_stmt = $conn->prepare($spouse1_query);
$spouse1_stmt->bind_param("i", $marriage['spouse1_id']);
$spouse1_stmt->execute();
$spouse1_result = $spouse1_stmt->get_result();
$spouse1 = $spouse1_result->fetch_assoc();

$spouse2_query = "SELECT first_name, last_name FROM persons WHERE id = ?";
$spouse2_stmt = $conn->prepare($spouse2_query);
$spouse2_stmt->bind_param("i", $marriage['spouse2_id']);
$spouse2_stmt->execute();
$spouse2_result = $spouse2_stmt->get_result();
$spouse2 = $spouse2_result->fetch_assoc();
?>

<div class="form-container">
    <h2>Edit Marriage/Relationship</h2>
    
    <?php if ($success_message): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form id="editMarriageForm" method="POST" onsubmit="return validateForm('editMarriageForm')">
        <div class="form-group">
            <label for="spouse1_id">Spouse 1 *</label>
            <select id="spouse1_id" name="spouse1_id" required>
                <option value="">Select First Spouse</option>
                <?php while ($person = $persons_result->fetch_assoc()): ?>
                    <option value="<?php echo $person['id']; ?>" <?php echo $marriage['spouse1_id'] == $person['id'] ? 'selected' : ''; ?>>
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
                    <option value="<?php echo $person['id']; ?>" <?php echo $marriage['spouse2_id'] == $person['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="marriage_date">Marriage Date *</label>
            <input type="date" id="marriage_date" name="marriage_date" value="<?php echo $marriage['marriage_date']; ?>" required>
        </div>

        <div class="form-group">
            <label for="divorce_date">Divorce Date</label>
            <input type="date" id="divorce_date" name="divorce_date" value="<?php echo $marriage['divorce_date']; ?>">
        </div>

        <div class="form-group">
            <label for="end_reason">End Reason (if applicable)</label>
            <select id="end_reason" name="end_reason">
                <option value="">Select Reason</option>
                <option value="divorce" <?php echo $marriage['end_reason'] == 'divorce' ? 'selected' : ''; ?>>Divorce</option>
                <option value="death" <?php echo $marriage['end_reason'] == 'death' ? 'selected' : ''; ?>>Death</option>
                <option value="unknown" <?php echo $marriage['end_reason'] == 'unknown' ? 'selected' : ''; ?>>Unknown</option>
            </select>
        </div>

        <div class="form-group">
            <label for="relationship_type">Relationship Type *</label>
            <select id="relationship_type" name="relationship_type" required>
                <option value="marriage" <?php echo $marriage['relationship_type'] == 'marriage' ? 'selected' : ''; ?>>Marriage</option>
                <option value="civil_partnership" <?php echo $marriage['relationship_type'] == 'civil_partnership' ? 'selected' : ''; ?>>Civil Partnership</option>
                <option value="common_law" <?php echo $marriage['relationship_type'] == 'common_law' ? 'selected' : ''; ?>>Common Law</option>
                <option value="other" <?php echo $marriage['relationship_type'] == 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="location_id">Marriage Location</label>
            <select id="location_id" name="location_id">
                <option value="">Select Location</option>
                <?php while ($location = $locations_result->fetch_assoc()): ?>
                    <option value="<?php echo $location['id']; ?>" <?php echo $marriage['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($location['location_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit">Update Marriage/Relationship</button>
    </form>
</div>

<script>
// Add validation to ensure spouse1 and spouse2 are different
document.getElementById('editMarriageForm').addEventListener('submit', function(e) {
    const spouse1 = document.getElementById('spouse1_id').value;
    const spouse2 = document.getElementById('spouse2_id').value;
    
    if (spouse1 === spouse2) {
        e.preventDefault();
        alert('Please select two different people for the marriage/relationship.');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 