/**
 * Backup & Recovery System - Frontend JavaScript
 */

// Utility function to show notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: ${type === 'success' ? '#4caf50' : '#f44336'};
        color: white;
        padding: 16px 24px;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        z-index: 9999;
        max-width: 400px;
        animation: slideIn 0.3s ease-out;
    `;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function backupUrl(name, backupId) {
    const template = window.backupRoutes?.[name];
    if (!template) {
        throw new Error(`Backup route "${name}" is unavailable on this page.`);
    }

    return backupId === undefined
        ? template
        : template.replace('__BACKUP_ID__', encodeURIComponent(String(backupId)));
}

async function requestJson(url, options = {}) {
    let response;
    try {
        response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.headers || {}),
            },
        });
    } catch (error) {
        throw new Error(`Network request failed: ${error.message}`);
    }

    return parseJsonResponse(response);
}

// Initialize backup system
document.addEventListener('DOMContentLoaded', function() {
    // Manual Backup Form
    const manualBackupForm = document.getElementById('manualBackupForm');
    if (manualBackupForm) {
        manualBackupForm.addEventListener('submit', handleManualBackup);
    }

    // Auto Backup Toggle
    const autoBackupToggle = document.getElementById('autoBackupToggle');
    if (autoBackupToggle) {
        autoBackupToggle.addEventListener('change', handleAutoBackupToggle);
    }

    // Frequency Dropdown
    const frequencySelect = document.getElementById('frequencySelect');
    if (frequencySelect) {
        frequencySelect.addEventListener('change', handleFrequencyChange);
    }

    // Time Picker
    const timeInput = document.getElementById('timeInput');
    if (timeInput) {
        timeInput.addEventListener('change', handleTimeChange);
    }

    // Day of Week Dropdown
    const daySelect = document.getElementById('daySelect');
    if (daySelect) {
        daySelect.addEventListener('change', handleDayChange);
    }

    // LoadInitial Data
    loadBackupSchedule();
    loadBackupHistory();
});

/**
 * Handle manual backup creation
 */
async function handleManualBackup(e) {
    e.preventDefault();

    const button = e.target.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '⏳ Creating backup...';

    try {
        const data = await requestJson(backupUrl('create'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
        });

        if (data.status === 'success') {
            const backupId = data.backup_id;
            if (!backupId) {
                throw new Error('Backup was created, but no download ID was returned.');
            }

            showNotification('✓ Backup created successfully! Downloading...', 'success');
            const downloadUrl = data.download_url || backupUrl('download', backupId);
            await triggerBackupDownload(backupId, data.filename || 'backup.zip', downloadUrl);
            loadBackupHistory();
        } else {
            throw new Error(data.message || 'Backup creation failed');
        }
    } catch (error) {
        showNotification('✗ ' + error.message, 'error');
    } finally {
        button.disabled = false;
        button.innerHTML = originalText;
    }
}

async function parseJsonResponse(response) {
    const contentType = response.headers.get('content-type') || '';

    if (response.redirected) {
        let destination = response.url;
        try {
            destination = new URL(response.url, window.location.href).pathname;
        } catch (error) {
            destination = 'another page';
        }
        throw new Error(`Request was redirected to ${destination} (HTTP ${response.status}). Check your login/session and backup route.`);
    }

    if (contentType.toLowerCase().includes('json')) {
        let data;
        try {
            data = await response.json();
        } catch (error) {
            throw new Error(`The server returned invalid JSON (HTTP ${response.status}).`);
        }

        if (!response.ok || data.status === 'error') {
            throw new Error(data.message || `Backup request failed (HTTP ${response.status}).`);
        }

        return data;
    }

    const text = await response.text().catch(() => '');
    const snippet = text
        .replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, ' ')
        .replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, ' ')
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, 220);
    const detail = snippet ? ` Response: ${snippet}` : '';
    throw new Error(`Backup request returned non-JSON content (HTTP ${response.status}).${detail}`);
}

async function triggerBackupDownload(backupId, filename, downloadUrl = backupUrl('download', backupId)) {
    let response;
    try {
        response = await fetch(downloadUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/zip, application/octet-stream, application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
    } catch (error) {
        throw new Error(`Backup download network request failed: ${error.message}`);
    }

    const contentType = (response.headers.get('content-type') || '').toLowerCase();
    if (response.redirected || !response.ok || (!contentType.includes('application/zip') && !contentType.includes('application/octet-stream'))) {
        await parseJsonResponse(response);
        throw new Error(`Backup download returned an unexpected content type (HTTP ${response.status}).`);
    }

    const blob = await response.blob();
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = filename || 'backup.zip';
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(objectUrl);
}

/**
 * Handle auto backup toggle
 */
async function handleAutoBackupToggle(e) {
    const enabled = e.target.checked;
    const statusText = this.parentElement.nextElementSibling;

    try {
        const schedule = await getBackupSchedule();
        const validated = {
            enabled: enabled,
            frequency: document.getElementById('frequencySelect')?.value ?? schedule.frequency,
            time: document.getElementById('timeInput')?.value ?? schedule.time,
            day_of_week: document.getElementById('daySelect')?.value ?? schedule.day_of_week,
        };

        const data = await requestJson(backupUrl('scheduleUpdate'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(validated),
        });

        if (data.status === 'success') {
            statusText.textContent = enabled ? 'On' : 'Off';
            statusText.style.color = enabled ? '#4caf50' : '#d4a76a';
            showNotification('✓ Automatic backup ' + (enabled ? 'enabled' : 'disabled'), 'success');
        } else {
            e.target.checked = !enabled;
            showNotification('✗ Failed to update settings', 'error');
        }
    } catch (error) {
        e.target.checked = !enabled;
        showNotification('✗ Error: ' + error.message, 'error');
    }
}

/**
 * Handle frequency change
 */
async function handleFrequencyChange(e) {
    const frequency = e.target.value;
    const daySelectGroup = document.getElementById('daySelectGroup');

    // Show/hide day selector for weekly backups
    if (daySelectGroup) {
        daySelectGroup.style.display = frequency === 'weekly' ? 'flex' : 'none';
    }

    try {
        const schedule = await getBackupSchedule();
        const data = await requestJson(backupUrl('scheduleUpdate'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                enabled: schedule.enabled,
                frequency: frequency,
                time: schedule.time,
                day_of_week: schedule.day_of_week,
            }),
        });

        if (data.status === 'success') {
            showNotification('✓ Frequency updated', 'success');
        } else {
            showNotification('✗ Failed to update frequency', 'error');
        }
    } catch (error) {
        showNotification('✗ Error: ' + error.message, 'error');
    }
}

/**
 * Handle time change
 */
async function handleTimeChange(e) {
    const time = e.target.value;

    try {
        const schedule = await getBackupSchedule();
        const data = await requestJson(backupUrl('scheduleUpdate'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                enabled: schedule.enabled,
                frequency: schedule.frequency,
                time: time,
                day_of_week: schedule.day_of_week,
            }),
        });

        if (data.status === 'success') {
            showNotification('✓ Time updated', 'success');
        } else {
            showNotification('✗ Failed to update time', 'error');
        }
    } catch (error) {
        showNotification('✗ Error: ' + error.message, 'error');
    }
}

/**
 * Handle day of week change
 */
async function handleDayChange(e) {
    const day = e.target.value;

    try {
        const schedule = await getBackupSchedule();
        const data = await requestJson(backupUrl('scheduleUpdate'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                enabled: schedule.enabled,
                frequency: schedule.frequency,
                time: schedule.time,
                day_of_week: day,
            }),
        });

        if (data.status === 'success') {
            showNotification('✓ Day updated', 'success');
        } else {
            showNotification('✗ Failed to update day', 'error');
        }
    } catch (error) {
        showNotification('✗ Error: ' + error.message, 'error');
    }
}

/**
 * Load backup schedule from API
 */
async function getBackupSchedule() {
    try {
        const data = await requestJson(backupUrl('scheduleGet'));
        
        if (data.status === 'success') {
            return data.data;
        }
        return null;
    } catch (error) {
        console.error('Failed to load schedule:', error);
        return null;
    }
}

/**
 * Load and display backup schedule settings
 */
async function loadBackupSchedule() {
    try {
        const schedule = await getBackupSchedule();
        
        if (schedule) {
            // Update toggle
            const toggle = document.getElementById('autoBackupToggle');
            if (toggle) {
                toggle.checked = schedule.enabled;
                const statusText = toggle.parentElement.nextElementSibling;
                if (statusText) {
                    statusText.textContent = schedule.enabled ? 'On' : 'Off';
                    statusText.style.color = schedule.enabled ? '#4caf50' : '#d4a76a';
                }
            }

            // Update frequency
            const frequencySelect = document.getElementById('frequencySelect');
            if (frequencySelect) {
                frequencySelect.value = schedule.frequency;
            }

            // Update time
            const timeInput = document.getElementById('timeInput');
            if (timeInput) {
                timeInput.value = schedule.time;
            }

            // Update day
            const daySelect = document.getElementById('daySelect');
            if (daySelect && schedule.frequency === 'weekly') {
                daySelect.value = schedule.day_of_week || 'monday';
                daySelect.parentElement.style.display = 'flex';
            }

            // Update next run info
            const nextRunInfo = document.getElementById('nextRunInfo');
            if (nextRunInfo) {
                if (schedule.last_run) {
                    nextRunInfo.innerHTML = `Last run: ${schedule.last_run} (${schedule.last_status})`;
                } else {
                    nextRunInfo.innerHTML = 'No backups run yet. Next run: ' + schedule.time;
                }
            }
        }
    } catch (error) {
        console.error('Failed to load backup schedule:', error);
    }
}

/**
 * Load and display backup history
 */
async function loadBackupHistory() {
    try {
        const data = await requestJson(backupUrl('list'));

        if (data.status === 'success' && data.data.length > 0) {
            displayBackupHistory(data.data);
        } else {
            displayEmptyBackupHistory();
        }
    } catch (error) {
        console.error('Failed to load backup history:', error);
        showNotification('✗ Failed to load backup history', 'error');
    }
}

/**
 * Display backup history table
 */
function displayBackupHistory(backups) {
    const tableSection = document.querySelector('.backup-history-section');
    
    let html = `
        <h2>Backup History</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Date Created</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;

    backups.forEach(backup => {
        const statusBadgeClass = backup.status === 'Success' ? 'badge-success' : 'badge-error';
        const typeBadgeClass = backup.type === 'Manual' ? 'badge-manual' : 'badge-automatic';

        html += `
            <tr>
                <td>${backup.filename}</td>
                <td>${backup.created_at_short}</td>
                <td><span class="badge ${typeBadgeClass}">${backup.type}</span></td>
                <td>${backup.size}</td>
                <td><span class="badge ${statusBadgeClass}">${backup.status}</span></td>
                <td>
                    <div class="actions-cell">
                        ${backup.status === 'Success' ? `
                            <button class="btn-icon" onclick="restoreBackup(${backup.id})" title="Restore backup">
                                ↺
                            </button>
                        ` : ''}
                        <button class="btn-icon" onclick="downloadBackup(${backup.id})" title="Download backup">
                            ↓
                        </button>
                        <button class="btn-icon" onclick="deleteBackup(${backup.id})" title="Delete backup">
                            ✕
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    tableSection.innerHTML = html;
}

/**
 * Display empty state
 */
function displayEmptyBackupHistory() {
    const tableSection = document.querySelector('.backup-history-section');
    tableSection.innerHTML = `
        <h2>Backup History</h2>
        <div class="empty-state">
            <p>No backups found. Create your first backup by clicking "Generate and Download Manual Backup Now".</p>
        </div>
    `;
}

/**
 * Restore backup
 */
async function restoreBackup(backupId) {
    if (!confirm('⚠️ WARNING: Restoring will overwrite all current data with the backup data. This action cannot be undone. Are you sure you want to proceed?')) {
        return;
    }

    const button = event.target;
    button.disabled = true;
    button.textContent = '⏳';

    try {
        const data = await requestJson(backupUrl('restore', backupId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
        });

        if (data.status === 'success') {
            showNotification('✓ ' + data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification('✗ ' + (data.message || 'Restore failed'), 'error');
        }
    } catch (error) {
        showNotification('✗ Error: ' + error.message, 'error');
    } finally {
        button.disabled = false;
        button.textContent = '↺';
    }
}

/**
 * Download backup
 */
async function downloadBackup(backupId) {
    try {
        const backupRow = document.querySelector(`button[onclick="downloadBackup(${backupId})"]`);
        const filename = backupRow && backupRow.closest('tr')
            ? backupRow.closest('tr').querySelector('td')?.textContent?.trim() || 'backup.zip'
            : 'backup.zip';

        await triggerBackupDownload(backupId, filename);
        showNotification('✓ Download started', 'success');
    } catch (error) {
        showNotification('✗ Error: ' + error.message, 'error');
    }
}

/**
 * Delete backup
 */
async function deleteBackup(backupId) {
    if (!confirm('Are you sure you want to delete this backup?')) {
        return;
    }

    const button = event.target;
    button.disabled = true;
    button.textContent = '⏳';

    try {
        const data = await requestJson(backupUrl('delete', backupId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
        });

        if (data.status === 'success') {
            showNotification('✓ Backup deleted successfully', 'success');
            loadBackupHistory();
        } else {
            showNotification('✗ ' + (data.message || 'Delete failed'), 'error');
        }
    } catch (error) {
        showNotification('✗ Error: ' + error.message, 'error');
    } finally {
        button.disabled = false;
        button.textContent = '✕';
    }
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }

    .badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        min-width: 80px;
    }

    .badge-success {
        background-color: #c8e6c9;
        color: #2d5c2d;
    }

    .badge-error {
        background-color: #f4978e;
        color: #8b2e2e;
    }

    .badge-manual {
        background-color: #bbdefb;
        color: #1a3d5c;
    }

    .badge-automatic {
        background-color: #fff9c4;
        color: #6d5c00;
    }
`;
document.head.appendChild(style);
