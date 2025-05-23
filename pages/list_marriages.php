<?php
require_once '../config/database.php';
require_once '../includes/header.php';

// Get all marriages with their related information
$query = "SELECT DISTINCT
    m.id,
    m.spouse1_id,
    m.spouse2_id,
    m.marriage_date,
    m.divorce_date,
    m.end_reason,
    m.relationship_type,
    m.location_id,
    s1.first_name as spouse1_first_name, 
    s1.last_name as spouse1_last_name,
    s2.first_name as spouse2_first_name, 
    s2.last_name as spouse2_last_name,
    l.location_name
FROM marriages m
INNER JOIN persons s1 ON m.spouse1_id = s1.id
INNER JOIN persons s2 ON m.spouse2_id = s2.id
LEFT JOIN locations l ON m.location_id = l.id
ORDER BY m.marriage_date DESC";

$result = $conn->query($query);
?>

<div class="container">
    <h2>All Marriages/Relationships</h2>
    
    <div class="marriages-grid">
        <?php while ($marriage = $result->fetch_assoc()): ?>
            <div class="marriage-card">
                <div class="marriage-info">
                    <h3>
                        <?php 
                        echo htmlspecialchars($marriage['spouse1_first_name'] . ' ' . $marriage['spouse1_last_name']);
                        echo ' and ';
                        echo htmlspecialchars($marriage['spouse2_first_name'] . ' ' . $marriage['spouse2_last_name']);
                        ?>
                    </h3>
                    
                    <div class="info-row">
                        <span class="label">Relationship Type:</span>
                        <span class="value"><?php echo ucwords(str_replace('_', ' ', htmlspecialchars($marriage['relationship_type']))); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="label">Marriage Date:</span>
                        <span class="value"><?php echo htmlspecialchars($marriage['marriage_date']); ?></span>
                    </div>
                    
                    <?php if ($marriage['divorce_date']): ?>
                        <div class="info-row">
                            <span class="label">Divorce Date:</span>
                            <span class="value"><?php echo htmlspecialchars($marriage['divorce_date']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($marriage['end_reason']): ?>
                        <div class="info-row">
                            <span class="label">End Reason:</span>
                            <span class="value"><?php echo ucfirst(htmlspecialchars($marriage['end_reason'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($marriage['location_name']): ?>
                        <div class="info-row">
                            <span class="label">Location:</span>
                            <span class="value"><?php echo htmlspecialchars($marriage['location_name']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="actions">
                        <a href="edit_marriage.php?id=<?php echo $marriage['id']; ?>" class="edit-button">Edit</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.marriages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.marriage-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.marriage-info h3 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 1.2em;
    text-align: center;
}

.info-row {
    margin-bottom: 8px;
    display: flex;
    flex-wrap: wrap;
}

.label {
    font-weight: bold;
    margin-right: 5px;
    color: #666;
    min-width: 120px;
}

.value {
    color: #333;
}

.actions {
    margin-top: 15px;
    text-align: right;
}

.edit-button {
    display: inline-block;
    padding: 8px 15px;
    background-color: #4CAF50;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.edit-button:hover {
    background-color: #45a049;
}
</style>

<?php require_once '../includes/footer.php'; ?> 