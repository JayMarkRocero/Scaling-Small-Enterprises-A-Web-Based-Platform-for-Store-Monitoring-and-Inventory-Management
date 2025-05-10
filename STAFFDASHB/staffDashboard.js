document.addEventListener('DOMContentLoaded', () => {
        // Check for low stock products
        const lowStockProducts = document.querySelectorAll('tr.table-warning');
        if (lowStockProducts.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Low Stock Alert',
                html: `There are ${lowStockProducts.length} products with low stock levels. Please inform the administrator to restock these items.`,
                confirmButtonText: 'View Products'
            });
        }
    });

    document.getElementById('profilePicUpload').addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const formData = new FormData();
            formData.append('profile_pic', this.files[0]);

            // Show loading state
            const uploadButton = this.previousElementSibling;
            const originalHtml = uploadButton.innerHTML;
            uploadButton.innerHTML = '<i class="bi bi-arrow-repeat spin"></i>';

            fetch('upload_profile_pic.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Upload response:', data); // Debug log
                
                if (data.success) {
                    // Show success message with debug info
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        html: `${data.message}<br><br>Debug info:<br>${JSON.stringify(data.debug, null, 2)}`,
                        timer: 3000,
                        showConfirmButton: true
                    }).then(() => {
                        // Reload the page to show the new profile picture
                        location.reload();
                    });
                } else {
                    // Show error message with debug info
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: `${data.message}<br><br>Debug info:<br>${JSON.stringify(data.debug, null, 2)}`
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while uploading the profile picture.'
                });
            })
            .finally(() => {
                // Reset button state
                uploadButton.innerHTML = originalHtml;
                // Reset file input
                this.value = '';
            });
        }
    });

    // Add spin animation for loading state
    const style = document.createElement('style');
    style.textContent = `
        .spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);