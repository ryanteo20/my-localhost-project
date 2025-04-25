document.addEventListener('DOMContentLoaded', function() {
    const updateFieldSelect = document.getElementById('updateField');
    const newRoleDropdown = document.getElementById('newRoleDropdown');
    const newValueField = document.getElementById('newValueField');
    const firstLoginOptions = document.getElementById('firstLoginOptions');

    updateFieldSelect.addEventListener('change', function() {
        const selectedOption = updateFieldSelect.value;
        if (selectedOption === 'role') {
            newRoleDropdown.style.display = 'block';
            newValueField.style.display = 'none';
            firstLoginOptions.style.display = 'none'; // Hide first login options
        } else if (selectedOption === 'first_login') {
            firstLoginOptions.style.display = 'block'; // Show first login options
            newValueField.style.display = 'none';
            newRoleDropdown.style.display = 'none';
        } else {
            newRoleDropdown.style.display = 'none';
            newValueField.style.display = 'block';
            firstLoginOptions.style.display = 'none'; // Hide first login options
        }
    });

    const saveChangesBtn = document.getElementById('saveChangesBtn');
    saveChangesBtn.addEventListener('click', function() {
        const selectedField = updateFieldSelect.value;
        let newValue;

        if (selectedField === 'role') {
            newValue = document.getElementById('newRole').value;
        } else if (selectedField === 'first_login') {
            newValue = document.getElementById('firstLoginSelect').value;
        } else {
            newValue = document.getElementById('newValue').value;
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_employee.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                console.log(xhr.responseText);
            }
        };
        xhr.send(`employeeId=${document.getElementById('employeeIdInput').value}&selectedField=${selectedField}&newValue=${newValue}`);
    });
});


$(document).ready(function() {
    var selectedField; // Define the selectedField variable outside the click event handlers

    $('#saveChangesBtn').click(function() {
        // Get the new value
        var newValue = $('#newValue').val();

        $.ajax({
            url: 'update_employee.php',
            type: 'POST',
            data: {
                employeeId: $('#employeeIdInput').val(),
                selectedField: selectedField, // Use the selectedField variable set earlier
                newValue: newValue
            },
            success: function(response) {
                console.log(response);
                $('#editFieldModal').modal('hide');
                location.reload();
            },
        });
    });
});

document.getElementById('editpersonalfile').addEventListener('change', function() {
    const selectedField = this.value;

    // Get the containers for the new input types and the label
    const newStateDropdown = document.getElementById('newStateDropdown');
    const newRaceDropdown = document.getElementById('newRaceDropdown');
    const newReligionDropdown = document.getElementById('newReligionDropdown');
    const newMaritalDropdown = document.getElementById('newMaritalDropdown');
    const newGenderDropdown = document.getElementById('newGenderDropdown');
    const editNewValueInput = document.getElementById('editpersonalvalue');
    const editNewValueLabel = document.querySelector('label[for="editNewValueInput"]');

    // Hide all dropdowns initially
    newStateDropdown.style.display = 'none';
    newRaceDropdown.style.display = 'none';
    newReligionDropdown.style.display = 'none';
    newMaritalDropdown.style.display = 'none';
    newGenderDropdown.style.display = 'none';
    editNewValueInput.style.display = 'none';
    editNewValueLabel.style.display = 'none'; // Hide the label initially

    // Check the selectedField value and show the appropriate dropdown
    switch (selectedField) {
        case 'state':
            newStateDropdown.style.display = 'block';
            break;
        case 'race':
            newRaceDropdown.style.display = 'block';
            break;
        case 'religion':
            newReligionDropdown.style.display = 'block';
            break;
        case 'marital':
            newMaritalDropdown.style.display = 'block';
            break;
        case 'gender':
            newGenderDropdown.style.display = 'block';
            break;
        default:
            // For other fields, show the default input field and label
            editNewValueInput.style.display = 'block';
            editNewValueLabel.style.display = 'block';
            break;
    }
});

$(document).ready(function() {
    $('#savePersonalBtn').click(function() {
        var personalid = $('#editpersonalid').val();
        var personalField = $('#editpersonalfile').val();
        var personalValue = getPersonalValue();

        if (personalid && personalField && personalValue !== null) {
            $.ajax({
                url: 'update_personal.php',
                type: 'POST',
                data: {
                    editpersonalid: personalid,
                    editpersonalfile: personalField,
                    editpersonalvalue: personalValue
                },
                success: function(response) {
                    console.log(response);
                    alert("Data updated successfully");
                    $('#editDataModal').modal('hide');
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert("Error updating data");
                }
            });
        } else {
            alert("Please fill all required fields");
        }
    });

    function getPersonalValue() {
        var personalValue = null;
        $('select[id^="new"]').each(function() {
            if ($(this).is(':visible')) {
                personalValue = $(this).val();
                return false; // Exit loop after finding the visible dropdown
            }
        });
        if (personalValue === null) {
            personalValue = $('#editpersonalvalue').val();
        }
        return personalValue;
    }
});

//Employee Document
$(document).ready(function() {
    $('#editemploymentfile').change(function() {
        var selectedField = $(this).val();
        var $employmentValueField = $('#employmentValueField');

        $employmentValueField.empty();

        if (selectedField === 'employment_type') {
            $employmentValueField.html(`
                <label for="inputType" class="form-label">Employee Type:</label>
                <select class="form-select inputType">
                    <option value="" disabled selected hidden>Choose...</option>
                    <option value="Full-Time">Full-Time</option>
                    <option value="Monthly Part-Time">Monthly Part-Time</option>
                    <option value="Hourly Part-Time">Hourly Part-Time</option>
                    <option value="Intern">Intern</option>
                </select>
            `);
        } else if (selectedField === 'employment_status') {
            $employmentValueField.html(`
                <label for="inputStatus" class="form-label">Status:</label>
                <select class="form-select inputStatus">
                    <option value="" disabled selected hidden>Choose...</option>
                    <option value="Active-Confirmed">Active - Confirmed</option>
                    <option value="Active-Probation">Active - Probation</option>
                    <option value="Resigned">Resigned</option>
                    <option value="Terminated">Terminated</option>
                    <option value="Suspended">Suspended</option>
                    <option>Other</option>
                </select>
            `);
        } else if (selectedField === 'employment_position') {
            $employmentValueField.html(`
                <label for="inputPosition" class="form-label">Position:</label>
                <select class="form-select inputPosition">
                    <option value="" disabled selected hidden>Choose...</option>
                    <option value="General Manager">General Manager</option>
                    <option value="Operation Manager">Operation Manager</option>
                    <option value="Event Executive">Event Executive</option>
                    <option value="Event Decorator">Event Decorator</option>
                    <option value="Finance Manager">Finance Manager</option>
                    <option>Other</option>
                </select>
            `);
        } else if (selectedField === 'employment_start' || selectedField === 'employment_end') {
            $employmentValueField.html(`
                <label for="inputDate" class="form-label">Enter Date:</label>
                <input type="date" class="form-control inputDate">
            `);
        } else if(selectedField === 'employment_department'){
            $employmentValueField.html(`
                <label for="editemploymentvalue" class="form-label">Enter New Value:</label>
                <input type="text" class="form-control editemploymentvalue" placeholder="Enter New Value">
            `);
        }
    });

    $('#saveEmploymentBtn').click(function() {
        var employmentId = $('#editemploymentid').val();
        var employmentField = $('#editemploymentfile').val();
        var employmentValue;
    
        if (employmentId && employmentField) {
            if (employmentField === 'employment_type') {
                employmentValue = $('.inputType').val();
            } else if (employmentField === 'employment_status'){
                employmentValue = $('.inputStatus').val();
            }else if (employmentField === 'employment_position') {
                employmentValue = $('.inputPosition').val();
            }else if (employmentField === 'employment_start' || employmentField === 'employment_end') {
                employmentValue = $('.inputDate').val();
            } else if(employmentField === 'employment_department'){
                employmentValue = $('.editemploymentvalue').val();
            }
    
            $.ajax({
                url: 'update_employment.php',
                type: 'POST',
                data: {
                    editemploymentid: employmentId,
                    editemploymentfile: employmentField,
                    editemploymentvalue: employmentValue
                },
                success: function(response) {
                    console.log(response);
                    alert("Employment data updated successfully");
                    $('#editEmployment').modal('hide');
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert("Error updating employment data");
                }
            });
        } else {
            alert("Please fill all required fields");
        }
    });    
});


$(document).ready(function() {
    $('#editPayrollSelect').change(function() {
        var selectedField = $(this).val();
        var $payrollValueField = $('#payrollValueField');

        $payrollValueField.empty();

        if (selectedField === 'salary_type') {
            $payrollValueField.html(`
            <label for="inputS_Type" class="form-label">Salary Type</label>
                <select id="inputS_Type" class="form-select" name="inputS_Type">
                    <option value="" disabled selected hidden>Choose...</option>
                    <option value="Monthly">Monthly</option>
                    <option value="Daily">Daily</option>
                    <option value="Hourly">Hourly</option>
                </select>
            `);
        } else {
            $payrollValueField.html(`
                <label for="editpayrollvalue" class="form-label">Enter New Value:</label>
                <input type="text" class="form-control" id="editpayrollvalue" placeholder="Enter New Value">
            `);
        }
    });

    $('#savePayrollBtn').click(function() {
        var payrollId = $('#editPayrollId').val();
        var payrollField = $('#editPayrollSelect').val();
        var payrollValue;

        if (payrollId && payrollField) {
            if (payrollField === 'salary_type') {
                payrollValue = $('#inputS_Type').val();
            } else {
                payrollValue = $('#editpayrollvalue').val();
            }

            $.ajax({
                url: 'update_payroll.php',
                type: 'POST',
                data: {
                    editPayrollId: payrollId,
                    editFieldSelect: payrollField,
                    editNewValueInput: payrollValue
                },
                success: function(response) {
                    console.log(response);
                    alert("Payroll data updated successfully");
                    $('#editDataModal').modal('hide');
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert("Error updating payroll data");
                }
            });
        } else {
            alert("Please fill all required fields");
        }
    });    
});

$(document).ready(function() {
    $('#editdocumentfile').change(function() {
        var selectedField = $(this).val();
        var $valueField = $('#documentValueField');

        // Reset the value field
        $valueField.val("");

        if (selectedField === 'education_level') {
            // If "Education Level" is selected, provide a dropdown menu
            $valueField.html(`
            <label for="inputEducation" class="form-label">Education Level</label>
            <select id="editdocumentvalue" class="form-select" name="editdocumentvalue">
                <option value="" disabled selected hidden>Choose...</option>
                <option value="UPSR">UPSR</option>
                <option value="SRP/PT3/PMR">SRP/PT3/PMR</option>
                <option value="SPM">SPM</option>
                <option value="STPM/STPVM">STPM/STPVM</option>
                <option value="Diploma">Diploma</option>
                <option value="Bachelors Degree">Bachelors Degree</option>
                <option value="Masters Degree">Masters Degree</option>
                <option value="Doctorate Degree">Doctorate Degree</option>
                <option value="Post-Graduate">Post-Graduate</option>
                <option value="Professional Degree">Professional Degree</option>
                <option value="Certificate">Doctorate Certificate</option>
                <option value="Other">Other Qualification</option>
                <option value="No Qualification">No Qualification</option>
            </select>
            `);
        } else if (selectedField == 'ic_picture'){
            $valueField.html(`
                <input type="file" class="form-control" id="editdocumentvalue">
            `);
        }
    });

    $('#saveDocumentBtn').click(function() {
        var documentId = $('#editdocumentid').val();
        var documentField = $('#editdocumentfile').val();
        var documentValue;

        // Retrieve the selected value based on the type of input field
        if ($('#editdocumentvalue')[0].type === "file") {
            documentValue = $('#editdocumentvalue')[0].files[0];
        } else {
            documentValue = $('#editdocumentvalue').val();
        }

        // Proceed with saving only if all required fields are filled
        if (documentId && documentField && documentValue) {
            var formData = new FormData();
            formData.append('editdocumentid', documentId);
            formData.append('editdocumentfile', documentField);
            formData.append('editdocumentvalue', documentValue);

            $.ajax({
                url: 'update_document.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log(response);
                    alert("Document data updated successfully");
                    $('#editDocumentModal').modal('hide');
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert("Error updating document data");
                }
            });
        } else {
            alert("Please fill all required fields");
        }
    });    
});




//Leave Information
$(document).ready(function() {
    $('#saveLeaveBtn').click(function() {
        // Get the entered values
        var leaveid = $('#editleaveid').val();
        var leaveField = $('#editleavefile').val();
        var leaveValue = $('#editleavevalue').val();

        // Validate if all fields are filled
        if (leaveid && leaveField && leaveValue) {
            // Send an AJAX request to update the database
            $.ajax({
                url: 'update_leave.php',
                type: 'POST',
                data: {
                    editleaveid : leaveid,
                    editleavefile: leaveField,
                    editleavevalue: leaveValue
                },
                success: function(response) {
                    // Handle success response
                    console.log(response);
                    alert("Data updated successfully");
                    $('#editLeaveModal').modal('hide');
                    location.reload();
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    console.error(xhr.responseText);
                    alert("Error updating data");
                }
            });
        } else {
            // Alert if any field is empty
            alert("Please fill all fields");
        }
    });
});

