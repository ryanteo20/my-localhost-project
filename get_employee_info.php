<?php
// Include your database connection
require('database.php');

// Check if the employee ID is provided
if (isset($_POST['employeeId'])) {
    // Sanitize the input to prevent SQL injection
    $employeeId = mysqli_real_escape_string($con, $_POST['employeeId']);

    // Prepare the SQL query to select employee information by ID
    $query = "SELECT * FROM employees WHERE id = '$employeeId'";

    // Execute the query
    $result = mysqli_query($con, $query);

    // Check if there is exactly one row returned
    if ($result && mysqli_num_rows($result) == 1) {
        // Fetch the employee's information
        $row = mysqli_fetch_assoc($result);

        // Generate the HTML content for the edit form or modal
        $html = '<form id="editEmployeeForm">';
        // Add input fields for editing employee information
        // Example:
        $html .= '<input type="text" name="fullname" value="' . $row['fullname'] . '">';
        $html .= '<input type="text" name="email" value="' . $row['email'] . '">';
        // Add more input fields as needed
        $html .= '<button type="submit">Save Changes</button>';
        $html .= '</form>';

        // Output the HTML content
        echo $html;
    } else {
        echo "Error: Employee not found or multiple employees with the same ID.";
    }
} else {
    echo "Error: Employee ID not provided.";
}

// Close the connection
mysqli_close($con);
?>
