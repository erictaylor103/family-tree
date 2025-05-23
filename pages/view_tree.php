<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/header.php';

// Check if database connection exists and is valid
if (!isset($conn) || $conn->connect_error) {
    die("Connection failed: " . ($conn->connect_error ?? "Database connection not established"));
}

// Function to get all marriages
function getMarriages($conn) {
    try {
        $query = "SELECT m.*, 
            s1.id as spouse1_id, s1.first_name as spouse1_first_name, s1.last_name as spouse1_last_name,
            s2.id as spouse2_id, s2.first_name as spouse2_first_name, s2.last_name as spouse2_last_name,
            l.location_name
            FROM marriages m
            JOIN persons s1 ON m.spouse1_id = s1.id
            JOIN persons s2 ON m.spouse2_id = s2.id
            LEFT JOIN locations l ON m.location_id = l.id
            ORDER BY m.marriage_date ASC";  // Order marriages by date
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Error in marriages query: " . $conn->error);
        }
        
        $marriages = [];
        while ($row = $result->fetch_assoc()) {
            $marriages[] = $row;
        }
        return $marriages;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

// Function to get all persons with their relationships
function getPersons($conn) {
    try {
        $query = "SELECT DISTINCT p.*, 
            father.id as father_id, father.first_name as father_first_name, father.last_name as father_last_name,
            mother.id as mother_id, mother.first_name as mother_first_name, mother.last_name as mother_last_name,
            l.location_name as birth_location_name,
            ph.photo_url as photo_path
            FROM persons p 
            LEFT JOIN persons father ON p.father_id = father.id
            LEFT JOIN persons mother ON p.mother_id = mother.id
            LEFT JOIN locations l ON p.location_id = l.id
            LEFT JOIN photos ph ON p.main_photo_id = ph.id
            ORDER BY p.birth_date ASC";
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Error in persons query: " . $conn->error);
        }
        
        $persons = [];
        while ($row = $result->fetch_assoc()) {
            $persons[$row['id']] = $row;
        }
        return $persons;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

// Function to get children of a couple
function getChildren($parent1_id, $parent2_id, $persons) {
    if (empty($persons)) {
        return [];
    }
    
    $children = [];
    foreach ($persons as $person) {
        if (($person['father_id'] == $parent1_id && $person['mother_id'] == $parent2_id) ||
            ($person['father_id'] == $parent2_id && $person['mother_id'] == $parent1_id)) {
            $children[] = $person;
        }
    }
    return $children;
}

// Function to check if a person has descendants
function hasDescendants($person_id, $persons) {
    foreach ($persons as $potential_descendant) {
        if ($potential_descendant['father_id'] == $person_id || 
            $potential_descendant['mother_id'] == $person_id) {
            return true;
        }
    }
    return false;
}

// Function to check if a person is a child of any displayed person
function isChildOfDisplayedPerson($person, $persons) {
    if (!$person['father_id'] && !$person['mother_id']) {
        return false;
    }
    
    foreach ($persons as $potential_parent) {
        if ($person['father_id'] == $potential_parent['id'] || 
            $person['mother_id'] == $potential_parent['id']) {
            return true;
        }
    }
    return false;
}

// Function to check if a person is already shown as a child
function isShownAsChild($person_id, $persons, $marriages) {
    foreach ($persons as $potential_parent) {
        if ($potential_parent['id'] == $person_id) {
            continue;
        }
        // Check if this person is a child of someone else
        if ($potential_parent['father_id'] == $person_id || 
            $potential_parent['mother_id'] == $person_id) {
            return false; // This person is a parent, not a child
        }
    }
    return true; // This person is only shown as a child
}

// Get all data
$marriages = getMarriages($conn);
$persons = getPersons($conn);

// Track displayed persons and relationships
$displayed_persons = [];
$displayed_in_tree = [];
$displayed_relationships = [];

// Function to check if a person has been displayed in a specific context
function isPersonDisplayed($person_id, $context) {
    global $displayed_persons, $displayed_in_tree;
    
    // If person has been displayed in the tree at a higher level, don't show them lower
    if (isset($displayed_in_tree[$person_id])) {
        return true;
    }
    
    $context_key = $person_id . '_' . $context;
    if (isset($displayed_persons[$context_key])) {
        return true;
    }
    
    $displayed_persons[$context_key] = true;
    return false;
}

// Function to mark person as displayed in tree
function markPersonDisplayed($person_id) {
    global $displayed_in_tree;
    $displayed_in_tree[$person_id] = true;
}

// Function to check if a parent-child relationship has been displayed
function isRelationshipDisplayed($parent_id, $child_id) {
    global $displayed_relationships;
    $key = $parent_id . '_' . $child_id;
    if (isset($displayed_relationships[$key])) {
        return true;
    }
    $displayed_relationships[$key] = true;
    return false;
}

// Function to determine contextual relationship
function getContextualRelationship($person, $root_person = null, $spouse = null, $is_child = false) {
    if ($is_child) {
        return $person['gender'] == 'male' ? 'Son' : 'Daughter';
    }
    
    if ($root_person && $spouse) {
        // If this person is the spouse of the root person
        if ($person['id'] == $spouse['id']) {
            return $person['gender'] == 'male' ? 'Husband' : 'Wife';
        }
        
        // If this person is the root person
        if ($person['id'] == $root_person['id']) {
            return $person['gender'] == 'male' ? 'Husband' : 'Wife';
        }
    }
    
    return '';
}

// Function to render a person card with context-aware duplicate check
function renderPersonCard($person, $root_person = null, $spouse = null, $is_child = false, $is_root = false) {
    // Generate a context string based on the relationship
    $context = $is_root ? 'root' : 'single';
    if ($root_person && $spouse) {
        if ($person['id'] == $root_person['id']) {
            $context = 'spouse1_' . $spouse['id'];
        } else if ($person['id'] == $spouse['id']) {
            $context = 'spouse2_' . $root_person['id'];
        }
    }
    if ($is_child) {
        $context = 'child_' . ($root_person ? $root_person['id'] : '') . '_' . ($spouse ? $spouse['id'] : '');
    }
    
    // Only check for duplicates in the same context
    if (isPersonDisplayed($person['id'], $context)) {
        return;
    }
    
    // Mark root persons as displayed in tree
    if ($is_root) {
        markPersonDisplayed($person['id']);
    }
    
    $photo = $person['photo_path'] ? 'http://localhost/familytree/' . $person['photo_path'] : null;
    $initials = strtoupper(substr($person['first_name'], 0, 1) . substr($person['last_name'], 0, 1));
    $birth_date = date('M d, Y', strtotime($person['birth_date']));
    $death_date = $person['death_date'] ? date('M d, Y', strtotime($person['death_date'])) : null;
    $relationship = getContextualRelationship($person, $root_person, $spouse, $is_child);
    ?>
    <div class="person-card" data-id="<?php echo $person['id']; ?>">
        <div class="person-photo">
            <?php if ($photo): ?>
                <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($person['first_name']); ?>">
            <?php else: ?>
                <div class="initials"><?php echo $initials; ?></div>
            <?php endif; ?>
        </div>
        <div class="person-info">
            <h3><?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></h3>
            <?php if ($relationship): ?>
                <p class="relationships">
                    <?php echo htmlspecialchars($relationship); ?>
                </p>
            <?php endif; ?>
            <p class="dates">
                <?php echo $birth_date; ?>
                <?php if ($death_date): ?>
                    - <?php echo $death_date; ?>
                <?php endif; ?>
            </p>
            <?php if ($person['birth_location_name']): ?>
                <p class="location"><?php echo htmlspecialchars($person['birth_location_name']); ?></p>
            <?php endif; ?>
        </div>
        <a href="edit_person.php?id=<?php echo $person['id']; ?>" class="edit-button">Edit</a>
    </div>
    <?php
}

// Check if we have any data
if (empty($persons)) {
    echo '<div class="tree-container">';
    echo '<h1>Family Tree</h1>';
    echo '<p>No family members found in the database. Please add some persons first.</p>';
    echo '</div>';
    require_once '../includes/footer.php';
    exit;
}

// Find root persons (those without parents and who are the oldest generation)
$roots = array_filter($persons, function($person) use ($persons) {
    // Person has no parents
    $hasNoParents = !$person['father_id'] && !$person['mother_id'];
    
    // Check if this person is a parent
    $isParent = hasDescendants($person['id'], $persons);
    
    // Check if this person is a child of anyone in the tree
    $isChild = false;
    foreach ($persons as $potential_parent) {
        if ($person['father_id'] == $potential_parent['id'] || 
            $person['mother_id'] == $potential_parent['id']) {
            $isChild = true;
            break;
        }
    }
    
    // Only include as root if they have no parents, are parents themselves, and are not children
    return $hasNoParents && $isParent && !$isChild;
});

// Sort roots by birth date to ensure oldest generation is first
uasort($roots, function($a, $b) {
    return strtotime($a['birth_date']) - strtotime($b['birth_date']);
});

// If no root persons found after filtering
if (empty($roots)) {
    echo '<div class="tree-container">';
    echo '<h1>Family Tree</h1>';
    echo '<p>No root family members found. Please ensure there are persons with family relationships in the database.</p>';
    echo '</div>';
    require_once '../includes/footer.php';
    exit;
}

?>

<style>
.tree-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.family-tree {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 40px;
}

.tree-branch {
    width: 100%;
}

.generation {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 30px;
}

.couple {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    gap: 20px;
    position: relative;
}

/* Horizontal line between spouses */
.marriage-connector {
    display: flex;
    align-items: center;
    min-width: 60px;
    height: 2px;
    background-color: #666;
}

/* Container for children with vertical line */
.children-container {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    margin-top: 20px;
}

/* Vertical line from parents to children */
.children-container::before {
    content: '';
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    width: 2px;
    height: 20px;
    background-color: #666;
}

/* Container for all children */
.children {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 40px;
    position: relative;
    padding-top: 20px;
}

/* Horizontal line above children */
.children::before {
    content: '';
    position: absolute;
    top: 0;
    left: 20px;
    right: 20px;
    height: 2px;
    background-color: #666;
}

/* Vertical lines to each child */
.child {
    position: relative;
}

.child::before {
    content: '';
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    width: 2px;
    height: 20px;
    background-color: #666;
}

.person-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 15px;
    width: 200px;
    text-align: center;
    position: relative;
}

.person-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    margin: 0 auto 10px;
    overflow: hidden;
    background-color: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.person-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.initials {
    font-size: 1.5em;
    font-weight: bold;
    color: #666;
}

.person-info h3 {
    margin: 0 0 5px;
    font-size: 1em;
    color: #333;
}

.person-info .dates {
    font-size: 0.8em;
    color: #666;
    margin: 0 0 5px;
}

.person-info .location {
    font-size: 0.8em;
    color: #999;
    margin: 0;
}

.edit-button {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 0.8em;
    text-decoration: none;
    opacity: 0;
    transition: opacity 0.2s;
}

.person-card:hover .edit-button {
    opacity: 1;
}

.single-parent {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .couple {
        flex-direction: column;
        gap: 10px;
    }

    .marriage-connector {
        transform: rotate(90deg);
        margin: 10px 0;
    }

    .children {
        gap: 20px;
    }

    .person-card {
        width: 180px;
    }
}

.relationships {
    font-size: 0.85em;
    color: #007bff;
    margin: 2px 0;
    font-style: italic;
}
</style>

<div class="tree-container">
    <h1>Family Tree</h1>
    <div class="family-tree">
        <?php foreach ($roots as $root): ?>
            <div class="tree-branch">
                <div class="generation">
                    <?php
                    // Find if root person is in a marriage
                    $root_marriage = null;
                    foreach ($marriages as $marriage) {
                        if ($marriage['spouse1_id'] == $root['id'] || $marriage['spouse2_id'] == $root['id']) {
                            $root_marriage = $marriage;
                            break;
                        }
                    }
                    
                    if ($root_marriage): 
                        $spouse = $root_marriage['spouse1_id'] == $root['id'] ? 
                            $persons[$root_marriage['spouse2_id']] : 
                            $persons[$root_marriage['spouse1_id']];
                    ?>
                        <div class="couple">
                            <?php renderPersonCard($root, $root, $spouse, false, true); ?>
                            <div class="marriage-connector"></div>
                            <?php renderPersonCard($spouse, $root, $spouse, false, true); ?>
                        </div>
                        <?php
                        // Get and display children
                        $children = getChildren($root['id'], $spouse['id'], $persons);
                        if (!empty($children)):
                        ?>
                            <div class="children-container">
                                <div class="children">
                                    <?php foreach ($children as $child): 
                                        // Skip if this parent-child relationship has already been displayed
                                        if (isRelationshipDisplayed($root['id'], $child['id']) || 
                                            ($spouse && isRelationshipDisplayed($spouse['id'], $child['id']))) {
                                            continue;
                                        }
                                    ?>
                                        <div class="child">
                                            <?php renderPersonCard($child, $root, $spouse, true); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="single-parent">
                            <?php renderPersonCard($root, null, null, false, true); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 