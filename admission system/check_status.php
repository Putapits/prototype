<?php
// check_status.php - Application status check page

// Include database connection
require_once 'db_config.php';

// Initialize variables
$message = '';
$applicationData = null;
$statusHistory = null;

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form data
    $applicationId = mysqli_real_escape_string($conn, $_POST['applicationId']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Validate input
    if(empty($applicationId) || empty($email)) {
        $message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">Please fill in all fields.</div>';
    } else {
        // Query to get application details
        $sql = "SELECT a.application_id, a.submission_date, a.application_status, a.decision_date,
                s.first_name, s.last_name, s.email, s.phone,
                p.program_name, st.type_name, sem.semester_name
                FROM applications a
                JOIN students s ON a.student_id = s.student_id
                JOIN programs p ON a.program_id = p.program_id
                JOIN student_types st ON a.type_id = st.type_id
                JOIN semesters sem ON a.semester_id = sem.semester_id
                WHERE a.application_id = ? AND s.email = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $applicationId, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) > 0) {
            $applicationData = mysqli_fetch_assoc($result);
            
            // Get status history
            $historySQL = "SELECT status, status_date, comments 
                          FROM application_status_history 
                          WHERE application_id = ? 
                          ORDER BY status_date DESC";
            
            $historyStmt = mysqli_prepare($conn, $historySQL);
            mysqli_stmt_bind_param($historyStmt, "i", $applicationId);
            mysqli_stmt_execute($historyStmt);
            $historyResult = mysqli_stmt_get_result($historyStmt);
            
            if(mysqli_num_rows($historyResult) > 0) {
                $statusHistory = [];
                while($row = mysqli_fetch_assoc($historyResult)) {
                    $statusHistory[] = $row;
                }
            }
        } else {
            $message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">No application found with the provided ID and email. Please check your information and try again.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Application Status - University Admission Portal</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <header class="bg-blue-700 text-white rounded-lg shadow-lg mb-8">
            <div class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold">Student Admission Portal</h1>
                        <p class="text-blue-200">Your pathway to academic excellence</p>
                    </div>
                    <div class="hidden md:flex">
                        <a href="index.html" class="px-3 py-2 rounded hover:bg-blue-800">Home</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <!-- Status Check Section -->
            <section class="bg-white rounded-lg shadow-lg mb-8 p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Check Application Status</h2>
                
                <?php echo $message; ?>
                
                <?php if ($applicationData === null): ?>
                <!-- Status Check Form -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-bold mb-2" for="applicationId">Application ID</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="applicationId" name="applicationId" type="text" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-bold mb-2" for="email">Email Address</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" name="email" type="email" required>
                        </div>
                    </div>
                    <div class="flex justify-center">
                        <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition duration-300" type="submit">
                            Check Status
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <!-- Application Status Display -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <div class="text-center mb-4">
                        <h3 class="font-bold text-xl">Application ID: <span class="text-blue-700"><?php echo $applicationData['application_id']; ?></span></h3>
                        <p class="text-sm text-gray-500">Submitted on: <?php echo date("F j, Y, g:i a", strtotime($applicationData['submission_date'])); ?></p>
                        <div class="mt-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                            <?php 
                            switch($applicationData['application_status']) {
                                case 'Pending':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                case 'Under Review':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                                case 'Approved':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'Rejected':
                                    echo 'bg-red-100 text-red-800';
                                    break;
                            }
                            ?>">
                                Status: <?php echo $applicationData['application_status']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-bold mb-2">Applicant Information</h4>
                            <p><span class="font-semibold">Name:</span> <?php echo $applicationData['first_name'] . ' ' . $applicationData['last_name']; ?></p>
                            <p><span class="font-semibold">Email:</span> <?php echo $applicationData['email']; ?></p>
                            <p><span class="font-semibold">Phone:</span> <?php echo $applicationData['phone']; ?></p>
                            <p><span class="font-semibold">Application Type:</span> <?php echo $applicationData['type_name']; ?></p>
                        </div>
                        <div>
                            <h4 class="font-bold mb-2">Program Information</h4>
                            <p><span class="font-semibold">Program:</span> <?php echo $applicationData['program_name']; ?></p>
                            <p><span class="font-semibold">Starting Semester:</span> <?php echo $applicationData['semester_name']; ?></p>
                            <?php if($applicationData['decision_date']): ?>
                            <p><span class="font-semibold">Decision Date:</span> <?php echo date("F j, Y", strtotime($applicationData['decision_date'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if($statusHistory): ?>
                <!-- Status History -->
                <div class="mt-6">
                    <h4 class="font-bold mb-3">Application Status History</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="py-2 px-4 border-b text-left">Date</th>
                                    <th class="py-2 px-4 border-b text-left">Status</th>
                                    <th class="py-2 px-4 border-b text-left">Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($statusHistory as $history): ?>
                                <tr>
                                    <td class="py-2 px-4 border-b"><?php echo date("M j, Y, g:i a", strtotime($history['status_date'])); ?></td>
                                    <td class="py-2 px-4 border-b">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php 
                                        switch($history['status']) {
                                            case 'Pending':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'Under Review':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                            case 'Approved':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'Rejected':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                        }
                                        ?>">
                                            <?php echo $history['status']; ?>
                                        </span>
                                    </td>
                                    <td class="py-2 px-4 border-b"><?php echo $history['comments']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flex justify-center mt-8">
                    <a href="check_status.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition duration-300">
                        Check Another Application
                    </a>
                    <a href="index.html" class="ml-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition duration-300">
                        Return to Home
                    </a>
                </div>
                <?php endif; ?>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-blue-800 text-white rounded-lg shadow-lg mt-8 p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h3 class="text-lg font-bold mb-4">Contact Us</h3>
                    <p>Email: admissions@university.edu</p>
                    <p>Phone: (555) 123-4567</p>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4">Quick Links</h3>
                    <ul>
                        <li><a href="#" class="hover:underline">About Us</a></li>
                        <li><a href="#" class="hover:underline">Admission Requirements</a></li>
                        <li><a href="#" class="hover:underline">Financial Aid</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="hover:text-blue-300">Facebook</a>
                        <a href="#" class="hover:text-blue-300">Twitter</a>
                        <a href="#" class="hover:text-blue-300">Instagram</a>
                    </div>
                </div>
            </div>
            <div class="mt-8 text-center text-sm">
                <p>&copy; 2025 Admission Management System. All rights reserved.</p>
            </div>
        </footer>
    </div>
</body>
</html>