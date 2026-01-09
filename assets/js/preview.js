// Centralized Publication Preview script

(function() {
    function previewPublication(filePath, title, author, publishDate, abstract, department, type, thumbnail) {
        // If first parameter is an element, extract data from attributes
        if (filePath && typeof filePath === 'object' && filePath.getAttribute) {
            const element = filePath;
            filePath = element.getAttribute('data-filepath');
            title = element.getAttribute('data-title');
            author = element.getAttribute('data-author');
            publishDate = element.getAttribute('data-date');
            abstract = element.getAttribute('data-abstract');
            department = element.getAttribute('data-department');
            type = element.getAttribute('data-type');
            thumbnail = element.getAttribute('data-thumbnail');
        }

        // Format the publication date safely
        let formattedDate = '';
        try {
            if (publishDate) {
                formattedDate = new Date(publishDate).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }
        } catch (e) {
            formattedDate = publishDate || '';
        }

        // Create preview modal
        const modal = document.createElement('div');
        modal.id = 'previewModal';
        modal.className = 'modal';
        modal.setAttribute('data-modal-type', 'preview');
        modal.setAttribute('aria-label', 'Publication Preview');
        modal.innerHTML = `
            <div class="modal-content preview-modal-content">
                <div class="modal-header">
                    <h3>${escapeHtml(title || '')}</h3>
                    <span class="close-modal" onclick="closePreviewModal()" aria-label="Close preview modal">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="preview-content-wrapper">
                        ${thumbnail ? `<div class="preview-thumbnail-container">
                            <img src="../${thumbnail}?t=${Date.now()}" alt="Document preview" class="preview-thumbnail">
                        </div>` : ''}
                        <div class="preview-details-container">
                            <div class="publication-details">
                                <div class="detail-row">
                                    <strong>Author:</strong> <span>${escapeHtml(author || '')}</span>
                                </div>
                                <div class="detail-row">
                                    <strong>Published:</strong> <span>${escapeHtml(formattedDate)}</span>
                                </div>
                                ${department ? `<div class="detail-row"><strong>Department:</strong> <span>${escapeHtml(department)}</span></div>` : ''}
                                ${type ? `<div class="detail-row"><strong>Type:</strong> <span>${escapeHtml(type)}</span></div>` : ''}
                            </div>
                            <div class="abstract-section">
                                <div class="abstract-label"><strong>Abstract:</strong></div>
                                <div class="abstract-text">${escapeHtml(abstract || 'No abstract available.')}</div>
                            </div>
                        </div>
                    </div>
                    <div class="preview-actions">
                        <a href="../${filePath}" target="_blank" class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i> View Full Document
                        </a>
                        <a href="../${filePath}" download class="btn btn-secondary">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        // trigger CSS visibility and animation
        requestAnimationFrame(() => {
            modal.style.display = 'flex';
            modal.style.opacity = '1';
        });
    }

    function closePreviewModal() {
        const modal = document.getElementById('previewModal');
        if (modal) modal.remove();
    }

    // Close preview modal when clicking outside (without overriding existing handlers)
    window.addEventListener('click', function(event) {
        const previewModal = document.getElementById('previewModal');
        if (previewModal && event.target === previewModal) {
            closePreviewModal();
        }
    });

    // Basic HTML escape to prevent accidental injection of dynamic values
    function escapeHtml(unsafe) {
        return String(unsafe)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Expose globally for inline onclick usage in templates
    window.previewPublication = previewPublication;
    window.closePreviewModal = closePreviewModal;
})();
