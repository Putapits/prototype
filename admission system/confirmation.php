<?php
// confirmation.php - Confirmation page after application submission

// Include database connection
require_once 'db_config.php';

// Check if application ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.html");
    exit;
}

$applicationId = intval($_GET['id']);

// Get application details
$sql = "SELECT a.application_id, a.submission_date, a.application_status, 
        s.first_name, s.last_name, s.email,
        p.program_name, st.type_name, sem.semester_name
        FROM applications a
        JOIN students s ON a.student_id = s.student_id
        JOIN programs p ON a.program_id = p.program_id
        JOIN student_types st ON a.type_id = st.type_id
        JOIN semesters sem ON a.semester_id = sem.semester_id
        WHERE a.application_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $applicationId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    header("Location: index.html");
    exit;
}

$application = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Confirmation - University Admission Portal</title>
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
            <!-- Confirmation Section -->
            <section class="bg-white rounded-lg shadow-lg mb-8 p-6">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-500 mb-4">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Application Submitted Successfully!</h2>
                    <p class="text-gray-600">Thank you for submitting your application. Please save your application ID for future reference.</p>
                </div>

                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <div class="text-center mb-4">
                        <h3 class="font-bold text-xl">Application ID: <span class="text-blue-700"><?php echo $applicationId; ?></span></h3>
                        <p class="text-sm text-gray-500">Submitted on: <?php echo date("F j, Y, g:i a", strtotime($application['submission_date'])); ?></p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-bold mb-2">Applicant Information</h4>
                            <p><span class="font-semibold">Name:</span> <?php echo $application['first_name'] . ' ' . $application['last_name']; ?></p>
                            <p><span class="font-semibold">Email:</span> <?php echo $application['email']; ?></p>
                            <p><span class="font-semibold">Application Type:</span> <?php echo $application['type_name']; ?></p>
                        </div>
                        <div>
                            <h4 class="font-bold mb-2">Program Information</h4>
                            <p><span class="font-semibold">Program:</span> <?php echo $application['program_name']; ?></p>
                            <p><span class="font-semibold">Starting Semester:</span> <?php echo $application['semester_name']; ?></p>
                            <p><span class="font-semibold">Status:</span> 
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                <?php 
                                switch($application['application_status']) {
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
                                    <?php echo $application['application_status']; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 p-6 rounded-lg mb-6">
                    <h4 class="font-bold mb-2">Next Steps</h4>
                    <ol class="list-decimal pl-5 space-y-2">
                        <li>Check your email for a confirmation message with your application details.</li>
                        <li>Complete any additional requirements specific to your program.</li>
                        <li>You can check your application status anytime using your Application ID and email.</li>
                        <li>Our admissions team will review your application and may contact you for additional information.</li>
                    </ol>
                </div>

                <div class="flex justify-center mt-8">
                    <a href="index.html" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition duration-300">
                        Return to Home
                    </a>
                    <a href="check_status.php" class="ml-4 bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition duration-300">
                        Check Application Status
                    </a>
                </div>
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