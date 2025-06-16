import './bootstrap';

// Global JavaScript for Polaris Compute Dashboard

/**
 * Resource Management Functions
 */

// Auto-refresh functionality
let autoRefreshInterval;

function startAutoRefresh(intervalMs = 15000) {
    stopAutoRefresh(); // Clear any existing interval
    
    autoRefreshInterval = setInterval(() => {
        if (document.visibilityState === 'visible') {
            refreshResources();
        }
    }, intervalMs);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

function refreshResources() {
    const dashboard = Alpine.$data(document.querySelector('[x-data]'));
    if (dashboard && typeof dashboard.refreshResources === 'function') {
        dashboard.refreshResources();
    }
}

// Start auto-refresh when page loads
document.addEventListener('DOMContentLoaded', () => {
    startAutoRefresh();
});

// Stop auto-refresh when page is hidden
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
});

/**
 * Filter Management
 */

// Quick filter functions
window.updateFilter = function(type, value) {
    const url = new URL(window.location);
    url.searchParams.set(type, value);
    window.location.href = url.toString();
}

window.toggleSystemFilter = function(filterName) {
    showLoading();
    
    fetch(`/api/filters/${filterName}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast(`Filter ${filterName} ${data.enabled ? 'enabled' : 'disabled'}`, 'success');
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast('Failed to toggle filter', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error toggling filter:', error);
        showToast('Error toggling filter', 'error');
    });
}

window.resetAllFilters = function() {
    showLoading();
    
    // Reset URL filters
    const url = new URL(window.location);
    url.searchParams.delete('type');
    url.searchParams.delete('ownership');
    
    // Reset system filters
    fetch('/api/filters/reset', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast('All filters reset', 'success');
            window.location.href = url.toString();
        } else {
            showToast('Failed to reset filters', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error resetting filters:', error);
        showToast('Error resetting filters', 'error');
    });
}

/**
 * Resource Actions
 */

window.deployResource = function(resourceId) {
    console.log('Deploy resource:', resourceId);
    showToast('Deployment feature coming soon!', 'info');
    
    // TODO: Implement deployment modal/process
    // This would typically open a modal for configuration
    // and then make API calls to deploy the resource
}

window.showRentalDetails = function(resourceId) {
    console.log('Show rental details:', resourceId);
    showToast('Rental management coming soon!', 'info');
    
    // TODO: Implement rental details modal
    // This would show SSH details, manage container, etc.
}

window.exportResources = function() {
    const params = new URLSearchParams(window.location.search);
    params.set('format', 'json');
    
    showLoading();
    
    fetch(`/api/compute/export?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                const blob = new Blob([JSON.stringify(data.data, null, 2)], {
                    type: 'application/json'
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = data.filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                showToast('Resources exported successfully', 'success');
            } else {
                showToast('Failed to export resources', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Export error:', error);
            showToast('Error exporting resources', 'error');
        });
}

/**
 * Search Functionality
 */

// Debounced search
let searchTimeout;

function debounceSearch(callback, delay = 300) {
    return function(...args) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => callback.apply(this, args), delay);
    };
}

// Search within current page (client-side filtering)
window.searchResources = debounceSearch(function(query) {
    const resourceCards = document.querySelectorAll('[data-resource-id]');
    const normalizedQuery = query.toLowerCase().trim();
    
    resourceCards.forEach(card => {
        const resourceText = card.textContent.toLowerCase();
        const matches = !normalizedQuery || resourceText.includes(normalizedQuery);
        
        card.style.display = matches ? 'block' : 'none';
        
        // Add/remove search highlight
        if (matches && normalizedQuery) {
            card.classList.add('ring-2', 'ring-yellow-500');
        } else {
            card.classList.remove('ring-2', 'ring-yellow-500');
        }
    });
    
    // Update results count
    const visibleCards = document.querySelectorAll('[data-resource-id]:not([style*="display: none"])');
    const resultsText = document.querySelector('#search-results-text');
    if (resultsText) {
        resultsText.textContent = `${visibleCards.length} resources shown`;
    }
});

/**
 * Keyboard Shortcuts
 */

document.addEventListener('keydown', function(e) {
    // Don't trigger shortcuts when typing in input fields
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
    }
    
    switch(e.key) {
        case 'r':
            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                refreshResources();
            }
            break;
            
        case 'f':
            const dashboard = Alpine.$data(document.querySelector('[x-data]'));
            if (dashboard && 'showFilters' in dashboard) {
                dashboard.showFilters = !dashboard.showFilters;
            }
            break;
            
        case '/':
            e.preventDefault();
            const searchInput = document.querySelector('input[type="text"][placeholder*="Search"]');
            if (searchInput) {
                searchInput.focus();
            }
            break;
            
        case 'Escape':
            // Close any open modals or overlays
            const modals = document.querySelectorAll('.modal-overlay');
            modals.forEach(modal => modal.remove());
            break;
    }
});

/**
 * Performance Monitoring
 */

// Track page load performance
window.addEventListener('load', function() {
    const loadTime = performance.now();
    console.log(`Page loaded in ${Math.round(loadTime)}ms`);
    
    // Send performance data (optional)
    if ('sendBeacon' in navigator) {
        const perfData = {
            loadTime: Math.round(loadTime),
            timestamp: Date.now(),
            userAgent: navigator.userAgent
        };
        
        // navigator.sendBeacon('/api/performance', JSON.stringify(perfData));
    }
});

/**
 * Error Tracking
 */

// Track JavaScript errors
window.addEventListener('error', function(e) {
    console.error('JavaScript error:', e.error);
    
    // Send error data (optional)
    const errorData = {
        message: e.message,
        filename: e.filename,
        lineno: e.lineno,
        colno: e.colno,
        stack: e.error ? e.error.stack : null,
        timestamp: Date.now(),
        url: window.location.href
    };
    
    // navigator.sendBeacon('/api/errors', JSON.stringify(errorData));
});

/**
 * Theme Management
 */

window.toggleDarkMode = function() {
    const url = new URL(window.location);
    const currentDark = url.searchParams.get('dark') === 'true';
    url.searchParams.set('dark', (!currentDark).toString());
    window.location.href = url.toString();
}

// System theme preference detection
function detectSystemTheme() {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return 'dark';
    }
    return 'light';
}

// Listen for system theme changes
if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        if (!localStorage.getItem('theme-preference')) {
            // Only auto-switch if user hasn't set a preference
            const url = new URL(window.location);
            url.searchParams.set('dark', e.matches.toString());
            window.location.href = url.toString();
        }
    });
}

/**
 * Utility Functions
 */

// Format numbers with commas
window.formatNumber = function(num) {
    return new Intl.NumberFormat().format(num);
}

// Format bytes to human readable
window.formatBytes = function(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Format duration from seconds
window.formatDuration = function(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

// Copy text to clipboard
window.copyToClipboard = function(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copied to clipboard', 'success', 1000);
        }).catch(err => {
            console.error('Failed to copy:', err);
            showToast('Failed to copy', 'error');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showToast('Copied to clipboard', 'success', 1000);
        } catch (err) {
            console.error('Failed to copy:', err);
            showToast('Failed to copy', 'error');
        }
        document.body.removeChild(textArea);
    }
}

/**
 * Analytics (Optional)
 */

// Track user interactions
function trackEvent(action, category, label, value) {
    // Google Analytics 4 example
    if (typeof gtag !== 'undefined') {
        gtag('event', action, {
            event_category: category,
            event_label: label,
            value: value
        });
    }
    
    // Custom analytics endpoint
    // fetch('/api/analytics', {
    //     method: 'POST',
    //     headers: { 'Content-Type': 'application/json' },
    //     body: JSON.stringify({ action, category, label, value, timestamp: Date.now() })
    // });
}

// Track filter usage
document.addEventListener('click', function(e) {
    if (e.target.matches('[data-filter]')) {
        trackEvent('filter_click', 'interface', e.target.dataset.filter);
    }
    
    if (e.target.matches('[data-resource-id]')) {
        trackEvent('resource_click', 'resource', e.target.dataset.resourceType);
    }
});

console.log('🚀 Polaris Compute Dashboard initialized');
console.log('⌨️ Keyboard shortcuts: R (refresh), F (filters), / (search), ESC (close)');