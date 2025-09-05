// CloudeDrive Professional File Manager
class CloudeDrive {
    constructor() {
        this.currentFolderId = null;
        this.selectedFiles = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupDragAndDrop();
        this.setupGlobalDragAndDrop();
    }

    setupEventListeners() {
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
        }

        // View toggle
        this.setupViewToggle();

        // Upload functionality
        const uploadBtn = document.getElementById('uploadBtn');
        if (uploadBtn) {
            uploadBtn.addEventListener('click', () => this.handleUpload());
        }

        // File input
        const fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => this.handleFileSelection(e));
        }

        // Folder input
        const folderInput = document.getElementById('folderInput');
        if (folderInput) {
            folderInput.addEventListener('change', (e) => this.handleFileSelection(e));
        }

        // Folder upload toggle
        const folderUpload = document.getElementById('folderUpload');
        if (folderUpload) {
            folderUpload.addEventListener('change', (e) => this.toggleFolderUpload(e));
        }

        // Folder form
        const folderForm = document.getElementById('folderForm');
        if (folderForm) {
            folderForm.addEventListener('submit', (e) => this.handleCreateFolder(e));
        }
    }

    setupViewToggle() {
        const listViewBtn = document.getElementById('listView');
        const gridViewBtn = document.getElementById('gridView');
        
        if (listViewBtn && gridViewBtn) {
            // Load saved preference
            const savedView = localStorage.getItem('cloudeDriveView') || 'list';
            if (savedView === 'grid') {
                gridViewBtn.checked = true;
                this.setGridView();
            } else {
                listViewBtn.checked = true;
                this.setListView();
            }
            
            listViewBtn.addEventListener('change', () => {
                if (listViewBtn.checked) {
                    this.setListView();
                    localStorage.setItem('cloudeDriveView', 'list');
                }
            });
            
            gridViewBtn.addEventListener('change', () => {
                if (gridViewBtn.checked) {
                    this.setGridView();
                    localStorage.setItem('cloudeDriveView', 'grid');
                }
            });
        }
    }

    setListView() {
        // Show table views, hide grid views
        const foldersTable = document.getElementById('foldersTableView');
        const foldersGrid = document.getElementById('foldersGridView');
        const filesTable = document.getElementById('filesTableView');
        const filesGrid = document.getElementById('filesGridView');
        
        if (foldersTable) {
            foldersTable.classList.remove('d-none');
        }
        if (foldersGrid) {
            foldersGrid.classList.add('d-none');
        }
        if (filesTable) {
            filesTable.classList.remove('d-none');
        }
        if (filesGrid) {
            filesGrid.classList.add('d-none');
        }
    }
    
    setGridView() {
        // Show grid views, hide table views
        const foldersTable = document.getElementById('foldersTableView');
        const foldersGrid = document.getElementById('foldersGridView');
        const filesTable = document.getElementById('filesTableView');
        const filesGrid = document.getElementById('filesGridView');
        
        if (foldersTable) {
            foldersTable.classList.add('d-none');
        }
        if (foldersGrid) {
            foldersGrid.classList.remove('d-none');
        }
        if (filesTable) {
            filesTable.classList.add('d-none');
        }
        if (filesGrid) {
            filesGrid.classList.remove('d-none');
        }
    }

    handleSearch(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        
        // Search in tables
        const tableRows = document.querySelectorAll('#foldersTableView tbody tr, #filesTableView tbody tr');
        tableRows.forEach(row => {
            const name = row.querySelector('td:first-child')?.textContent.toLowerCase() || '';
            row.style.display = (term === '' || name.includes(term)) ? '' : 'none';
        });
        
        // Search in grids
        const gridItems = document.querySelectorAll('#foldersGridView .grid-item, #filesGridView .grid-item');
        gridItems.forEach(item => {
            const name = item.querySelector('.grid-item-title')?.textContent.toLowerCase() || '';
            item.style.display = (term === '' || name.includes(term)) ? '' : 'none';
        });
    }

    setupDragAndDrop() {
        const dropZone = document.getElementById('dropZone');
        if (!dropZone) return;

        dropZone.addEventListener('click', () => {
            const folderUpload = document.getElementById('folderUpload');
            if (folderUpload && folderUpload.checked) {
                document.getElementById('folderInput').click();
            } else {
                document.getElementById('fileInput').click();
            }
        });

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            if (!dropZone.contains(e.relatedTarget)) {
                dropZone.classList.remove('dragover');
            }
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            
            const files = Array.from(e.dataTransfer.files);
            this.handleDroppedFiles(files);
        });
    }

    setupGlobalDragAndDrop() {
        let dragCounter = 0;
        
        document.addEventListener('dragenter', (e) => {
            e.preventDefault();
            dragCounter++;
            
            // Show upload modal when dragging files over the page
            if (e.dataTransfer.types.includes('Files')) {
                openUploadModal();
            }
        });

        document.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dragCounter--;
        });

        document.addEventListener('dragover', (e) => {
            e.preventDefault();
        });

        document.addEventListener('drop', (e) => {
            e.preventDefault();
            dragCounter = 0;
            
            if (e.target.closest('#dropZone')) {
                return; // Let the dropZone handle it
            }
            
            const files = Array.from(e.dataTransfer.files);
            if (files.length > 0) {
                openUploadModal();
                setTimeout(() => {
                    this.handleDroppedFiles(files);
                }, 100);
            }
        });
    }

    handleDroppedFiles(files) {
        this.selectedFiles = files;
        this.displaySelectedFiles(files);
        
        // Auto-populate the file input
        const fileInput = document.getElementById('fileInput');
        if (fileInput && files.length > 0) {
            // Create a new FileList-like object
            const dt = new DataTransfer();
            files.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        }
    }

    handleFileSelection(e) {
        const files = Array.from(e.target.files);
        this.selectedFiles = files;
        this.displaySelectedFiles(files);
        
        // Check if this is folder input and has webkitRelativePath
        if (e.target.id === 'folderInput' && files.length > 0 && files[0].webkitRelativePath) {
            // Auto-check folder upload if folder input was used
            const folderUpload = document.getElementById('folderUpload');
            if (folderUpload) {
                folderUpload.checked = true;
            }
            
            // Update drop zone text
            const dropZoneText = document.querySelector('.drop-zone-text');
            if (dropZoneText) {
                dropZoneText.textContent = 'Folder selected for upload';
            }
        }
    }

    displaySelectedFiles(files) {
        const fileList = document.getElementById('fileList');
        if (!fileList || files.length === 0) return;

        fileList.classList.remove('d-none');
        fileList.innerHTML = '';
        
        files.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            // Show folder path for folder uploads
            let fileName = file.name;
            let pathInfo = '';
            if (file.webkitRelativePath) {
                fileName = file.webkitRelativePath;
                const pathParts = file.webkitRelativePath.split('/');
                if (pathParts.length > 1) {
                    pathInfo = `<small class="text-muted">in: ${pathParts.slice(0, -1).join('/')}</small>`;
                }
            }
            
            fileItem.innerHTML = `
                <div class="file-item-info">
                    <i class="${this.getFileIcon(file.name)}"></i>
                    <div>
                        <div class="file-item-name">${fileName}</div>
                        ${pathInfo}
                        <div class="file-item-size">${this.formatFileSize(file.size)}</div>
                    </div>
                </div>
                <button class="btn-professional btn-danger btn-sm" onclick="window.app.removeFile(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            fileList.appendChild(fileItem);
        });
    }

    removeFile(index) {
        this.selectedFiles.splice(index, 1);
        this.displaySelectedFiles(this.selectedFiles);
        
        if (this.selectedFiles.length === 0) {
            document.getElementById('fileList').classList.add('d-none');
        }
    }

    toggleFolderUpload(e) {
        const fileInput = document.getElementById('fileInput');
        const folderInput = document.getElementById('folderInput');
        const dropZoneText = document.querySelector('.drop-zone-text');
        
        if (e.target.checked) {
            fileInput.style.display = 'none';
            folderInput.style.display = 'block';
            if (dropZoneText) {
                dropZoneText.textContent = 'Or click to browse and select a folder';
            }
        } else {
            fileInput.style.display = 'block';
            folderInput.style.display = 'none';
            if (dropZoneText) {
                dropZoneText.textContent = 'Or click to browse and select files';
            }
        }
        
        // Clear selections
        this.selectedFiles = [];
        document.getElementById('fileList').classList.add('d-none');
    }

    async handleUpload() {
        const folderUpload = document.getElementById('folderUpload').checked;
        let files = [];
        
        if (this.selectedFiles.length > 0) {
            files = this.selectedFiles;
        } else {
            const input = folderUpload ? document.getElementById('folderInput') : document.getElementById('fileInput');
            files = Array.from(input.files);
        }
        
        if (files.length === 0) {
            showAlert('Please select files to upload before proceeding', 'warning');
            return;
        }

        const uploadProgress = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('progressBar');
        const uploadStatus = document.getElementById('uploadStatus');
        
        uploadProgress.classList.remove('d-none');
        
        let uploadedCount = 0;
        const totalFiles = files.length;
        
        uploadStatus.textContent = `Processing ${totalFiles} file(s)...`;
        
        if (folderUpload) {
            // Handle folder upload with structure
            await this.handleFolderUpload(files, uploadedCount, totalFiles, progressBar, uploadStatus);
        } else {
            // Handle regular file upload
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                try {
                    await this.uploadFile(file);
                    uploadedCount++;
                    
                    const progress = (uploadedCount / totalFiles) * 100;
                    progressBar.style.width = `${progress}%`;
                    uploadStatus.textContent = `Uploaded ${uploadedCount} of ${totalFiles} files`;
                    
                } catch (error) {
                    console.error('Upload error:', error);
                    showAlert(`Upload failed for ${file.name}: ${error.message}`, 'danger');
                }
            }
            
            if (uploadedCount === totalFiles) {
                showAlert(`Successfully uploaded ${uploadedCount} file${uploadedCount > 1 ? 's' : ''}!`, 'success');
                setTimeout(() => {
                    closeUploadModal();
                    location.reload();
                }, 2000);
            } else {
                showAlert(`Uploaded ${uploadedCount} of ${totalFiles} files - some uploads failed`, 'warning');
            }
        }
    }

    async handleFolderUpload(files, uploadedCount, totalFiles, progressBar, uploadStatus) {
        // Create folder structure map
        const folderStructure = new Map();
        const folderPaths = new Set();
        
        // Extract all unique folder paths
        files.forEach(file => {
            if (file.webkitRelativePath) {
                const pathParts = file.webkitRelativePath.split('/');
                let currentPath = '';
                
                // Build all nested folder paths
                for (let i = 0; i < pathParts.length - 1; i++) {
                    currentPath += (currentPath ? '/' : '') + pathParts[i];
                    folderPaths.add(currentPath);
                }
            }
        });
        
        // Sort paths by depth (shallow first)
        const sortedPaths = Array.from(folderPaths).sort((a, b) => {
            return a.split('/').length - b.split('/').length;
        });
        
        // Create folders in order
        uploadStatus.textContent = 'Creating folder structure...';
        
        for (const folderPath of sortedPaths) {
            try {
                const folderId = await this.createFolderStructure(folderPath, folderStructure);
                folderStructure.set(folderPath, folderId);
                console.log(`Successfully created folder: ${folderPath} with ID: ${folderId}`);
            } catch (error) {
                console.error('Failed to create folder:', folderPath, error);
                showAlert(`Unable to create folder "${folderPath}": ${error.message}`, 'danger');
                return; // Stop the process if folder creation fails
            }
        }
        
        // Upload files to their respective folders
        uploadStatus.textContent = `Uploading ${totalFiles} file(s)...`;
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            try {
                let targetFolderId = '';
                
                if (file.webkitRelativePath) {
                    const pathParts = file.webkitRelativePath.split('/');
                    if (pathParts.length > 1) {
                        const folderPath = pathParts.slice(0, -1).join('/');
                        targetFolderId = folderStructure.get(folderPath) || '';
                    }
                }
                
                await this.uploadFile(file, targetFolderId);
                uploadedCount++;
                
                const progress = (uploadedCount / totalFiles) * 100;
                progressBar.style.width = `${progress}%`;
                uploadStatus.textContent = `Uploaded ${uploadedCount} of ${totalFiles} files`;
                
            } catch (error) {
                console.error('Upload error:', error);
                showAlert(`Upload failed for ${file.name}: ${error.message}`, 'danger');
            }
        }
        
        if (uploadedCount === totalFiles) {
            showAlert(`Successfully uploaded ${uploadedCount} file${uploadedCount > 1 ? 's' : ''} with folder structure!`, 'success');
            setTimeout(() => {
                closeUploadModal();
                location.reload();
            }, 2000);
        } else {
            showAlert(`Uploaded ${uploadedCount} of ${totalFiles} files - some uploads failed`, 'warning');
        }
    }

    async createFolderStructure(folderPath, folderStructure) {
        const pathParts = folderPath.split('/');
        const folderName = pathParts[pathParts.length - 1];
        
        let parentId = '';
        
        // Get parent folder ID if this is a nested folder
        if (pathParts.length > 1) {
            const parentPath = pathParts.slice(0, -1).join('/');
            parentId = folderStructure.get(parentPath) || '';
        } else {
            // For root level folders, use current folder context
            const urlParams = new URLSearchParams(window.location.search);
            parentId = urlParams.get('folder') || '';
        }
        
        console.log(`Creating folder: ${folderName} in parent: ${parentId || 'root'}`);
        
        try {
            const response = await fetch('api/create_folder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: folderName,
                    parent_id: parentId || null
                })
            });
            
            console.log(`Response status: ${response.status}`);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error(`HTTP Error ${response.status}: ${errorText}`);
                throw new Error(`Failed to create folder: ${folderName} (HTTP ${response.status}: ${errorText})`);
            }
            
            const result = await response.json();
            console.log(`Folder creation result:`, result);
            
            if (!result.success) {
                console.error(`Folder creation failed:`, result);
                throw new Error(result.message || 'Unable to create folder - please try again');
            }
            
            return result.folder_id;
        } catch (error) {
            console.error(`Error creating folder ${folderName}:`, error);
            throw error;
        }
    }

    async uploadFile(file, targetFolderId = null) {
        const formData = new FormData();
        formData.append('file', file);
        
        // Use provided target folder ID or get current folder ID from URL
        let folderId = targetFolderId;
        if (folderId === null) {
            const urlParams = new URLSearchParams(window.location.search);
            folderId = urlParams.get('folder') || '';
        }
        
        formData.append('folder_id', folderId);
        
        const response = await fetch('api/upload.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Upload failed - please try again');
        }
        
        return result;
    }

    async handleCreateFolder(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const folderName = formData.get('name');
        
        if (!folderName || folderName.trim() === '') {
            showAlert('Please enter a valid folder name', 'warning');
            return;
        }
        
        // Get current folder ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const currentFolderId = urlParams.get('folder') || null;
        
        try {
            const response = await fetch('api/create_folder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: folderName.trim(),
                    parent_id: currentFolderId
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                showAlert(result.message || 'Folder created successfully', 'success');
                closeFolderModal();
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showAlert(result.message || 'Unable to create folder - please try again', 'danger');
            }
        } catch (error) {
            console.error('Create folder error:', error);
            showAlert('Unable to create folder: ' + error.message, 'danger');
        }
    }

    getFileIcon(filename) {
        const extension = filename.split('.').pop().toLowerCase();
        const icons = {
            'pdf': 'fas fa-file-pdf',
            'doc': 'fas fa-file-word',
            'docx': 'fas fa-file-word',
            'xls': 'fas fa-file-excel',
            'xlsx': 'fas fa-file-excel',
            'ppt': 'fas fa-file-powerpoint',
            'pptx': 'fas fa-file-powerpoint',
            'jpg': 'fas fa-file-image',
            'jpeg': 'fas fa-file-image',
            'png': 'fas fa-file-image',
            'gif': 'fas fa-file-image',
            'mp4': 'fas fa-file-video',
            'avi': 'fas fa-file-video',
            'mp3': 'fas fa-file-audio',
            'wav': 'fas fa-file-audio',
            'zip': 'fas fa-file-archive',
            'rar': 'fas fa-file-archive',
            'txt': 'fas fa-file-alt',
            'html': 'fas fa-file-code',
            'css': 'fas fa-file-code',
            'js': 'fas fa-file-code',
            'php': 'fas fa-file-code',
            'py': 'fas fa-file-code'
        };
        
        return icons[extension] || 'fas fa-file';
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// Global functions for onclick handlers
async function downloadFile(fileId) {
    try {
        console.log('Starting download for file ID:', fileId);
        const response = await fetch(`api/download.php?file_id=${fileId}`);
        
        console.log('Response status:', response.status);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));
        
        if (response.ok) {
            const contentType = response.headers.get('Content-Type');
            
            // Check if response is JSON (error response)
            if (contentType && contentType.includes('application/json')) {
                const errorData = await response.json();
                console.error('JSON Error from server:', errorData);
                throw new Error(errorData.message || 'Download failed - please try again');
            }
            
            // Handle file download
            const blob = await response.blob();
            console.log('Blob created, size:', blob.size);
            
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            
            // Try to get filename from Content-Disposition header
            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = 'download';
            if (contentDisposition) {
                console.log('Content-Disposition:', contentDisposition);
                const filenameMatch = contentDisposition.match(/filename="(.+)"/);
                if (filenameMatch) {
                    filename = filenameMatch[1];
                } else {
                    // Try without quotes
                    const altMatch = contentDisposition.match(/filename=([^;]+)/);
                    if (altMatch) {
                        filename = altMatch[1].trim();
                    }
                }
            }
            
            console.log('Download filename:', filename);
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showAlert('File downloaded successfully', 'success');
        } else {
            // Handle HTTP error responses
            const contentType = response.headers.get('Content-Type');
            let errorMessage = 'Download failed - please try again';
            
            if (contentType && contentType.includes('application/json')) {
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorMessage;
                } catch (e) {
                    // Fallback to text if JSON parsing fails
                    errorMessage = await response.text() || errorMessage;
                }
            } else {
                errorMessage = await response.text() || errorMessage;
            }
            
            console.error('HTTP Error:', response.status, errorMessage);
            throw new Error(errorMessage);
        }
    } catch (error) {
        console.error('Download error:', error);
        showAlert(error.message || 'Unable to download file - please try again', 'danger');
    }
}

async function downloadFolder(folderId) {
    try {
        console.log('Starting folder download for folder ID:', folderId);
        showAlert('Preparing folder for download...', 'info');
        
        const response = await fetch(`api/download_folder.php?folder_id=${folderId}`);
        
        console.log('Response status:', response.status);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));
        
        if (response.ok) {
            const contentType = response.headers.get('Content-Type');
            
            // Check if response is JSON (error response)
            if (contentType && contentType.includes('application/json')) {
                const errorData = await response.json();
                console.error('JSON Error from server:', errorData);
                throw new Error(errorData.message || 'Folder download failed - please try again');
            }
            
            // Handle ZIP download
            const blob = await response.blob();
            console.log('ZIP blob created, size:', blob.size);
            
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            
            // Try to get filename from Content-Disposition header
            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = 'folder.zip';
            if (contentDisposition) {
                console.log('Content-Disposition:', contentDisposition);
                const filenameMatch = contentDisposition.match(/filename="(.+)"/);
                if (filenameMatch) {
                    filename = filenameMatch[1];
                } else {
                    // Try without quotes
                    const altMatch = contentDisposition.match(/filename=([^;]+)/);
                    if (altMatch) {
                        filename = altMatch[1].trim();
                    }
                }
            }
            
            console.log('Download filename:', filename);
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showAlert('Folder downloaded successfully as ZIP file', 'success');
        } else {
            // Handle HTTP error responses
            const contentType = response.headers.get('Content-Type');
            let errorMessage = 'Folder download failed';
            
            if (contentType && contentType.includes('application/json')) {
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorMessage;
                } catch (e) {
                    // Fallback to text if JSON parsing fails
                    errorMessage = await response.text() || errorMessage;
                }
            } else {
                errorMessage = await response.text() || errorMessage;
            }
            
            console.error('HTTP Error:', response.status, errorMessage);
            throw new Error(errorMessage);
        }
    } catch (error) {
        console.error('Folder download error:', error);
        showAlert(error.message || 'Unable to download folder - please try again', 'danger');
    }
}

async function deleteFile(fileId) {
    if (!confirm('Are you sure you want to delete this file?')) return;
    
    try {
        const response = await fetch('api/delete_file.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ file_id: fileId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('File deleted successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message || 'Unable to delete file - please try again', 'danger');
        }
    } catch (error) {
        console.error('Delete file error:', error);
        showAlert('Unable to delete file - please try again', 'danger');
    }
}

async function deleteFolder(folderId) {
    if (!confirm('Are you sure you want to delete this folder and all its contents?')) return;
    
    try {
        const response = await fetch('api/delete_folder.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ folder_id: folderId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('Folder deleted successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message || 'Unable to delete folder - please try again', 'danger');
        }
    } catch (error) {
        console.error('Delete folder error:', error);
        showAlert('Unable to delete folder - please try again', 'danger');
    }
}

function openShareModal() {
    console.log('Opening share modal...');
    try {
        const modal = document.getElementById('shareModal');
        console.log('shareModal element:', modal);
        if (modal) {
            modal.classList.remove('d-none');
            console.log('Modal should now be visible');
        } else {
            console.error('shareModal element not found!');
            return;
        }
        
        // Clear form
        const shareFolderSelect = document.getElementById('shareFolder');
        const shareUsers = document.getElementById('shareUsers');
        const currentSharesSection = document.getElementById('currentSharesSection');
        
        console.log('Form elements:', {
            shareFolder: shareFolderSelect,
            shareUsers: shareUsers,
            currentSharesSection: currentSharesSection
        });
        
        if (shareFolderSelect) shareFolderSelect.value = '';
        if (shareUsers) shareUsers.innerHTML = '';
        if (currentSharesSection) currentSharesSection.classList.add('d-none');
        
        // Reset permissions
        const canView = document.getElementById('canView');
        const canDownload = document.getElementById('canDownload');
        const canUpload = document.getElementById('canUpload');
        const canDelete = document.getElementById('canDelete');
        
        if (canView) canView.checked = true;
        if (canDownload) canDownload.checked = true;
        if (canUpload) canUpload.checked = false;
        if (canDelete) canDelete.checked = false;
    } catch (error) {
        console.error('Error in openShareModal:', error);
    }
}

function closeShareModal() {
    console.log('Closing share modal...');
    const modal = document.getElementById('shareModal');
    modal.classList.add('d-none');
}

function shareFolder(folderId) {
    alert('Share button clicked! Folder ID: ' + folderId);
    console.log('shareFolder called with ID:', folderId);
    try {
        openShareModal();
        
        // Set the folder ID in the select and load shares
        setTimeout(() => {
            const shareSelect = document.getElementById('shareFolder');
            console.log('shareSelect element:', shareSelect);
            if (shareSelect) {
                shareSelect.value = folderId;
                console.log('Set shareSelect value to:', folderId);
                loadCurrentShares(); // Load the shares for this folder
            } else {
                console.error('shareFolder select element not found!');
            }
        }, 100);
    } catch (error) {
        console.error('Error in shareFolder:', error);
        alert('Error in shareFolder: ' + error.message);
    }
}

function previewFile(fileId) {
    // For now, just download the file
    downloadFile(fileId);
}

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    window.app = new CloudeDrive();
});


// Improved Share Modal Functions
async function loadCurrentShares() {
    console.log('Loading current shares...');
    
    const folderId = document.getElementById("shareFolder").value;
    const currentSharesSection = document.getElementById("currentSharesSection");
    const currentSharesList = document.getElementById("currentSharesList");
    const shareUsers = document.getElementById("shareUsers");
    
    console.log('Folder ID:', folderId);
    
    if (!folderId) {
        currentSharesSection.classList.add("d-none");
        shareUsers.innerHTML = "";
        return;
    }
    
    try {
        console.log('Fetching folder shares...');
        const response = await fetch(`api/get_folder_shares.php?folder_id=${folderId}`);
        const data = await response.json();
        
        console.log('API Response:', data);
        
        if (data.success) {
            // Show current shares - always show the section
            currentSharesSection.classList.remove("d-none");
            console.log('Showing current shares section');
            
            if (data.current_shares.length > 0) {
                console.log('Current shares:', data.current_shares);
                currentSharesList.innerHTML = data.current_shares.map(share => `
                    <div class="current-share-item">
                        <div class="share-user-info">
                            <div class="share-user-name">${share.username}</div>
                            <div class="share-user-email">${share.email}</div>
                        </div>
                        <div class="share-permissions">
                            ${share.can_view ? `<span class="permission-badge view">View</span>` : ""}
                            ${share.can_download ? `<span class="permission-badge download">Download</span>` : ""}
                            ${share.can_upload ? `<span class="permission-badge upload">Upload</span>` : ""}
                            ${share.can_delete ? `<span class="permission-badge delete">Delete</span>` : ""}
                        </div>
                        <div class="share-actions">
                            <button class="btn-share-action btn-remove-share" onclick="removeShare(${share.id})" title="Remove Share">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `).join("");
            } else {
                console.log('No current shares found');
                currentSharesList.innerHTML = `<div class="no-shares-message">This folder is not shared with anyone yet.</div>`;
            }
            
            // Load available users
            console.log('Available users:', data.available_users);
            shareUsers.innerHTML = data.available_users.map(user => `
                <option value="${user.id}">${user.username} (${user.email})</option>
            `).join("");
            
            if (data.available_users.length === 0) {
                shareUsers.innerHTML = `<option disabled>No additional users available</option>`;
            }
        } else {
            console.error('API Error:', data.message);
            showAlert(data.message || "Unable to load folder sharing information", "danger");
        }
    } catch (error) {
        console.error("Error loading shares:", error);
        showAlert("Unable to load folder sharing information - please try again", "danger");
    }
}

async function handleShareFolder() {
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
    
    try {
        const response = await fetch('api/share_folder.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                folder_id: folderId,
                user_ids: selectedUsers,
                permissions: permissions
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Folder shared successfully', 'success');
            loadCurrentShares(); // Reload the shares list
            
            // Clear the selected users
            document.getElementById('shareUsers').selectedIndex = -1;
        } else {
            showAlert(data.message || 'Unable to share folder - please try again', 'danger');
        }
    } catch (error) {
        console.error('Error sharing folder:', error);
        showAlert('Unable to share folder - please try again', 'danger');
    }
}

async function removeShare(shareId) {
    if (!confirm("Are you sure you want to remove this share?")) {
        return;
    }
    
    try {
        const response = await fetch("api/remove_folder_share.php", {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                share_id: shareId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert("Share access removed successfully", "success");
            loadCurrentShares(); // Reload the shares list
        } else {
            showAlert(data.message || "Unable to remove share access - please try again", "danger");
        }
    } catch (error) {
        console.error("Error removing share:", error);
        showAlert("Unable to remove share access - please try again", "danger");
    }
}

// Multi-select functionality
function toggleSelectAll(type) {
    const checkboxes = document.querySelectorAll(`.${type}-checkbox`);
    const selectAllCheckbox = document.getElementById(`selectAll${type.charAt(0).toUpperCase() + type.slice(1)}s`);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateMultiSelectControls(type);
}

function updateMultiSelectControls(type) {
    const checkboxes = document.querySelectorAll(`.${type}-checkbox:checked`);
    
    // Find the controls more reliably
    let controls;
    if (type === 'file') {
        controls = document.querySelector('#filesTableView').closest('.card-professional').querySelector('.multi-select-controls');
    } else if (type === 'folder') {
        controls = document.querySelector('#foldersTableView').closest('.card-professional').querySelector('.multi-select-controls');
    }
    
    if (!controls) {
        console.error(`Could not find multi-select controls for ${type}`);
        return;
    }
    
    if (checkboxes.length > 0) {
        controls.style.display = 'flex';
    } else {
        controls.style.display = 'none';
    }
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll(`.${type}-checkbox`);
    const selectAllCheckbox = document.getElementById(`selectAll${type.charAt(0).toUpperCase() + type.slice(1)}s`);
    
    if (checkboxes.length === allCheckboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else if (checkboxes.length > 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    }
}

function clearSelection(type) {
    const checkboxes = document.querySelectorAll(`.${type}-checkbox`);
    const selectAllCheckbox = document.getElementById(`selectAll${type.charAt(0).toUpperCase() + type.slice(1)}s`);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    selectAllCheckbox.checked = false;
    selectAllCheckbox.indeterminate = false;
    
    updateMultiSelectControls(type);
}

function getSelectedItems(type) {
    const checkboxes = document.querySelectorAll(`.${type}-checkbox:checked`);
    return Array.from(checkboxes).map(checkbox => checkbox.value);
}

// Rename functionality
async function renameFile(fileId, currentName) {
    const newName = prompt('Enter new name for the file:', currentName);
    if (!newName || newName === currentName) return;
    
    try {
        const response = await fetch('api/rename_file.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                file_id: fileId,
                new_name: newName
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('File renamed successfully', 'success');
            location.reload(); // Refresh the page to show the new name
        } else {
            showAlert(result.message || 'Unable to rename file - please try again', 'danger');
        }
    } catch (error) {
        console.error('Rename file error:', error);
        showAlert('Unable to rename file - please try again', 'danger');
    }
}

async function renameFolder(folderId, currentName) {
    const newName = prompt('Enter new name for the folder:', currentName);
    if (!newName || newName === currentName) return;
    
    try {
        const response = await fetch('api/rename_folder.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                folder_id: folderId,
                new_name: newName
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('Folder renamed successfully', 'success');
            location.reload(); // Refresh the page to show the new name
        } else {
            showAlert(result.message || 'Unable to rename folder - please try again', 'danger');
        }
    } catch (error) {
        console.error('Rename folder error:', error);
        showAlert('Unable to rename folder - please try again', 'danger');
    }
}

function renameSelected(type) {
    const selectedItems = getSelectedItems(type);
    
    if (selectedItems.length === 0) {
        showAlert('Please select items to rename', 'warning');
        return;
    }
    
    if (selectedItems.length > 1) {
        showAlert('Please select only one item to rename', 'warning');
        return;
    }
    
    // Get the current name from the table row
    const checkbox = document.querySelector(`.${type}-checkbox[value="${selectedItems[0]}"]`);
    const row = checkbox.closest('tr');
    const nameCell = row.querySelector('td:nth-child(2) span');
    const currentName = nameCell.textContent;
    
    if (type === 'file') {
        renameFile(selectedItems[0], currentName);
    } else {
        renameFolder(selectedItems[0], currentName);
    }
}

// Multi-delete functionality
async function deleteSelected(type) {
    const selectedItems = getSelectedItems(type);
    
    if (selectedItems.length === 0) {
        showAlert('Please select items to delete', 'warning');
        return;
    }
    
    const itemWord = selectedItems.length === 1 ? type : type + 's';
    if (!confirm(`Are you sure you want to delete ${selectedItems.length} ${itemWord}?`)) return;
    
    try {
        const response = await fetch(`api/delete_multiple_${type}s.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                [`${type}_ids`]: selectedItems
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showAlert(result.message, 'success');
            location.reload(); // Refresh the page
        } else {
            showAlert(result.message || `Unable to delete ${itemWord} - please try again`, 'danger');
        }
    } catch (error) {
        console.error(`Delete multiple ${type}s error:`, error);
        showAlert(`Unable to delete ${itemWord} - server error: ${error.message}`, 'danger');
    }
}
