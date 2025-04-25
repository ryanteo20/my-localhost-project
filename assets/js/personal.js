$(document).ready(function() {
    // Handle click event for the "Edit Personal Information" button
    $('#editPersonalInfoBtn').click(function() {
        // Show the modal to select the field
        $('#selectFieldModal').modal('show');
    });

    // Handle click event for the "Edit" button in the select field modal
    $('#selectFieldBtn').click(function() {
        console.log("Button clicked!"); // Check if the click event is being triggered
        // Hide the select field modal and show the edit field modal
        $('#selectFieldModal').modal('hide');
        $('#editFieldModal').modal('show');
    });

    // Handle click event for the "Save Changes" button in the edit field modal
    $('#saveChangesBtn').click(function() {
        console.log("Save Changes button clicked");

        var personalId = $('#personalIdInput').val();
        var fieldToEdit = $('#fieldToEditSelect').val();
        var newValue = $('#newValueInput').val();

        console.log("Personal ID:", personalId);
        console.log("Field to Edit:", fieldToEdit);
        console.log("New Value:", newValue);

        // Simulate AJAX request
        $.ajax({
            url: 'update_personal_info.php',
            type: 'POST',
            data: {
                personalId: personalId,
                fieldToEdit: fieldToEdit,
                newValue: newValue
            },
            success: function(response) {
                console.log("AJAX Success:", response);
                $('#editFieldModal').modal('hide');
                location.reload(); // Reload the page to reflect changes
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", xhr.responseText);
            }
        });
    });
});
