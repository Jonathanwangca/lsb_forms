/**
 * LSB RFQ System - Main JavaScript
 */

/**
 * Section 折叠/展开管理
 */
const SectionCollapse = {
    storageKey: 'rfq_collapsed_sections',

    init: function() {
        this.bindEvents();
        this.restoreState();
        this.addCollapseIcons();
    },

    bindEvents: function() {
        document.querySelectorAll('.form-section-header').forEach(header => {
            header.addEventListener('click', (e) => {
                // 避免点击按钮时触发折叠
                if (e.target.closest('button') || e.target.closest('a')) return;
                this.toggle(header.closest('.form-section'));
            });
        });
    },

    addCollapseIcons: function() {
        document.querySelectorAll('.form-section-header').forEach(header => {
            // 如果已有图标则跳过
            if (header.querySelector('.collapse-icon')) return;

            // 保存并移除 float-end 按钮（如 Add Note 按钮）
            const floatButtons = header.querySelectorAll('.float-end');
            floatButtons.forEach(btn => btn.remove());

            // 创建包装器（只包装标题内容，不包括按钮）
            const titleSpan = document.createElement('span');
            titleSpan.className = 'section-title-wrapper';
            titleSpan.innerHTML = header.innerHTML;
            header.innerHTML = '';
            header.appendChild(titleSpan);

            // 添加折叠图标
            const icon = document.createElement('i');
            icon.className = 'bi bi-chevron-down collapse-icon';
            header.appendChild(icon);

            // 重新添加 float-end 按钮到 header（在图标之前）
            floatButtons.forEach(btn => {
                header.insertBefore(btn, icon);
            });
        });
    },

    toggle: function(section) {
        if (!section) return;
        section.classList.toggle('collapsed');
        this.saveState();
    },

    expand: function(section) {
        if (!section) return;
        section.classList.remove('collapsed');
        this.saveState();
    },

    collapseAll: function() {
        document.querySelectorAll('.form-section').forEach(section => {
            section.classList.add('collapsed');
        });
        this.saveState();
    },

    expandAll: function() {
        document.querySelectorAll('.form-section').forEach(section => {
            section.classList.remove('collapsed');
        });
        this.saveState();
    },

    saveState: function() {
        const collapsed = [];
        document.querySelectorAll('.form-section.collapsed').forEach((section, index) => {
            // 使用 section 的索引作为标识
            const allSections = document.querySelectorAll('.form-section');
            const sectionIndex = Array.from(allSections).indexOf(section);
            collapsed.push(sectionIndex);
        });
        localStorage.setItem(this.storageKey, JSON.stringify(collapsed));
    },

    restoreState: function() {
        const saved = localStorage.getItem(this.storageKey);
        if (!saved) return;

        try {
            const collapsed = JSON.parse(saved);
            const allSections = document.querySelectorAll('.form-section');
            collapsed.forEach(index => {
                if (allSections[index]) {
                    allSections[index].classList.add('collapsed');
                }
            });
        } catch (e) {
            console.error('Failed to restore section state:', e);
        }
    }
};

const RFQ = {
    // 自动保存定时器
    autoSaveTimer: null,
    autoSaveInterval: 30000, // 30秒

    /**
     * 初始化
     */
    init: function() {
        this.bindEvents();
        this.initAutoSave();
    },

    /**
     * 绑定事件
     */
    bindEvents: function() {
        // 动态添加行
        document.querySelectorAll('[data-action="add-row"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const target = e.target.closest('[data-action="add-row"]');
                const template = target.dataset.template;
                const container = document.getElementById(target.dataset.container);
                this.addDynamicRow(container, template);
            });
        });

        // 删除行
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="remove-row"]')) {
                const row = e.target.closest('.dynamic-row');
                if (row) {
                    row.remove();
                    this.reindexRows(row.parentElement);
                }
            }
        });

        // 表单变化时触发自动保存
        document.querySelectorAll('form.rfq-form input, form.rfq-form select, form.rfq-form textarea').forEach(el => {
            el.addEventListener('change', () => this.triggerAutoSave());
        });
    },

    /**
     * 添加动态行
     */
    addDynamicRow: function(container, templateId) {
        const template = document.getElementById(templateId);
        if (!template || !container) return;

        const clone = template.content.cloneNode(true);
        const index = container.querySelectorAll('.dynamic-row').length;

        // 更新索引
        clone.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace(/\[\d*\]/, '[' + index + ']');
        });

        container.appendChild(clone);
    },

    /**
     * 重新索引行
     */
    reindexRows: function(container) {
        container.querySelectorAll('.dynamic-row').forEach((row, index) => {
            row.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace(/\[\d+\]/, '[' + index + ']');
            });
        });
    },

    /**
     * 初始化自动保存
     */
    initAutoSave: function() {
        const form = document.querySelector('form.rfq-form');
        if (!form) return;

        // 每30秒检查是否需要保存
        this.autoSaveTimer = setInterval(() => {
            if (this.needsSave) {
                this.saveDraft();
            }
        }, this.autoSaveInterval);
    },

    /**
     * 触发自动保存
     */
    triggerAutoSave: function() {
        this.needsSave = true;
    },

    /**
     * 保存草稿
     */
    saveDraft: function(callback) {
        const form = document.querySelector('form.rfq-form');
        if (!form) return;

        const formData = new FormData(form);
        formData.append('action', 'save_draft');

        fetch('/aiforms/api/rfq.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showAutoSaveIndicator();
                this.needsSave = false;

                // 更新ID
                if (data.data && data.data.id) {
                    const idInput = form.querySelector('input[name="id"]');
                    if (idInput) idInput.value = data.data.id;
                }

                if (callback) callback(data);
            } else {
                console.error('Save failed:', data.message);
            }
        })
        .catch(error => {
            console.error('Save error:', error);
        });
    },

    /**
     * 浮动按钮保存（带动画效果）
     */
    floatingSave: function() {
        const btn = document.getElementById('floatingSaveBtn');
        if (!btn || btn.classList.contains('saving')) return;

        // 显示保存中状态
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        btn.classList.add('saving');

        this.saveDraft(function(data) {
            // 保存成功，显示成功状态
            btn.innerHTML = '<i class="bi bi-check-lg"></i>';
            setTimeout(() => {
                btn.innerHTML = originalIcon;
                btn.classList.remove('saving');
            }, 1500);
        });

        // 设置超时恢复（防止回调失败时按钮卡住）
        setTimeout(() => {
            if (btn.classList.contains('saving')) {
                btn.innerHTML = originalIcon;
                btn.classList.remove('saving');
            }
        }, 5000);
    },

    /**
     * 设置状态为已提交（Submit按钮调用）
     */
    setStatusSubmitted: function() {
        const statusSelect = document.querySelector('select[name="main[status]"]');
        if (statusSelect) {
            statusSelect.value = 'submitted';
        }
    },

    /**
     * 显示自动保存指示器
     */
    showAutoSaveIndicator: function() {
        let indicator = document.querySelector('.autosave-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'autosave-indicator';
            indicator.innerHTML = '<i class="bi bi-check-circle"></i> Saved';
            document.body.appendChild(indicator);
        }

        indicator.classList.add('show');
        setTimeout(() => {
            indicator.classList.remove('show');
        }, 2000);
    },

    /**
     * 导出JSON
     */
    exportJson: function(rfqId) {
        window.location.href = '/aiforms/api/rfq.php?action=export&id=' + rfqId;
    },

    /**
     * 保存JSON到文件
     */
    saveJsonFile: function(rfqId) {
        fetch('/aiforms/api/rfq.php?action=export&id=' + rfqId)
            .then(response => response.json())
            .then(data => {
                const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'RFQ_' + (data.main?.rfq_no || rfqId) + '.json';
                a.click();
                URL.revokeObjectURL(url);
            });
    },

    /**
     * 打印PDF (先保存再打印，确保数据一致性)
     */
    printPdf: function(rfqId, size = 'letter') {
        const form = document.querySelector('form.rfq-form');

        // 如果在表单页面，先保存再打印
        if (form) {
            this.showLoading();

            // 先保存当前表单数据
            const formData = new FormData(form);
            formData.append('action', 'save_draft');

            fetch('/aiforms/api/rfq.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                this.hideLoading();
                if (data.success) {
                    // 保存成功后打开打印页面
                    const id = data.data?.id || rfqId;
                    window.open('/aiforms/rfq/print.php?id=' + id + '&size=' + size, '_blank');
                    this.showAutoSaveIndicator();
                } else {
                    alert('Save failed before printing: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                this.hideLoading();
                console.error('Save error:', error);
                // 即使保存失败，也允许用户查看打印（可能数据已存在）
                if (confirm('Failed to save. Open print preview with existing data?')) {
                    window.open('/aiforms/rfq/print.php?id=' + rfqId + '&size=' + size, '_blank');
                }
            });
        } else {
            // 如果不在表单页面（如列表页面），直接打开打印
            window.open('/aiforms/rfq/print.php?id=' + rfqId + '&size=' + size, '_blank');
        }
    },

    /**
     * 删除RFQ
     */
    deleteRfq: function(rfqId, callback) {
        if (!confirm('Are you sure you want to delete this RFQ?')) {
            return;
        }

        fetch('/aiforms/api/rfq.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'delete',
                id: rfqId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (callback) callback(data);
                else window.location.reload();
            } else {
                alert('Delete failed: ' + data.message);
            }
        });
    },

    /**
     * 显示加载
     */
    showLoading: function() {
        let overlay = document.querySelector('.loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = '<div class="loading-spinner"></div>';
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    },

    /**
     * 隐藏加载
     */
    hideLoading: function() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    },

    /**
     * 从localStorage恢复草稿
     */
    restoreFromLocal: function() {
        const rfqNo = document.querySelector('input[name="rfq_no"]')?.value;
        if (!rfqNo) return;

        const saved = localStorage.getItem('rfq_draft_' + rfqNo);
        if (saved) {
            if (confirm('Found unsaved changes. Do you want to restore them?')) {
                const data = JSON.parse(saved);
                this.fillFormData(data);
            }
        }
    },

    /**
     * 保存到localStorage
     */
    saveToLocal: function() {
        const form = document.querySelector('form.rfq-form');
        if (!form) return;

        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        const rfqNo = data.rfq_no || 'new';
        localStorage.setItem('rfq_draft_' + rfqNo, JSON.stringify(data));
        this.showAutoSaveIndicator();
    },

    /**
     * 填充表单数据
     */
    fillFormData: function(data) {
        const form = document.querySelector('form.rfq-form');
        if (!form) return;

        Object.keys(data).forEach(key => {
            const el = form.querySelector('[name="' + key + '"]');
            if (el) {
                if (el.type === 'checkbox') {
                    el.checked = data[key] == '1' || data[key] === true;
                } else {
                    el.value = data[key];
                }
            }
        });
    }
};

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    SectionCollapse.init();
    RFQ.init();
});
