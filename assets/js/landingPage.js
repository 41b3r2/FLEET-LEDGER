document.addEventListener('DOMContentLoaded', function() {
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
                        }, 1000);
                    }
                });
            });
            
            // Add loading screen to menu items (only non-disabled ones)
            const menuItems = document.querySelectorAll('.menu-item:not(.disabled)');
            
            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    
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
                        }, 1000);
                    } else {
                        setTimeout(() => {
                            ripple.remove();
                        }, 500);
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
                    }, 1000);
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
        });