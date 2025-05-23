<?php
require_once '../config/database.php';
require_once '../includes/header.php';

// Get all persons with their related information
$query = "SELECT 
    p.*,
    f.first_name as father_first_name, f.last_name as father_last_name,
    m.first_name as mother_first_name, m.last_name as mother_last_name,
    l.location_name,
    ph.photo_url
FROM persons p
LEFT JOIN persons f ON p.father_id = f.id
LEFT JOIN persons m ON p.mother_id = m.id
LEFT JOIN locations l ON p.location_id = l.id
LEFT JOIN photos ph ON p.main_photo_id = ph.id
ORDER BY p.last_name, p.first_name";

$result = $conn->query($query);
?>

<div class="container">
    <h2>All Persons</h2>
    
    <div class="persons-grid">
        <?php while ($person = $result->fetch_assoc()): ?>
            <div class="person-card">
                <?php if ($person['photo_url']): ?>
                    <div class="person-photo">
                        <img src="/familytree/<?php echo htmlspecialchars($person['photo_url']); ?>" alt="Profile photo">
                    </div>
                <?php endif; ?>
                
                <div class="person-info">
                    <h3><?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></h3>
                    
                    <div class="info-row">
                        <span class="label">Gender:</span>
                        <span class="value"><?php echo ucfirst(htmlspecialchars($person['gender'])); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="label">Birth Date:</span>
                        <span class="value"><?php echo htmlspecialchars($person['birth_date']); ?></span>
                    </div>
                    
                    <?php if (!$person['is_alive']): ?>
                        <div class="info-row">
                            <span class="label">Death Date:</span>
                            <span class="value"><?php echo htmlspecialchars($person['death_date']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($person['father_first_name'] || $person['mother_first_name']): ?>
                        <div class="info-row">
                            <span class="label">Parents:</span>
                            <span class="value">
                                <?php
                                $parents = [];
                                if ($person['father_first_name']) {
                                    $parents[] = htmlspecialchars($person['father_first_name'] . ' ' . $person['father_last_name']);
                                }
                                if ($person['mother_first_name']) {
                                    $parents[] = htmlspecialchars($person['mother_first_name'] . ' ' . $person['mother_last_name']);
                                }
                                echo implode(' and ', $parents);
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($person['location_name']): ?>
                        <div class="info-row">
                            <span class="label">Birth Location:</span>
                            <span class="value"><?php echo htmlspecialchars($person['location_name']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($person['biography']): ?>
                        <div class="info-row">
                            <span class="label">Biography:</span>
                            <span class="value"><?php echo htmlspecialchars($person['biography']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="actions">
                        <a href="edit_person.php?id=<?php echo $person['id']; ?>" class="edit-button">Edit</a>
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

.persons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.person-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.person-photo {
    text-align: center;
    margin-bottom: 15px;
}

.person-photo img {
    max-width: 150px;
    max-height: 150px;
    border-radius: 50%;
    object-fit: cover;
}

.person-info h3 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 1.2em;
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
    min-width: 100px;
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