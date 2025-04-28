<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admission System - Students</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Ensures footer stays at bottom */
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    main {
      flex: 1;
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-800">

  <!-- Header -->
  <header class="bg-blue-600 text-white p-6">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
      <h1 class="text-2xl font-bold">
        <a href="admission.html">
          <span class="text-sm align-top">admission</span><span class="text-green-400">system</span>
        </a>
      </h1>
      <nav class="space-x-6">
        <a href="dashboard.php" class="hover:underline" aria-label="Go to Dashboard">Dashboard</a>
        <a href="applications.php" class="hover:underline" aria-label="Go to Applications">Applications</a>
        <a href="students.php" class="hover:underline font-semibold" aria-current="page" aria-label="Current page: Students">Students</a>
      </nav>
    </div>
  </header>

  <!-- Main Content -->
  <main class="max-w-7xl mx-auto p-6">

    <!-- Top Bar -->
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-semibold">Students</h2>
      <button id="addStudentBtn" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Add a new student">
        + Add Student
      </button>
    </div>

    <!-- Student Form Modal (Hidden by default) -->
    <div id="studentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
      <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-semibold" id="modalTitle">Add New Student</h3>
          <button class="text-gray-500 hover:text-gray-700" id="closeModal">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <form id="studentForm">
          <input type="hidden" id="studentId" name="student_id" value="0">
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label for="firstName" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
              <input type="text" id="firstName" name="first_name" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
            <div>
              <label for="middleName" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
              <input type="text" id="middleName" name="middle_name" class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
            <div>
              <label for="lastName" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
              <input type="text" id="lastName" name="last_name" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input type="email" id="email" name="email" required class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
              <input type="tel" id="phone" name="phone" class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
          </div>
          
          <div class="mt-4">
            <label for="dateOfBirth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
            <input type="date" id="dateOfBirth" name="date_of_birth" class="border border-gray-300 rounded px-3 py-2">
          </div>
          
          <div class="mt-6 flex justify-end space-x-3">
            <button type="button" id="cancelBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Cancel</button>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Save Student</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Student Table -->
    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
      <table class="min-w-full table-auto" aria-label="Students information table">
        <thead class="bg-gray-100 text-gray-700">
          <tr>
            <th class="text-left px-4 py-3" scope="col">ID</th>
            <th class="text-left px-4 py-3" scope="col">Full Name</th>
            <th class="text-left px-4 py-3" scope="col">Email</th>
            <th class="text-left px-4 py-3" scope="col">Phone</th>
            <th class="text-left px-4 py-3" scope="col">Applications</th>
            <th class="text-left px-4 py-3" scope="col">Actions</th>
          </tr>
        </thead>
        <tbody id="studentsTableBody">
          <!-- Loading state -->
          <tr id="loadingRow">
            <td colspan="6" class="text-center py-8 text-gray-500">Loading students...</td>
          </tr>
        </tbody>
      </table>
    </div>

  </main>

  <!-- Footer -->
  <footer class="bg-white text-center py-4 text-sm text-gray-500 mt-auto">
    <p>© <span id="currentYear">2025</span> xyz - Admission Management System</p>
  </footer>

  <script>
    // DOM Elements
    const studentModal = document.getElementById('studentModal');
    const studentForm = document.getElementById('studentForm');
    const addStudentBtn = document.getElementById('addStudentBtn');
    const closeModal = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const modalTitle = document.getElementById('modalTitle');
    const studentsTableBody = document.getElementById('studentsTableBody');
    const studentIdField = document.getElementById('studentId');
    
    // Update copyright year automatically
    document.getElementById('currentYear').textContent = new Date().getFullYear();

    // Open modal in "add" mode
    addStudentBtn.addEventListener('click', function() {
      modalTitle.textContent = 'Add New Student';
      studentForm.reset();
      studentIdField.value = '0';
      studentModal.classList.remove('hidden');
    });

    // Close modal
    function closeStudentModal() {
      studentModal.classList.add('hidden');
    }

    closeModal.addEventListener('click', closeStudentModal);
    cancelBtn.addEventListener('click', closeStudentModal);

    // Close modal when clicking outside
    studentModal.addEventListener('click', function(e) {
      if (e.target === studentModal) {
        closeStudentModal();
      }
    });

    // Form submission
    studentForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(studentForm);
      formData.append('action', studentIdField.value === '0' ? 'addStudent' : 'updateStudent');
      
      // AJAX request to backend
      fetch('student_handler.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          closeStudentModal();
          loadStudents(); // Refresh the table
          alert(studentIdField.value === '0' ? 'Student added successfully!' : 'Student updated successfully!');
        } else {
          alert('Error: ' + (data.message || 'Unknown error occurred'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
      });
    });

    // Load all students from database
    function loadStudents() {
      studentsTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">Loading students...</td></tr>';
      
      const formData = new FormData();
      formData.append('action', 'getAllStudents');
      
      fetch('student_handler.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(students => {
        if (students.length === 0) {
          studentsTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No students available yet.</td></tr>';
          return;
        }
        
        studentsTableBody.innerHTML = '';
        students.forEach(student => {
          const row = document.createElement('tr');
          row.className = 'border-t border-gray-200 hover:bg-gray-50';
          
          row.innerHTML = `
            <td class="px-4 py-3">${student.student_id}</td>
            <td class="px-4 py-3">${student.first_name} ${student.middle_name ? student.middle_name + ' ' : ''}${student.last_name}</td>
            <td class="px-4 py-3">${student.email}</td>
            <td class="px-4 py-3">${student.phone || 'N/A'}</td>
            <td class="px-4 py-3">${student.application_count || 0}</td>
            <td class="px-4 py-3">
              <button class="text-blue-600 hover:text-blue-800 mr-2 edit-btn" data-id="${student.student_id}">Edit</button>
              <button class="text-blue-600 hover:text-blue-800 mr-2 view-btn" data-id="${student.student_id}">View</button>
              <button class="text-red-600 hover:text-red-800 delete-btn" data-id="${student.student_id}">Delete</button>
            </td>
          `;
          
          studentsTableBody.appendChild(row);
        });
        
        // Add event listeners to action buttons
        document.querySelectorAll('.edit-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            editStudent(this.getAttribute('data-id'));
          });
        });
        
        document.querySelectorAll('.view-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            viewStudent(this.getAttribute('data-id'));
          });
        });
        
        document.querySelectorAll('.delete-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            deleteStudent(this.getAttribute('data-id'));
          });
        });
      })
      .catch(error => {
        console.error('Error:', error);
        studentsTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-500">Error loading students. Please try again.</td></tr>';
      });
    }

    // Load student data for editing
    function editStudent(studentId) {
      modalTitle.textContent = 'Edit Student';
      studentIdField.value = studentId;
      
      const formData = new FormData();
      formData.append('action', 'getStudentDetails');
      formData.append('student_id', studentId);
      
      fetch('student_handler.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(student => {
        if (student) {
          document.getElementById('firstName').value = student.first_name;
          document.getElementById('middleName').value = student.middle_name;
          document.getElementById('lastName').value = student.last_name;
          document.getElementById('email').value = student.email;
          document.getElementById('phone').value = student.phone;
          document.getElementById('dateOfBirth').value = student.date_of_birth;
          
          studentModal.classList.remove('hidden');
        } else {
          alert('Student not found');
        }
      })
      .catch(error => {
        console.