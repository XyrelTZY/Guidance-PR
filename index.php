<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission to add new student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $name = $_POST['name'];
    $building = $_POST['building'];
    $section = $_POST['section'];
    $grade_level = $_POST['grade_level'];
    $offense = $_POST['offense'];
    $community_service_done = isset($_POST['community_service_done']) ? 1 : 0;

    $sql = "INSERT INTO students (name, building, section, grade_level, offense, community_service_done) VALUES ('$name', '$building', '$section', '$grade_level', '$offense', '$community_service_done')";
    if ($conn->query($sql) === TRUE) {
        echo "<div class='bg-green-100 text-green-700 p-4 rounded mb-6'>New record created successfully</div>";
    } else {
        echo "<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Error: " . $sql . "<br>" . $conn->error . "</div>";
    }
}

// Handle form submission to update student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_student'])) {
    $id = $_POST['id'];
    $building = $_POST['building'];
    $section = $_POST['section'];
    $grade_level = $_POST['grade_level'];
    $offense = $_POST['offense'];
    $community_service_done = isset($_POST['community_service_done']) ? 1 : 0;

    $sql = "UPDATE students SET building='$building', section='$section', grade_level='$grade_level', offense='$offense', community_service_done='$community_service_done' WHERE id='$id'";
    if ($conn->query($sql) === TRUE) {
        echo "<div class='bg-green-100 text-green-700 p-4 rounded mb-6'>Record updated successfully</div>";
    } else {
        echo "<div class='bg-red-100 text-red-700 p-4 rounded mb-6'>Error: " . $sql . "<br>" . $conn->error . "</div>";
    }
}

// Handle search query
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination settings
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle sorting
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validate sort column and order
$valid_columns = ['id', 'name', 'building', 'section', 'grade_level', 'offense', 'community_service_done'];
if (!in_array($sort_column, $valid_columns)) $sort_column = 'id';
if ($sort_order !== 'ASC' && $sort_order !== 'DESC') $sort_order = 'ASC';

// Fetch student data with search, sort, and pagination
$sql = "SELECT * FROM students WHERE name LIKE '%$search%' ORDER BY $sort_column $sort_order LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Fetch total number of records for pagination
$total_sql = "SELECT COUNT(*) as total FROM students WHERE name LIKE '%$search%'";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Handle form to fetch student for editing
$edit_student = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $sql = "SELECT * FROM students WHERE id='$id'";
    $edit_student = $conn->query($sql)->fetch_assoc();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Community Service</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <!-- Search Form -->
        <form method="get" class="bg-white p-6 rounded-lg shadow-md mb-6 flex items-center space-x-4">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name..." class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Search</button>
        </form>

        <!-- Form to add new student -->
        <form method="post" class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-bold mb-4">Add Student</h2>
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" id="name" name="name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="mb-4">
                <label for="building" class="block text-sm font-medium text-gray-700">Building</label>
                <input type="text" id="building" name="building" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="mb-4">
                <label for="section" class="block text-sm font-medium text-gray-700">Section</label>
                <input type="text" id="section" name="section" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="mb-4">
                <label for="grade_level" class="block text-sm font-medium text-gray-700">Grade Level</label>
                <select id="grade_level" name="grade_level" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="11">11</option>
                    <option value="12">12</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="offense" class="block text-sm font-medium text-gray-700">Offense</label>
                <textarea id="offense" name="offense" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
            </div>
            <div class="mb-4">
                <input type="checkbox" id="community_service_done" name="community_service_done">
                <label for="community_service_done" class="ml-2 text-sm font-medium text-gray-700">Community Service Done</label>
            </div>
            <button type="submit" name="add_student" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Add Student</button>
        </form>

        <!-- Form to edit student -->
        <?php if ($edit_student): ?>
            <form method="post" class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h2 class="text-xl font-bold mb-4">Edit Student</h2>
                <input type="hidden" name="id" value="<?php echo $edit_student['id']; ?>">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_student['name']); ?>" disabled class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600">
                </div>
                <div class="mb-4">
                    <label for="building" class="block text-sm font-medium text-gray-700">Building</label>
                    <input type="text" id="building" name="building" value="<?php echo htmlspecialchars($edit_student['building']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label for="section" class="block text-sm font-medium text-gray-700">Section</label>
                    <input type="text" id="section" name="section" value="<?php echo htmlspecialchars($edit_student['section']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label for="grade_level" class="block text-sm font-medium text-gray-700">Grade Level</label>
                    <select id="grade_level" name="grade_level" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="11" <?php echo $edit_student['grade_level'] === '11' ? 'selected' : ''; ?>>11</option>
                        <option value="12" <?php echo $edit_student['grade_level'] === '12' ? 'selected' : ''; ?>>12</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="offense" class="block text-sm font-medium text-gray-700">Offense</label>
                    <textarea id="offense" name="offense" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($edit_student['offense']); ?></textarea>
                </div>
                <div class="mb-4">
                    <input type="checkbox" id="community_service_done" name="community_service_done" <?php echo $edit_student['community_service_done'] ? 'checked' : ''; ?>>
                    <label for="community_service_done" class="ml-2 text-sm font-medium text-gray-700">Community Service Done</label>
                </div>
                <button type="submit" name="update_student" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">Update Student</button>
                <a href="index.php" class="ml-4 text-blue-500 hover:text-blue-600">Cancel</a>
            </form>
        <?php endif; ?>

        <!-- Table displaying students -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 bg-white shadow-md rounded-lg">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?search=<?php echo urlencode($search); ?>&sort=id&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="flex items-center">ID 
                                <?php if ($sort_column === 'id'): ?>
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?search=<?php echo urlencode($search); ?>&sort=name&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="flex items-center">Name 
                                <?php if ($sort_column === 'name'): ?>
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Building</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?search=<?php echo urlencode($search); ?>&sort=grade_level&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="flex items-center">Grade Level 
                                <?php if ($sort_column === 'grade_level'): ?>
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Offense</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?search=<?php echo urlencode($search); ?>&sort=community_service_done&order=<?php echo $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="flex items-center">Community Service Done 
                                <?php if ($sort_column === 'community_service_done'): ?>
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $row['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['building']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['section']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['grade_level']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['offense']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['community_service_done'] ? 'Yes' : 'No'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="index.php?edit=<?php echo $row['id']; ?>" class="text-blue-500 hover:text-blue-600">Edit</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex justify-between">
            <div>
                <?php if ($page > 1): ?>
                    <a href="?search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $page - 1; ?>" class="text-blue-500 hover:text-blue-600">&laquo; Previous</a>
                <?php endif; ?>
            </div>
            <div>
                <?php if ($page < $total_pages): ?>
                    <a href="?search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $page + 1; ?>" class="text-blue-500 hover:text-blue-600">Next &raquo;</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

        <!-- Create Table From SQL -->
<!-- CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    community_service_done BOOLEAN NOT NULL
);
 -->

 <!-- -- Add new columns to the students table
ALTER TABLE students
ADD COLUMN building VARCHAR(100) AFTER name,
ADD COLUMN section VARCHAR(100) AFTER building,
ADD COLUMN grade_level ENUM('11', '12') AFTER section,
ADD COLUMN offense TEXT AFTER grade_level;
 -->