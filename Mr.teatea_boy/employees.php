<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="employees.css">
    <title>Employees</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" type="text/css" href="./css/main.css">
    <link rel="stylesheet" type="text/css" href="./css/admin.css">
    <link rel="stylesheet" type="text/css" href="./css/util.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
    <div class="container">
        <!-- Employee Details Section -->
        <div class="employee-details">
            <h2>Employee Details</h2>
            <div class="image-container">
                <img src="https://via.placeholder.com/100" alt="Employee Image" id="employee-image">
            </div>
            <div class="form-group">
                <input type="file" id="image-upload" accept="image/*">
            </div>
            <div class="form-group">
                <label for="employee-username">Name</label>
                <input type="text" id="name" placeholder="Name" readonly>
            </div>
            <div class="form-group">
                <label for="employee-username">Email</label>
                <input type="text" id="employee-email" placeholder="Email" readonly>
            </div>
            <div class="form-group">
                <label for="employee-username">Password</label>
                <input type="text" id="password" placeholder="Password" readonly>
            </div>
            <div class="form-group">
                <label for="employee-bday">B-day</label>
                <input type="date" id="employee-bday" readonly>
            </div>
            <div class="form-group">
                <label for="employee-age">Age</label>
                <input type="number" id="employee-age" placeholder="Age" readonly>
            </div>
            <button id="register-btn">Register Employee</button>
            <button id="delete-btn">Delete Employee</button>
        </div>

        <!-- Employee List Section -->
        <div class="employee-list">
            <h2>Employee List</h2>
            <table>
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Age</th>
                        <th>BirthDate</th>
                    </tr>
                </thead>
                <tbody id="employee-list-body">
                    <!-- Employee rows will go here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- The Modal for Employee Registration -->
    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Register New Employee</h2>
            <form id="register-form">
                <div>
                    <label for="reg-name">Name</label>
                    <input type="text" id="reg-name" name="name" placeholder="Enter name" required>
                </div>
                <div>
                    <label for="reg-email">Email</label>
                    <input type="email" id="reg-email" name="email" placeholder="Enter email" required>
                </div>
                <div>
                    <label for="reg-password">Password</label>
                    <input type="password" id="reg-password" name="password" placeholder="Enter password" required>
                </div>
                <div>
                    <label for="reg-bday">Birth Date</label>
                    <input type="date" id="reg-bday" name="bday" required>
                </div>
                <div>
                    <label for="reg-age">Age</label>
                    <input type="number" id="reg-age" name="age" placeholder="Enter age" required>
                </div>
                <button type="submit">Register</button>
            </form>
        </div>
    </div>

    <script>
        // Get modal element
        var modal = document.getElementById("employeeModal");
        var registerBtn = document.getElementById("register-btn");
        var closeBtn = document.getElementsByClassName("close")[0];

        // Open the modal when "Register Employee" is clicked
        registerBtn.onclick = function() {
            modal.style.display = "block";
        }

        // Close the modal when the user clicks the "x" button
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }

        // Close the modal if user clicks outside of the modal
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Handle form submission
        document.getElementById("register-form").addEventListener("submit", function(event) {
            event.preventDefault();

            // Get form data
            const name = document.getElementById("reg-name").value;
            const email = document.getElementById("reg-email").value;
            const password = document.getElementById("reg-password").value;
            const bday = document.getElementById("reg-bday").value;
            const age = document.getElementById("reg-age").value;

            // Prepare data for sending to PHP script
            const formData = new FormData();
            formData.append('name', name);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('bday', bday);
            formData.append('age', age);

            // Send data to PHP script
            fetch('add_employee.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Create a new row in the employee list if registration was successful
                if (data.includes("successfully")) {
                    const employeeListBody = document.getElementById("employee-list-body");
                    const newRow = employeeListBody.insertRow();
                    newRow.innerHTML = `
                        <td><img src="https://via.placeholder.com/50" alt="Employee Image"></td>
                        <td>${name}</td>
                        <td>${email}</td>
                        <td>${password}</td>
                        <td>${age}</td>
                        <td>${bday}</td>
                    `;

                    // Clear the form
                    document.getElementById("register-form").reset();
                    modal.style.display = "none"; // Close modal after submission
                    alert("Employee Registered Successfully!");
                } else {
                    alert("Error: " + data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("There was an error registering the employee.");
            });
        });

        // Fetch employee data on page load
        window.onload = function() {
            fetch('get_employees.php')
                .then(response => response.json())
                .then(data => {
                    const employeeListBody = document.getElementById("employee-list-body");
                    data.forEach(employee => {
                        const newRow = employeeListBody.insertRow();
                        newRow.innerHTML = `
                            <td><img src="https://via.placeholder.com/50" alt="Employee Image"></td>
                            <td>${employee.name}</td>
                            <td>${employee.email}</td>
                            <td>${employee.password}</td>
                            <td>${employee.age}</td>
                            <td>${employee.birthdate}</td>
                        `;
                    });
                })
                .catch(error => console.error('Error:', error));
        };
    </script>

    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
