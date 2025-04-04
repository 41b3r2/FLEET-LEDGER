document.addEventListener('DOMContentLoaded', function() {
    // Force hide the update modal when the page loads
    const updateModal = document.getElementById('updateTripModal');
    if (updateModal) {
        updateModal.style.display = 'none';
    }
    
    const mobileToggle = document.querySelector('.mobile-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay');
    const mainContent = document.querySelector('.main-content');
    
    mobileToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    });
    
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });
    
    function checkMobile() {
        if (window.innerWidth <= 768) {
            mainContent.style.marginLeft = '0';
            sidebar.classList.remove('active');
        } else {
            mainContent.style.marginLeft = '-20px';
        }
    }
    
    checkMobile();
    window.addEventListener('resize', checkMobile);
    
    // Add loading screen to metric sections
    document.querySelectorAll('.metric-section').forEach(section => {
        section.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link action
            let link = this.getAttribute('data-href'); // Get target link
            if (link) {
                // Show loading screen
                document.getElementById("loading-screen").style.display = "flex";
                
                // Navigate after 2 seconds
                setTimeout(() => {
                    window.location.href = link;
                }, 2000);
            }
        });
    });
    
    // Add loading screen to menu items
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            const dataHref = this.getAttribute('data-href');
            
            // Create ripple effect
            const ripple = document.createElement('div');
            ripple.classList.add('ripple');
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = `${size}px`;
            
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            
            this.appendChild(ripple);
            
            // Only proceed with navigation if href is not "#"
            if (href !== '#') {
                e.preventDefault();
                
                // Show loading screen
                document.getElementById("loading-screen").style.display = "flex";
                
                setTimeout(() => {
                    ripple.remove();
                    window.location.href = href;
                }, 2000);
            } else {
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            }
        });
    });
    
    // Add loading screen to logout
    const logoutLink = document.getElementById('logout-link');
    
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            const logoutButton = document.querySelector('.logout-section');
            logoutButton.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
            
            // Show loading screen
            document.getElementById("loading-screen").style.display = "flex";
            
            setTimeout(() => {
                logoutButton.style.backgroundColor = '';
                window.location.href = this.getAttribute('href');
            }, 2000);
        });
    }
    
    // Pie chart animation
    const pieSlice = document.querySelector('.pie-slice');
    setTimeout(() => {
        pieSlice.style.transition = 'transform 1s ease-out';
        pieSlice.style.transform = 'rotate(135deg)';
    }, 500);
    
    // Bar animation
    const bars = document.querySelectorAll('.bar');
    bars.forEach((bar, index) => {
        const heights = ['30%', '70%', '50%'];
        bar.style.height = '0';
        setTimeout(() => {
            bar.style.transition = 'height 1s ease-out';
            bar.style.height = heights[index % 3];
        }, 300 + (index * 100));
    });
    
    // Get modal elements - check if elements exist before attaching event listeners
    const modal = document.getElementById('addTripModal');
    
    // Only attach event listeners if elements exist
    if (modal) {
        const closeBtn = modal.querySelector('.close-modal');
        const cancelBtn = modal.querySelector('.cancel-btn');
        const form = document.getElementById('addTripForm');
        const addBtn = document.querySelector('.add-btn'); // Make sure this exists
        
        if (addBtn) {
            // Add trip button - show modal
            addBtn.addEventListener('click', function() {
                modal.style.display = 'flex';
            });
        }
        
        if (closeBtn) {
            // Close button - hide modal
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        }
        
        if (cancelBtn) {
            // Cancel button - hide modal
            cancelBtn.addEventListener('click', function() {
                modal.style.display = 'none';
                if (form) form.reset(); // Reset form fields
            });
        }
        
        // Close when clicking outside of modal content
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        if (form) {
            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show loading screen
                document.getElementById("loading-screen").style.display = "flex";
                
                // Collect form data and submit using fetch
                const formData = new FormData(form);
                
                fetch('add_trip.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById("loading-screen").style.display = "none";
                    
                    if (data.success) {
                        alert('Trip added successfully!');
                        modal.style.display = 'none';
                        form.reset();
                        // Reload the page to show the new data
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    document.getElementById("loading-screen").style.display = "none";
                    alert('An error occurred: ' + error.message);
                });
            });
        }
    }
 
    // Get update modal elements
    if (updateModal) {
        const updateForm = document.getElementById('updateTripForm');
        
        if (updateForm) {
            const updateCloseBtn = updateModal.querySelector('.close-modal');
            const updateCancelBtn = updateModal.querySelector('.cancel-btn');
            
        
            
            // Function to load dropdown options - modified to return a promise
            // Function to load dropdown options - modified to include trip ID
function loadDropdownOptions(tripId = 0) {
    // Show loading screen
    document.getElementById("loading-screen").style.display = "flex";
    
    // Create a promise that resolves when all data is loaded
    return Promise.all([
        // Fetch truck data
        fetch(`get_dropdown_data.php?type=trucks&trip_id=${tripId}`)
            .then(response => response.json())
            .then(data => {
                const truckDropdown = document.getElementById('update_truck_id');
                truckDropdown.innerHTML = '<option value="">Select Truck</option>';
                
                if (data.success) {
                    data.data.forEach(truck => {
                        truckDropdown.innerHTML += `<option value="${truck.truck_id}">${truck.model} - ${truck.truck_plate} (${truck.truck_type})</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error loading truck data:', error);
            }),
            
        // Fetch driver data
        fetch(`get_dropdown_data.php?type=drivers&trip_id=${tripId}`)
            .then(response => response.json())
            .then(data => {
                const driverDropdown = document.getElementById('update_driver');
                driverDropdown.innerHTML = '<option value="">Select Driver</option>';
                
                if (data.success) {
                    data.data.forEach(driver => {
                        driverDropdown.innerHTML += `<option value="${driver.driver_id}">${driver.fullname}</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error loading driver data:', error);
            }),
            
        // Fetch helper1 data
        fetch(`get_dropdown_data.php?type=helper1&trip_id=${tripId}`)
            .then(response => response.json())
            .then(data => {
                const helper1Dropdown = document.getElementById('update_helper1');
                helper1Dropdown.innerHTML = '<option value="">Select Helper 1</option>';
                
                if (data.success) {
                    data.data.forEach(helper => {
                        helper1Dropdown.innerHTML += `<option value="${helper.helper1_id}">${helper.fullname}</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error loading helper1 data:', error);
            }),
            
        // Fetch helper2 data
        fetch(`get_dropdown_data.php?type=helper2&trip_id=${tripId}`)
            .then(response => response.json())
            .then(data => {
                const helper2Dropdown = document.getElementById('update_helper2');
                helper2Dropdown.innerHTML = '<option value="">Select Helper 2</option>';
                
                if (data.success) {
                    data.data.forEach(helper => {
                        helper2Dropdown.innerHTML += `<option value="${helper.helper2_id}">${helper.fullname}</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error loading helper2 data:', error);
            })
    ]).then(() => {
        // All data is loaded, hide loading screen
        document.getElementById("loading-screen").style.display = "none";
    });
}

// Update the click event handler for update buttons
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('update-btn')) {
        const tripId = e.target.getAttribute('data-id');
        // Pass the trip ID to loadDropdownOptions, then fetch trip details
        loadDropdownOptions(tripId).then(() => {
            fetchTripDetails(tripId);
        });
    }
});
            
            // Function to fetch trip details for updating
            function fetchTripDetails(id) {
                // Show loading screen
                document.getElementById("loading-screen").style.display = "flex";
                
                fetch('update_trip.php?cs_id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById("loading-screen").style.display = "none";
                        
                        if (data.success) {
                            // Populate the form with trip details
                            document.getElementById('update_id').value = data.data.cs_id;
                            document.getElementById('update_topsheet').value = data.data.topsheet;
                            document.getElementById('update_waybill').value = data.data.waybill;
                            document.getElementById('update_date').value = data.data.date;
                            document.getElementById('update_status').value = data.data.status;
                            document.getElementById('update_delivery_type').value = data.data.delivery_type;
                            document.getElementById('update_amount').value = data.data.amount;
                            document.getElementById('update_source').value = data.data.source;
                            document.getElementById('update_pickup').value = data.data.pickup;
                            document.getElementById('update_dropoff').value = data.data.dropoff;
                            document.getElementById('update_rate').value = data.data.rate;
                            document.getElementById('update_call_time').value = data.data.call_time;
                            
                            // Set values for dropdowns directly
                            if (data.data.truck_id) {
                                document.getElementById('update_truck_id').value = data.data.truck_id;
                            }
                            
                            if (data.data.driver) {
                                document.getElementById('update_driver').value = data.data.driver;
                            }
                            
                            if (data.data.helper1) {
                                document.getElementById('update_helper1').value = data.data.helper1;
                            }
                            
                            if (data.data.helper2) {
                                document.getElementById('update_helper2').value = data.data.helper2;
                            }
                            
                            // Remove !important from display
                            updateModal.style.cssText = "display: flex !important";
                        }
                        else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        document.getElementById("loading-screen").style.display = "none";
                        alert('An error occurred: ' + error.message);
                    });
            }
            
            if (updateCloseBtn) {
                // Close update modal
                updateCloseBtn.addEventListener('click', function() {
                    updateModal.style.cssText = "display: none !important";
                });
            }
            
            if (updateCancelBtn) {
                // Cancel button - hide update modal
                updateCancelBtn.addEventListener('click', function() {
                    updateModal.style.cssText = "display: none !important";
                    updateForm.reset();
                });
            }
            
            // Close update modal when clicking outside of modal content
            window.addEventListener('click', function(event) {
                if (event.target === updateModal) {
                    updateModal.style.cssText = "display: none !important";
                }
            });
            
            // Update form submission
            updateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show loading screen
                document.getElementById("loading-screen").style.display = "flex";
                
                // Collect form data
                const formData = new FormData(updateForm);
                
                fetch('update_trip.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById("loading-screen").style.display = "none";
                    
                    if (data.success) {
                        alert('Trip updated successfully!');
                        updateModal.style.cssText = "display: none !important";
                        updateForm.reset();
                        // Reload the page to show the updated data
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    document.getElementById("loading-screen").style.display = "none";
                    alert('An error occurred: ' + error.message);
                });
            });
        }
    }
});