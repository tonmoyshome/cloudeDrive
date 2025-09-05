// Professional Modal Management
class ModalManager {
    constructor() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Close modals when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-professional')) {
                this.closeAllModals();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
    }

    closeAllModals() {
        const modals = document.querySelectorAll('.modal-professional');
        modals.forEach(modal => {
            modal.classList.add('d-none');
        });
    }
}

// Upload Modal Functions
function openUploadModal() {
    const modal = document.getElementById('uploadModal');
    modal.classList.remove('d-none');
    
    // Reset form
    document.getElementById('fileInput').value = '';
    document.getElementById('folderInput').value = '';
    document.getElementById('folderUpload').checked = false;
    document.getElementById('fileList').innerHTML = '';
    document.getElementById('fileList').classList.add('d-none');
    document.getElementById('uploadProgress').classList.add('d-none');
}

function closeUploadModal() {
    const modal = document.getElementById('uploadModal');
    modal.classList.add('d-none');
    
    // Reset form
    document.getElementById('fileInput').value = '';
    document.getElementById('folderInput').value = '';
    document.getElementById('folderUpload').checked = false;
    document.getElementById('fileList').innerHTML = '';
    document.getElementById('fileList').classList.add('d-none');
    document.getElementById('uploadProgress').classList.add('d-none');
}

// Folder Modal Functions
function openFolderModal() {
    const modal = document.getElementById('folderModal');
    modal.classList.remove('d-none');
    
    // Reset form
    document.getElementById('folderForm').reset();
    document.getElementById('folderName').focus();
}

function closeFolderModal() {
    const modal = document.getElementById('folderModal');
    modal.classList.add('d-none');
    
    // Reset form
    document.getElementById('folderForm').reset();
}

// Share Modal Functions
function openShareModal() {
    const modal = document.getElementById('shareModal');
    if (modal) {
        modal.classList.remove('d-none');
        
        // Load users
        loadUsersForSharing();
    }
}

function closeShareModal() {
    const modal = document.getElementById('shareModal');
    if (modal) {
        modal.classList.add('d-none');
        
        // Reset form
        document.getElementById('shareFolder').value = '';
        document.getElementById('shareUsers').innerHTML = '';
        document.getElementById('canView').checked = true;
        document.getElementById('canDownload').checked = true;
        document.getElementById('canUpload').checked = false;
        document.getElementById('canDelete').checked = false;
    }
}

function loadUsersForSharing() {
    fetch('api/get_users.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const userSelect = document.getElementById('shareUsers');
                userSelect.innerHTML = '';
                data.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = `${user.username} (${user.email})`;
                    userSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
            showAlert('Unable to load user list - please try again', 'danger');
        });
}

function handleShareFolder() {
    const folderId = document.getElementById('shareFolder').value;
    const selectedUsers = Array.from(document.getElementById('shareUsers').selectedOptions).map(option => option.value);
    
    if (!folderId) {
        showAlert('Please select a folder to share', 'warning');
        return;
    }
    
    if (selectedUsers.length === 0) {
        showAlert('Please select at least one user to share with', 'warning');
        return;
    }
    
    const permissions = {
        can_view: document.getElementById('canView').checked,
        can_download: document.getElementById('canDownload').checked,
        can_upload: document.getElementById('canUpload').checked,
        can_delete: document.getElementById('canDelete').checked
    };
    
    fetch('api/share_folder.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            folder_id: parseInt(folderId),
            user_ids: selectedUsers.map(id => parseInt(id)),
            permissions: permissions
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert(result.message || 'Folder shared successfully', 'success');
            closeShareModal();
        } else {
            showAlert(result.message || 'Unable to share folder - please try again', 'danger');
        }
    })
    .catch(error => {
        console.error('Share folder error:', error);
        showAlert('Unable to share folder - please try again', 'danger');
    });
}

// Alert System
function showAlert(message, type = 'info') {
    const container = document.getElementById('alertContainer');
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <span>${message}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Initialize modal manager
document.addEventListener('DOMContentLoaded', function() {
    new ModalManager();
});
