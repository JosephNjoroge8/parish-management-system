document.addEventListener('DOMContentLoaded', function() {
    const churchGroupSelect = document.getElementById('church_group');
    
    if (churchGroupSelect) {
        churchGroupSelect.addEventListener('change', function() {
            updateFormFields(this.value);
        });
        
        // Initial load of form fields based on selected church group
        if (churchGroupSelect.value) {
            updateFormFields(churchGroupSelect.value);
        }
    }
    
    function updateFormFields(churchGroup) {
        fetch(`/members/form-fields?church_group=${churchGroup}`)
            .then(response => response.json())
            .then(data => {
                const visibleFields = data.visible_fields;
                const requiredFields = data.required_fields;
                
                // Hide all optional fields first
                const formGroups = document.querySelectorAll('.form-group');
                formGroups.forEach(group => {
                    const fieldName = group.dataset.field;
                    if (fieldName && fieldName !== 'local_church' && fieldName !== 'church_group') {
                        group.style.display = 'none';
                    }
                });
                
                // Show only the fields that should be visible for this church group
                visibleFields.forEach(field => {
                    const fieldGroup = document.querySelector(`.form-group[data-field="${field}"]`);
                    if (fieldGroup) {
                        fieldGroup.style.display = 'block';
                        
                        // Set required attribute based on whether field is required
                        const input = fieldGroup.querySelector('input, select, textarea');
                        if (input) {
                            if (requiredFields.includes(field)) {
                                input.setAttribute('required', 'required');
                                const label = fieldGroup.querySelector('label');
                                if (label) {
                                    if (!label.innerHTML.includes('*')) {
                                        label.innerHTML += ' *';
                                    }
                                }
                            } else {
                                input.removeAttribute('required');
                                const label = fieldGroup.querySelector('label');
                                if (label) {
                                    label.innerHTML = label.innerHTML.replace(' *', '');
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching form fields:', error));
    }
});