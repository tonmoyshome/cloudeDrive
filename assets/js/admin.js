// Admin Panel JavaScript

class AdminPanel {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Add user form
        document.getElementById('addUserForm').addEventListener('submit', (e) => this.handleAddUser(e));
        
        // Edit user form
        const editUserForm = document.getElementById('editUserForm');
        if (editUserForm) {
            editUserForm.addEventListener('submit', (e) => this.handleEditUser(e));
        }
    }

    async handleAddUser(e) {
        e.preventDefault();
        
        const username = document.getElementById('newUsername').value;
        const email = document.getElementById('newEmail').value;
        const password = document.getElementById('newPassword').value;
        const role = document.getElementById('newRole').value;
        const storageLimit = parseFloat(document.getElementById('newStorageLimit').value) * 1024 * 1024 * 1024; // Convert GB to bytes

        try {
            const response = await fetch('api/admin/add_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    email: email,
                    password: password,
                    role: role,
                    storage_limit: storageLimit
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showAlert(result.message, 'success');
                this.closeAddUserModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            console.error('Add user error:', error);
            this.showAlert('Unable to create user account - please try again', 'error');
        }
    }

    async handleEditUser(e) {
        e.preventDefault();
        
        const userId = document.getElementById('editUserIdFull').value;
        const username = document.getElementById('editUsername').value;
        const email = document.getElementById('editEmail').value;
        const role = document.getElementById('editRole').value;
        const storageLimit = parseFloat(document.getElementById('editStorageLimitUser').value) * 1024 * 1024 * 1024; // Convert GB to bytes
        const password = document.getElementById('editPassword').value;
        const confirmPassword = document.getElementById('confirmEditPassword').value;
        
        // Validate password confirmation if password is provided
        if (password !== confirmPassword) {
            this.showAlert('New password and confirmation do not match', 'error');
            return;
        }
        
        const requestData = {
            user_id: parseInt(userId),
            username: username,
            email: email,
            role: role,
            storage_limit: storageLimit
        };
        
        // Only include password if it's provided
        if (password && password.trim() !== '') {
            requestData.password = password;
            requestData.confirm_password = confirmPassword;
        }
        
        try {
            const response = await fetch('api/admin/edit_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            const result = await response.json();
            
            if (result.success) {
                this.showAlert(result.message, 'success');
                this.closeEditUserModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            console.error('Edit user error:', error);
            this.showAlert('Unable to update user information - please try again', 'error');
        }
    }

    showAddUserModal() {
        const modal = document.getElementById('addUserModal');
        modal.classList.remove('d-none');
        document.body.style.overflow = 'hidden';
    }

    closeAddUserModal() {
        const modal = document.getElementById('addUserModal');
        modal.classList.add('d-none');
        document.body.style.overflow = '';
        document.getElementById('addUserForm').reset();
    }

    async toggleUserRole(userId, currentRole) {
        const newRole = currentRole === 'admin' ? 'user' : 'admin';
        
        if (!confirm(`Are you sure you want to change this user's role to ${newRole}?`)) {
            return;
        }

        try {
            const response = await fetch('api/admin/update_user_role.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId,
                    role: newRole
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showAlert(result.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            console.error('Update role error:', error);
            this.showAlert('Unable to update user role - please try again', 'error');
        }
    }

    async deleteUser(userId) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch('api/admin/delete_user.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showAlert(result.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            console.error('Delete user error:', error);
            this.showAlert('Unable to delete user - please try again', 'error');
        }
    }

    showAlert(message, type) {
        // Create alert using the same system as main.js
        const alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) return;

        const alertId = 'alert-' + Date.now();
        const alertDiv = document.createElement('div');
        alertDiv.id = alertId;
        alertDiv.className = `alert-professional alert-${type}`;
        alertDiv.innerHTML = `
            <div class="alert-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="alert-close" onclick="removeAlert('${alertId}')">
                <i class="fas fa-times"></i>
            </button>
        `;

        alertContainer.appendChild(alertDiv);

        // Auto remove after 5 seconds
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.classList.add('hiding');
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            }
        }, 5000);
    }

    showEditUserModal() {
        const modal = document.getElementById('editUserModal');
        modal.classList.remove('d-none');
        document.body.style.overflow = 'hidden';
    }

    closeEditUserModal() {
        const modal = document.getElementById('editUserModal');
        modal.classList.add('d-none');
        document.body.style.overflow = '';
        
        // Reset form
        document.getElementById('editUserForm').reset();
        document.getElementById('editPassword').value = '';
        document.getElementById('confirmEditPassword').value = '';
    }
}

// Global functions for onclick handlers
function showAddUserModal() {
    adminPanel.showAddUserModal();
}

function closeAddUserModal() {
    adminPanel.closeAddUserModal();
}

function editUser(userId, username, email, role, storageLimit) {
    // Populate the edit user form
    document.getElementById('editUserIdFull').value = userId;
    document.getElementById('editUsername').value = username;
    document.getElementById('editEmail').value = email;
    document.getElementById('editRole').value = role;
    document.getElementById('editStorageLimitUser').value = (storageLimit / (1024 * 1024 * 1024)).toFixed(1); // Convert bytes to GB
    
    // Clear password fields
    document.getElementById('editPassword').value = '';
    document.getElementById('confirmEditPassword').value = '';
    
    adminPanel.showEditUserModal();
}

function closeEditUserModal() {
    adminPanel.closeEditUserModal();
}

function toggleUserRole(userId, currentRole) {
    adminPanel.toggleUserRole(userId, currentRole);
}

function deleteUser(userId) {
    adminPanel.deleteUser(userId);
}

function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    menu.classList.toggle('d-none');
}

function removeAlert(alertId) {
    const alert = document.getElementById(alertId);
    if (alert) {
        alert.classList.add('hiding');
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 300);
    }
}

// Initialize the admin panel
const adminPanel = new AdminPanel();

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-professional')) {
        e.target.classList.add('d-none');
        document.body.style.overflow = '';
    }
});

// Close user menu when clicking outside
document.addEventListener('click', function(e) {
    const userMenu = document.getElementById('userMenu');
    const userProfile = document.querySelector('.user-profile');
    
    if (userMenu && !userMenu.contains(e.target) && !userProfile.contains(e.target)) {
        userMenu.classList.add('d-none');
    }
});
