<div id="oldGrantUploadModal" class="modal hidden">
    <div class="modal-overlay" onclick="closeOldGrantUploadModal()"></div>
    <div class="modal-dialog">
        <div class="modal-header">
            <h2>Upload Historical Scholarship Records</h2>
            <button type="button" class="modal-close" onclick="closeOldGrantUploadModal()">&times;</button>
        </div>
        <form id="oldGrantUploadForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="grantType">Grant Type</label>
                    <select id="grantType" name="grant_type" required class="form-control">
                        <option value="">-- Select Grant Type --</option>
                        <option value="academic">Old Academic Grants</option>
                        <option value="non_academic">Old Non-Academic Grants</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="excelFile">Excel File (.xlsx)</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="excelFile" name="excel_file" accept=".xlsx,.xls" required>
                        <span id="fileName" class="file-name">Choose Excel File</span>
                    </div>
                    <small style="color: #666; margin-top: 5px; display: block;">
                        Supported format: .xlsx
                    </small>
                </div>

                <div id="uploadStatus" class="alert hidden" style="margin-top: 15px;">
                    <div id="uploadMessage"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeOldGrantUploadModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="uploadBtn">Upload</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    display: flex;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
}

.modal.hidden {
    display: none;
}

.modal-overlay {
    position: absolute;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.modal-dialog {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    position: relative;
    z-index: 1;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.modal-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: #000;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 20px;
    border-top: 1px solid #e0e0e0;
    background-color: #f9f9f9;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: #6c63ff;
    box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.1);
}

.file-input-wrapper {
    position: relative;
    display: inline-block;
    width: 100%;
}

.file-input-wrapper input[type="file"] {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.file-input-wrapper {
    display: block;
    padding: 10px 12px;
    border: 2px dashed #ddd;
    border-radius: 4px;
    cursor: pointer;
    text-align: center;
    transition: all 0.3s ease;
}

.file-input-wrapper:hover {
    border-color: #6c63ff;
    background-color: #f9f7ff;
}

.file-name {
    display: block;
    color: #666;
    font-size: 14px;
}

.file-input-wrapper.has-file .file-name {
    color: #333;
    font-weight: 500;
}

.btn {
    padding: 10px 16px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: #6c63ff;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: #5651cc;
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: #e0e0e0;
    color: #333;
}

.btn-secondary:hover {
    background-color: #d0d0d0;
}

.alert {
    padding: 12px;
    border-radius: 4px;
    font-size: 14px;
}

.alert.hidden {
    display: none;
}

.alert.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert.warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}
</style>

<script>
document.getElementById('excelFile').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name || 'Choose Excel File';
    document.getElementById('fileName').textContent = fileName;
    document.getElementById('excelFile').parentElement.classList.toggle('has-file', e.target.files.length > 0);
});

document.getElementById('oldGrantUploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const uploadBtn = document.getElementById('uploadBtn');
    const originalBtnText = uploadBtn.textContent;
    
    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Uploading...';

    try {
        const response = await fetch('/admin/old-grants/import', {
            method: 'POST',
            body: formData,
        });

        const data = await response.json();
        const statusDiv = document.getElementById('uploadStatus');
        const messageDiv = document.getElementById('uploadMessage');

        statusDiv.classList.remove('hidden', 'success', 'error', 'warning');
        statusDiv.classList.add(data.status);

        if (data.status === 'success') {
            messageDiv.innerHTML = `<strong>Success!</strong> ${data.message}`;
            // Reset form after successful upload
            setTimeout(() => {
                closeOldGrantUploadModal();
                location.reload(); // Reload to show new records
            }, 2000);
        } else if (data.status === 'warning') {
            messageDiv.innerHTML = `<strong>Warning:</strong> ${data.message}`;
        } else {
            messageDiv.innerHTML = `<strong>Error:</strong> ${data.message}`;
        }
    } catch (error) {
        const statusDiv = document.getElementById('uploadStatus');
        const messageDiv = document.getElementById('uploadMessage');
        statusDiv.classList.remove('hidden');
        statusDiv.classList.add('error');
        messageDiv.innerHTML = `<strong>Error:</strong> ${error.message}`;
    } finally {
        uploadBtn.disabled = false;
        uploadBtn.textContent = originalBtnText;
    }
});

function openOldGrantUploadModal() {
    document.getElementById('oldGrantUploadModal').classList.remove('hidden');
}

function closeOldGrantUploadModal() {
    document.getElementById('oldGrantUploadModal').classList.add('hidden');
    document.getElementById('oldGrantUploadForm').reset();
    document.getElementById('uploadStatus').classList.add('hidden');
    document.getElementById('fileName').textContent = 'Choose Excel File';
    document.getElementById('excelFile').parentElement.classList.remove('has-file');
}
</script>
