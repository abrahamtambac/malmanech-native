// utils.js

// Debounce function to limit the rate of function execution
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
      const later = () => {
          clearTimeout(timeout);
          func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
  };
}

// Get file icon based on file type or extension
function getFileIcon(fileType, fileName) {
  if (!fileType && fileName) {
      const ext = fileName.split('.').pop().toLowerCase();
      switch (ext) {
          case 'jpg': case 'jpeg': case 'png': case 'gif': return '<i class="bi bi-image"></i>';
          case 'doc': case 'docx': return '<i class="bi bi-file-word"></i>';
          case 'xls': case 'xlsx': return '<i class="bi bi-file-excel"></i>';
          case 'ppt': case 'pptx': return '<i class="bi bi-file-ppt"></i>';
          case 'pdf': return '<i class="bi bi-file-pdf"></i>';
          default: return '<i class="bi bi-file-earmark"></i>';
      }
  }
  if (!fileType) return '<i class="bi bi-file-earmark"></i>';
  if (fileType.includes('image')) return '<i class="bi bi-image"></i>';
  if (fileType.includes('word')) return '<i class="bi bi-file-word"></i>';
  if (fileType.includes('excel') || fileType.includes('spreadsheet')) return '<i class="bi bi-file-excel"></i>';
  if (fileType.includes('powerpoint') || fileType.includes('presentation')) return '<i class="bi bi-file-ppt"></i>';
  if (fileType.includes('pdf')) return '<i class="bi bi-file-pdf"></i>';
  return '<i class="bi bi-file-earmark"></i>';
}

// Format file size in a human-readable format
function formatFileSize(bytes) {
  if (!bytes) return '0 B';
  if (bytes < 1024) return bytes + ' B';
  else if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
  else if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
  else return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
}