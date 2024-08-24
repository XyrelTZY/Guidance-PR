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

// Clear students who were added more than 24 hours ago
$sql = "DELETE FROM students WHERE created_at < NOW() - INTERVAL 1 DAY";
$conn->query($sql);

// Handle form submission to add new student
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_student'])) {
        $name = $_POST['name'];
        $building = $_POST['building'];
        $section = $_POST['section'];
        $grade_level = $_POST['grade_level'];
        $offense = $_POST['offense'];
        $community_service_done = isset($_POST['community_service_done']) ? 1 : 0;
        $expelled = isset($_POST['expelled']) ? 1 : 0;

        $sql = "INSERT INTO students (name, building, section, grade_level, offense, community_service_done, expelled) VALUES ('$name', '$building', '$section', '$grade_level', '$offense', '$community_service_done', '$expelled')";
        if ($conn->query($sql) === TRUE) {
            $message = "New record created successfully";
        } else {
            $message = "Error: " . $sql . "<br>" . $conn->error;
        }
        
        // Redirect to the same page to prevent re-submission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['update_student'])) {
        $id = $_POST['id'];
        $building = $_POST['building'];
        $section = $_POST['section'];
        $grade_level = $_POST['grade_level'];
        $offense = $_POST['offense'];
        $community_service_done = isset($_POST['community_service_done']) ? 1 : 0;
        $expelled = isset($_POST['expelled']) ? 1 : 0;

        $sql = "UPDATE students SET building='$building', section='$section', grade_level='$grade_level', offense='$offense', community_service_done='$community_service_done', expelled='$expelled' WHERE id='$id'";
        if ($conn->query($sql) === TRUE) {
            $message = "Record updated successfully";
        } else {
            $message = "Error: " . $sql . "<br>" . $conn->error;
        }

        // Redirect to the same page to prevent re-submission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle search query
$search = isset($_GET['search']) ? $_GET['search'] : '';
$section_filter = isset($_GET['section']) ? $_GET['section'] : '';
$building_filter = isset($_GET['building']) ? $_GET['building'] : '';
$name_filter = isset($_GET['name']) ? $_GET['name'] : '';
$grade_filter = isset($_GET['grade']) ? $_GET['grade'] : '';

// Pagination settings
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle sorting
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validate sort column and order
$valid_columns = ['id', 'name', 'building', 'section', 'grade_level', 'offense', 'community_service_done', 'expelled'];
if (!in_array($sort_column, $valid_columns)) $sort_column = 'id';
if ($sort_order !== 'ASC' && $sort_order !== 'DESC') $sort_order = 'ASC';

// Fetch student data with search, sort, and pagination
$sql = "SELECT * FROM students WHERE name LIKE '%$search%' AND section LIKE '%$section_filter%' AND building LIKE '%$building_filter%' AND grade_level LIKE '%$grade_filter%' ORDER BY $sort_column $sort_order LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Fetch total number of records for pagination
$total_sql = "SELECT COUNT(*) as total FROM students WHERE name LIKE '%$search%' AND section LIKE '%$section_filter%' AND building LIKE '%$building_filter%' AND grade_level LIKE '%$grade_filter%'";
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

// Dashboard Data
$students_count = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$expelled_count = $conn->query("SELECT COUNT(*) as count FROM students WHERE expelled = 1")->fetch_assoc()['count'];
$community_service_count = $conn->query("SELECT COUNT(*) as count FROM students WHERE community_service_done = 1")->fetch_assoc()['count'];
$recent_students_sql = "SELECT * FROM students ORDER BY id DESC LIMIT 5";
$recent_students_result = $conn->query($recent_students_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>

        body{
            overflow-x: hidden;
        }
        /* Modal Container */
        .modal {
            visibility: hidden;
            opacity: 0;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            transition: opacity 0.3s ease;
        }
        /* Show Modal */
        .modal.show {
            visibility: visible;
            opacity: 1;
        }
        /* Modal Content */
        .modal-content {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            max-width: 600px;
            width: 100%;
        }
        /* Circle Design */
        .circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background-color: #2563eb;
        }
        .circle span {
            font-size: 1.25rem;
        }
        .circle .icon {
            font-size: 2rem;
        }

        /* Conditional Background Color for Community Service and Expelled Status */
.bg-community-service-done {
    background-color: #d4edda; /* Light green */
}

.bg-community-service-not-done {
    background-color: #f8d7da; /* Light red */
}

.bg-expelled {
    background-color: #f8d7da; /* Light red */
}

.bg-not-expelled {
    background-color: #d4edda; /* Light green */
}

    </style>
    <title>Student Community Service</title>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <!-- Navigation Links -->
            <nav class="flex space-x-4">
                <a href="index.php" class="bg-blue-400 text-black hover:bg-blue-700 px-3 py-2 rounded cursor-pointer">Home</a>
                <a href="logout.php" class="bg-red-700 hover:bg-red-800 px-3 py-2 rounded">Logout</a>
            </nav>
            <!-- Admin Title -->
            <div class="text-lg font-bold">Admin</div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="p-8">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row space-y-8 lg:space-y-0 lg:space-x-8">
            <!-- Dashboard -->
            <div class="w-full lg:w-1/4">
                <h2 class="text-lg font-semibold mb-4 bg-blue-300 w-auto text-center h-10 pt-1 rounded-lg">Dashboard</h2>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6 flex flex-col items-center space-y-4">
                    <div class="flex items-center space-x-4">
                        <div class="circle relative left-[-50px]">
                            <span class="font-bold"><?php echo $students_count; ?></span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700 relative left-[-40px]">Total Students</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="circle bg-expelled relative left-[-40px]">
                            <span class="font-bold"><?php echo $expelled_count; ?></span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700 relative left-[-30px]">Total Expelled</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="circle bg-community-service-done relative left-[-40px]">
                            <span class="font-bold"><?php echo $community_service_count; ?></span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700 relative left-[-30px]">Community Service Done</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Students List -->
                <div class="bg-white p-4 rounded-lg shadow-md mb-6">
                    <h3 class="font-semibold mb-4">Recent Students</h3>
                    <?php if ($recent_students_result->num_rows > 0): ?>
                        <ul class="space-y-2">
                            <?php while ($row = $recent_students_result->fetch_assoc()): ?>
                                <li class="border p-2 bg-white hover:bg-gray-50 rounded-lg">
                                    <p><strong>Name:</strong> <?php echo $row['name']; ?></p>
                                    <p><strong>Building:</strong> <?php echo $row['building']; ?></p>
                                    <p><strong>Section:</strong> <?php echo $row['section']; ?></p>
                                    <p><strong>Date:</strong> <?php echo date("F j, Y, g:i a", strtotime($row['created_at'])); ?></p>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No recent students found.</p>
                    <?php endif; ?>
                </div>
            </div>
       
            <!-- Students Table -->
            <div class="w-full lg:w-3/4 bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold relative top-[-30px]">Student Records</h2>
                    <form method="GET" class="flex space-x-4 mt-[50px]">
                        <!-- Filter by Section -->
                        <input type="text" name="section" placeholder="Section" class="border rounded p-2 relative left-[-65px]">
                        <!-- Filter by Building -->
                        <input type="text" name="building" placeholder="Building" class="border rounded p-2 relative left-[-65px]">
                        <!-- Filter by Name -->
                        <input type="text" name="name" placeholder="Name" class="border rounded p-2 relative left-[-65px]">
                        <!-- Filter by Grade Level -->
                        <input type="text" name="grade" placeholder="Grade Level" class="border rounded p-2 relative left-[-65px]">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded relative left-[-70px] w-[100px]">Filter</button>
                    </form>
                </div>
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border">ID</th>
                            <th class="py-2 px-4 border">Name</th>
                            <th class="py-2 px-4 border">Building</th>
                            <th class="py-2 px-4 border">Section</th>
                            <th class="py-2 px-4 border">Grade Level</th>
                            <th class="py-2 px-4 border">Offense</th>
                            <th class="py-2 px-4 border">Community Service</th>
                            <th class="py-2 px-4 border">Expelled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="<?php echo $row['expelled'] ? 'bg-expelled' : 'bg-not-expelled'; ?>">
                                    <td class="py-2 px-4 border"><?php echo $row['id']; ?></td>
                                    <td class="py-2 px-4 border"><?php echo $row['name']; ?></td>
                                    <td class="py-2 px-4 border"><?php echo $row['building']; ?></td>
                                    <td class="py-2 px-4 border"><?php echo $row['section']; ?></td>
                                    <td class="py-2 px-4 border"><?php echo $row['grade_level']; ?></td>
                                    <td class="py-2 px-4 border"><?php echo $row['offense']; ?></td>
                                    <td class="py-2 px-4 border">
                                        <?php echo $row['community_service_done'] ? 'Done' : 'Not Done'; ?>
                                    </td>
                                    <td class="py-2 px-4 border">
                                        <?php echo $row['expelled'] ? 'Yes' : 'No'; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="py-2 px-4 border">No students found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="mt-6 flex justify-between items-center">
                    <div>
                        <p>Page <?php echo $page; ?> of <?php echo $total_pages; ?></p>
                    </div>
                    <div>
                        <a href="?page=1" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded">First</a>
                        <a href="?page=<?php echo max(1, $page - 1); ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded">Previous</a>
                        <a href="?page=<?php echo min($total_pages, $page + 1); ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded">Next</a>
                        <a href="?page=<?php echo $total_pages; ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded">Last</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal for Editing Student Data -->
    <?php if ($edit_student): ?>
        <div class="modal show">
            <div class="modal-content">
                <h2 class="text-lg font-semibold mb-4">Edit Student</h2>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $edit_student['id']; ?>">
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="name" value="<?php echo $edit_student['name']; ?>" class="border rounded p-2 w-full">
                    </div>
                    <div class="mb-4">
                        <label for="building" class="block text-sm font-medium text-gray-700">Building</label>
                        <input type="text" name="building" id="building" value="<?php echo $edit_student['building']; ?>" class="border rounded p-2 w-full">
                    </div>
                    <div class="mb-4">
                        <label for="section" class="block text-sm font-medium text-gray-700">Section</label>
                        <input type="text" name="section" id="section" value="<?php echo $edit_student['section']; ?>" class="border rounded p-2 w-full">
                    </div>
                    <div class="mb-4">
                        <label for="grade_level" class="block text-sm font-medium text-gray-700">Grade Level</label>
                        <input type="text" name="grade_level" id="grade_level" value="<?php echo $edit_student['grade_level']; ?>" class="border rounded p-2 w-full">
                    </div>
                    <div class="mb-4">
                        <label for="offense" class="block text-sm font-medium text-gray-700">Offense</label>
                        <input type="text" name="offense" id="offense" value="<?php echo $edit_student['offense']; ?>" class="border rounded p-2 w-full">
                    </div>
                    <div class="mb-4">
                        <label for="community_service_done" class="block text-sm font-medium text-gray-700">Community Service Done</label>
                        <input type="checkbox" name="community_service_done" id="community_service_done" <?php echo $edit_student['community_service_done'] ? 'checked' : ''; ?> class="border rounded">
                    </div>
                    <div class="mb-4">
                        <label for="expelled" class="block text-sm font-medium text-gray-700">Expelled</label>
                        <input type="checkbox" name="expelled" id="expelled" <?php echo $edit_student['expelled'] ? 'checked' : ''; ?> class="border rounded">
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" name="update_student" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Update</button>
                        <a href="/" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded ml-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
